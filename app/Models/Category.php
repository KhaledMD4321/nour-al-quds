<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Guard against deleting in-use categories ──────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            if ($category->children()->count() > 0) {
                throw new \Exception('مش ممكن تحذف التصنيف ده لأن فيه تصنيفات فرعية');
            }
            if ($category->products()->count() > 0) {
                throw new \Exception('مش ممكن تحذف التصنيف ده لأن فيه أصناف مرتبطة بيه');
            }
        });
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('sort_order');
    }

    /** Recursive children — for full tree eager loading. */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Full breadcrumb path: "أدوات صحية > أحواض > أحواض معلّقة"
     */
    public function getFullPathAttribute(): string
    {
        $path   = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode(' > ');
    }
}
