<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'company_id',
        'category_id',
        'unit_of_measure',
        'list_price',
        'image',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'list_price' => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    // ─── Auto-code generation ──────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->code)) {
                $product->code = static::generateCode();
            }
        });
    }

    private static function generateCode(): string
    {
        $last = static::withTrashed()
            ->whereRaw("code ~ '^PRD-[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(code FROM 5) AS INTEGER) DESC")
            ->value('code');

        $next = $last ? (int) substr($last, 4) + 1 : 1;

        return 'PRD-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Partial-name search using PostgreSQL ILIKE (case-insensitive).
     * Usage: Product::search('صنبور')->get()
     */
    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where('name', 'ILIKE', "%{$term}%");
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    /**
     * "PRD-00001 — صنبور حمام كروم"
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }

    /**
     * Human-readable unit label from the lookup table.
     * Returns the raw code as fallback if lookup not found.
     */
    public function getUnitLabelAttribute(): string
    {
        return LookupType::getLabel('unit_of_measure', $this->unit_of_measure)
            ?? $this->unit_of_measure;
    }

    /**
     * سعر الصنف من اللستة النشطة للمصنّع بتاعه.
     * بترجع null لو مفيش مصنّع أو مفيش لستة نشطة.
     */
    public function getCurrentPrice(): ?float
    {
        if (!$this->company_id) {
            return null;
        }

        $activeVersion = PriceListVersion::where('company_id', $this->company_id)
                                         ->where('status', 'active')
                                         ->latest('effective_date')
                                         ->first();

        if (!$activeVersion) {
            return null;
        }

        return $activeVersion->getPriceFor($this->id);
    }
}
