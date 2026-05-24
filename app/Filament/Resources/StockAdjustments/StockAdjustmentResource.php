<?php

namespace App\Filament\Resources\StockAdjustments;

use App\Filament\Concerns\HasModuleGuard;
use App\Filament\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\EditStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Resources\StockAdjustments\RelationManagers\AdjustmentItemsRelationManager;
use App\Filament\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Filament\Resources\StockAdjustments\Tables\StockAdjustmentsTable;
use App\Models\StockAdjustment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
{
    use HasModuleGuard;

    protected static string $module = 'inventory';

    protected static ?string $model = StockAdjustment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'تسويات المخزون';

    protected static ?string $modelLabel = 'تسوية مخزون';

    protected static ?string $pluralModelLabel = 'تسويات المخزون';

    public static function form(Schema $schema): Schema
    {
        return StockAdjustmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockAdjustmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdjustmentItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustments::route('/'),
            'create' => CreateStockAdjustment::route('/create'),
            'edit' => EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
