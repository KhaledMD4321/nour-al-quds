<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\LookupType;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Treasury;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\HasModuleGuard;
use Illuminate\Support\HtmlString;

class PaymentResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'finance';

    protected static ?string $model = Payment::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null   $navigationGroup = 'الخزينة والمالية';
    protected static ?int                    $navigationSort  = 11;
    protected static ?string                 $navigationLabel  = 'سندات الصرف';
    protected static ?string                 $modelLabel       = 'سند صرف';
    protected static ?string                 $pluralModelLabel = 'سندات الصرف';
    protected static ?string                 $recordTitleAttribute = 'payment_number';

    // ── RBAC ──────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('finance.payment.view');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('finance.payment.create');
    }

    public static function canEdit($record): bool
    {
        return false; // سندات الصرف لا تُعدَّل
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ── Scope ─────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $base = parent::getEloquentQuery()
            ->with(['supplier', 'purchaseInvoice', 'treasury', 'businessUnit', 'createdBy']);

        if ($user?->isSuperAdmin()) {
            return $base;
        }

        return $base->where('business_unit_id', $user?->business_unit_id);
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── بيانات السند ─────────────────────────────────────────────────
            Section::make('بيانات السند')
                ->columns(3)
                ->schema([

                    // الوحدة — dehydrated دائماً
                    Select::make('business_unit_id')
                        ->label('الوحدة')
                        ->options(BusinessUnit::pluck('name', 'id'))
                        ->required()
                        ->default(fn () => auth()->user()?->business_unit_id)
                        ->disabled(fn () => ! auth()->user()?->isSuperAdmin())
                        ->dehydrated()
                        ->hidden(fn () => ! auth()->user()?->isSuperAdmin())
                        ->live()
                        ->columnSpanFull(),

                    DatePicker::make('payment_date')
                        ->label('تاريخ السند')
                        ->required()
                        ->default(today())
                        ->displayFormat('Y-m-d'),

                    Select::make('category')
                        ->label('نوع المصروف')
                        ->options(LookupType::getOptions('expense_category'))
                        ->required()
                        ->default('supplier_payment')
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('supplier_id', null);
                            $set('purchase_invoice_id', null);
                            $set('expense_account_id', null);
                        }),

                    Select::make('payment_method')
                        ->label('طريقة الدفع')
                        ->options([
                            'cash'          => 'كاش',
                            'bank_transfer' => 'تحويل بنكي',
                            'cheque'        => 'شيك',
                        ])
                        ->required()
                        ->default('cash')
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('treasury_id', null)),

                ]),

            // ── بيانات المورد ─────────────────────────────────────────────────
            Section::make('بيانات المورد')
                ->hidden(fn (Get $get) => $get('category') !== 'supplier_payment')
                ->columns(2)
                ->schema([

                    Select::make('supplier_id')
                        ->label('المورد')
                        ->options(
                            Supplier::active()->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->code . ' — ' . $s->name])
                                ->toArray()
                        )
                        ->required(fn (Get $get) => $get('category') === 'supplier_payment')
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('purchase_invoice_id', null)),

                    Placeholder::make('supplier_balance')
                        ->label('رصيد المورد الحالي')
                        ->content(function (Get $get) {
                            $id = $get('supplier_id');
                            if (! $id) return new HtmlString('<span style="color:#9ca3af">اختر مورداً أولاً</span>');
                            $supplier = Supplier::find($id);
                            if (! $supplier) return '—';
                            $balance = $supplier->current_balance;
                            $color = $balance > 0 ? '#dc2626' : '#059669';
                            $label = $balance > 0 ? 'مستحق للمورد' : ($balance < 0 ? 'دائن لنا' : 'متعادل');
                            return new HtmlString(
                                '<span style="color:' . $color . '; font-weight:bold; font-size:16px">'
                                . number_format(abs($balance), 2) . ' ج.م</span>'
                                . '<span style="color:#6b7280; margin-right:8px">(' . $label . ')</span>'
                            );
                        }),

                    Select::make('purchase_invoice_id')
                        ->label('فاتورة المشتريات (اختياري)')
                        ->helperText('اتركه فارغاً للدفع على الحساب')
                        ->nullable()
                        ->options(function (Get $get) {
                            $supplierId = $get('supplier_id');
                            if (! $supplierId) return [];
                            return PurchaseInvoice::where('supplier_id', $supplierId)
                                ->whereIn('status', ['confirmed'])
                                ->whereRaw('total_amount > paid_amount')
                                ->orderBy('invoice_date')
                                ->get()
                                ->mapWithKeys(function ($i) {
                                    $rem = round((float) $i->total_amount - (float) $i->paid_amount, 2);
                                    return [$i->id => $i->reference_number . ' — متبقي: ' . number_format($rem, 2) . ' ج.م'];
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state) {
                                $inv = PurchaseInvoice::find($state);
                                if ($inv) {
                                    $set('amount', round($inv->remaining_amount, 2));
                                }
                            }
                        }),

                    // placeholder فارغ للمحاذاة
                    Placeholder::make('_spacer')->label('')->columnSpan(1),

                ]),

            // ── حساب المصروف ─────────────────────────────────────────────────
            Section::make('حساب المصروف')
                ->hidden(fn (Get $get) => ($get('category') ?? 'supplier_payment') === 'supplier_payment')
                ->schema([
                    Select::make('expense_account_id')
                        ->label('حساب المصروف')
                        ->options(fn () => ChartOfAccount::where('type', 'expense')
                            ->where('level', 3)  // L3 فقط — حسابات تفصيلية
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' — ' . $a->name])
                            ->toArray()
                        )
                        ->required(fn (Get $get) => ($get('category') ?? 'supplier_payment') !== 'supplier_payment')
                        ->searchable(),
                ]),

            // ── المبلغ والخزينة ───────────────────────────────────────────────
            Section::make('المبلغ والخزينة')
                ->columns(2)
                ->schema([

                    TextInput::make('amount')
                        ->label('المبلغ (ج.م)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01),

                    Select::make('treasury_id')
                        ->label('الخزينة')
                        ->hidden(fn (Get $get) => $get('payment_method') === 'cheque')
                        ->required(fn (Get $get) => in_array($get('payment_method'), ['cash', 'bank_transfer']))
                        ->options(function (Get $get) {
                            $unitId = $get('business_unit_id') ?: auth()->user()?->business_unit_id;
                            $method = $get('payment_method');
                            $type   = match ($method) {
                                'cash'          => 'cash',
                                'bank_transfer' => 'bank',
                                default         => null,
                            };
                            if (! $type || ! $unitId) return [];
                            return Treasury::active()
                                ->where('type', $type)
                                ->where('business_unit_id', $unitId)
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->live(),

                    TextInput::make('bank_reference')
                        ->label('رقم مرجع التحويل')
                        ->hidden(fn (Get $get) => $get('payment_method') !== 'bank_transfer')
                        ->maxLength(100),

                ]),

            // ── بيانات الشيك ──────────────────────────────────────────────────
            Section::make('بيانات الشيك')
                ->hidden(fn (Get $get) => $get('payment_method') !== 'cheque')
                ->columns(3)
                ->schema([
                    TextInput::make('cheque_details.cheque_number')
                        ->label('رقم الشيك')
                        ->requiredIf('payment_method', 'cheque'),

                    TextInput::make('cheque_details.bank_name')
                        ->label('اسم البنك')
                        ->requiredIf('payment_method', 'cheque'),

                    DatePicker::make('cheque_details.due_date')
                        ->label('تاريخ الاستحقاق')
                        ->requiredIf('payment_method', 'cheque')
                        ->displayFormat('Y-m-d'),
                ]),

            // ── ملاحظات ───────────────────────────────────────────────────────
            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم السند')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('payment_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('category')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => LookupType::getLabel('expense_category', $state) ?? $state)
                    ->color(fn ($state) => $state === 'supplier_payment' ? 'info' : 'warning'),

                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->default('مصروف تشغيلي')
                    ->weight('semibold')
                    ->limit(25)
                    ->searchable(),

                TextColumn::make('purchaseInvoice.reference_number')
                    ->label('فاتورة الشراء')
                    ->fontFamily('mono')
                    ->default('—'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger')
                    ->alignEnd(),

                TextColumn::make('payment_method')
                    ->label('الطريقة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash'          => 'كاش',
                        'bank_transfer' => 'تحويل بنكي',
                        'cheque'        => 'شيك',
                        default         => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cash'          => 'success',
                        'bank_transfer' => 'info',
                        'cheque'        => 'warning',
                        default         => 'gray',
                    }),

                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('النوع')
                    ->options(LookupType::getOptions('expense_category')),

                SelectFilter::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash'          => 'كاش',
                        'bank_transfer' => 'تحويل بنكي',
                        'cheque'        => 'شيك',
                    ]),

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة')
                    ->relationship('businessUnit', 'name')
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (Payment $record) => route('payments.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.payment.print')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('لا توجد سندات صرف')
            ->emptyStateDescription('ابدأ بإضافة سند صرف جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view'   => ViewPayment::route('/{record}'),
        ];
    }
}
