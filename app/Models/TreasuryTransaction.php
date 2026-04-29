<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TreasuryTransaction extends Model
{
    // ⚠️ سجل أبدي — بدون updated_at، بدون softDeletes
    public const UPDATED_AT = null;

    protected $fillable = [
        'treasury_id',
        'type',
        'amount',
        'balance_after',
        'transaction_date',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'balance_after'    => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'receipt'      => 'مقبوضات',
            'payment'      => 'مدفوعات',
            'transfer_in'  => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            default        => $this->type,
        };
    }

    public function getIsInflowAttribute(): bool
    {
        return in_array($this->type, ['receipt', 'transfer_in']);
    }
}
