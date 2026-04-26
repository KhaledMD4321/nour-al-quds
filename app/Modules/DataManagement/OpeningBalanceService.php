<?php

namespace App\Modules\DataManagement;

use App\Models\Customer;
use App\Models\OpeningBalance;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class OpeningBalanceService
{
    /**
     * تسجيل رصيد افتتاحي لعميل
     * مدين = العميل عليه فلوس لينا
     */
    public function setCustomerBalance(int $customerId, float $amount, string $date, ?int $createdBy = null): OpeningBalance
    {
        return DB::transaction(function () use ($customerId, $amount, $date, $createdBy) {
            Customer::where('id', $customerId)->update(['opening_balance' => $amount]);

            OpeningBalance::where('type', 'customer')
                          ->where('reference_id', $customerId)
                          ->delete();

            return OpeningBalance::create([
                'type'         => 'customer',
                'reference_id' => $customerId,
                'debit'        => $amount,
                'credit'       => 0,
                'balance_date' => $date,
                'created_by'   => $createdBy,
            ]);
        });
    }

    /**
     * تسجيل رصيد افتتاحي لمورد
     * دائن = إحنا مديونين للمورد
     */
    public function setSupplierBalance(int $supplierId, float $amount, string $date, ?int $createdBy = null): OpeningBalance
    {
        return DB::transaction(function () use ($supplierId, $amount, $date, $createdBy) {
            Supplier::where('id', $supplierId)->update(['opening_balance' => $amount]);

            OpeningBalance::where('type', 'supplier')
                          ->where('reference_id', $supplierId)
                          ->delete();

            return OpeningBalance::create([
                'type'         => 'supplier',
                'reference_id' => $supplierId,
                'debit'        => 0,
                'credit'       => $amount,
                'balance_date' => $date,
                'created_by'   => $createdBy,
            ]);
        });
    }

    /**
     * ★ تسجيل مخزون افتتاحي — صنف واحد في مخزن واحد
     * بيعمل: stock row + stock_movement من نوع opening
     */
    public function setStockBalance(
        int $warehouseId,
        int $productId,
        float $quantity,
        float $unitCost,
        string $date,
        ?int $createdBy = null
    ): OpeningBalance {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $unitCost, $date, $createdBy) {

            // حذف أي رصيد افتتاحي سابق لنفس الصنف في نفس المخزن
            $existing = OpeningBalance::where('type', 'stock')
                                      ->where('reference_id', $warehouseId)
                                      ->where('product_id', $productId)
                                      ->first();

            if ($existing) {
                Stock::where('warehouse_id', $warehouseId)
                     ->where('product_id', $productId)
                     ->update(['quantity' => 0, 'avg_cost' => 0]);

                StockMovement::where('reference_type', OpeningBalance::class)
                             ->where('reference_id', $existing->id)
                             ->delete();

                $existing->delete();
            }

            // إضافة / تحديث في جدول stock
            Stock::updateOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['quantity' => $quantity, 'avg_cost' => $unitCost]
            );

            $totalValue = round($quantity * $unitCost, 2);

            // تسجيل في opening_balances
            $ob = OpeningBalance::create([
                'type'         => 'stock',
                'reference_id' => $warehouseId,
                'product_id'   => $productId,
                'debit'        => $totalValue,
                'credit'       => 0,
                'quantity'     => $quantity,
                'unit_cost'    => $unitCost,
                'balance_date' => $date,
                'created_by'   => $createdBy,
            ]);

            // تسجيل حركة مخزون (opening)
            StockMovement::create([
                'warehouse_id'   => $warehouseId,
                'product_id'     => $productId,
                'type'           => 'opening',
                'quantity'       => $quantity,
                'unit_cost'      => $unitCost,
                'balance_after'  => $quantity,
                'reference_type' => OpeningBalance::class,
                'reference_id'   => $ob->id,
                'notes'          => 'رصيد افتتاحي',
                'created_by'     => $createdBy,
            ]);

            return $ob;
        });
    }

    /**
     * ★ رفع مخزون افتتاحي من Excel
     *
     * تنسيق Excel:
     * A: كود الصنف أو اسمه (مطلوب)
     * B: الكمية (مطلوب)
     * C: تكلفة الوحدة (مطلوب)
     */
    public function importStockFromExcel(
        \Illuminate\Http\UploadedFile $file,
        int $warehouseId,
        string $date,
        ?int $createdBy = null
    ): array {
        $allRows = Excel::toArray([], $file)[0] ?? [];
        $rows = collect($allRows)
            ->slice(1)
            ->filter(fn ($row) => !empty(array_filter($row, fn ($cell) => $cell !== null && $cell !== '')))
            ->values();

        $results = ['added' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, $warehouseId, $date, $createdBy, &$results) {
            foreach ($rows as $index => $row) {
                $rowNumber  = $index + 2;
                $codeOrName = trim((string) ($row[0] ?? ''));
                $quantity   = (float) ($row[1] ?? 0);
                $unitCost   = (float) ($row[2] ?? 0);

                if (empty($codeOrName) || $quantity <= 0 || $unitCost <= 0) {
                    $results['errors'][] = "الصف {$rowNumber}: بيانات ناقصة أو غير صالحة";
                    $results['skipped']++;
                    continue;
                }

                $product = Product::where('code', $codeOrName)
                                  ->orWhere('name', $codeOrName)
                                  ->first();

                if (!$product) {
                    $results['errors'][] = "الصف {$rowNumber}: الصنف '{$codeOrName}' مش موجود";
                    $results['skipped']++;
                    continue;
                }

                $this->setStockBalance($warehouseId, $product->id, $quantity, $unitCost, $date, $createdBy);
                $results['added']++;
            }
        });

        return $results;
    }
}
