<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    protected $fillable = [
        'entity_type', 'field_key', 'field_label', 'field_type',
        'options', 'default_value', 'placeholder',
        'is_required', 'is_searchable', 'is_printable', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'options'       => 'array',
        'is_required'   => 'boolean',
        'is_searchable' => 'boolean',
        'is_printable'  => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────────

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType)
                     ->where('is_active', true)
                     ->orderBy('sort_order');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    public static function getEntityTypes(): array
    {
        return [
            'customer' => 'العملاء',
            'supplier' => 'الموردين',
            'product'  => 'الأصناف',
            'company'  => 'المصنّعين',
            'invoice'  => 'الفواتير',
        ];
    }

    public function getEntityLabelAttribute(): string
    {
        return self::getEntityTypes()[$this->entity_type] ?? $this->entity_type;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->field_type) {
            'text'     => 'نص',
            'number'   => 'رقم',
            'date'     => 'تاريخ',
            'select'   => 'قائمة اختيار',
            'toggle'   => 'تفعيل/تعطيل',
            'textarea' => 'نص طويل',
            default    => $this->field_type,
        };
    }
}
