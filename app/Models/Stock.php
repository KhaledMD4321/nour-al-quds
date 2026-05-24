<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    /**
     * ★ جدول stock بدون timestamps عادية — فقط last_updated
     */
    public $timestamps = false;

    protected $table = 'stock';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'avg_cost',
        'last_updated',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'avg_cost' => 'decimal:4',
        'last_updated' => 'datetime',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    /** الأصناف اللي رصيدها صفر */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /** الأصناف اللي تحت الحد الأدنى (لو عُرِّف على المنتج مستقبلاً) */
    public function scopeBelowMinimum($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->whereColumn('stock.quantity', '<', 'products.min_stock_level')
                ->where('products.min_stock_level', '>', 0);
        });
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    /** القيمة الإجمالية = الكمية × متوسط التكلفة */
    public function getTotalValueAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->avg_cost, 2);
    }
}
