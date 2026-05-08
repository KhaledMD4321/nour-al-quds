<?php

namespace App\Filament\Resources\Stocks;

use App\Filament\Resources\Stocks\Pages\ListStocks;
use App\Filament\Resources\Stocks\Tables\StocksTable;
use App\Models\Stock;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use App\Filament\Concerns\HasModuleGuard;
use Filament\Tables\Table;

class StockResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'inventory';

    protected static ?string $model = Stock::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-archive-box';
    protected static string|\UnitEnum|null   $navigationGroup = 'المخزون';
    protected static ?int                    $navigationSort   = 2;
    protected static ?string                 $navigationLabel  = 'جرد المخزون';
    protected static ?string                 $modelLabel       = 'رصيد مخزن';
    protected static ?string                 $pluralModelLabel = 'جرد المخزون';

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('showroom_manager')
            || $user->hasRole('distribution_manager')
            || $user->hasRole('storekeeper');
    }

    /** ★ شاشة للعرض فقط — لا إنشاء يدوي */
    public static function canCreate(): bool
    {
        return false;
    }

    // ─── Form — غير مستخدم (لا create/edit page) ──────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return StocksTable::configure($table);
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListStocks::route('/'),
        ];
    }
}
