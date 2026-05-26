<?php

namespace App\Modules\Reports;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * QuotationFunnelService — معدل تحويل عروض الأسعار (win-rate) والعروض المعلّقة.
 * يعتمد على ربط الفاتورة المحوّلة بـ quotation_id.
 */
class QuotationFunnelService
{
    private function quotationsInPeriod(string $fromDate, string $toDate, ?int $unitId): Builder
    {
        return Invoice::query()
            ->where('type', 'quotation')
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('invoice_date', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
            ->when($unitId, fn (Builder $q) => $q->where('business_unit_id', $unitId));
    }

    /** معرّفات عروض الأسعار التي تحوّلت إلى فواتير بيع. */
    private function convertedQuotationIds(): Collection
    {
        return Invoice::query()
            ->where('type', 'sale')
            ->whereNotNull('quotation_id')
            ->distinct()
            ->pluck('quotation_id');
    }

    /**
     * @return object{total: int, total_value: float, converted: int, converted_value: float, pending: int, win_rate: float}
     */
    public function summary(string $fromDate, string $toDate, ?int $unitId = null): object
    {
        $converted = $this->convertedQuotationIds();

        $total = (int) $this->quotationsInPeriod($fromDate, $toDate, $unitId)->count();
        $totalValue = (float) $this->quotationsInPeriod($fromDate, $toDate, $unitId)->sum('total_amount');
        $wonCount = (int) $this->quotationsInPeriod($fromDate, $toDate, $unitId)->whereIn('id', $converted)->count();
        $wonValue = (float) $this->quotationsInPeriod($fromDate, $toDate, $unitId)->whereIn('id', $converted)->sum('total_amount');

        return (object) [
            'total' => $total,
            'total_value' => round($totalValue, 2),
            'converted' => $wonCount,
            'converted_value' => round($wonValue, 2),
            'pending' => $total - $wonCount,
            'win_rate' => $total > 0 ? round($wonCount / $total * 100, 1) : 0.0,
        ];
    }

    /** عروض أسعار لم تتحوّل بعد (للمتابعة)، الأعلى قيمة أولاً. */
    public function openQuotations(string $fromDate, string $toDate, ?int $unitId = null): Collection
    {
        $converted = $this->convertedQuotationIds();

        return $this->quotationsInPeriod($fromDate, $toDate, $unitId)
            ->with('customer')
            ->whereNotIn('id', $converted)
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($q) => (object) [
                'reference' => $q->reference_number,
                'customer' => $q->customer?->name ?? '—',
                'date' => $q->invoice_date->toDateString(),
                'age' => (int) $q->invoice_date->diffInDays(today()),
                'total' => (float) $q->total_amount,
            ]);
    }
}
