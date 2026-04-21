# 02 — معمارية النظام (Architecture)

## الطبقات الأساسية

```
المستخدم (متصفح)
    ↓ HTTPS
Nginx Web Server
    ↓ FastCGI
Laravel 11 Application
    ├── Filament Panel (واجهة) ← Resource, Pages, Widgets
    ├── Livewire Components ← تفاعل سريع (Quick Sale, Invoice Builder)
    ├── Business Layer ← app/Modules/*/Service.php (★ المنطق هنا)
    ├── Data Layer ← Eloquent ORM + Models + Migrations
    └── Infrastructure ← Spatie, Queues, DomPDF, Excel
        ↓ PDO/Eloquent
PostgreSQL 16
```

## قانون الطبقات — ممنوع الكسر

| الطبقة | ملفاتها | مسؤولياتها ✅ | ممنوع فيها ✗ |
|--------|---------|-------------|-------------|
| **Model** | `app/Models/*.php` | Relations, Scopes, Casts, Accessors | Business logic, DB::transaction |
| **Service** | `app/Modules/*/Service.php` | Business logic, Validation, Transaction, Events | SQL مباشر, Response |
| **Filament Resource** | `app/Filament/Resources/*.php` | Form, Table, Filters, Actions | Business logic, DB queries |
| **Livewire** | `app/Livewire/*.php` | UI interaction, wire:click | Complex business logic |
| **Migration** | `database/migrations/*.php` | Schema changes | Data manipulation |

### مثال صح ✅

```php
// Filament Resource يستدعي Service
class InvoiceResource extends Resource
{
    public static function handleCreate(array $data): Invoice
    {
        return app(InvoiceService::class)->create($data, auth()->id());
    }
}
```

### مثال غلط ✗

```php
// Logic مباشرة في Resource — ممنوع
class InvoiceResource extends Resource
{
    public static function handleCreate(array $data): Invoice
    {
        $invoice = Invoice::create($data);           // ✗
        Stock::where(...)->decrement('quantity');     // ✗
        return $invoice;
    }
}
```

## هيكل الملفات الكامل

```
app/
├── Models/
│   ├── BusinessUnit.php
│   ├── User.php
│   ├── Company.php (المصنّعين)
│   ├── Product.php
│   ├── PriceListVersion.php
│   ├── PriceListItem.php
│   ├── Warehouse.php
│   ├── Stock.php
│   ├── StockMovement.php
│   ├── StockTransfer.php
│   ├── Customer.php
│   ├── Supplier.php
│   ├── Invoice.php
│   ├── InvoiceItem.php
│   ├── QuickSale.php
│   ├── Treasury.php
│   ├── TreasuryTransaction.php
│   ├── Receipt.php
│   ├── Payment.php
│   ├── Cheque.php
│   ├── ChartOfAccount.php
│   ├── JournalEntry.php
│   └── JournalEntryLine.php
│
├── Modules/                    ★ Business Logic
│   ├── Catalog/
│   │   └── PriceListService.php
│   ├── Inventory/
│   │   └── InventoryService.php
│   ├── Sales/
│   │   ├── InvoiceService.php
│   │   └── PriceCalculator.php
│   ├── Purchases/
│   │   └── PurchaseService.php
│   ├── Finance/
│   │   ├── TreasuryService.php
│   │   └── ChequeService.php
│   ├── Accounting/
│   │   └── AccountingService.php
│   ├── Reports/
│   │   └── ReportService.php
│   └── DataManagement/
│       ├── ExportService.php
│       ├── ImportService.php
│       ├── PeriodRollbackService.php
│       └── DataCleanupService.php
│
├── Filament/
│   └── Resources/
│       ├── CompanyResource.php
│       ├── ProductResource.php
│       ├── CustomerResource.php
│       ├── SupplierResource.php
│       ├── InvoiceResource.php
│       ├── PurchaseInvoiceResource.php
│       ├── TreasuryResource.php
│       ├── ChequeResource.php
│       ├── ChartOfAccountResource.php
│       └── ...
│
├── Livewire/
│   ├── QuickSaleForm.php
│   └── InvoiceBuilder.php
│
├── Http/Middleware/
│   └── CheckBusinessUnit.php
│
└── Providers/
    ├── AppServiceProvider.php
    └── Filament/
        └── AdminPanelProvider.php

config/
└── modules.php

database/
├── migrations/     (32 migration files)
└── seeders/
    ├── DatabaseSeeder.php
    ├── BusinessUnitSeeder.php
    ├── WarehouseSeeder.php
    ├── TreasurySeeder.php
    ├── RolesAndPermissionsSeeder.php
    ├── AdminUserSeeder.php
    ├── CompanySettingsSeeder.php
    └── ChartOfAccountsSeeder.php

resources/views/
├── pdf/
│   ├── invoice.blade.php
│   └── report.blade.php
└── livewire/
    ├── quick-sale-form.blade.php
    └── invoice-builder.blade.php
```

