<?php

namespace App\Console\Commands;

use App\Mail\DailyDigestMail;
use App\Models\User;
use App\Modules\Notifications\AlertService;
use App\Modules\Notifications\DailyDigestService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * يرسل "ملخص الصباح" + التنبيهات لأصحاب الصلاحية:
 *   - إيميل (أفضل جهد)
 *   - إشعارات داخل النظام (جرس Filament)
 *
 * يُجدوَل يومياً في routes/console.php.
 */
class SendDailyDigest extends Command
{
    protected $signature = 'app:daily-digest';

    protected $description = 'إرسال الملخص اليومي + التنبيهات لأصحاب الصلاحية (إيميل + داخل النظام)';

    public function handle(DailyDigestService $digestService, AlertService $alertService): int
    {
        $digest = $digestService->build();
        $alerts = $alertService->evaluate();

        // whereHas (وليس scope role) كي لا تنهار العملية لو الدور غير موجود بعد (قبل seeding)
        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('لا يوجد مستلمون (super_admin نشط).');

            return self::SUCCESS;
        }

        // ── الإيميل (أفضل جهد — لا يُسقط العملية لو SMTP غير مهيأ) ──
        try {
            Mail::to($recipients)->send(new DailyDigestMail($digest, $alerts));
            $this->info('✓ أُرسل الإيميل لـ '.$recipients->count().' مستلم.');
        } catch (\Throwable $e) {
            Log::warning('Daily digest email failed', ['error' => $e->getMessage()]);
            $this->warn('⚠ تعذّر إرسال الإيميل: '.$e->getMessage());
        }

        // ── إشعار داخل النظام: ملخص اليوم ──
        Notification::make()
            ->title('ملخص اليوم — '.$digest['date'])
            ->body('مبيعات الأمس: '.number_format($digest['yesterday_sales'], 2).' ج.م · النقدية: '.number_format($digest['cash'], 2).' ج.م')
            ->icon('heroicon-o-newspaper')
            ->iconColor('info')
            ->sendToDatabase($recipients);

        // ── إشعارات التنبيهات ──
        foreach ($alerts as $alert) {
            Notification::make()
                ->title($alert['title'])
                ->body($alert['body'])
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor($alert['level'] === 'danger' ? 'danger' : 'warning')
                ->sendToDatabase($recipients);
        }

        $this->info('✓ تنبيهات: '.count($alerts).' · إشعارات داخل النظام أُرسلت.');

        return self::SUCCESS;
    }
}
