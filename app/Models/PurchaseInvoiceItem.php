<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total',
        'landed_cost_share',
        'avg_cost_after',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'total' => 'decimal:2',
        'landed_cost_share' => 'decimal:4',
        'avg_cost_after' => 'decimal:4',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * التكلفة الفعلية للوحدة بعد توزيع الـ landed costs
     */
    public function getEffectiveUnitCostAttribute(): float
    {
        $qty = (float) $this->quantity;
        if ($qty <= 0) {
            return (float) $this->unit_cost;
        }

        return ((float) $this->total + (float) $this->landed_cost_share) / $qty;
    }
}
