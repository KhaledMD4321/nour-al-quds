<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Root categories ────────────────────────────────────────────────────
        $sanitaryWare = Category::create(['name' => 'أدوات صحية',      'sort_order' => 1]);
        $plumbing = Category::create(['name' => 'سباكة',            'sort_order' => 2]);
        $mixers = Category::create(['name' => 'خلاطات',           'sort_order' => 3]);
        $waterHeaters = Category::create(['name' => 'سخانات',           'sort_order' => 4]);
        $accessories = Category::create(['name' => 'إكسسوارات حمامات', 'sort_order' => 5]);

        // ── أدوات صحية — children ─────────────────────────────────────────────
        Category::create(['name' => 'أحواض',               'parent_id' => $sanitaryWare->id, 'sort_order' => 1]);
        Category::create(['name' => 'قواعد',               'parent_id' => $sanitaryWare->id, 'sort_order' => 2]);
        Category::create(['name' => 'شطافات',              'parent_id' => $sanitaryWare->id, 'sort_order' => 3]);
        Category::create(['name' => 'بانيوهات',            'parent_id' => $sanitaryWare->id, 'sort_order' => 4]);
        Category::create(['name' => 'بيديهات',             'parent_id' => $sanitaryWare->id, 'sort_order' => 5]);
        Category::create(['name' => 'أطقم حمامات كاملة',  'parent_id' => $sanitaryWare->id, 'sort_order' => 6]);

        // ── سباكة — children ──────────────────────────────────────────────────
        Category::create(['name' => 'مواسير PVC',          'parent_id' => $plumbing->id, 'sort_order' => 1]);
        Category::create(['name' => 'مواسير PPR',          'parent_id' => $plumbing->id, 'sort_order' => 2]);
        Category::create(['name' => 'كوعات وتوصيلات',     'parent_id' => $plumbing->id, 'sort_order' => 3]);
        Category::create(['name' => 'محابس',               'parent_id' => $plumbing->id, 'sort_order' => 4]);
        Category::create(['name' => 'مصافي وبالوعات',     'parent_id' => $plumbing->id, 'sort_order' => 5]);

        // ── خلاطات — children ─────────────────────────────────────────────────
        Category::create(['name' => 'خلاطات حوض',          'parent_id' => $mixers->id, 'sort_order' => 1]);
        Category::create(['name' => 'خلاطات مطبخ',         'parent_id' => $mixers->id, 'sort_order' => 2]);
        Category::create(['name' => 'خلاطات دش',           'parent_id' => $mixers->id, 'sort_order' => 3]);
        Category::create(['name' => 'وحدات دش كاملة',      'parent_id' => $mixers->id, 'sort_order' => 4]);

        // ── سخانات — children ─────────────────────────────────────────────────
        Category::create(['name' => 'سخانات غاز',          'parent_id' => $waterHeaters->id, 'sort_order' => 1]);
        Category::create(['name' => 'سخانات كهرباء',       'parent_id' => $waterHeaters->id, 'sort_order' => 2]);

        // ── إكسسوارات حمامات — children ──────────────────────────────────────
        Category::create(['name' => 'علّاقات فوط',         'parent_id' => $accessories->id, 'sort_order' => 1]);
        Category::create(['name' => 'حاملات صابون',        'parent_id' => $accessories->id, 'sort_order' => 2]);
        Category::create(['name' => 'مرايات حمامات',       'parent_id' => $accessories->id, 'sort_order' => 3]);
    }
}
