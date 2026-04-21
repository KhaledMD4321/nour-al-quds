# 05 — الأدوار والصلاحيات (RBAC)

> القاعدة: **"ما لا تملكه، لا تراه"** — Spatie Laravel Permission

## الأدوار (Roles)

| الدور | الكود | الوحدة | الوصف |
|-------|-------|--------|-------|
| الإدارة العُليا | `super_admin` | الكل | صلاحية كاملة على كل شيء |
| مدير المعرض | `showroom_manager` | المعرض | مبيعات + مخزون معرض + خزينة معرض + تقارير المعرض |
| كاشير المعرض | `showroom_cashier` | المعرض | بيع سريع + فاتورة فقط |
| مدير التوزيع | `distribution_manager` | التوزيع | مبيعات جملة + مخزون + خزينة + تقارير التوزيع |
| محاسب التوزيع | `distribution_accountant` | التوزيع | خزينة + قيود + محاسبة |
| أمين المخزن | `warehouse_keeper` | التوزيع | عرض مخزون + تحويل + تسوية |

## الصلاحيات (Permissions)

```
sales.quick.create          بيع سريع
sales.invoice.create        إنشاء فاتورة
sales.invoice.delete        حذف فاتورة

inventory.view              عرض المخزون
inventory.transfer          تحويل بين مخازن
inventory.adjust            تسوية مخزون

finance.treasury.view       عرض الخزينة
finance.receipt.create      سند قبض
finance.payment.create      سند صرف

reports.sales               تقارير المبيعات
reports.profit_loss         تقرير الأرباح والخسائر

accounting.journal          القيود اليومية
accounting.lock_period      قفل/فتح فترة مالية

settings.users              إدارة المستخدمين
settings.company            إعدادات الشركة
```

## توزيع الصلاحيات على الأدوار

### super_admin
- كل الصلاحيات

### showroom_manager
- `sales.quick.create`, `sales.invoice.create`
- `inventory.view`, `inventory.transfer`
- `finance.treasury.view`, `finance.receipt.create`
- `reports.sales`

### showroom_cashier
- `sales.quick.create`, `sales.invoice.create`

### distribution_manager
- `sales.invoice.create`
- `inventory.view`, `inventory.transfer`, `inventory.adjust`
- `finance.treasury.view`, `finance.receipt.create`, `finance.payment.create`
- `reports.sales`

### distribution_accountant
- `finance.treasury.view`, `finance.receipt.create`, `finance.payment.create`
- `accounting.journal`

### warehouse_keeper
- `inventory.view`, `inventory.transfer`, `inventory.adjust`

## التطبيق في الكود

### Middleware — CheckBusinessUnit
```php
class CheckBusinessUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin() && !$user->business_unit_id) {
            abort(403, 'المستخدم غير مرتبط بوحدة تشغيلية');
        }
        return $next($request);
    }
}
```

### فلترة البيانات في Filament Resource
```php
// كل Resource بيفلتر حسب الوحدة تلقائياً
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user  = auth()->user();

    if (!$user->isSuperAdmin() && $user->business_unit_id) {
        $query->where('business_unit_id', $user->business_unit_id);
    }

    return $query;
}
```

### إخفاء الشاشات حسب الصلاحية
```php
public static function canAccess(): bool
{
    return auth()->user()->hasPermissionTo('sales.invoice.create')
        || auth()->user()->isSuperAdmin();
}

public static function shouldRegisterNavigation(): bool
{
    return static::canAccess();
}
```

## ماذا يرى كل دور؟

### فريق المعرض
- ✅ مخزن المعرض فقط
- ✅ فواتير التجزئة فقط
- ✅ عملاء المعرض فقط
- ✅ خزينة المعرض فقط
- ❌ مبيعات التوزيع
- ❌ أرباح الشركة الإجمالية
- ❌ خزينة التوزيع

### فريق التوزيع
- ✅ مخزن التوزيع فقط
- ✅ فواتير الجملة فقط
- ✅ عملاء الجملة فقط
- ✅ خزينة التوزيع فقط
- ❌ مبيعات المعرض اليومية
- ❌ خزينة المعرض

### Super Admin
- ✅ كل شيء — الوحدتين + التقارير الموحدة + الإعدادات
