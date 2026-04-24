<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    protected $fillable = [
        'version_id',
        'product_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:4',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function version(): BelongsTo
    {
        return $this->belongsTo(PriceListVersion::class, 'version_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
