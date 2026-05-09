<?php

namespace App\Models;

use App\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes, HasCustomFields;

    protected function getCustomFieldEntityType(): string { return 'company'; }

    protected $fillable = [
        'name',
        'country',
        'phone',
        'representative',
        'notes',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function priceListVersions(): HasMany
    {
        return $this->hasMany(PriceListVersion::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * الإصدار النشط حالياً (آخر تاريخ سريان).
     */
    public function activePriceList(): ?PriceListVersion
    {
        return $this->priceListVersions()
                    ->where('status', 'active')
                    ->latest('effective_date')
                    ->first();
    }
}
