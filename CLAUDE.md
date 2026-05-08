# CLAUDE.md — نظام نور القدس ERP

> **هذا الملف هو المرجع الأساسي لـ Claude Code. يُقرأ تلقائياً في بداية كل جلسة.**

## ما هو المشروع؟

نظام ERP مخصص لشركة **نور القدس** لتوزيع وبيع الأدوات الصحية والسباكة في مصر.
الشركة فيها وحدتين تشغيليتين منفصلتين مالياً تحت إدارة واحدة:
- **المعرض** (showroom) — بيع تجزئة
- **مخزن التوزيع** (distribution) — بيع جملة

المعرض بيشتري من المخزن كتاجر عادي بفاتورة رسمية.

## Tech Stack

- **Backend:** Laravel 11 + PHP 8.3
- **Admin Panel:** Filament 5 (مش 3 — تأكد من الـ namespace)
- **Database:** PostgreSQL 16
- **Auth & RBAC:** Spatie Laravel Permission
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** Maatwebsite/Excel
- **Realtime:** Livewire 3 (included with Filament)
- **Font:** Cairo (Google Fonts) — Arabic RTL
- **Backup:** spatie/laravel-backup (يومياً 02:00)

## Architecture Rules — إلزامية

1. **Business Logic في Services فقط** — `app/Modules/*/Service.php`
2. **Filament Resource = واجهة فقط** — لا يحتوي على business logic
3. **DB::transaction()** لكل عملية مالية مركبة
4. **lockForUpdate()** قبل تعديل Stock أو Treasury
5. **softDeletes** على كل الجداول التجارية (أرشفة لا حذف)
6. **balance_after** في كل stock_movement و treasury_transaction
7. **Eager Loading دائماً** — `with([...])` في `getEloquentQuery()` لكل Resource
8. كل جدول عبر **Migration** — لا تعديل يدوي على قاعدة البيانات
9. كل معاملة تولّد **قيد يومي أوتوماتيكي** متوازن (مدين = دائن)
10. **الفصل المالي بين الوحدتين** قرار معمارية، مش خيار إعدادات

## Filament 5 — Namespace المهم

```php
// ✅ الصح
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;   // وليس BadgeColumn المحذوف
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

// ❌ خطأ (Filament 3/4 قديم)
use Filament\Tables\Actions\EditAction;   // ← خطأ
use Filament\Tables\Columns\BadgeColumn; // ← محذوف، استخدم TextColumn->badge()
use Filament\Forms\Form;                  // ← خطأ، استخدم Filament\Schemas\Schema
use Filament\Forms\Get;                   // ← خطأ
```

## File Structure

```
app/
├── Models/                     ← Eloquent Models
├── Modules/                    ← Business Logic (★ القلب)
│   ├── Catalog/
│   │   └── PriceListService.php
│   ├── Inventory/
│   │   └── InventoryService.php
│   ├── Sales/
│   │   ├── InvoiceService.php      ← حد ائتمان + fiscal period
│   │   ├── QuickSaleService.php
│   │   └── PriceCalculator.php
│   ├── Purchases/
│   │   └── PurchaseService.php
│   ├── Finance/
│   │   ├── TreasuryService.php
│   │   ├── ReceiptService.php      ← max 10M guard
│   │   ├── PaymentService.php      ← max 10M guard
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
├── Filament/
│   ├── Resources/              ← واجهة فقط
│   ├── Pages/                  ← صفحات مخصصة
│   │   ├── CustomerStatement.php   ← كشف حساب عميل
│   │   ├── SupplierStatement.php   ← كشف حساب مورد
│   │   ├── AuditLogPage.php        ← سجل عمليات (super_admin)
│   │   ├── GeneralLedger.php
│   │   ├── TrialBalance.php
│   │   ├── AgingReport.php
│   │   ├── ProfitLossReport.php
│   │   ├── InventoryReport.php
│   │   ├── SalesReport.php
│   │   ├── PurchasesReport.php
│   │   ├── CashFlowReport.php
│   │   ├── ExportCenterPage.php
│   │   ├── ImportCenterPage.php
│   │   ├── PeriodManagerPage.php
│   │   ├── DataCleanupPage.php
│   │   └── ImportPriceListPage.php
│   └── Widgets/                ← 6 Dashboard Widgets
│       ├── SalesStatsWidget.php    sort=1
│       ├── TreasuryStatsWidget.php sort=2
│       ├── LowStockWidget.php      sort=3
│       ├── DueChequesWidget.php    sort=4
│       ├── SalesChartWidget.php    sort=5
│       └── TopProductsWidget.php   sort=6 (Widget+blade, مش TableWidget)
├── Http/
│   ├── Controllers/
│   │   ├── InvoicePdfController.php
│   │   ├── QuotationPdfController.php
│   │   ├── QuickSaleReceiptController.php
│   │   ├── ReceiptPrintController.php
│   │   ├── PaymentPrintController.php
│   │   ├── CustomerStatementPrintController.php
│   │   └── SupplierStatementPrintController.php
│   └── Middleware/
│       └── CheckBusinessUnit.php
└── Providers/

config/
├── modules.php     ← fallback defaults لحالة الوحدات (المصدر الحقيقي جدول modules)
└── backup.php      ← spatie/laravel-backup config

routes/
├── web.php         ← print routes
└── console.php     ← scheduled backup (02:00 + 03:00)

docs/               ← المواصفات التفصيلية
```

