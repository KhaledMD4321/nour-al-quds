<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'treasury_id',
        'supplier_id',
        'purchase_invoice_id',
        'business_unit_id',
        'amount',
        'category',
        'payment_method',
        'payment_date',
        'cheque_details',
        'bank_reference',
        'expense_account_id',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'payment_date'   => 'date',
        'cheque_details' => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'expense_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('business_unit_id', $unitId);
    }

    public function scopeSupplierPayments($query)
    {
        return $query->where('category', 'supplier_payment');
    }

    public function scopeExpenses($query)
    {
        return $query->where('category', '!=', 'supplier_payment');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isSupplierPayment(): bool
    {
        return $this->category === 'supplier_payment';
    }

    public function getCategoryLabelAttribute(): string
    {
        return LookupType::getLabel('expense_category', $this->category) ?? $this->category;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash'          => 'كاش',
            'cheque'        => 'شيك',
            'bank_transfer' => 'تحويل بنكي',
            default         => $this->payment_method,
        };
    }

    public static function generateReference(): string
    {
        $last = static::withTrashed()
            ->where('payment_number', 'like', 'PAY-%')
            ->orderByDesc('id')
            ->value('payment_number');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'PAY-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
