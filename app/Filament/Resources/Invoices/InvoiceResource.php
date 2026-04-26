<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon      = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null   $navigationGroup     = 'المبيعات';
    protected static ?int                    $navigationSort      = 3;
    protected static ?string                 $navigationLabel     = 'فواتير المبيعات';
    protected static ?string                 $modelLabel          = 'فاتورة بيع';
    protected static ?string                 $pluralModelLabel    = 'فواتير المبيعات';
    protected static ?string                 $recordTitleAttribute = 'reference_number';

    // ── Form & Table ────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    // ── Relations ───────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    // ── Pages ────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view'   => ViewInvoice::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
