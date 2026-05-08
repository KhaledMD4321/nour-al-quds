<?php

namespace App\Filament\Resources\TreasuryTransactions;

use App\Filament\Resources\TreasuryTransactions\Pages\ListTreasuryTransactions;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Concerns\HasModuleGuard;
use Illuminate\Database\Eloquent\Builder;

class TreasuryTransactionResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'finance';

    protected static ?string $model = TreasuryTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null   $navigationGroup = 'الخزينة والمالية';
    protected static ?int                    $navigationSort  = 2;
    protected static ?string                 $navigationLabel  = 'حركات الخزائن';
    protected static ?string                 $modelLabel       = 'حركة خزينة';
    protected static ?string                 $pluralModelLabel = 'حركات الخزائن';

    // ── RBAC — قراءة فقط ─────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('finance.treasury.view') ?? false;
    }

    public static function canCreate(): bool  { return false; }
    public static function canEdit($r): bool  { return false; }
    public static function canDelete($r): bool { return false; }

    // الموظف يشوف حركات خزائن وحدته فقط
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['treasury.businessUnit', 'createdBy']);

        $user = auth()->user();
        if (! $user->isSuperAdmin() && $user->business_unit_id) {
            $query->whereHas('treasury', fn ($q) =>
                $q->where('business_unit_id', $user->business_unit_id)
            );
        }

        return $query;
    }

    // ── Form (فارغ — لا إنشاء) ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('transaction_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('treasury.name')
                    ->label('الخزينة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'receipt'      => 'مقبوضات',
                        'payment'      => 'مدفوعات',
                        'transfer_in'  => 'تحويل وارد',
                        'transfer_out' => 'تحويل صادر',
                        default        => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'receipt'      => 'success',
                        'payment'      => 'danger',
                        'transfer_in'  => 'info',
                        'transfer_out' => 'warning',
                        default        => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state, TreasuryTransaction $record): string =>
                        ($record->is_inflow ? '+ ' : '- ') . number_format((float) $state, 2)
                    )
                    ->color(fn (TreasuryTransaction $record): string =>
                        $record->is_inflow ? 'success' : 'danger'
                    ),

                TextColumn::make('balance_after')
                    ->label('الرصيد بعد الحركة')
                    ->money('EGP')
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('البيان')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->wrap(),

                TextColumn::make('reference_type')
                    ->label('المرجع')
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'App\\Models\\QuickSale'        => 'بيع سريع',
                        'App\\Models\\Receipt'          => 'سند قبض',
                        'App\\Models\\Payment'          => 'سند صرف',
                        'App\\Models\\Cheque'           => 'شيك',
                        'App\\Models\\TreasuryTransaction' => 'تحويل بين خزائن',
                        default                         => $state ?? '—',
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('createdBy.name')
                    ->label('المستخدم')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('وقت التسجيل')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('treasury_id')
                    ->label('الخزينة')
                    ->options(fn () => Treasury::pluck('name', 'id')->toArray()),

                SelectFilter::make('type')
                    ->label('نوع الحركة')
                    ->options([
                        'receipt'      => 'مقبوضات',
                        'payment'      => 'مدفوعات',
                        'transfer_in'  => 'تحويل وارد',
                        'transfer_out' => 'تحويل صادر',
                    ]),

                Filter::make('transaction_date')
                    ->label('فترة زمنية')
                    ->form([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('to')->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null,
                                fn ($q, $d) => $q->whereDate('transaction_date', '>=', $d)
                            )
                            ->when($data['to'] ?? null,
                                fn ($q, $d) => $q->whereDate('transaction_date', '<=', $d)
                            );
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد معاملات خزينة')
            ->emptyStateDescription('ابدأ بإضافة معاملة جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListTreasuryTransactions::route('/'),
        ];
    }
}
