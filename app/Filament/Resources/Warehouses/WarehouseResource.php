<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Pages\ViewWarehouse;
use App\Filament\Resources\Warehouses\RelationManagers\StockItemsRelationManager;
use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasModuleGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'inventory';

    protected static ?string $model = Warehouse::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-building-storefront';
    protected static string|\UnitEnum|null   $navigationGroup = 'المخزون';
    protected static ?int                    $navigationSort   = 1;
    protected static ?string                 $navigationLabel  = 'المخازن';
    protected static ?string                 $modelLabel       = 'مخزن';
    protected static ?string                 $pluralModelLabel = 'المخازن';
    protected static ?string                 $recordTitleAttribute = 'name';

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('showroom_manager')
            || $user->hasRole('distribution_manager')
            || $user->hasRole('storekeeper');
    }

    // ─── Form & Table ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            StockItemsRelationManager::class,
        ];
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'view'   => ViewWarehouse::route('/{record}'),
            'edit'   => EditWarehouse::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
