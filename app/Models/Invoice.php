<?php

namespace App\Models;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'type',
        'business_unit_id',
        'warehouse_id',
        'customer_id',
        'created_by',
        'status',
        'payment_type',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'original_invoice_id',
        'invoice_date',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'invoice_date'    => 'date',
        'due_date'        => 'date',
    ];

    // ── Auto reference generation ────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->reference_number)) {
                $invoice->reference_number = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $prefix = SystemSetting::get('numbering.invoice_prefix', 'INV-');
        $digits = (int) SystemSetting::get('numbering.digits', 5);
        $len    = strlen($prefix);

        $last = static::withTrashed()
            ->where('type', '!=', 'quotation')
            ->whereRaw("reference_number ~ '^" . addslashes($prefix) . "[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(reference_number FROM " . ($len + 1) . ") AS INTEGER) DESC")
            ->value('reference_number');

        $num = $last ? ((int) substr($last, $len)) + 1 : 1;

        return $prefix . str_pad($num, $digits, '0', STR_PAD_LEFT);
    }

    public static function generateQuotationReference(): string
    {
        $prefix = SystemSetting::get('numbering.quotation_prefix', 'QUO-');
        $digits = (int) SystemSetting::get('numbering.digits', 5);
        $len    = strlen($prefix);

        $last = static::withTrashed()
            ->where('type', 'quotation')
            ->whereRaw("reference_number ~ '^" . addslashes($prefix) . "[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(reference_number FROM " . ($len + 1) . ") AS INTEGER) DESC")
            ->value('reference_number');

        $num = $last ? ((int) substr($last, $len)) + 1 : 1;

        return $prefix . str_pad($num, $digits, '0', STR_PAD_LEFT);
    }

    // ── Status helpers ───────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isConfirmedOrBeyond(): bool
    {
        return in_array($this->status, ['confirmed', 'delivered', 'partially_paid', 'paid']);
    }

    public function isQuotation(): bool
    {
        return $this->type === 'quotation';
    }

    public function isSaleReturn(): bool
    {
        return $this->type === 'sale_return';
    }

    public static function generateReturnReference(): string
    {
        $last = self::withTrashed()
            ->where('type', 'sale_return')
            ->whereRaw("reference_number ~ '^RET-[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(reference_number FROM 5) AS INTEGER) DESC")
            ->value('reference_number');

        $num = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'RET-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'draft'          => 'مسودة',
            'confirmed'      => 'مؤكدة',
            'delivered'      => 'مسلّمة',
            'partially_paid' => 'مدفوعة جزئياً',
            'paid'           => 'مدفوعة',
            'cancelled'      => 'ملغاة',
            'quotation'      => 'عرض سعر',
            'sale_return'    => 'مرتجع مبيعات',
            default          => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'draft'          => 'gray',
            'confirmed'      => 'info',
            'delivered'      => 'primary',
            'partially_paid' => 'warning',
            'paid'           => 'success',
            'cancelled'      => 'danger',
            'sale_return'    => 'danger',
            default          => 'gray',
        };
    }

    // ── Accessors ────────────────────────────────────────────────────────────────

    /** المبلغ المتبقي = الإجمالي - المدفوع */
    public function getRemainingAmountAttribute(): float
    {
        return round((float) $this->total_amount - (float) $this->paid_amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabel($this->status);
    }

    // ── Relations ────────────────────────────────────────────────────────────────

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'original_invoice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    // ── Payment helpers ──────────────────────────────────────────────────────────

    public function isFullyPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * أعد حساب paid_amount وحدِّث status بناءً على إجمالي الإيصالات.
     * يُستدعى داخل transaction بعد إنشاء إيصال جديد.
     */
    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->receipts()->sum('amount');

        $this->paid_amount = $paid;

        $total = (float) $this->total_amount;

        if ($paid <= 0) {
            $this->status = in_array($this->status, ['delivered', 'confirmed']) ? $this->status : 'confirmed';
        } elseif ($paid >= $total) {
            $this->status = 'paid';
        } else {
            $this->status = 'partially_paid';
        }

        $this->saveQuietly();
    }
}
