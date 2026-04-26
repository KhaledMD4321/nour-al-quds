<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningBalance extends Model
{
    protected $fillable = [
        'type',
        'reference_id',
        'product_id',
        'debit',
        'credit',
        'quantity',
        'unit_cost',
        'balance_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'debit'        => 'decimal:2',
        'credit'       => 'decimal:2',
        'quantity'     => 'decimal:3',
        'unit_cost'    => 'decimal:4',
        'balance_date' => 'date',
    ];

    // ======= Accessors =======

    /**
     * اسم الكيان المرتبط (عميل / مورد / مخزن)
     */
    public function getReferenceNameAttribute(): ?string
    {
        return match ($this->type) {
            'customer' => Customer::find($this->reference_id)?->name,
            'supplier' => Supplier::find($this->reference_id)?->name,
            'stock'    => Warehouse::find($this->reference_id)?->name,
            'treasury' => null,
            default    => null,
        };
    }

    /**
     * اسم المنتج (للمخزون فقط)
     */
    public function getProductNameAttribute(): ?string
    {
        if ($this->type !== 'stock' || !$this->product_id) {
            return null;
        }
        return Product::find($this->product_id)?->name;
    }

    // ======= Relations =======

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
