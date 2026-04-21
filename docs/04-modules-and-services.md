# 04 — Modules & Services

## القاعدة الحاكمة

> كل business logic في `app/Modules/*/Service.php` — Filament Resource = واجهة فقط.

## 1. Catalog Module — الشركات والأصناف

### PriceListService.php

**المسؤوليات:**
- إنشاء إصدار جديد من قائمة الأسعار لمصنّع
- أرشفة الإصدار القديم (status → archived)
- ربط أصناف بالإصدار الجديد
- استيراد قائمة أسعار من Excel

**Methods:**
```php
createNewVersion(int $companyId, array $items): PriceListVersion
archiveVersion(int $versionId): void
getActiveVersion(int $companyId): ?PriceListVersion
importFromExcel(UploadedFile $file, int $companyId): ImportResult
```

## 2. Inventory Module — المخزون

### InventoryService.php

**المسؤوليات:**
- زيادة/نقصان المخزون مع تسجيل stock_movement
- حساب الـ balance_after
- حساب متوسط التكلفة (avg_cost)
- تنفيذ التحويلات بين المخازن
- تنفيذ التسويات (adjustments)

**Methods:**
```php
increase(int $warehouseId, int $productId, float $qty, float $cost, string $refType, int $refId): StockMovement
decrease(int $warehouseId, int $productId, float $qty, string $refType, int $refId): StockMovement
transfer(StockTransfer $transfer): void
adjustStock(StockAdjustment $adjustment): void
getBalance(int $warehouseId, int $productId): float
```

**قاعدة مهمة:** `lockForUpdate()` قبل أي تعديل على جدول `stock`:
```php
$stock = Stock::where('warehouse_id', $warehouseId)
    ->where('product_id', $productId)
    ->lockForUpdate()
    ->first();
```

## 3. Sales Module — المبيعات

### InvoiceService.php

**المسؤوليات:**
- إنشاء فاتورة مبيعات (نقدي / آجل / شيك)
- فحص حد الائتمان
- حساب الخصومات والضرائب
- خصم المخزون
- إضافة للخزينة (لو نقدي)
- توليد القيد المحاسبي
- إنشاء مرتجع مبيعات

**Methods:**
```php
create(array $data, int $userId): Invoice
createReturn(int $originalInvoiceId, array $returnItems): Invoice
confirm(Invoice $invoice): void
cancel(Invoice $invoice): void
convertFromQuotation(int $quotationId): Invoice
```

### PriceCalculator.php

**المعادلة الأساسية — خصم ثلاثي متتابع:**
```php
// ((list_price × (1 - d1/100)) × (1 - d2/100)) × (1 - d3/100)
// مش: list_price × (1 - (d1+d2+d3)/100)

public function calculateFinalPrice(
    float $listPrice,
    float $discount1 = 0,
    float $discount2 = 0,
    float $discount3 = 0
): float {
    $price = $listPrice;
    $price *= (1 - $discount1 / 100);
    $price *= (1 - $discount2 / 100);
    $price *= (1 - $discount3 / 100);
    return round($price, 4);
}
```

**مثال:**
```
list_price = 100, d1 = 25%, d2 = 5%, d3 = 2%
= 100 × 0.75 = 75
= 75  × 0.95 = 71.25
= 71.25 × 0.98 = 69.825
```

### Quick Sale — البيع السريع
- شاشة Livewire منفصلة (مش Filament Resource عادي)
- بحث بالاسم الجزئي → اختيار المنتج → الكمية → السعر
- الدفع كاش فقط
- بدون فاتورة رسمية — إيصال بسيط
- المخزون بينقص + الخزينة بتزيد + قيد يتسجل

## 4. Purchases Module — المشتريات

### PurchaseService.php

**المسؤوليات:**
- إنشاء أمر شراء
- تحويل أمر شراء لفاتورة
- إنشاء فاتورة شراء مباشرة
- إضافة Landed Costs وتوزيعها على البنود
- إضافة المخزون
- توليد القيد المحاسبي

