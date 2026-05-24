<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'from_business_unit_id', 'from_warehouse_id',
        'to_business_unit_id',   'to_warehouse_id',
        'transfer_date',
        'status',
        'sale_invoice_id',
        'purchase_invoice_id',
        'total_amount',
        'transfer_price_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────────

    public function fromBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'from_business_unit_id');
    }

    public function toBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'to_business_unit_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UnitTransferItem::class);
    }

    public function saleInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'sale_invoice_id');
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        $last = self::withTrashed()
            ->whereRaw("reference_number ~ '^UTR-[0-9]+$'")
            ->orderByRaw('CAST(SUBSTRING(reference_number FROM 5) AS INTEGER) DESC')
            ->value('reference_number');

        $num = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'UTR-'.str_pad($num, 5, '0', STR_PAD_LEFT);
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
