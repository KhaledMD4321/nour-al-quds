<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'purchase_invoice_id',
        'supplier_id',
        'warehouse_id',
        'business_unit_id',
        'return_date',
        'status',
        'total_amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'total_amount' => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────────

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        $last = self::withTrashed()
            ->whereRaw("reference_number ~ '^PRR-[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(reference_number FROM 5) AS INTEGER) DESC")
            ->value('reference_number');

        $num = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'PRR-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
