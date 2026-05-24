<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treasury extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'business_unit_id',
        'current_balance',
        'account_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForUnit(Builder $query, int $unitId): Builder
    {
        return $query->where('business_unit_id', $unitId);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function getDisplayLabelAttribute(): string
    {
        return $this->name.' ('.($this->businessUnit?->name ?? '—').')';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'cash' => 'نقدية',
            'bank' => 'بنك',
            default => $this->type,
        };
    }
}
