<?php

namespace App\Filament\Resources\PurchaseInvoices\RelationManagers;

use App\Models\PurchaseInvoice;
use App\Models\Stock;
use App\Modules\Purchases\PurchaseService;
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
    protected static string $relationship = 'items';
    protected static ?string $title = 'بنود الفاتورة';

    // ── Form ───────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('product_id')
                ->label('الصنف')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set): void {
                    if ($state) {
                        $warehouseId = $this->getOwnerRecord()->warehouse_id;
                        $stock = Stock::where('product_id', $state)
                            ->where('warehouse_id', $warehouseId)
                            ->first();
                        // اقتراح آخر متوسط تكلفة من المخزن
                        if ($stock && (float) $stock->avg_cost > 0) {
                            $set('unit_cost', (float) $stock->avg_cost);
                        }
                    }
                })
                ->columnSpanFull(),

            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->step(0.001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) $state * (float) $get('unit_cost'), 2));
                }),

            TextInput::make('unit_cost')
                ->label('سعر الوحدة')
                ->numeric()
                ->required()
                ->prefix('ج.م.')
                ->minValue(0.0001)
                ->step(0.0001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) $get('quantity') * (float) $state, 2));
                }),

            TextInput::make('total')
                ->label('الإجمالي')
                ->prefix('ج.م.')
                ->numeric()
                ->disabled()
                ->dehydrated(),

        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────────────

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
                    ->label('سعر الوحدة')
                    ->money('EGP'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold'),

                TextColumn::make('landed_cost_share')
                    ->label('نصيب من المصاريف')
                    ->money('EGP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('avg_cost_after')
                    ->label('متوسط التكلفة بعد')
                    ->money('EGP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة بند')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
            ])
            ->emptyStateHeading('لا توجد بنود')
            ->emptyStateDescription('أضف البنود من زرار "إضافة بند" أعلاه');
    }

    // ── Mutate ─────────────────────────────────────────────────────────────────

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

    // ── Recalculate after every change ─────────────────────────────────────────

    protected function afterCreate(): void
    {
        app(PurchaseService::class)->recalculateTotals($this->getOwnerRecord());
    }

    protected function afterSave(): void
    {
        app(PurchaseService::class)->recalculateTotals($this->getOwnerRecord());
    }

    protected function afterDelete(): void
    {
        app(PurchaseService::class)->recalculateTotals($this->getOwnerRecord());
    }
}
