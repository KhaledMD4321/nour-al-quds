<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitTransferItem extends Model
{
    protected $fillable = [
        'unit_transfer_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:4',
        'total'      => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────────

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(UnitTransfer::class, 'unit_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
