<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Models\PurchaseInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ─── بيانات الفاتورة ──────────────────────────────────────────────
            Section::make('بيانات الفاتورة')
                ->schema([

                    TextInput::make('reference_number')
                        ->label('رقم الفاتورة الداخلي')
                        ->default(fn () => PurchaseInvoice::generateReference())
                        ->disabled()
                        ->dehydrated(),

                    Select::make('supplier_id')
                        ->label('المورد')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('اختر المورد'),

                    Select::make('business_unit_id')
                        ->label('الوحدة التشغيلية')
                        ->relationship('businessUnit', 'name')
                        ->required()
                        ->placeholder('اختر الوحدة'),

                    Select::make('warehouse_id')
                        ->label('المخزن')
                        ->relationship('warehouse', 'name')
                        ->required()
                        ->placeholder('اختر المخزن'),

                    TextInput::make('invoice_number')
                        ->label('رقم فاتورة المورد')
                        ->placeholder('رقم الفاتورة الورقية من المورد')
                        ->maxLength(100),

                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'draft' => 'مسودة',
                            'confirmed' => 'مؤكدة',
                            'paid' => 'مدفوعة',
                        ])
                        ->default('draft')
                        ->disabled()
                        ->dehydrated(),

                    DatePicker::make('invoice_date')
                        ->label('تاريخ الفاتورة')
                        ->required()
                        ->default(now()),

                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق'),

                    TextInput::make('tax_amount')
                        ->label('ضريبة القيمة المضافة')
                        ->numeric()
                        ->prefix('ج.م.')
                        ->default(0)
                        ->minValue(0),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull(),

                ])
                ->columns(3),

            // ─── ملخص الفاتورة (للعرض فقط في Edit) ──────────────────────────
            Section::make('ملخص الفاتورة')
                ->schema([

                    TextInput::make('subtotal')
                        ->label('إجمالي البضاعة')
                        ->prefix('ج.م.')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('total_landed_cost')
                        ->label('إجمالي المصاريف الإضافية')
                        ->prefix('ج.م.')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('total_amount')
                        ->label('الإجمالي الكلي')
                        ->prefix('ج.م.')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('paid_amount')
                        ->label('المدفوع')
                        ->prefix('ج.م.')
                        ->disabled()
                        ->dehydrated(false),

                ])
                ->columns(4)
                ->hiddenOn('create'),

        ]);
    }
}
