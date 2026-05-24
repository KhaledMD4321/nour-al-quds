<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuickSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'business_unit_id',
        'warehouse_id',
        'treasury_id',
        'total_amount',
        'payment_method',
        'customer_name',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    // ── Auto-code ────────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        $last = static::orderByRaw('CAST(SUBSTRING(reference_number FROM 4) AS INTEGER) DESC')
            ->value('reference_number');
        $num = $last ? ((int) substr($last, 3)) + 1 : 1;

        return 'QS-'.str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuickSaleItem::class);
    }
}
