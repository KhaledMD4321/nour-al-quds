<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /**
     * ★ لا softDeletes — سجل أبدي لا يُحذف
     */
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'balance_after' => 'decimal:3',
    ];

    // ======= العلاقات =======

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ======= Scopes =======

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ======= Accessors =======

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in' => 'دخول',
            'out' => 'خروج',
            'transfer_in' => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            'adjustment_plus' => 'تسوية بالزيادة',
            'adjustment_minus' => 'تسوية بالنقص',
            'opening' => 'رصيد افتتاحي',
            default => $this->type,
        };
    }
}
