# 08 — خارطة طريق البناء

> **القانون:** ممنوع تقفز مرحلة قبل ما السابقة تخلص وتتجرب.

## المراحل بنظرة واحدة

| # | المرحلة | المدة التقديرية |
|---|---------|----------------|
| 0 | تجهيز بيئة العمل | يوم واحد |
| 1 | هيكل المشروع (Laravel + Filament + Git) | يوم – يومين |
| 2 | طبقة الأساس (شجرة حسابات + وحدات + صلاحيات + فترات) | أسبوع – 10 أيام |
| 3 | البيانات الرئيسية (مصنّعين + منتجات + أسعار + عملاء + موردين) | أسبوع – 10 أيام |
| 4 | الوحدات التشغيلية (مشتريات → مخزون → مبيعات → مرتجعات → تحويلات) | 3 – 4 أسابيع |
| 5 | الطبقة المالية (خزائن + PDC + مقبوضات + مدفوعات + قيود) | أسبوعين |
| 6 | التقارير (aging + P&L + مخزون + مبيعات + مشتريات + ميزان مراجعة) | أسبوع – 10 أيام |
| 7 | إدارة البيانات (تصدير + استيراد + فترات + تنظيف) | أسبوع |
| 8 | الاختبار والنشر (UAT + تدريب + سيرفر + تشغيل موازي) | 2 – 3 أسابيع |

**المدة الإجمالية:** 3 – 4 شهور شغل جاد مع Claude Code.

## ترتيب البناء الداخلي لكل feature

```
1. Migration (إنشاء الجدول)
2. Model + Relations + Scopes + Casts
3. Seeder (بيانات تجريبية)
4. Service (business logic)
5. Filament Resource (الواجهة)
6. اختبار ببيانات حقيقية
7. Git commit
```

## المرحلة 0 — تجهيز البيئة

تركيب: Laravel Herd (PHP + Composer + Nginx) → PostgreSQL 16 → pgAdmin → Git + GitHub → VS Code + Extensions → Claude Code → Node.js LTS

**اختبار:**
```bash
php --version       # PHP 8.3.x
composer --version  # Composer 2.x
node --version      # v20.x+
git --version       # أي نسخة حديثة
psql --version      # PostgreSQL 16.x+
```

## المرحلة 1 — هيكل المشروع

1. `composer create-project laravel/laravel:^11.0 nour-al-quds`
2. ضبط .env (PostgreSQL + Arabic locale)
3. `php artisan migrate`
4. تنصيب Filament 3 + make:filament-user
5. تنصيب Spatie Permission + publish + migrate
6. تنصيب DomPDF + Maatwebsite Excel
7. ضبط RTL + Cairo font + brandName في AdminPanelProvider
8. Git init + أول commit + رفع GitHub
9. `php artisan serve` → التأكد من شاشة دخول عربية

## المرحلة 2 — طبقة الأساس

بالترتيب:
1. Company Settings (جدول إعدادات — صف واحد)
2. Business Units (المعرض + التوزيع)
3. Users + Roles + Permissions (Spatie Seeder)
4. Chart of Accounts (شجرة حسابات كاملة — Seeder جاهز)
5. Fiscal Periods (السنوات والشهور + قفل/فتح)
6. Units of Measure + Currencies + Tax Rates

**Exit Criteria:**
- [ ] شركة واحدة ووحدتين في قاعدة البيانات
- [ ] شجرة حسابات كاملة (50+ حساب)
- [ ] 3 مستخدمين بصلاحيات مختلفة يشوفوا شاشات مختلفة
- [ ] فترة مالية مقفولة ترفض الإضافة
- [ ] PDF تجريبي بالترويسة واللوجو

## المرحلة 3 — البيانات الرئيسية

بالترتيب:
1. المصنّعين (Companies/Manufacturers)
2. التصنيفات (Categories) — هيكل شجري
3. المنتجات (Products) — مع index على name
4. قوائم الأسعار (Price Lists) — versions + items
5. المخزنين (Warehouses) — معرض + توزيع
6. العملاء (Customers)
7. الموردين (Suppliers)
8. أرصدة الافتتاح (Opening Balances)

**Exit Criteria:**
- [ ] 5 مصنّعين + 20 منتج تجريبي
- [ ] قائمة أسعار شغالة بكل المنتجات
- [ ] مخزنين + 3 عملاء + 3 موردين بأرصدة افتتاحية
- [ ] البحث بجزء من الاسم سريع (< 1 ثانية)
- [ ] قيد أرصدة افتتاحية متوازن

## المرحلة 4 — الوحدات التشغيلية

**الترتيب إلزامي:**
1. المشتريات (Purchase Order → Invoice → Stock increase)
2. المخزون (stock_movements, stock, adjustments)
3. البيع السريع (Quick Sale — Livewire)
4. فاتورة المبيعات الكاملة (Invoice — Filament Resource)
5. عروض الأسعار (Quotations → تحويل لفاتورة)
6. المرتجعات (مبيعات + مشتريات)
7. التحويلات بين الوحدتين (فاتورتين متكاملتين)

**Exit Criteria:**
- [ ] دورة كاملة: شراء → تخزين → بيع → مرتجع → بيانات متسقة
- [ ] كل معاملة تولّد قيد يومي متوازن
- [ ] المخزون = مجموع الحركات الداخلة - الخارجة
- [ ] الخصم الثلاثي المتتابع شغّال صح
- [ ] التحويل بين الوحدتين = فاتورتين
- [ ] فواتير A4 نظيفة بالعربي

## المرحلة 5 — الطبقة المالية

1. الخزائن (خزينة لكل وحدة)
2. المقبوضات (كاش + شيك + تحويل)
3. المدفوعات (موردين + مصروفات)
4. إدارة الشيكات المؤجلة (PDC) مع "تحت التحصيل"
5. القيود اليدوية
6. دفتر الأستاذ (General Ledger)

## المرحلة 6 — التقارير

بترتيب الأهمية:
1. أعمار الديون (Aging) — عملاء + موردين
2. P&L لكل وحدة
3. P&L موحّد (مع استبعاد البينيات)
4. تقارير المخزون
5. تقارير المبيعات
6. تقارير المشتريات
7. ميزان المراجعة (Trial Balance)

كل تقرير = فلترة + Excel export + PDF print

## المرحلة 7 — إدارة البيانات

1. مركز التصدير
2. مركز الاستيراد مع Preview + Validation
3. مدير الفترات (قفل + فتح + rollback)
4. أدوات التنظيف (تكرارات + دمج + أرشفة)

## المرحلة 8 — الاختبار والنشر

1. Seeder ببيانات واقعية (500 منتج + 100 معاملة)
2. UAT — 3 موظفين يشتغلوا أسبوع
3. تدريب + فيديوهات + دليل مستخدم
4. سيرفر VPS (DigitalOcean/Hetzner) + Nginx + SSL
5. تشغيل موازي شهر مع AccountPedia

## Git Workflow

```bash
# كل feature صغيرة = commit
git add .
git commit -m "Phase 2: Add ChartAccount model and migration"
git push

# كل مرحلة = branch
git checkout -b phase-2
# ... العمل ...
git checkout main
git merge phase-2
```
