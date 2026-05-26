<?php

namespace App\Modules\Notifications;

use App\Models\Cheque;
use App\Models\Stock;
use App\Models\SystemSetting;
use App\Modules\Reports\ExecutiveDashboardService;

/**
 * AlertService — يكتشف الحالات الاستثنائية التي تحتاج انتباه الإدارة.
 * عتبات قابلة للضبط عبر SystemSetting (alerts.*) مع قيم افتراضية آمنة.
 *
 * كل تنبيه: ['key','level'(danger|warning|info),'title','body']
 */
class AlertService
{
    public function __construct(private ExecutiveDashboardService $exec) {}

    /** @return array<int, array{key: string, level: string, title: string, body: string}> */
    public function evaluate(): array
    {
        $alerts = [];

        // ── عملاء تجاوزوا حد الائتمان ──
        $overCredit = $this->exec->customersOverCreditLimit();
        if ($overCredit > 0) {
            $alerts[] = [
                'key' => 'credit_limit',
                'level' => 'warning',
                'title' => "{$overCredit} عميل تجاوزوا حد الائتمان",
                'body' => 'راجع أرصدة العملاء قبل بيع آجل جديد.',
            ];
        }

        // ── السيولة النقدية منخفضة ──
        $minCash = (float) SystemSetting::get('alerts.min_cash_balance', 0);
        $cash = $this->exec->cashOnHand();
        if ($cash < 0 || $cash < $minCash) {
            $alerts[] = [
                'key' => 'low_cash',
                'level' => 'danger',
                'title' => 'السيولة النقدية منخفضة',
                'body' => 'إجمالي الخزائن: '.number_format($cash, 2).' ج.م',
            ];
        }

        // ── شيكات تستحق خلال يومين ──
        $dueSoon = Cheque::dueSoon(2)->count();
        if ($dueSoon > 0) {
            $alerts[] = [
                'key' => 'cheques_due',
                'level' => 'warning',
                'title' => "{$dueSoon} شيك يستحق خلال يومين",
                'body' => 'تابع جدول الشيكات لتفادي التأخير.',
            ];
        }

        // ── شيكات مرفوضة بحاجة متابعة ──
        $bounced = Cheque::where('status', 'bounced')->count();
        if ($bounced > 0) {
            $alerts[] = [
                'key' => 'bounced_cheques',
                'level' => 'danger',
                'title' => "{$bounced} شيك مرفوض",
                'body' => 'يحتاج تحصيل بديل أو متابعة قانونية.',
            ];
        }

        // ── أصناف برصيد سالب (خطأ مخزون) ──
        $negativeStock = Stock::where('quantity', '<', 0)->count();
        if ($negativeStock > 0) {
            $alerts[] = [
                'key' => 'negative_stock',
                'level' => 'danger',
                'title' => "{$negativeStock} صنف برصيد مخزون سالب",
                'body' => 'راجع حركات المخزون — هناك خطأ يحتاج تصحيح.',
            ];
        }

        return $alerts;
    }
}
