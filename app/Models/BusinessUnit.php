<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessUnit extends Model
{
    const TYPE_SHOWROOM = 'showroom';

    const TYPE_DISTRIBUTION = 'distribution';

    protected $fillable = [
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isShowroom(): bool
    {
        return $this->type === self::TYPE_SHOWROOM;
    }

    public function isDistribution(): bool
    {
        return $this->type === self::TYPE_DISTRIBUTION;
    }
}