**Methods:**
```php
createOrder(array $data, int $userId): PurchaseOrder
createInvoice(array $data, int $userId): PurchaseInvoice
createFromOrder(int $orderId): PurchaseInvoice
addLandedCost(int $invoiceId, string $type, float $amount, string $desc): void
distributeLandedCosts(int $invoiceId): void
```

**توزيع Landed Costs:**
```
كل بند يحصل على نسبة من المصاريف الإضافية بناءً على نسبته من إجمالي الفاتورة.
مثال: بند قيمته 1000 من فاتورة 10000 = يحصل على 10% من الـ landed costs.
```

## 5. Finance Module — الخزينة والشيكات

### TreasuryService.php

**المسؤوليات:**
- إضافة مبلغ للخزينة (مقبوضات)
- خصم من الخزينة (مدفوعات)
- تحويل بين خزائن
- حساب balance_after

**Methods:**
```php
addFunds(int $treasuryId, float $amount, string $refType, int $refId, string $desc): TreasuryTransaction
deductFunds(int $treasuryId, float $amount, string $refType, int $refId, string $desc): TreasuryTransaction
transfer(int $fromId, int $toId, float $amount, ?int $approvedBy): TreasuryTransfer
```

**قاعدة:** `lockForUpdate()` قبل أي تعديل على `treasuries.current_balance`.

### ChequeService.php

**دورة حياة الشيك الوارد:**
```
pending (تسجيل) → deposited (تم إيداعه بالبنك) → collected (تم تحصيله) ✓
                                                  → bounced (مرفوض) ✗
                                                     → replaced (تم استبداله)
```

**Methods:**
```php
register(array $data): Cheque
deposit(int $chequeId): void
collect(int $chequeId): void      // ينقل من "تحت التحصيل" → الخزينة
bounce(int $chequeId): void       // يرجع لرصيد العميل
replace(int $oldId, array $newData): Cheque
```

## 6. Accounting Module — المحاسبة

### AccountingService.php

**المسؤوليات:**
- توليد قيد يومي أوتوماتيكي من أي معاملة
- تسجيل قيد يدوي
- التحقق من توازن القيد (مدين = دائن)
- فحص الفترة المالية (مقفولة؟)
- حساب ميزان المراجعة

**Methods:**
```php
generateEntry(string $sourceType, int $sourceId, array $lines, string $description): JournalEntry
createManualEntry(array $data, int $userId): JournalEntry
getTrialBalance(?int $businessUnitId, ?string $fromDate, ?string $toDate): array
```

**قاعدة التوازن:**
```php
// قبل حفظ أي قيد:
$totalDebit  = collect($lines)->sum('debit');
$totalCredit = collect($lines)->sum('credit');
if (abs($totalDebit - $totalCredit) > 0.01) {
    throw new UnbalancedEntryException();
}
```

## 7. Reports Module — التقارير

### ReportService.php

التقارير الأساسية:
1. **أعمار الديون (Aging)** — عملاء وموردين
2. **P&L لكل وحدة** — إيرادات - تكلفة مبيعات - مصروفات
3. **P&L موحّد** — مجموع الوحدتين - المعاملات البينية
4. **تقارير المخزون** — أرصدة، حركة صنف، راكد، تحت الحد الأدنى
5. **تقارير المبيعات** — بالمنتج، بالعميل، بالموظف، بالفترة
6. **تقارير المشتريات** — بالمورد، بالمصنّع
7. **ميزان المراجعة** — كل الحسابات مدين/دائن

كل تقرير يدعم: فلترة بالفترة والوحدة + تصدير Excel + طباعة PDF.

## 8. DataManagement Module — إدارة البيانات

> راجع `07-data-management.md` للتفاصيل الكاملة.

- ExportService — تصدير أي جدول
- ImportService — استيراد من Excel مع validation
- PeriodRollbackService — إلغاء مدخلات فترة
- DataCleanupService — حذف آمن مع أرشفة
