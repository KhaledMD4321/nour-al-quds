<?php

namespace App\Models;

use App\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes, HasCustomFields;

    protected function getCustomFieldEntityType(): string { return 'supplier'; }

    protected $fillable = [
        'code',
        'name',
        'phone',
        'phone_2',
        'address',
        'company_id',
        'tax_registration_number',
        'opening_balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    // ======= العلاقات =======

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    // ======= Scopes =======

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
              ->orWhere('phone', 'ILIKE', "%{$term}%")
              ->orWhere('code', 'ILIKE', "%{$term}%");
        });
    }

    // ======= Auto-generate Code =======

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            if (empty($supplier->code)) {
                $supplier->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()
                      ->where('code', 'LIKE', 'SUP-%')
                      ->orderByRaw("CAST(SUBSTRING(code FROM 5) AS INTEGER) DESC")
                      ->value('code');

        $nextNumber = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'SUP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    // ─── Financial Helpers ────────────────────────────────────────────────────

    /**
     * الرصيد الحالي = رصيد افتتاحي + فواتير مشتريات - مرتجعات - مدفوعات
     * موجب = مستحق للمورد، سالب = دائن لنا
     */
    public function getCurrentBalanceAttribute(): float
    {
        $purchased = (float) $this->purchaseInvoices()
            ->whereIn('status', ['confirmed', 'paid'])
            ->sum('total_amount');

        $returned = (float) PurchaseReturn::where('supplier_id', $this->id)
            ->where('status', 'confirmed')
            ->sum('total_amount');

        $paid = (float) $this->payments()->sum('amount');

        return round((float) $this->opening_balance + $purchased - $returned - $paid, 2);
    }

    /**
     * فواتير مشتريات غير مدفوعة بالكامل
     */
    public function openPurchaseInvoices()
    {
        return $this->purchaseInvoices()
            ->whereIn('status', ['confirmed'])
            ->whereRaw('total_amount > paid_amount')
            ->orderBy('invoice_date');
    }
}
