<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Models\BusinessUnit;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات التحويل')
                ->columns(3)
                ->schema([
                    TextInput::make('reference_number')
                        ->label('رقم المرجع')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('يُولَّد تلقائياً'),

                    DatePicker::make('transfer_date')
                        ->label('تاريخ التحويل')
                        ->required()
                        ->default(now()),

                    Select::make('business_unit_id')
                        ->label('وحدة الأعمال')
                        ->options(BusinessUnit::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ]),

            Section::make('المخازن')
                ->columns(2)
                ->schema([
                    Select::make('from_warehouse_id')
                        ->label('من مخزن')
                        ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    Select::make('to_warehouse_id')
                        ->label('إلى مخزن')
                        ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ]),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }
}
