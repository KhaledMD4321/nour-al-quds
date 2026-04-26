<?php

namespace App\Modules\Sales;

class PriceCalculator
{
    /**
     * الخصم الثلاثي المتتابع (مش تراكمي).
     *
     * مثال: 1000 × (1 - 10%) × (1 - 5%) × (1 - 2%)
     *      = 1000 × 0.90 × 0.95 × 0.98
     *      = 837.90
     *
     * @param  float  $listPrice  سعر اللستة
     * @param  float  $d1         خصم 1 (%)
     * @param  float  $d2         خصم 2 (%)
     * @param  float  $d3         خصم 3 (%)
     * @return float              سعر الوحدة بعد الخصم
     */
    public static function calculateUnitPrice(
        float $listPrice,
        float $d1 = 0,
        float $d2 = 0,
        float $d3 = 0,
    ): float {
        $price = $listPrice
            * (1 - $d1 / 100)
            * (1 - $d2 / 100)
            * (1 - $d3 / 100);

        return round($price, 2);
    }

    /**
     * نسبة الخصم الفعلية الإجمالية — للعرض فقط.
     *
     * مثال: d1=10, d2=5, d3=2 → 16.21%
     */
    public static function effectiveDiscountPercent(
        float $d1 = 0,
        float $d2 = 0,
        float $d3 = 0,
    ): float {
        $multiplier = (1 - $d1 / 100)
                    * (1 - $d2 / 100)
                    * (1 - $d3 / 100);

        return round((1 - $multiplier) * 100, 2);
    }
}
