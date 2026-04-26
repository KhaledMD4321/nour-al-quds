<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'list_price',
        'discount_1',
        'discount_2',
        'discount_3',
        'unit_price',
        'total',
        'price_list_version_id',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'list_price' => 'decimal:2',
        'discount_1' => 'decimal:2',
        'discount_2' => 'decimal:2',
        'discount_3' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceListVersion(): BelongsTo
    {
        return $this->belongsTo(PriceListVersion::class);
    }
}
