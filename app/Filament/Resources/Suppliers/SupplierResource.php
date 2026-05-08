<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasModuleGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'customers';

    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-truck';
    protected static string|\UnitEnum|null   $navigationGroup = 'العملاء والموردين';
    protected static ?int                    $navigationSort   = 2;
    protected static ?string                 $navigationLabel  = 'الموردين';
    protected static ?string                 $modelLabel       = 'مورد';
    protected static ?string                 $pluralModelLabel = 'الموردين';
    protected static ?string                 $recordTitleAttribute = 'name';

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('showroom_manager')
            || $user->hasRole('distribution_manager');
    }

    // ─── Global Search ─────────────────────────────────────────────────────────

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'code'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'المصنّع'   => $record->company?->name ?? 'غير محدد',
            'التليفون' => $record->phone ?? '—',
        ];
    }

    // ─── Form & Table ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view'   => ViewSupplier::route('/{record}'),
            'edit'   => EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
