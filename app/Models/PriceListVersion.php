<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceListVersion extends Model
{
    protected $fillable = [
        'company_id',
        'version_number',
        'effective_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * جلب سعر صنف معين من هذا الإصدار.
     */
    public function getPriceFor(int $productId): ?float
    {
        $price = $this->items()
                      ->where('product_id', $productId)
                      ->value('price');

        return $price !== null ? (float) $price : null;
    }
}