## الجداول الرئيسية

| الجدول | الوصف | ملاحظات مهمة |
|--------|-------|--------------|
| `invoices` | فواتير (بيع + مرتجع + عروض أسعار) | type: sale/sale_return/quotation |
| `invoice_items` | بنود الفواتير | total (مش total_price) |
| `receipts` | سندات القبض | customer_id, treasury_id |
| `payments` | سندات الصرف | supplier_id أو expense_account_id |
| `cheques` | الشيكات | direction: incoming/outgoing |
| `treasury_transactions` | حركات الخزينة | type: in/out |
| `journal_entries` | القيود اليومية | is_manual, is_posted |
| `journal_entry_lines` | سطور القيود | debit, credit |
| `customers` | العملاء | opening_balance, credit_limit |
| `suppliers` | الموردون | opening_balance |
| `purchase_invoices` | فواتير الشراء | status: confirmed/paid |
| `purchase_returns` | مرتجعات الشراء | return_date |
| `stock` | أرصدة المخزون الحالية | quantity لكل product+warehouse |
| `stock_movements` | حركات المخزون | reference_type, reference_id |
| `price_list_versions` | قوائم الأسعار | status: active/archived |
| `price_list_items` | بنود قوائم الأسعار | product_id, price |
| `quick_sales` | البيع السريع | بدون فاتورة رسمية |
| `fiscal_periods` | الفترات المالية | is_closed |
| `treasuries` | الخزائن والبنوك | type: cash/bank, current_balance |
| `system_settings` | إعدادات النظام (key-value) | group+key unique; types: text/number/toggle/select/textarea/file/color |
| `modules` | وحدات النظام (تفعيل/تعطيل) | code unique; is_active = boolean |

## خريطة الحسابات (chart_of_accounts)

```
1000 أصول
  1100 أصول متداولة
    1110 الخزائن النقدية
      1111 خزينة المعرض
      1112 خزينة المخزن
    1114 حسابات البنوك - المعرض
    1115 حسابات البنوك - المخزن
    1120 الذمم المدينة (العملاء)
    1130 شيكات تحت التحصيل
    1140 المخزون
2000 خصوم
  2100 خصوم متداولة
    2110 الذمم الدائنة (الموردون)
    2120 شيكات مستحقة الدفع
3000 حقوق الملكية
4000 إيرادات
  4100 إيرادات المبيعات
5000 مصروفات
  5100 تكلفة البضاعة المباعة
  5200 مصروفات تشغيلية
```

## Critical Business Rules — خصوصيات العمل

- **الخصم ثلاثي متتابع (مش تراكمي):**
  `((list_price × (1-d1/100)) × (1-d2/100)) × (1-d3/100)`
