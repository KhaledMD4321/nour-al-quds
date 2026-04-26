<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ─── بيانات الفاتورة ──────────────────────────────────────────────
            Section::make('بيانات الفاتورة')
                ->schema([

                    TextInput::make('reference_number')
                        ->label('رقم الفاتورة')
                        ->default(fn () => Invoice::generateReference())
                        ->disabled()
                        ->dehydrated(),

                    Select::make('business_unit_id')
                        ->label('الوحدة التشغيلية')
                        ->relationship('businessUnit', 'name')
                        ->required()
                        ->placeholder('اختر الوحدة'),

                    Select::make('warehouse_id')
                        ->label('المخزن')
                        ->options(
                            Warehouse::where('is_active', true)->pluck('name', 'id')
                        )
                        ->required()
                        ->placeholder('اختر المخزن'),

                    // ── العميل — يملأ الخصومات تلقائياً ──────────────────────
                    Select::make('customer_id')
                        ->label('العميل')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('اختر العميل')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) return;
                            $customer = Customer::find($state);
                            if (! $customer) return;
                            $set('_default_discount_1', (float) $customer->default_discount_1);
                            $set('_default_discount_2', (float) $customer->default_discount_2);
                            $set('_default_discount_3', (float) $customer->default_discount_3);
                        }),

                    Select::make('payment_type')
                        ->label('طريقة الدفع')
                        ->options([
                            'cash'   => 'نقدي',
                            'credit' => 'آجل',
                            'cheque' => 'شيك',
                        ])
                        ->required()
                        ->default('cash'),

                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'draft'          => 'مسودة',
                            'confirmed'      => 'مؤكدة',
                            'delivered'      => 'مسلّمة',
                            'partially_paid' => 'مدفوعة جزئياً',
                            'paid'           => 'مدفوعة',
                            'cancelled'      => 'ملغاة',
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

                    // ── خصومات العميل الافتراضية (للعرض فقط — تُملأ في البنود) ─
                    TextInput::make('_default_discount_1')
                        ->label('خصم 1 الافتراضي (%)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->dehydrated(false)
                        ->disabled(),

                    TextInput::make('_default_discount_2')
                        ->label('خصم 2 الافتراضي (%)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->dehydrated(false)
                        ->disabled(),

                    TextInput::make('_default_discount_3')
                        ->label('خصم 3 الافتراضي (%)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->dehydrated(false)
                        ->disabled(),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull(),

                ])
                ->columns(3),

            // ─── ملخص الفاتورة (للعرض فقط) ────────────────────────────────────
            Section::make('ملخص الفاتورة')
                ->schema([

                    TextInput::make('subtotal')
                        ->label('إجمالي البضاعة')
                        ->prefix('ج.م.')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('discount_amount')
                        ->label('خصم إضافي على الفاتورة')
                        ->numeric()
                        ->prefix('ج.م.')
                        ->default(0)
                        ->minValue(0),

                    TextInput::make('tax_amount')
                        ->label('الضريبة')
                        ->numeric()
                        ->prefix('ج.م.')
                        ->default(0)
                        ->minValue(0),

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
                ->columns(5)
                ->hiddenOn('create'),

        ]);
    }
}
