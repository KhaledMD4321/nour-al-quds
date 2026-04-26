<?php

namespace App\Filament\Resources\StockTransfers;

use App\Filament\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\EditStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Resources\StockTransfers\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Resources\StockTransfers\Tables\StockTransfersTable;
use App\Models\StockTransfer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null   $navigationGroup = 'المخزون';
    protected static ?int                    $navigationSort  = 1;
    protected static ?string                 $navigationLabel  = 'تحويلات المخزون';
    protected static ?string                 $modelLabel       = 'تحويل مخزون';
    protected static ?string                 $pluralModelLabel = 'تحويلات المخزون';

    public static function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransfersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStockTransfers::route('/'),
            'create' => CreateStockTransfer::route('/create'),
            'edit'   => EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
