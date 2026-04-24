<?php

namespace App\Modules\Catalog;

use App\Models\PriceListItem;
use App\Models\PriceListVersion;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PriceListService
{
    /**
     * إنشاء إصدار جديد يدوياً (بدون Excel).
     *
     * @param  array<array{product_id: int, price: float}>  $items
     */
    public function createNewVersion(
        int $companyId,
        array $items,
        ?string $notes = null,
        ?int $createdBy = null,
    ): PriceListVersion {
        return DB::transaction(function () use ($companyId, $items, $notes, $createdBy) {
            // 1. أرشفة الإصدار النشط الحالي
            $this->archiveActiveVersions($companyId);

            // 2. حساب رقم الإصدار الجديد
            $next = (PriceListVersion::where('company_id', $companyId)->max('version_number') ?? 0) + 1;

            // 3. إنشاء الإصدار الجديد
            $version = PriceListVersion::create([
                'company_id'     => $companyId,
                'version_number' => $next,
                'effective_date' => now()->toDateString(),
                'status'         => 'active',
                'notes'          => $notes,
                'created_by'     => $createdBy,
            ]);

            // 4. إضافة البنود
            foreach ($items as $item) {
                PriceListItem::create([
                    'version_id' => $version->id,
                    'product_id' => $item['product_id'],
                    'price'      => $item['price'],
                ]);
            }

            return $version->load('items.product');
        });
    }

    // ─── Excel Import ──────────────────────────────────────────────────────────

    /**
     * ★ المرحلة الأولى: قراءة الملف وعرض ملخص المعاينة — بدون حفظ.
     *
     * تنسيق Excel المتوقع (أعمدة):
     *   A: كود الصنف   (اختياري)
     *   B: اسم الصنف   (مطلوب)
     *   C: السعر        (مطلوب، رقم موجب)
     *   D: وحدة القياس  (اختياري — default: piece)
     *
     * أول صف = headers (يُتجاهل تلقائياً).
     *
     * @return array{
     *   new_products: list<array{row:int,code:string,name:string,price:float,unit:string}>,
     *   existing_products: list<array{row:int,product_id:int,code:string,name:string,new_price:float,old_price:float|null}>,
     *   invalid_rows: list<array{row:int,data:array,error:string}>,
     *   summary: array{total_rows:int,new_products:int,existing:int,invalid:int}
     * }
     */
    public function previewImport(UploadedFile $file, int $companyId): array
    {
        $rows = $this->readExcelFile($file);

        $result = [
            'new_products'      => [],
            'existing_products' => [],
            'invalid_rows'      => [],
            'summary'           => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: صف headers + بداية array من 0

            $code  = trim((string) ($row[0] ?? ''));
            $name  = trim((string) ($row[1] ?? ''));
            $price = $this->parsePrice($row[2] ?? '');
            $unit  = trim((string) ($row[3] ?? 'piece')) ?: 'piece';

            if (empty($name)) {
                $result['invalid_rows'][] = [
                    'row'   => $rowNumber,
                    'data'  => $row,
                    'error' => "الصف {$rowNumber}: اسم الصنف مطلوب",
                ];
                continue;
            }

            if ($price === null || $price <= 0) {
                $result['invalid_rows'][] = [
                    'row'   => $rowNumber,
                    'data'  => $row,
                    'error' => "الصف {$rowNumber}: السعر غير صالح",
                ];
                continue;
            }

            // بحث بالكود أولاً، ثم بالاسم داخل نفس المصنّع
            $product = null;
            if (!empty($code)) {
                $product = Product::where('code', $code)->first();
            }
            if (!$product) {
                $product = Product::where('name', $name)
                                  ->where('company_id', $companyId)
                                  ->first();
            }

            if ($product) {
                $result['existing_products'][] = [
                    'row'        => $rowNumber,
                    'product_id' => $product->id,
                    'code'       => $product->code,
                    'name'       => $product->name,
                    'new_price'  => $price,
                    'old_price'  => $product->getCurrentPrice(),
                ];
            } else {
                $result['new_products'][] = [
                    'row'   => $rowNumber,
                    'code'  => $code,
                    'name'  => $name,
                    'price' => $price,
                    'unit'  => $unit,
                ];
            }
        }

        $result['summary'] = [
            'total_rows'   => count($rows),
            'new_products' => count($result['new_products']),
            'existing'     => count($result['existing_products']),
            'invalid'      => count($result['invalid_rows']),
        ];

        return $result;
    }

    /**
     * ★ المرحلة الثانية: تأكيد الاستيراد — يحفظ الأصناف والأسعار فعلياً.
     *
     * @return array{version: PriceListVersion, new_products: int, updated: int, skipped: int, message: string}
     */
    public function confirmImport(UploadedFile $file, int $companyId, ?int $createdBy = null): array
    {
        $preview = $this->previewImport($file, $companyId);

        return DB::transaction(function () use ($preview, $companyId, $createdBy) {
            // 1. أرشفة الإصدارات النشطة
            $this->archiveActiveVersions($companyId);

            // 2. رقم الإصدار الجديد
            $next = (PriceListVersion::where('company_id', $companyId)->max('version_number') ?? 0) + 1;

            // 3. إنشاء الإصدار
            $version = PriceListVersion::create([
                'company_id'     => $companyId,
                'version_number' => $next,
                'effective_date' => now()->toDateString(),
                'status'         => 'active',
                'notes'          => 'تم الاستيراد من Excel',
                'created_by'     => $createdBy,
            ]);

            $newCount     = 0;
            $updatedCount = 0;

            // 4. أصناف جديدة — تتضاف للـ products أولاً
            foreach ($preview['new_products'] as $item) {
                $product = Product::create([
                    'name'            => $item['name'],
                    'company_id'      => $companyId,
                    'unit_of_measure' => $item['unit'],
                    // الكود يتولّد تلقائياً من Product::booted()
                ]);

                PriceListItem::create([
                    'version_id' => $version->id,
                    'product_id' => $product->id,
                    'price'      => $item['price'],
                ]);

                $newCount++;
            }

            // 5. أصناف موجودة — سجّل أسعارها في الإصدار الجديد
            foreach ($preview['existing_products'] as $item) {
                PriceListItem::create([
                    'version_id' => $version->id,
                    'product_id' => $item['product_id'],
                    'price'      => $item['new_price'],
                ]);

                $updatedCount++;
            }

            return [
                'version'      => $version,
                'new_products' => $newCount,
                'updated'      => $updatedCount,
                'skipped'      => count($preview['invalid_rows']),
                'message'      => "تم إنشاء الإصدار رقم {$version->version_number} — {$newCount} صنف جديد، {$updatedCount} سعر محدّث",
            ];
        });
    }

    // ─── Queries ───────────────────────────────────────────────────────────────

    /**
     * جلب الإصدار النشط لمصنّع معين.
     */
    public function getActiveVersion(int $companyId): ?PriceListVersion
    {
        return PriceListVersion::where('company_id', $companyId)
                               ->where('status', 'active')
                               ->latest('effective_date')
                               ->first();
    }

    /**
     * جلب سعر صنف من اللستة النشطة لمصنّعه.
     */
    public function getPrice(int $productId, int $companyId): ?float
    {
        $version = $this->getActiveVersion($companyId);

        return $version?->getPriceFor($productId);
    }

    // ─── Mutations ─────────────────────────────────────────────────────────────

    /**
     * أرشفة كل الإصدارات النشطة لمصنّع.
     */
    public function archiveActiveVersions(int $companyId): void
    {
        PriceListVersion::where('company_id', $companyId)
                        ->where('status', 'active')
                        ->update(['status' => 'archived']);
    }

    /**
     * أرشفة إصدار واحد بالـ ID.
     */
    public function archiveVersion(int $versionId): void
    {
        PriceListVersion::where('id', $versionId)
                        ->update(['status' => 'archived']);
    }

    /**
     * تحديث كل الأسعار في إصدار بنسبة مئوية.
     * مثال: updateByPercentage($id, +10) → زيادة 10%
     * مثال: updateByPercentage($id, -5)  → خصم  5%
     *
     * @return int عدد الصفوف المحدّثة
     */
    public function updateByPercentage(int $versionId, float $percentage): int
    {
        $multiplier = 1 + ($percentage / 100);

        return PriceListItem::where('version_id', $versionId)
                            ->update([
                                'price' => DB::raw("ROUND(price * {$multiplier}, 4)"),
                            ]);
    }

    // ─── Private helpers ───────────────────────────────────────────────────────

    /**
     * قراءة ملف Excel وإرجاع الصفوف (بدون أول صف headers).
     */
    private function readExcelFile(UploadedFile $file): array
    {
        $allRows = Excel::toArray([], $file)[0] ?? [];

        return collect($allRows)
            ->slice(1)                         // تجاهل أول صف
            ->filter(function ($row) {
                // تجاهل الصفوف الفارغة تماماً
                return !empty(array_filter(
                    $row,
                    fn ($cell) => $cell !== null && $cell !== '',
                ));
            })
            ->values()
            ->toArray();
    }

    /**
     * تحويل قيمة خلية Excel إلى سعر float أو null لو غير صالح.
     */
    private function parsePrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // شيل أي حروف مش أرقام (عدا النقطة والفاصلة)
        $cleaned = preg_replace('/[^\d.,]/', '', (string) $value);
        // حوّل الفاصلة لنقطة
        $cleaned = str_replace(',', '.', $cleaned);
        // لو فيه أكتر من نقطة، احتفظ بالأخيرة بس
        if (substr_count($cleaned, '.') > 1) {
            $parts   = explode('.', $cleaned);
            $last    = array_pop($parts);
            $cleaned = implode('', $parts) . '.' . $last;
        }

        $price = (float) $cleaned;

        return $price > 0 ? $price : null;
    }
}
