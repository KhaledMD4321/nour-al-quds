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
- **Admin Panel:** Filament 3
- **Database:** PostgreSQL 16
- **Auth & RBAC:** Spatie Laravel Permission
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** Maatwebsite/Excel
- **Realtime:** Livewire 3 (included with Filament)
- **Font:** Cairo (Google Fonts) — Arabic RTL

## Architecture Rules — إلزامية

1. **Business Logic في Services فقط** — `app/Modules/*/Service.php`
2. **Filament Resource = واجهة فقط** — لا يحتوي على business logic
3. **DB::transaction()** لكل عملية مالية مركبة
4. **lockForUpdate()** قبل تعديل Stock أو Treasury
5. **softDeletes** على كل الجداول التجارية (أرشفة لا حذف)
6. **balance_after** في كل stock_movement و treasury_transaction
7. **Eager Loading دائماً** — `Invoice::with(['customer','items.product'])`
8. كل جدول عبر **Migration** — لا تعديل يدوي على قاعدة البيانات
9. كل معاملة تولّد **قيد يومي أوتوماتيكي** متوازن (مدين = دائن)
10. **الفصل المالي بين الوحدتين** قرار معمارية، مش خيار إعدادات

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
├── Filament/
│   └── Resources/              ← واجهة فقط
├── Livewire/                   ← تفاعل سريع (Quick Sale, Invoice Builder)
├── Http/Middleware/
│   └── CheckBusinessUnit.php
└── Providers/

config/
└── modules.php                 ← تشغيل/إيقاف أي module بسطر واحد

docs/                           ← المواصفات التفصيلية (اقرأها قبل أي مهمة)
├── 00-project-overview.md
├── 01-tech-stack.md
├── 02-architecture.md
├── 03-database-schema.md
├── 04-modules-and-services.md
├── 05-roles-and-permissions.md
├── 06-business-rules.md
├── 07-data-management.md
└── 08-build-roadmap.md
```

## Critical Business Rules — خصوصيات العمل

- **الخصم ثلاثي متتابع (مش تراكمي):**
  `((list_price × (1-d1/100)) × (1-d2/100)) × (1-d3/100)`
- **قوائم الأسعار مؤرشفة:** إصدارات per manufacturer. القديم يتأرشف مش يتحذف.
- **الشيكات المؤجلة:** تمر بحساب "تحت التحصيل" أولاً.
- **تحويلات المعرض/المخزن:** فاتورة بيع من المخزن + فاتورة شراء للمعرض.
- **البحث بالاسم الجزئي:** لا باركود. المستخدم بيكتب جزء من اسم المنتج.
- **الطباعة:** A4 فقط. عربي RTL.
- **البيع السريع (Quick Sale):** معاملة بدون فاتورة رسمية — إيصال بسيط فقط.
- **فترات مالية:** الفترة المقفولة ترفض أي معاملة جديدة.
- **أي "حذف" = soft delete + أرشفة.** لا يوجد حذف فعلي من قاعدة البيانات.

## Language & UI

- **الواجهة بالكامل عربي مصري** — labels, messages, notifications
- **RTL** من أول يوم — `->defaultDirection('rtl')` في Filament
- **Font:** Cairo من Google Fonts
- أسماء المتغيرات والكود بالإنجليزي، النصوص الظاهرة للمستخدم بالعربي

## How to Work with This Project

1. **اقرأ الملف المناسب من `docs/`** قبل ما تكتب أي كود
2. **كل feature = Git commit منفصل** — `"Phase X: Add Y"`
3. **اختبر كل خطوة** قبل الانتقال للي بعدها
4. **لما تشك — اسأل** بدل ما تفترض
5. **Migration → Model → Service → Filament Resource** — ده الترتيب دايماً
