<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Services\CustomFieldRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ─── بيانات المورد ────────────────────────────────────────────────
            Section::make('بيانات المورد')
                ->schema([

                    TextInput::make('code')
                        ->label('كود المورد')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('يتولّد تلقائياً'),

                    TextInput::make('name')
                        ->label('اسم المورد')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: مورد إيديال ستاندرد — فرع القاهرة')
                        ->columnSpan(2),

                    Select::make('company_id')
                        ->label('المصنّع')
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('اختياري — لو المورد موزّع لمصنّع محدد')
                        ->nullable(),

                    TextInput::make('phone')
                        ->label('التليفون')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('01055512345'),

                    TextInput::make('phone_2')
                        ->label('تليفون ثاني')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('اختياري'),

                    Textarea::make('address')
                        ->label('العنوان')
                        ->rows(2)
                        ->columnSpanFull(),

                    TextInput::make('tax_registration_number')
                        ->label('رقم التسجيل الضريبي')
                        ->maxLength(50)
                        ->placeholder('اختياري'),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),

                ])
                ->columns(3),

            // ─── الأرصدة ─────────────────────────────────────────────────────
            Section::make('الأرصدة')
                ->schema([

                    TextInput::make('opening_balance')
                        ->label('الرصيد الافتتاحي')
                        ->numeric()
                        ->default(0)
                        ->prefix('ج.م.')
                        ->helperText('المبلغ المستحق للمورد عند بداية استخدام النظام (موجب = إحنا مديونين ليه)'),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2),

                ])
                ->columns(2)
                ->collapsible(),

            // ─── حقول مخصصة (ديناميكية) ──────────────────────────────────────
            ...array_filter([
                (function () {
                    $components = CustomFieldRenderer::formComponents('supplier');
                    if (empty($components)) return null;
                    return Section::make('بيانات إضافية')
                        ->schema($components)
                        ->columns(2)
                        ->collapsible();
                })(),
            ]),

        ]);
    }
}
