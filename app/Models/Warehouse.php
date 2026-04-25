<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'business_unit_id',
        'location',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Guard against deleting warehouses with stock ──────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (Warehouse $warehouse) {
            if ($warehouse->stockItems()->where('quantity', '>', 0)->exists()) {
                throw new \Exception('مش ممكن تحذف المخزن ده لأن فيه أصناف برصيد');
            }
        });
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /** جلب كمية صنف معين في المخزن ده */
    public function getStockQuantity(int $productId): float
    {
        return (float) ($this->stockItems()
            ->where('product_id', $productId)
            ->value('quantity') ?? 0);
    }

    /** جلب متوسط تكلفة صنف معين */
    public function getAvgCost(int $productId): float
    {
        return (float) ($this->stockItems()
            ->where('product_id', $productId)
            ->value('avg_cost') ?? 0);
    }
}
