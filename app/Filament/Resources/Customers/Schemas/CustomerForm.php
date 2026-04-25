<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\LookupType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ─── البيانات الأساسية ────────────────────────────────────────────
            Section::make('بيانات العميل الأساسية')
                ->schema([

                    TextInput::make('code')
                        ->label('كود العميل')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('يتولّد تلقائياً'),

                    TextInput::make('name')
                        ->label('اسم العميل')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: شركة المقاولات المصرية')
                        ->columnSpan(2),

                    Select::make('type')
                        ->label('نوع العميل')
                        ->options(fn (): array => LookupType::getOptions('customer_type'))
                        ->default(fn (): ?string => LookupType::getDefault('customer_type'))
                        ->required()
                        ->searchable(),

                    TextInput::make('phone')
                        ->label('التليفون')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('01012345678'),

                    TextInput::make('phone_2')
                        ->label('تليفون ثاني')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('اختياري'),

                    Textarea::make('address')
                        ->label('العنوان')
                        ->rows(2)
                        ->columnSpanFull(),

                    Select::make('business_unit_id')
                        ->label('الوحدة التشغيلية')
                        ->relationship('businessUnit', 'name')
                        ->placeholder('عميل عام — كل الوحدات')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    TextInput::make('tax_registration_number')
                        ->label('رقم التسجيل الضريبي')
                        ->maxLength(50)
                        ->placeholder('اختياري — للفواتير الضريبية'),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),

                ])
                ->columns(3),

            // ─── الخصومات الافتراضية ──────────────────────────────────────────
            Section::make('الخصومات الافتراضية')
                ->description('الخصم بيتطبق متتابع: السعر × (1-خصم1%) × (1-خصم2%) × (1-خصم3%). مثال: 1000 ج.م. بخصم 10%+5%+2% = 837.90 ج.م.')
                ->schema([

                    TextInput::make('default_discount_1')
                        ->label('خصم 1 (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%'),

                    TextInput::make('default_discount_2')
                        ->label('خصم 2 (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%'),

                    TextInput::make('default_discount_3')
                        ->label('خصم 3 (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%'),

                ])
                ->columns(3)
                ->collapsible(),

            // ─── الائتمان والأرصدة ───────────────────────────────────────────
            Section::make('الائتمان والأرصدة')
                ->schema([

                    TextInput::make('credit_limit')
                        ->label('حد الائتمان')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('ج.م.')
                        ->helperText('صفر = كاش فقط. أي رقم = يسمح بالبيع الآجل حتى هذا المبلغ'),

                    TextInput::make('opening_balance')
                        ->label('الرصيد الافتتاحي')
                        ->numeric()
                        ->default(0)
                        ->prefix('ج.م.')
                        ->helperText('رصيد العميل عند بداية استخدام النظام (موجب = عليه فلوس)'),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull(),

                ])
                ->columns(2)
                ->collapsible(),

        ]);
    }
}
