<?php

namespace App\Filament\Resources\JournalEntries\RelationManagers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Modules\Accounting\AccountingService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'سطور القيد';

    /**
     * القراءة فقط إذا كان القيد أوتوماتيكياً (لا يسمح بإضافة سطور)
     */
    public function isReadOnly(): bool
    {
        /** @var JournalEntry $entry */
        $entry = $this->getOwnerRecord();

        return ! $entry->is_manual;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('account_id')
                ->label('الحساب')
                ->options(fn () => ChartOfAccount::where('is_active', true)
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.$a->name])
                    ->toArray()
                )
                ->required()
                ->searchable(),

            TextInput::make('debit')
                ->label('مدين')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01),

            TextInput::make('credit')
                ->label('دائن')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01),

            TextInput::make('description')
                ->label('البيان')
                ->maxLength(255),

        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('account.code')
                    ->label('كود')
                    ->fontFamily('mono')
                    ->sortable(),

                TextColumn::make('account.name')
                    ->label('اسم الحساب')
                    ->searchable(),

                TextColumn::make('debit')
                    ->label('مدين')
                    ->money('EGP')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray')
                    ->alignEnd(),

                TextColumn::make('credit')
                    ->label('دائن')
                    ->money('EGP')
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : 'gray')
                    ->alignEnd(),

                TextColumn::make('description')
                    ->label('البيان')
                    ->limit(50),

            ])
            ->headerActions(! $this->isReadOnly() ? [
                CreateAction::make()
                    ->label('إضافة سطر')
                    ->mutateFormDataUsing(function (array $data) {
                        $entry = $this->getOwnerRecord();
                        $data['business_unit_id'] = $entry->lines()->value('business_unit_id')
                            ?? auth()->user()?->business_unit_id;

                        return $data;
                    })
                    ->after(function () {
                        app(AccountingService::class)->recalculateTotals(
                            $this->getOwnerRecord()->id
                        );
                    }),
            ] : [])
            ->recordActions([])
            ->emptyStateHeading('لا توجد سطور')
            ->emptyStateDescription('أضف سطور القيد باستخدام زر "إضافة سطر"');
    }
}
