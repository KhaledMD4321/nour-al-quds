<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'name',
        'rate',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'rate'       => 'decimal:2',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ─── Static helpers ────────────────────────────────────────────────────────

    /**
     * Return the default active tax rate record, or null if none defined.
     */
    public static function getDefault(): ?self
    {
        return static::active()->default()->first();
    }

    /**
     * Return the default rate percentage as a float (e.g. 14.0),
     * or 0 when no default rate is configured.
     */
    public static function getRate(): float
    {
        return (float) (static::getDefault()?->rate ?? 0);
    }
}
