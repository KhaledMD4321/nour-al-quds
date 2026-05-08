<?php

namespace App\Models;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'treasury_id',
        'customer_id',
        'invoice_id',
        'business_unit_id',
        'amount',
        'payment_method',
        'receipt_date',
        'cheque_details',
        'bank_reference',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'receipt_date'   => 'date',
        'amount'         => 'decimal:2',
        'cheque_details' => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public static function generateReceiptNumber(): string
    {
        $prefix = SystemSetting::get('numbering.receipt_prefix', 'REC-');
        $digits = (int) SystemSetting::get('numbering.digits', 5);
        $len    = strlen($prefix);

        $last = static::withTrashed()
            ->where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('receipt_number');

        $next = $last ? ((int) substr($last, $len)) + 1 : 1;

        return $prefix . str_pad($next, $digits, '0', STR_PAD_LEFT);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash'          => 'نقدي',
            'cheque'        => 'شيك',
            'bank_transfer' => 'تحويل بنكي',
            default         => $this->payment_method,
        };
    }
}
