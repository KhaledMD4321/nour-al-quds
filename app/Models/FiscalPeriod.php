<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    // ─── Arabic month names ────────────────────────────────────────────────────

    const MONTHS = [
        1  => 'يناير',
        2  => 'فبراير',
        3  => 'مارس',
        4  => 'أبريل',
        5  => 'مايو',
        6  => 'يونيو',
        7  => 'يوليو',
        8  => 'أغسطس',
        9  => 'سبتمبر',
        10 => 'أكتوبر',
        11 => 'نوفمبر',
        12 => 'ديسمبر',
    ];

    // ─── Fillable / Casts ──────────────────────────────────────────────────────

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'locked_at'  => 'datetime',
        'is_locked'  => 'boolean',
        'year'       => 'integer',
        'month'      => 'integer',
    ];

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /** Filter to the period that contains the given date. */
    public function scopeForDate($query, Carbon $date)
    {
        return $query
            ->where('year', $date->year)
            ->where('month', $date->month);
    }

    // ─── Instance methods ──────────────────────────────────────────────────────

    /**
     * Lock this period, recording who locked it and when.
     */
    public function lock(User $user): void
    {
        $this->update([
            'is_locked' => true,
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);
    }

    /**
     * Re-open a locked period.
     */
    public function unlock(): void
    {
        $this->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);
    }

    /**
     * True if today falls within this period's start/end dates.
     */
    public function isCurrentPeriod(): bool
    {
        $today = now()->toDateString();
        return $today >= $this->start_date->toDateString()
            && $today <= $this->end_date->toDateString();
    }

    /**
     * Human-readable label, e.g. "أبريل 2026".
     */
    public function getDisplayName(): string
    {
        return (self::MONTHS[$this->month] ?? $this->month) . ' ' . $this->year;
    }

    // ─── Static helpers ────────────────────────────────────────────────────────

    /**
     * Return the open FiscalPeriod that covers $date, or null if none exists
     * (or if the matching period is locked).
     */
    public static function getActivePeriodForDate(Carbon $date): ?self
    {
        return static::forDate($date)->open()->first();
    }

    /**
     * Returns true when the period covering $date is locked,
     * OR when no period record exists for that date.
     */
    public static function isDateLocked(Carbon $date): bool
    {
        $period = static::forDate($date)->first();

        if ($period === null) {
            return true;   // no period configured → treat as locked
        }

        return $period->is_locked;
    }
}
