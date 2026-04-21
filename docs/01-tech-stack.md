# 01 — Tech Stack & Tools

## Stack المختار

| التقنية | الإصدار | الدور |
|---------|---------|-------|
| Laravel | 11.x | Backend framework — routing, ORM, validation, queues |
| Filament | 3.x | Admin panel — CRUD screens, forms, tables, widgets |
| Livewire | 3.x | تفاعل سريع بدون JavaScript (مع Filament تلقائياً) |
| PostgreSQL | 16+ | قاعدة البيانات الأساسية |
| Spatie Permission | latest | إدارة الأدوار والصلاحيات (RBAC) |
| DomPDF | latest | توليد PDF للفواتير والتقارير (عربي RTL) |
| Maatwebsite Excel | latest | استيراد/تصدير Excel |
| PHP | 8.3+ | لغة البرمجة |
| Node.js | 20+ LTS | بناء CSS/JS assets |
| Tailwind CSS | 3.x | التصميم (مع Filament تلقائياً) |

## أدوات التطوير

| الأداة | الدور |
|--------|-------|
| Laravel Herd | تركيب PHP + Composer + Nginx مرة واحدة |
| VS Code | محرر الكود + الإضافات |
| Git + GitHub | إدارة النسخ |
| pgAdmin | واجهة رسومية لقاعدة البيانات |
| Claude Code | كتابة الكود (الأداة الأساسية للبناء) |

## VS Code Extensions المطلوبة

- PHP Intelephense
- Laravel Extension Pack
- Tailwind CSS IntelliSense
- GitLens
- Arabic Language Pack

## Composer Packages — الأوامر

```bash
# المشروع
composer create-project laravel/laravel:^11.0 nour-al-quds

# Filament
composer require filament/filament:"^3.2"
php artisan filament:install --panels

# Spatie Permission
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# PDF & Excel
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
```

## إعدادات .env الأساسية

```env
APP_NAME="Nour Al-Quds"
APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
APP_FAKER_LOCALE=ar_EG
APP_TIMEZONE=Africa/Cairo

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nour_al_quds
DB_USERNAME=postgres
DB_PASSWORD=__PASSWORD__
```

## إعدادات Filament — AdminPanelProvider

```php
->viteTheme('resources/css/filament/admin/theme.css')
->font('Cairo', provider: GoogleFontProvider::class)
->defaultDirection('rtl')
->brandName('نور القدس')
```

## ليه اخترنا Laravel + Filament؟

1. **Filament** بيولّد شاشات CRUD كاملة بأمر واحد — أسرع من بناء React من الصفر
2. أخطاء PHP أوضح وأسهل في التعامل — مناسب لشخص مش مبرمج يستخدم Claude Code
3. المعمارية واضحة ومباشرة — Model → Service → Resource
4. مجتمع Laravel ضخم — أي مشكلة ليها حل موثّق
5. Filament بيدعم RTL والعربي بشكل ممتاز
