<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'business_unit_id',
        'transfer_date',
        'status',
        'notes',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'transfer_date'  => 'date',
        'confirmed_at'   => 'datetime',
    ];

    // ── Auto-code ───────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        $last = self::withTrashed()
            ->orderByRaw("CAST(SUBSTRING(reference_number FROM 5) AS INTEGER) DESC")
            ->value('reference_number');
        $num = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'TRF-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    // ── Status helpers ──────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'مسودة',
            'confirmed' => 'مؤكد',
            default     => $this->status,
        };
    }

    // ── Relations ───────────────────────────────────────────────────────────

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
