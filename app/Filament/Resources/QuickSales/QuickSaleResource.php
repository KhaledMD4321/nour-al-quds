<?php

namespace App\Filament\Resources\QuickSales;

use App\Filament\Resources\QuickSales\Pages\ListQuickSales;
use App\Filament\Resources\QuickSales\Pages\ViewQuickSale;
use App\Filament\Resources\QuickSales\RelationManagers\ItemsRelationManager;
use App\Models\QuickSale;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuickSaleResource extends Resource
{
    protected static ?string $model = QuickSale::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-receipt-percent';
    protected static string|\UnitEnum|null   $navigationGroup = 'المبيعات';
    protected static ?int                    $navigationSort  = 2;
    protected static ?string                 $navigationLabel  = 'سجل المبيعات السريعة';
    protected static ?string                 $modelLabel       = 'إيصال بيع سريع';
    protected static ?string                 $pluralModelLabel = 'سجل المبيعات السريعة';

    // ── Read-only ────────────────────────────────────────────────────────────
    public static function canCreate(): bool        { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    // ── Form (empty — read-only resource) ───────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Table ────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('رقم الإيصال')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('العميل')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('الكاشير')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('today')
                    ->label('اليوم')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('إيصال PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (QuickSale $record) => route('quick-sale.receipt', $record->id))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make()->label('تفاصيل'),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }

    // ── Relations ────────────────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    // ── Pages ────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => ListQuickSales::route('/'),
            'view'  => ViewQuickSale::route('/{record}'),
        ];
    }
}
