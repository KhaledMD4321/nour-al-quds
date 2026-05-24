<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    const TYPE_ASSET = 'asset';

    const TYPE_LIABILITY = 'liability';

    const TYPE_EQUITY = 'equity';

    const TYPE_REVENUE = 'revenue';

    const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'business_unit_id',
        'is_active',
        'level',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'integer',
        ];
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    public function getFullPath(): string
    {
        $parts = [$this->name];
        $current = $this;

        while ($current->parent_id) {
            $current = $current->parent;
            if ($current) {
                array_unshift($parts, $current->name);
            }
        }

        return implode(' > ', $parts);
    }
}
