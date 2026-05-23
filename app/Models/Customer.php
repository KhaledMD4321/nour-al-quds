<?php

namespace App\Models;

use App\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, HasCustomFields;

    protected function getCustomFieldEntityType(): string { return 'customer'; }

    protected $fillable = [
        'code',
        'name',
        'phone',
        'phone_2',
        'address',
        'type',
        'tax_registration_number',
        'credit_limit',
        'default_discount_1',
        'default_discount_2',
        'default_discount_3',
        'business_unit_id',
        'opening_balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'credit_limit'       => 'decimal:2',
        'default_discount_1' => 'decimal:2',
        'default_discount_2' => 'decimal:2',
        'default_discount_3' => 'decimal:2',
        'opening_balance'    => 'decimal:2',
        'is_active'          => 'boolean',
    ];

    // ─── Auto-code generation ──────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $customer->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('code', 'LIKE', 'CUS-%')
            ->orderByRaw("CAST(SUBSTRING(code FROM 5) AS INTEGER) DESC")
            ->value('code');

        $next = $last ? (int) substr($last, 4) + 1 : 1;

        return 'CUS-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name',  'ILIKE', "%{$term}%")
              ->orWhere('phone', 'ILIKE', "%{$term}%")
              ->orWhere('code',  'ILIKE', "%{$term}%");
        });
    }

    public function scopeForBusinessUnit($query, ?int $businessUnitId)
    {
        if (! $businessUnitId) return $query;
        return $query->where('business_unit_id', $businessUnitId);
    }

    // ─── الخصم الثلاثي المتتابع ★ ──────────────────────────────────────────────

    /**
     * احسب السعر بعد تطبيق الخصومات الثلاث متتابعاً.
     *
     * مثال: 1000 ج.م. + 10% + 5% + 2%
     *   = 1000 × 0.90 × 0.95 × 0.98
     *   = 837.90
     *   ≠ 1000 × (1 - 0.17) = 830  ← غلط
     */
    public function calculatePrice(float $listPrice): float
    {
        $price = $listPrice
            * (1 - (float) $this->default_discount_1 / 100)
            * (1 - (float) $this->default_discount_2 / 100)
            * (1 - (float) $this->default_discount_3 / 100);

        return round($price, 4);
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    /**
     * نسبة الخصم الفعلية الإجمالية (للعرض فقط).
     */
    public function getEffectiveDiscountPercentAttribute(): float
    {
        $multiplier = (1 - (float) $this->default_discount_1 / 100)
                    * (1 - (float) $this->default_discount_2 / 100)
                    * (1 - (float) $this->default_discount_3 / 100);

        return round((1 - $multiplier) * 100, 2);
    }

    /** نوع العميل بالعربي من الـ Lookup */
    public function getTypeLabelAttribute(): ?string
    {
        return LookupType::getLabel('customer_type', $this->type);
    }

    /** هل العميل عنده ائتمان (بيع آجل)؟ */
    public function getHasCreditAttribute(): bool
    {
        return (float) $this->credit_limit > 0;
    }

    /**
     * الرصيد الحالي = الرصيد الافتتاحي + إجمالي الفواتير - إجمالي المدفوعات
     */
    public function getCurrentBalanceAttribute(): float
    {
        $totalInvoiced = $this->invoices()
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');

        $totalPaid = $this->receipts()->sum('amount');

        return round((float) $this->opening_balance + (float) $totalInvoiced - (float) $totalPaid, 2);
    }

    /**
     * الفواتير المفتوحة (غير مدفوعة بالكامل)
     */
    public function openInvoices()
    {
        return $this->invoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('invoice_date');
    }
}
