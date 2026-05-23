<?php

namespace App\Filament\Resources\Cheques;

use App\Filament\Resources\Cheques\Pages\CreateCheque;
use App\Filament\Resources\Cheques\Pages\ListCheques;
use App\Filament\Resources\Cheques\Pages\ViewCheque;
use App\Models\BusinessUnit;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Modules\Finance\ChequeService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Concerns\HasModuleGuard;
use Illuminate\Database\Eloquent\Builder;

class ChequeResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'finance';

    protected static ?string $model = Cheque::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-check';
    protected static string|\UnitEnum|null   $navigationGroup = 'الخزينة والمالية';
    protected static ?int                    $navigationSort   = 12;
    protected static ?string                 $navigationLabel  = 'الشيكات';
    protected static ?string                 $modelLabel       = 'شيك';
    protected static ?string                 $pluralModelLabel = 'الشيكات';
    protected static ?string                 $recordTitleAttribute = 'cheque_number';

    // ── RBAC ──────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('finance.cheque.view');
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
        return $user->can('finance.cheque.create');
    }

    public static function canEdit($record): bool
    {
        return false; // الشيكات لا تُعدَّل — فقط transitions عبر Actions
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
            ->with(['customer', 'supplier', 'treasury', 'businessUnit', 'createdBy']);

        if ($user?->isSuperAdmin()) {
            return $base;
        }

        return $base->where('business_unit_id', $user?->business_unit_id);
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── الوحدة ───────────────────────────────────────────────────────
            Section::make()->schema([
                Select::make('business_unit_id')
                    ->label('الوحدة')
                    ->options(BusinessUnit::pluck('name', 'id'))
                    ->required()
                    ->default(fn () => auth()->user()?->business_unit_id)
                    ->disabled(fn () => ! auth()->user()?->isSuperAdmin())
                    ->dehydrated()
                    ->hidden(fn () => ! auth()->user()?->isSuperAdmin())
                    ->columnSpanFull(),
            ]),

            // ── بيانات الشيك ─────────────────────────────────────────────────
            Section::make('بيانات الشيك')
                ->columns(3)
                ->schema([

                    Select::make('direction')
                        ->label('الاتجاه')
                        ->options([
                            'incoming' => 'وارد (من عميل)',
                            'outgoing' => 'صادر (للمورد)',
                        ])
                        ->required()
                        ->default('incoming')
                        ->live()
                        ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set) {
                            $set('customer_id', null);
                            $set('supplier_id', null);
                        }),

                    TextInput::make('cheque_number')
                        ->label('رقم الشيك')
                        ->required()
                        ->maxLength(50),

                    TextInput::make('bank_name')
                        ->label('اسم البنك')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('amount')
                        ->label('المبلغ (ج.م)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01),

                    DatePicker::make('issue_date')
                        ->label('تاريخ الإصدار')
                        ->required()
                        ->default(today())
                        ->displayFormat('Y-m-d'),

                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->required()
                        ->displayFormat('Y-m-d'),

                ]),

            // ── العميل / المورد ───────────────────────────────────────────────
            Section::make('الطرف الآخر')
                ->columns(2)
                ->schema([

                    Select::make('customer_id')
                        ->label('العميل')
                        ->options(fn () => Customer::active()->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => ($c->code ?? '') . ' — ' . $c->name])
                            ->toArray()
                        )
                        ->hidden(fn (Get $get) => $get('direction') !== 'incoming')
                        ->required(fn (Get $get) => $get('direction') === 'incoming')
                        ->searchable(),

                    Select::make('supplier_id')
                        ->label('المورد')
                        ->options(fn () => Supplier::active()->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => $s->code . ' — ' . $s->name])
                            ->toArray()
                        )
                        ->hidden(fn (Get $get) => $get('direction') !== 'outgoing')
                        ->required(fn (Get $get) => $get('direction') === 'outgoing')
                        ->searchable(),

                    // الخزينة اختيارية عند التسجيل — تُحدَّد عند الإيداع/الصرف
                    Select::make('treasury_id')
                        ->label('الخزينة (اختياري)')
                        ->helperText('يمكن تركه فارغاً وتحديده عند الإيداع أو الصرف')
                        ->options(function (Get $get) {
                            $unitId = $get('business_unit_id') ?: auth()->user()?->business_unit_id;
                            if (! $unitId) return [];
                            return Treasury::active()
                                ->where('type', 'bank')
                                ->where('business_unit_id', $unitId)
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->nullable()
                        ->searchable(),

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
            ->defaultSort('due_date', 'asc')
            ->columns([

                TextColumn::make('cheque_number')
                    ->label('رقم الشيك')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('direction')
                    ->label('الاتجاه')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'incoming' ? 'وارد' : 'صادر')
                    ->color(fn ($state) => $state === 'incoming' ? 'success' : 'danger'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'   => 'قيد الانتظار',
                        'deposited' => 'مودع بالبنك',
                        'collected' => 'تم التحصيل',
                        'bounced'   => 'مرفوض',
                        'replaced'  => 'تم الاستبدال',
                        default     => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending'   => 'warning',
                        'deposited' => 'info',
                        'collected' => 'success',
                        'bounced'   => 'danger',
                        'replaced'  => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('bank_name')
                    ->label('البنك')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (Cheque $record): string => $record->direction === 'incoming' ? 'success' : 'danger')
                    ->alignEnd(),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Cheque $record): ?string =>
                        ($record->isPending() || $record->isDeposited())
                            ? ($record->due_date->isPast() ? 'danger' : ($record->due_date->diffInDays(today()) <= 3 ? 'warning' : 'gray'))
                            : 'gray'
                    ),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->default('—')
                    ->limit(20)
                    ->searchable(),

                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->default('—')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->toggleable(),

            ])
            ->filters([
                SelectFilter::make('direction')
                    ->label('الاتجاه')
                    ->options([
                        'incoming' => 'وارد',
                        'outgoing' => 'صادر',
                    ]),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'   => 'قيد الانتظار',
                        'deposited' => 'مودع بالبنك',
                        'collected' => 'تم التحصيل',
                        'bounced'   => 'مرفوض',
                        'replaced'  => 'تم الاستبدال',
                    ]),

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة')
                    ->relationship('businessUnit', 'name')
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ])
            ->actions([

                // ── إيداع بالبنك (incoming + pending) ────────────────────────
                Action::make('deposit')
                    ->label('إيداع بالبنك')
                    ->icon('heroicon-o-building-library')
                    ->color('info')
                    ->visible(fn (Cheque $record) => $record->canDeposit()
                        && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.deposit'))
                    )
                    ->modalHeading('إيداع الشيك بالبنك')
                    ->modalDescription(fn (Cheque $r) =>
                        "شيك #{$r->cheque_number} — " . number_format((float) $r->amount, 2) . ' ج.م'
                    )
                    ->form(fn (Cheque $record) => [
                        Select::make('bank_treasury_id')
                            ->label('البنك')
                            ->options(
                                Treasury::active()
                                    ->where('type', 'bank')
                                    ->where('business_unit_id', $record->business_unit_id)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->default($record->treasury_id)
                            ->required(),
                    ])
                    ->action(function (Cheque $record, array $data) {
                        try {
                            app(ChequeService::class)->deposit($record->id, $data['bank_treasury_id'], auth()->id());
                            Notification::make()->success()->title('تم إيداع الشيك بالبنك')->send();
                        } catch (\Exception $e) {
                            Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                        }
                    }),

                // ── تحصيل (incoming + deposited  أو  outgoing + pending) ──────
                Action::make('collect')
                    ->label('تم التحصيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Cheque $record) => $record->canCollect()
                        && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.collect'))
                    )
                    ->modalHeading('تأكيد تحصيل الشيك')
                    ->modalDescription(fn (Cheque $r) =>
                        "هل تم تحصيل شيك #{$r->cheque_number} بمبلغ " . number_format((float) $r->amount, 2) . ' ج.م؟'
                    )
                    // لو صادر → نختار البنك؛ لو وارد مودع → لا فورم
                    ->form(fn (Cheque $record) => $record->isOutgoing() ? [
                        Select::make('treasury_id')
                            ->label('البنك المُنفِّذ للصرف')
                            ->options(
                                Treasury::active()
                                    ->where('type', 'bank')
                                    ->where('business_unit_id', $record->business_unit_id)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->default($record->treasury_id)
                            ->required(),
                    ] : [])
                    ->action(function (Cheque $record, array $data) {
                        try {
                            $bankId = $record->isOutgoing() ? ($data['treasury_id'] ?? null) : null;
                            app(ChequeService::class)->collect($record->id, auth()->id(), $bankId);
                            Notification::make()->success()->title('تم تحصيل الشيك بنجاح')->send();
                        } catch (\Exception $e) {
                            Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                        }
                    }),

                // ── رفض (incoming + deposited) ────────────────────────────────
                Action::make('bounce')
                    ->label('مرفوض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Cheque $record) => $record->canBounce()
                        && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.bounce'))
                    )
                    ->modalHeading('تسجيل رفض الشيك')
                    ->form([
                        Textarea::make('bounce_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Cheque $record, array $data) {
                        try {
                            app(ChequeService::class)->bounce($record->id, $data['bounce_reason'], auth()->id());
                            Notification::make()->warning()->title('تم تسجيل رفض الشيك')->send();
                        } catch (\Exception $e) {
                            Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                        }
                    }),

                // ── استبدال (incoming + bounced) ──────────────────────────────
                Action::make('replace')
                    ->label('استبدال')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Cheque $record) => $record->canReplace()
                        && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('finance.cheque.replace'))
                    )
                    ->modalHeading('استبدال الشيك المرفوض')
                    ->form([
                        TextInput::make('cheque_number')
                            ->label('رقم الشيك الجديد')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('bank_name')
                            ->label('اسم البنك')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('amount')
                            ->label('المبلغ (ج.م)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),

                        DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق الجديد')
                            ->required()
                            ->displayFormat('Y-m-d'),
                    ])
                    ->action(function (Cheque $record, array $data) {
                        try {
                            $new = app(ChequeService::class)->replace($record->id, $data, auth()->id());
                            Notification::make()->success()
                                ->title('تم استبدال الشيك')
                                ->body("الشيك الجديد: #{$new->cheque_number}")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()->danger()->title('خطأ')->body($e->getMessage())->persistent()->send();
                        }
                    }),

                ViewAction::make()->label('عرض'),

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
            ->emptyStateHeading('لا توجد شيكات')
            ->emptyStateDescription('ابدأ بإضافة شيك جديد.')
            ->emptyStateIcon('heroicon-o-document-check')
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListCheques::route('/'),
            'create' => CreateCheque::route('/create'),
            'view'   => ViewCheque::route('/{record}'),
        ];
    }
}
