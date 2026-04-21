<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'address',
        'phone',
        'tax_number',
        'invoice_header',
        'invoice_footer',
        'default_currency',
    ];

    /**
     * Returns the single company settings row, creating it if it doesn't exist.
     */
    public static function getInstance(): static
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'name'             => 'شركة نور القدس للأدوات الصحية والسباكة',
                'default_currency' => 'EGP',
            ]
        );
    }
}
