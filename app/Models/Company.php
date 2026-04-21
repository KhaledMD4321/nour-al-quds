<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

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
}
