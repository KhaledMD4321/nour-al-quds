<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات المخزن')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم المخزن')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: مخزن المعرض'),

                    Select::make('business_unit_id')
                        ->label('الوحدة التشغيلية')
                        ->relationship('businessUnit', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder('اختر الوحدة'),

                    TextInput::make('location')
                        ->label('الموقع / العنوان')
                        ->maxLength(255)
                        ->placeholder('مثال: المنطقة الصناعية'),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

        ]);
    }
}
