<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'description',
        'source_type',
        'source_id',
        'is_manual',
        'is_posted',
        'total_debit',
        'total_credit',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'is_manual'    => 'boolean',
        'is_posted'    => 'boolean',
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source()
    {
        return $this->morphTo();
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public static function generateEntryNumber(): string
    {
        $last = static::withTrashed()
            ->where('entry_number', 'like', 'JE-%')
            ->orderByDesc('id')
            ->value('entry_number');

        $next = $last ? ((int) substr($last, 3)) + 1 : 1;

        return 'JE-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public function isBalanced(): bool
    {
        return bccomp(
            (string) $this->total_debit,
            (string) $this->total_credit,
            2
        ) === 0;
    }
}
