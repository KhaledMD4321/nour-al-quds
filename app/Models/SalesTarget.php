<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    protected $fillable = [
        'business_unit_id',
        'year',
        'month',
        'target_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'target_amount' => 'decimal:2',
    ];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
