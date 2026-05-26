<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── نسخ احتياطي يومي ──────────────────────────────────────────────────────
// يُشغَّل تلقائياً بعد إعداد Task Scheduler على الخادم:
//   php artisan schedule:run (كل دقيقة)
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('نسخ احتياطي يومي لقاعدة البيانات — 02:00 صباحاً');

Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->description('تنظيف النسخ الاحتياطية القديمة — 03:00 صباحاً');

// ── الملخص اليومي + التنبيهات ──────────────────────────────────────────────
// يرسل "ملخص الصباح" + التنبيهات لأصحاب الصلاحية (إيميل + داخل النظام).
Schedule::command('app:daily-digest')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->description('ملخص يومي + تنبيهات الإدارة — 07:30 صباحاً');