- **قوائم الأسعار مؤرشفة:** إصدارات per manufacturer. القديم يتأرشف مش يتحذف.
- **الشيكات المؤجلة:** تمر بحساب "تحت التحصيل" (1130) أولاً.
- **تحويلات المعرض/المخزن:** فاتورة بيع من المخزن + فاتورة شراء للمعرض.
- **البحث بالاسم الجزئي:** لا باركود. المستخدم بيكتب جزء من اسم المنتج.
- **الطباعة:** A4 فقط. عربي RTL. font: DejaVu Sans في PDF.
- **البيع السريع (Quick Sale):** معاملة بدون فاتورة رسمية — إيصال بسيط فقط.
- **فترات مالية:** الفترة المقفولة ترفض أي معاملة جديدة.
- **أي "حذف" = soft delete + أرشفة.** لا يوجد حذف فعلي من قاعدة البيانات.
- **حد الائتمان:** `customers.credit_limit` — 0 = بدون حد. يُفرض في `InvoiceService::confirmInvoice()`.
- **حماية المبالغ:** أي مبلغ > 10,000,000 يُرفض في ReceiptService/PaymentService.

## Session & Security

- **SESSION_LIFETIME=30** دقيقة (في .env)
- **Rate Limiting:** 5 محاولات/دقيقة لكل IP على صفحة الدخول
- **Backup:** يومياً 02:00 (DB فقط) + تنظيف 03:00 عبر `php artisan schedule:run`

## Language & UI

- **الواجهة بالكامل عربي مصري** — labels, messages, notifications
- **RTL** من أول يوم — `->defaultDirection('rtl')` في Filament
- **Font:** Cairo من Google Fonts (للواجهة) + DejaVu Sans (للـ PDF)
- أسماء المتغيرات والكود بالإنجليزي، النصوص الظاهرة للمستخدم بالعربي
- **BadgeColumn محذوف** — استخدم `TextColumn::make('x')->badge()`

## How to Work with This Project

1. **اقرأ الملف المناسب من `docs/`** قبل ما تكتب أي كود
2. **كل feature = Git commit منفصل** — `"Phase X: Add Y"`
3. **اختبر كل خطوة** قبل الانتقال للي بعدها
4. **لما تشك — اسأل** بدل ما تفترض
5. **Migration → Model → Service → Filament Resource** — ده الترتيب دايماً
6. **TopProductsWidget** يستخدم `Widget` مش `TableWidget` (PostgreSQL GROUP BY)
7. **لو عملت widget extends ChartWidget** — `$heading` مش static (يطلع error)
8. **SystemSetting** — `SystemSetting::get('group.key', $default)` لقراءة الإعدادات (كاش 1 ساعة)
9. **Module check** — `Module::isActive('sales')` — الـ Resources بتستخدم `HasModuleGuard` trait
10. **Livewire method reset()** محجوز — استخدم `discardChanges()` أو أي اسم آخر
11. **RoleManager** — لا تعدّل صلاحيات `super_admin` من الكود أبداً

## Phases Summary

| Phase | الوصف | الـ Commit |
|-------|-------|------------|
| 1-4 | Core: Models, Migrations, Catalog, Inventory | — |
| 5 | Finance: Receipts, Payments, Cheques, Treasury | — |
| 6 | Reports: Aging, P&L, Inventory, Sales, Purchases | 8c4c064 |
| 7 | Data Management: Export, Import, Period, Cleanup | 3c3eaeb |
| 8 | Dashboard Widgets + Statement Pages + Print | 3536542 |
| 8A-1 | Bug fixes: BadgeColumn, GROUP BY, BOM encoding | 79b5af6 |
| 8A-2 | Cleanup: N+1, Validation, Audit Log, Security, Backup, Indexes | 57b2ea8 |
| 8B-1 | SystemSetting model + migration + seeder (41 settings, 7 groups) | 3dfa36e |
| 8B-2 | SystemSettings Page (7 tabs, Livewire, file upload for logo) | 77043d3 |
| 8B-3 | Connect code to SystemSetting: prefixes, digits, business rules | 9d1477f |
| 8B-4 | RoleManager page: Spatie permissions grouped by category | 028b056 |
| 8B-5 | Frontend polish: brandName, NavigationGroups, empty states x28 | 1d8b7da |
| 8B-6 | Module toggle: modules table + Model + HasModuleGuard trait x23 | 5a46130 |
