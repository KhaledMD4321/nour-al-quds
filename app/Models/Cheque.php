<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cheque extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cheque_number', 'bank_name', 'amount', 'issue_date', 'due_date',
        'direction', 'status', 'treasury_id',
        'customer_id', 'supplier_id', 'business_unit_id',
        'receipt_id', 'payment_id', 'replaced_by_id',
        'deposited_at', 'collected_at', 'bounced_at',
        'bounce_reason', 'notes', 'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'deposited_at' => 'datetime',
        'collected_at' => 'datetime',
        'bounced_at'   => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function treasury(): BelongsTo     { return $this->belongsTo(Treasury::class); }
    public function customer(): BelongsTo     { return $this->belongsTo(Customer::class); }
    public function supplier(): BelongsTo     { return $this->belongsTo(Supplier::class); }
    public function businessUnit(): BelongsTo { return $this->belongsTo(BusinessUnit::class); }
    public function receipt(): BelongsTo      { return $this->belongsTo(Receipt::class); }
    public function payment(): BelongsTo      { return $this->belongsTo(Payment::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }

    /** الشيك البديل (الجديد الذي حلّ محلّ هذا الشيك) */
    public function replacedBy(): BelongsTo   { return $this->belongsTo(self::class, 'replaced_by_id'); }

    /** الشيك المُستبدَل (القديم الذي هذا الشيك يُحلّ محلّه) */
    public function replaces(): HasOne        { return $this->hasOne(self::class, 'replaced_by_id'); }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeIncoming($q)  { return $q->where('direction', 'incoming'); }
    public function scopeOutgoing($q)  { return $q->where('direction', 'outgoing'); }
    public function scopePending($q)   { return $q->where('status', 'pending'); }
    public function scopeDeposited($q) { return $q->where('status', 'deposited'); }

    public function scopeDueSoon($q, int $days = 3)
    {
        return $q->whereIn('status', ['pending', 'deposited'])
                 ->whereBetween('due_date', [today(), today()->addDays($days)]);
    }

    public function scopeForUnit($q, int $unitId)
    {
        return $q->where('business_unit_id', $unitId);
    }

    // ─── State Checks ─────────────────────────────────────────────────────────

    public function isIncoming(): bool  { return $this->direction === 'incoming'; }
    public function isOutgoing(): bool  { return $this->direction === 'outgoing'; }
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isDeposited(): bool { return $this->status === 'deposited'; }
    public function isCollected(): bool { return $this->status === 'collected'; }
    public function isBounced(): bool   { return $this->status === 'bounced'; }
    public function isReplaced(): bool  { return $this->status === 'replaced'; }

    // ─── Valid Transitions ────────────────────────────────────────────────────

    /** إيداع: وارد + قيد انتظار فقط */
    public function canDeposit(): bool
    {
        return $this->isIncoming() && $this->isPending();
    }

    /** تحصيل: وارد+مودع  أو  صادر+قيد انتظار */
    public function canCollect(): bool
    {
        return ($this->isIncoming() && $this->isDeposited())
            || ($this->isOutgoing() && $this->isPending());
    }

    /** رفض: وارد + مودع فقط */
    public function canBounce(): bool
    {
        return $this->isIncoming() && $this->isDeposited();
    }

    /** استبدال: وارد + مرفوض فقط */
    public function canReplace(): bool
    {
        return $this->isIncoming() && $this->isBounced();
    }

    // ─── Labels ───────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'قيد الانتظار',
            'deposited' => 'مودع بالبنك',
            'collected' => 'تم التحصيل',
            'bounced'   => 'مرفوض',
            'replaced'  => 'تم الاستبدال',
            default     => $this->status,
        };
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->isIncoming() ? 'وارد' : 'صادر';
    }

    // ─── Reference Number ─────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        $last = self::withTrashed()
            ->where('cheque_number', 'like', 'CHQ-%')
            ->orderByDesc('id')
            ->value('cheque_number');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'CHQ-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
