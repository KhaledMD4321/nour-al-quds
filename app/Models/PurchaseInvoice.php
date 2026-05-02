<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'supplier_id',
        'warehouse_id',
        'business_unit_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'total_landed_cost',
        'total_amount',
        'paid_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'      => 'date',
        'due_date'          => 'date',
        'subtotal'          => 'decimal:2',
        'tax_amount'        => 'decimal:2',
        'total_landed_cost' => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'paid_amount'       => 'decimal:2',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

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
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function landedCosts(): HasMany
    {
        return $this->hasMany(LandedCost::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        $last = self::withTrashed()
                    ->orderByRaw("CAST(SUBSTRING(reference_number FROM 6) AS INTEGER) DESC")
                    ->value('reference_number');
        $num = $last ? ((int) substr($last, 5)) + 1 : 1;
        return 'PINV-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

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
            'confirmed' => 'مؤكدة',
            'paid'      => 'مدفوعة',
            default     => $this->status,
        };
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0.005;
    }

    /**
     * حدّث status لـ paid لو الفاتورة اتدفعت كلها.
     * purchase_invoices مفيهاش partial_paid — بتفضل confirmed.
     */
    public function refreshPaymentStatus(): void
    {
        if ($this->isFullyPaid() && $this->status !== 'paid') {
            $this->update(['status' => 'paid']);
        }
    }
}
