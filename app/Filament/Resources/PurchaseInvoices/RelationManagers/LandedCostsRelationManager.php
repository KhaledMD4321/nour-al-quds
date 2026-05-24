<?php

namespace App\Filament\Resources\PurchaseInvoices\RelationManagers;

use App\Models\LookupType;
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

class LandedCostsRelationManager extends RelationManager
{
    protected static string $relationship = 'landedCosts';

    protected static ?string $title = 'المصاريف الإضافية (Landed Costs)';

    // ── Form ───────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('cost_type')
                ->label('نوع المصروف')
                ->options(fn (): array => LookupType::getOptions('landed_cost_type'))
                ->required(),

            TextInput::make('amount')
                ->label('المبلغ')
                ->numeric()
                ->required()
                ->prefix('ج.م.')
                ->minValue(0.01)
                ->step(0.01),

            TextInput::make('description')
                ->label('البيان')
                ->placeholder('وصف المصروف الإضافي')
                ->maxLength(255),

        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('cost_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => LookupType::getLabel('landed_cost_type', $state) ?? $state
                    )
                    ->badge()
                    ->color('info'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('البيان')
                    ->placeholder('—'),

            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة مصروف')
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
            ->emptyStateHeading('لا توجد مصاريف إضافية')
            ->emptyStateDescription('أضف مصاريف شحن أو جمارك أو تأمين من زرار "إضافة مصروف"');
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
