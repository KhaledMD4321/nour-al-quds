<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookupValue extends Model
{
    protected $fillable = [
        'lookup_type_id',
        'code',
        'label',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function lookupType(): BelongsTo
    {
        return $this->belongsTo(LookupType::class);
    }

    // ─── Enforce single default per type ───────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (LookupValue $value) {
            if ($value->is_default) {
                // Clear is_default on sibling values before saving this one
                static::where('lookup_type_id', $value->lookup_type_id)
                      ->where('id', '!=', $value->id ?? 0)
                      ->update(['is_default' => false]);
            }
        });
    }
}
