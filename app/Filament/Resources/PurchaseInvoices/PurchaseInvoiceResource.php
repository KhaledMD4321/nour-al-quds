<?php

namespace App\Filament\Resources\PurchaseInvoices;

use App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices;
use App\Filament\Resources\PurchaseInvoices\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\PurchaseInvoices\RelationManagers\LandedCostsRelationManager;
use App\Filament\Resources\PurchaseInvoices\Schemas\PurchaseInvoiceForm;
use App\Filament\Resources\PurchaseInvoices\Tables\PurchaseInvoicesTable;
use App\Models\PurchaseInvoice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-shopping-cart';
    protected static string|\UnitEnum|null   $navigationGroup = 'المشتريات';
    protected static ?int                    $navigationSort   = 1;
    protected static ?string                 $navigationLabel  = 'فواتير المشتريات';
    protected static ?string                 $modelLabel       = 'فاتورة مشتريات';
    protected static ?string                 $pluralModelLabel = 'فواتير المشتريات';
    protected static ?string                 $recordTitleAttribute = 'reference_number';

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('distribution_manager')
            || $user->hasRole('distribution_accountant');
    }

    // ─── Form & Table ──────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return PurchaseInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseInvoicesTable::configure($table);
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            LandedCostsRelationManager::class,
        ];
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseInvoices::route('/'),
            'create' => CreatePurchaseInvoice::route('/create'),
            'edit'   => EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
