<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FiscalPeriodResource\Pages;
use App\Models\FiscalPeriod;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FiscalPeriodResource extends Resource
{
    // Hidden from nav — replaced by FiscalPeriodsManager page
    public static function shouldRegisterNavigation(): bool { return false; }

    protected static ?string $model = FiscalPeriod::class;

    protected static string|\UnitEnum|null $navigationGroup = 'المحاسبة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'الفترات المالية';

    protected static ?string $modelLabel = 'فترة مالية';

    protected static ?string $pluralModelLabel = 'الفترات المالية';

    protected static ?int $navigationSort = 2;

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('accounting.lock_period');
    }

    public static function canCreate(): bool
    {
        return false;   // periods are managed via seeder only
    }

    // ─── Form (required by base class but never rendered) ──────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('السنة')
                    ->sortable(),

                TextColumn::make('month')
                    ->label('الشهر')
                    ->formatStateUsing(fn (int $state): string => FiscalPeriod::MONTHS[$state] ?? (string) $state)
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('من')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('إلى')
                    ->date('d/m/Y'),

                IconColumn::make('is_locked')
                    ->label('الحالة')
                    ->icon(fn (bool $state): string => $state
                        ? 'heroicon-s-lock-closed'
                        : 'heroicon-s-lock-open')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->boolean(),

                TextColumn::make('lockedByUser.name')
                    ->label('قفل بواسطة')
                    ->placeholder('—'),

                TextColumn::make('locked_at')
                    ->label('تاريخ القفل')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('السنة')
                    ->options(
                        FiscalPeriod::query()
                            ->distinct()
                            ->orderByDesc('year')
                            ->pluck('year', 'year')
                            ->toArray()
                    ),

                TernaryFilter::make('is_locked')
                    ->label('الحالة')
                    ->trueLabel('مقفولة')
                    ->falseLabel('مفتوحة'),
            ])
            ->actions([
                // Lock — visible only for open periods
                Action::make('lock')
                    ->label('قفل الفترة')
                    ->icon('heroicon-s-lock-closed')
                    ->color('danger')
                    ->visible(fn (FiscalPeriod $record): bool => ! $record->is_locked)
                    ->requiresConfirmation()
                    ->modalHeading('قفل الفترة المالية')
                    ->modalDescription('هل أنت متأكد من قفل هذه الفترة؟ لن تُقبل أي معاملات جديدة بعد القفل.')
                    ->action(fn (FiscalPeriod $record) => $record->lock(auth()->user())),

                // Unlock — visible only for locked periods
                Action::make('unlock')
                    ->label('فتح الفترة')
                    ->icon('heroicon-s-lock-open')
                    ->color('success')
                    ->visible(fn (FiscalPeriod $record): bool => $record->is_locked)
                    ->requiresConfirmation()
                    ->modalHeading('فتح الفترة المالية')
                    ->modalDescription('هل أنت متأكد من فتح هذه الفترة؟ ستُقبل معاملات جديدة بعد الفتح.')
                    ->action(fn (FiscalPeriod $record) => $record->unlock()),
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->emptyStateHeading('لا توجد فترات مالية')
            ->emptyStateDescription('ابدأ بإضافة فترة مالية جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFiscalPeriods::route('/'),
        ];
    }
}
