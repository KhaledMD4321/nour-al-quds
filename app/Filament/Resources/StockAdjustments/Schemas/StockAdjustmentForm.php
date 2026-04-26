<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use App\Models\BusinessUnit;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات التسوية')
                ->columns(3)
                ->schema([
                    TextInput::make('reference_number')
                        ->label('رقم المرجع')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('يُولَّد تلقائياً'),

                    DatePicker::make('adjustment_date')
                        ->label('تاريخ التسوية')
                        ->required()
                        ->default(now()),

                    Select::make('business_unit_id')
                        ->label('وحدة الأعمال')
                        ->options(BusinessUnit::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ]),

            Section::make('المخزن والسبب')
                ->columns(2)
                ->schema([
                    Select::make('warehouse_id')
                        ->label('المخزن')
                        ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    TextInput::make('reason')
                        ->label('سبب التسوية (عام)')
                        ->maxLength(255),
                ]),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }
}
