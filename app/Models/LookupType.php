<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LookupType extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function values(): HasMany
    {
        return $this->hasMany(LookupValue::class)->orderBy('sort_order');
    }

    public function activeValues(): HasMany
    {
        return $this->hasMany(LookupValue::class)
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    // ─── Static helpers — used across all Resources & Services ─────────────────

    /**
     * Active options for any lookup type, keyed by code.
     *
     * Usage: LookupType::getOptions('unit_of_measure')
     * Returns: ['piece' => 'قطعة', 'meter' => 'متر', ...]
     */
    public static function getOptions(string $typeCode): array
    {
        $type = static::where('code', $typeCode)->first();
        if (! $type) {
            return [];
        }

        return $type->activeValues()
                    ->pluck('label', 'code')
                    ->toArray();
    }

    /**
     * Default code value for a lookup type.
     *
     * Usage: LookupType::getDefault('unit_of_measure') → 'piece'
     */
    public static function getDefault(string $typeCode): ?string
    {
        $type = static::where('code', $typeCode)->first();
        if (! $type) {
            return null;
        }

        return $type->activeValues()
                    ->where('is_default', true)
                    ->first()
                    ?->code;
    }

    /**
     * Arabic label for a stored code value.
     *
     * Usage: LookupType::getLabel('unit_of_measure', 'piece') → 'قطعة'
     */
    public static function getLabel(string $typeCode, ?string $valueCode): ?string
    {
        if (! $valueCode) {
            return null;
        }

        return static::where('code', $typeCode)
                     ->first()
                     ?->values()
                     ->where('code', $valueCode)
                     ->first()
                     ?->label;
    }
}
