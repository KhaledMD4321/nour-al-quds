<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickSaleItem extends Model
{
    protected $fillable = [
        'quick_sale_id',
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

    public function quickSale(): BelongsTo
    {
        return $this->belongsTo(QuickSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
