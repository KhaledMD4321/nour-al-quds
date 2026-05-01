<?php

namespace App\Filament\Resources\Receipts;

use App\Filament\Resources\Receipts\Pages\CreateReceipt;
use App\Filament\Resources\Receipts\Pages\ListReceipts;
use App\Filament\Resources\Receipts\Pages\ViewReceipt;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Treasury;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-check';
    protected static string|\UnitEnum|null   $navigationGroup = 'الخزينة والمالية';
    protected static ?int                    $navigationSort  = 10;
    protected static ?string                 $navigationLabel  = 'إيصالات التحصيل';
    protected static ?string                 $modelLabel       = 'إيصال تحصيل';
    protected static ?string                 $pluralModelLabel = 'إيصالات التحصيل';
    protected static ?string                 $recordTitleAttribute = 'receipt_number';

    // ── RBAC ──────────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('finance.receipt.view');
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
        return $user->can('finance.receipt.create');
    }

    public static function canEdit($record): bool
    {
        return false; // الإيصالات لا تُعدَّل
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ── Scope ─────────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->where('business_unit_id', $user?->business_unit_id);
    }

    // ── Form ─────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات الإيصال')
                ->columns(2)
                ->schema([

                    Select::make('customer_id')
                        ->label('العميل')
                        ->required()
                        ->searchable()
                        ->preload(false)
                        ->getSearchResultsUsing(function (string $search) {
                            $user = auth()->user();
                            return Customer::query()
                                ->active()
                                ->when(! $user?->isSuperAdmin(), fn ($q) => $q->where('business_unit_id', $user?->business_unit_id))
                                ->where(fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
                                    ->orWhere('code', 'ILIKE', "%{$search}%"))
                                ->limit(20)
                                ->pluck('name', 'id');
                        })
                        ->getOptionLabelUsing(fn ($value) => Customer::find($value)?->name)
                        ->reactive()
                        ->afterStateUpdated(fn (Set $set) => $set('invoice_id', null)),

                    Select::make('invoice_id')
                        ->label('الفاتورة (اختياري)')
                        ->nullable()
                        ->searchable()
                        ->preload(false)
                        ->getSearchResultsUsing(function (string $search, Get $get) {
                            $customerId = $get('customer_id');
                            if (! $customerId) return [];

                            return Invoice::query()
                                ->where('customer_id', $customerId)
                                ->whereIn('status', ['confirmed', 'delivered', 'partially_paid'])
                                ->where('reference_number', 'ILIKE', "%{$search}%")
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($inv) => [
                                    $inv->id => $inv->reference_number . ' — متبقي: ' . number_format($inv->remaining_amount, 2) . ' ج.م',
                                ]);
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $inv = Invoice::find($value);
                            return $inv ? $inv->reference_number . ' — متبقي: ' . number_format($inv->remaining_amount, 2) . ' ج.م' : null;
                        })
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state) {
                                $inv = Invoice::find($state);
                                if ($inv) {
                                    $set('amount', $inv->remaining_amount);
                                }
                            }
                        }),

                    Select::make('payment_method')
                        ->label('طريقة الدفع')
                        ->required()
                        ->options([
                            'cash'          => 'نقدي',
                            'bank_transfer' => 'تحويل بنكي',
                            'cheque'        => 'شيك',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn (Set $set) => $set('treasury_id', null)),

                    Select::make('treasury_id')
                        ->label('الخزينة')
                        ->requiredUnless('payment_method', 'cheque')
                        ->nullable()
                        ->hidden(fn (Get $get) => $get('payment_method') === 'cheque')
                        ->options(function (Get $get) {
                            $method = $get('payment_method');
                            $user   = auth()->user();

                            $type = match ($method) {
                                'cash'          => 'cash',
                                'bank_transfer' => 'bank',
                                default         => null,
                            };

                            if (! $type) return [];

                            return Treasury::query()
                                ->active()
                                ->where('type', $type)
                                ->when(! $user?->isSuperAdmin(), fn ($q) => $q->where('business_unit_id', $user?->business_unit_id))
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload(),

                    TextInput::make('amount')
                        ->label('المبلغ (ج.م)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01),

                    DatePicker::make('receipt_date')
                        ->label('تاريخ الإيصال')
                        ->required()
                        ->default(today())
                        ->displayFormat('Y-m-d'),

                ]),

            Section::make('بيانات الشيك')
                ->columns(3)
                ->hidden(fn (Get $get) => $get('payment_method') !== 'cheque')
                ->schema([
                    TextInput::make('cheque_details.cheque_number')
                        ->label('رقم الشيك')
                        ->requiredIf('payment_method', 'cheque'),

                    TextInput::make('cheque_details.bank_name')
                        ->label('البنك')
                        ->requiredIf('payment_method', 'cheque'),

                    DatePicker::make('cheque_details.due_date')
                        ->label('تاريخ الاستحقاق')
                        ->requiredIf('payment_method', 'cheque')
                        ->displayFormat('Y-m-d'),
                ]),

            Section::make('بيانات التحويل البنكي')
                ->columns(1)
                ->hidden(fn (Get $get) => $get('payment_method') !== 'bank_transfer')
                ->schema([
                    TextInput::make('bank_reference')
                        ->label('رقم مرجع التحويل')
                        ->maxLength(100),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->nullable(),
                ]),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('receipt_date', 'desc')
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('رقم الإيصال')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('receipt_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice.reference_number')
                    ->label('رقم الفاتورة')
                    ->default('—')
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cash'          => 'نقدي',
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

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->default('—'),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash'          => 'نقدي',
                        'bank_transfer' => 'تحويل بنكي',
                        'cheque'        => 'شيك',
                    ]),

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة')
                    ->relationship('businessUnit', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                \Filament\Actions\Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Receipt $record) => route('receipts.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.receipt.print')),
            ])
            ->bulkActions([]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListReceipts::route('/'),
            'create' => CreateReceipt::route('/create'),
            'view'   => ViewReceipt::route('/{record}'),
        ];
    }
}
