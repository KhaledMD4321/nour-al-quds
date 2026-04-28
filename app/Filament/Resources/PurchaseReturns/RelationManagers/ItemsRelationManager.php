<?php

namespace App\Filament\Resources\PurchaseReturns\RelationManagers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Modules\Sales\ReturnService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string  $relationship = 'items';
    protected static ?string $title        = 'بنود المرتجع';

    // ── للقراءة فقط لو المرتجع مؤكد ─────────────────────────────────────────────
    public function isReadOnly(): bool
    {
        /** @var PurchaseReturn $return */
        $return = $this->getOwnerRecord();
        return $return->isConfirmed();
    }

    // ── Form ─────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('product_id')
                ->label('الصنف')
                ->required()
                ->searchable()
                ->live()
                ->options(function (): array {
                    /** @var PurchaseReturn $return */
                    $return = $this->getOwnerRecord();

                    if (! $return->purchase_invoice_id) return [];

                    $invoice = PurchaseInvoice::with('items.product')->find($return->purchase_invoice_id);
                    if (! $invoice) return [];

                    return $invoice->items
                        ->pluck('product.name', 'product_id')
                        ->filter()
                        ->toArray();
                })
                ->afterStateUpdated(function ($state, callable $set): void {
                    if (! $state) return;

                    /** @var PurchaseReturn $return */
                    $return  = $this->getOwnerRecord();
                    $invoice = PurchaseInvoice::with('items')->find($return->purchase_invoice_id);
                    if (! $invoice) return;

                    $originalItem = $invoice->items()->where('product_id', $state)->first();
                    if ($originalItem) {
                        $set('unit_cost', (float) $originalItem->unit_cost);
                    }
                })
                ->columnSpanFull(),

            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->step(0.001)
                ->default(1)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) $state * (float) ($get('unit_cost') ?? 0), 2));
                }),

            TextInput::make('unit_cost')
                ->label('تكلفة الوحدة')
                ->numeric()
                ->required()
                ->prefix('ج.م.')
                ->minValue(0.0001)
                ->step(0.0001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) ($get('quantity') ?? 0) * (float) $state, 2));
                }),

            TextInput::make('total')
                ->label('الإجمالي')
                ->prefix('ج.م.')
                ->numeric()
                ->disabled()
                ->dehydrated(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('الصنف')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('unit_cost')
                    ->label('تكلفة الوحدة')
                    ->money('EGP'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold'),
            ])
            ->headerActions(! $this->isReadOnly() ? [
                CreateAction::make()
                    ->label('إضافة صنف')
                    ->after(fn () => $this->recalcTotals()),
            ] : [])
            ->recordActions(! $this->isReadOnly() ? [
                EditAction::make()
                    ->label('تعديل')
                    ->after(fn () => $this->recalcTotals()),
                DeleteAction::make()
                    ->label('حذف')
                    ->after(fn () => $this->recalcTotals()),
            ] : [])
            ->emptyStateHeading('لا توجد بنود')
            ->emptyStateDescription('أضف أصناف المرتجع من زرار "إضافة صنف" أعلاه');
    }

    // ── Mutate ────────────────────────────────────────────────────────────────────

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = round(
            (float) ($data['quantity'] ?? 0) * (float) ($data['unit_cost'] ?? 0),
            2
        );
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total'] = round(
            (float) ($data['quantity'] ?? 0) * (float) ($data['unit_cost'] ?? 0),
            2
        );
        return $data;
    }

    // ── Recalculate ───────────────────────────────────────────────────────────────

    private function recalcTotals(): void
    {
        /** @var PurchaseReturn $return */
        $return = $this->getOwnerRecord();
        app(ReturnService::class)->recalculatePurchaseReturnTotals($return);
    }
}
