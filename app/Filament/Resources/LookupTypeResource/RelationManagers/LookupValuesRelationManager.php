<?php

namespace App\Filament\Resources\LookupTypeResource\RelationManagers;

use App\Models\LookupValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LookupValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';
    protected static ?string $title       = 'القيم';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('label')
                    ->label('الاسم بالعربي')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة قيمة')
                    ->form([
                        TextInput::make('code')
                            ->label('الكود (بالإنجليزي)')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('مثال: piece'),

                        TextInput::make('label')
                            ->label('الاسم بالعربي')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('مثال: قطعة'),

                        TextInput::make('sort_order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),

                        Toggle::make('is_default')
                            ->label('افتراضي')
                            ->default(false),
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('تعديل')
                    ->form([
                        TextInput::make('label')
                            ->label('الاسم بالعربي')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('sort_order')
                            ->label('الترتيب')
                            ->numeric(),

                        Toggle::make('is_active')
                            ->label('نشط'),

                        Toggle::make('is_default')
                            ->label('افتراضي'),
                    ]),

                DeleteAction::make()
                    ->label('حذف'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->emptyStateHeading('لا توجد قيم')
            ->emptyStateDescription('اضغط "إضافة قيمة" لإنشاء قيمة جديدة')
            ->emptyStateIcon('heroicon-o-list-bullet');
    }
}
