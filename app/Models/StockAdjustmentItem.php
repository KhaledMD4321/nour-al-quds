<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'expected_quantity',
        'actual_quantity',
        'difference',
        'reason',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'actual_quantity'   => 'decimal:3',
        'difference'        => 'decimal:3',
    ];

    // ── Accessors ───────────────────────────────────────────────────────────

    /**
     * Returns: 'surplus' | 'shortage' | 'match'
     */
    public function getDirectionAttribute(): string
    {
        $diff = (float) $this->difference;
        if ($diff > 0) return 'surplus';
        if ($diff < 0) return 'shortage';
        return 'match';
    }

    public function getDirectionLabelAttribute(): string
    {
        return match ($this->direction) {
            'surplus'  => 'زيادة',
            'shortage' => 'نقص',
            'match'    => 'مطابق',
        };
    }

    // ── Relations ───────────────────────────────────────────────────────────

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
