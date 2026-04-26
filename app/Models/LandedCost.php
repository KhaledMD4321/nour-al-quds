<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandedCost extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'cost_type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getCostTypeLabelAttribute(): string
    {
        return LookupType::getLabel('landed_cost_type', $this->cost_type) ?? $this->cost_type;
    }
}