## config/modules.php — تحكم مركزي

```php
return [
    'catalog'    => ['name'=>'الشركات والأصناف',   'active'=>true,  'roles'=>['super_admin','manager']],
    'inventory'  => ['name'=>'المخزون',             'active'=>true,  'roles'=>['super_admin','manager','warehouse_keeper']],
    'sales'      => ['name'=>'المبيعات',            'active'=>true,  'roles'=>['super_admin','manager','cashier']],
    'purchases'  => ['name'=>'المشتريات',           'active'=>true,  'roles'=>['super_admin','manager']],
    'contacts'   => ['name'=>'العملاء والموردين',   'active'=>true,  'roles'=>['super_admin','manager','accountant']],
    'finance'    => ['name'=>'الخزينة',             'active'=>true,  'roles'=>['super_admin','manager','accountant']],
    'accounting' => ['name'=>'المحاسبة',            'active'=>true,  'roles'=>['super_admin','accountant']],
    'reports'    => ['name'=>'التقارير',            'active'=>true,  'roles'=>['super_admin','manager']],
    'settings'   => ['name'=>'الإعدادات',           'active'=>true,  'roles'=>['super_admin']],
];
// إضافة module = سطر واحد هنا فقط
```

## تدفق البيانات — مثال: فاتورة مبيعات

```
01. المستخدم يضغط "حفظ الفاتورة" (Livewire InvoiceBuilder)
02. wire:click="saveInvoice" → Livewire يستدعي PHP method
03. $this->validate() → التحقق من المدخلات
04. CheckBusinessUnit Middleware → المستخدم في الوحدة الصح؟
05. Spatie canCreate() → التحقق من صلاحية 'sales.invoice.create'
06. InvoiceService::create() → فحص حد ائتمان العميل
07. DB::transaction() → بدء Transaction
08. Invoice::create() → حفظ الفاتورة
09. InvoiceItem::create() ×N → حفظ البنود مع الخصومات
10. InventoryService::decrease() → خصم من المخزون + StockMovement
11. TreasuryService::add() → إضافة للخزينة (لو نقدي) + TreasuryTransaction
12. AccountingService::generateEntry() → القيد المحاسبي الأوتوماتيكي
13. Transaction Commit → كل شيء نجح → رسالة نجاح
```

**لو أي خطوة من 8-12 فشلت → Transaction Rollback → كل شيء يرجع كأنه ما حصلش.**

## تدفق رفع قائمة أسعار جديدة

```
المرحلة 1 — رفع الملف:
1. المدير يرفع ملف Excel
2. Livewire يستقبل الملف
3. Maatwebsite Excel يقرأ الصفوف
4. التحقق من تنسيق الملف
5. عرض preview قبل الحفظ

المرحلة 2 — معالجة وحفظ:
1. PriceListService::createNewVersion()
2. إصدار جديد + أرشفة القديم (status=archived)
3. لكل صنف: هل موجود في products؟
4. موجود → update السعر في الإصدار الجديد
5. مش موجود → أضفه لـ products أولاً ثم لـ price_list_items
```

> اللستة القديمة لا تُحذف — status تتغير لـ archived فقط. الفواتير القديمة تظل مرتبطة بإصدارها.

## Service Registration

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(InvoiceService::class);
    $this->app->singleton(InventoryService::class);
    $this->app->singleton(TreasuryService::class);
    $this->app->singleton(PriceListService::class);
    $this->app->singleton(AccountingService::class);
    $this->app->singleton(ReportService::class);
}
```
