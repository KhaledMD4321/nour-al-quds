<?php

namespace App\Modules\Sales;

use App\Models\FiscalPeriod;
use App\Models\Product;
use App\Models\QuickSale;
use App\Models\QuickSaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\SystemSetting;
use App\Modules\Finance\TreasuryService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickSaleService
{
    public function __construct(
        protected TreasuryService $treasuryService,
    ) {}

    /**
     * تنفيذ بيع سريع كامل في transaction واحدة
     *
     * $data = [
     *   'business_unit_id' => 1,
     *   'warehouse_id'     => 1,
     *   'treasury_id'      => 1,  // إلزامي
     *   'customer_name'    => 'أحمد محمد',  // nullable
     *   'notes'            => null,
     *   'items' => [
     *     ['product_id' => 1, 'quantity' => 2, 'unit_price' => 150.00],
     *     ['product_id' => 3, 'quantity' => 1, 'unit_price' => 80.00],
     *   ]
     * ]
     */
    public function process(array $data): QuickSale
    {
        $this->checkFiscalPeriod(now()->toDateString());

        if (empty($data['items'])) {
            throw new Exception('لازم تضيف صنف واحد على الأقل');
        }

        if (empty($data['treasury_id'])) {
            throw new Exception('لازم تختار خزينة للبيع السريع');
        }

        // تحقق من صحة بنود البيع
        foreach ($data['items'] as $i => $item) {
            $qty   = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $row   = $i + 1;
            if ($qty <= 0) {
                throw new Exception("الصف {$row}: الكمية لازم تكون أكبر من صفر");
            }
            if ($price <= 0) {
                throw new Exception("الصف {$row}: السعر لازم يكون أكبر من صفر");
            }
        }

        return DB::transaction(function () use ($data) {

            // 1. حساب الإجمالي
            $totalAmount = collect($data['items'])->sum(
                fn ($item) => (float) $item['quantity'] * (float) $item['unit_price']
            );

            // 2. إنشاء رأس الإيصال
            $sale = QuickSale::create([
                'reference_number' => QuickSale::generateReference(),
                'business_unit_id' => $data['business_unit_id'],
                'warehouse_id'     => $data['warehouse_id'],
                'treasury_id'      => $data['treasury_id'],
                'total_amount'     => $totalAmount,
                'payment_method'   => $data['payment_method'] ?? 'cash',
                'customer_name'    => $data['customer_name'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => Auth::id(),
            ]);

            // 3. البنود + خصم المخزون
            foreach ($data['items'] as $itemData) {
                $qty   = (float) $itemData['quantity'];
                $price = (float) $itemData['unit_price'];

                // تحقق من الرصيد + lockForUpdate
                $stock = Stock::where('warehouse_id', $data['warehouse_id'])
                    ->where('product_id', $itemData['product_id'])
                    ->lockForUpdate()
                    ->first();

                $available = $stock ? (float) $stock->quantity : 0;

                if ($available < $qty) {
                    $productName = Product::find($itemData['product_id'])?->name ?? '#' . $itemData['product_id'];
                    if (! SystemSetting::get('business_rules.allow_negative_stock', false)) {
                        throw new Exception(
                            "الكمية المطلوبة للصنف \"{$productName}\" ({$qty}) أكبر من المتاح ({$available})"
                        );
                    }
                    // allow_negative_stock=true — نكمل بدون رصيد
                }

                // إنشاء البند
                QuickSaleItem::create([
                    'quick_sale_id' => $sale->id,
                    'product_id'    => $itemData['product_id'],
                    'quantity'      => $qty,
                    'unit_price'    => $price,
                    'total'         => round($qty * $price, 2),
                ]);

                // خصم المخزون
                $balanceAfter = $available - $qty;
                $stock->update([
                    'quantity'     => $balanceAfter,
                    'last_updated' => now(),
                ]);

                // تسجيل حركة المخزون — سجل أبدي
                StockMovement::create([
                    'warehouse_id'   => $data['warehouse_id'],
                    'product_id'     => $itemData['product_id'],
                    'type'           => 'out',
                    'quantity'       => $qty,
                    'unit_cost'      => $stock ? (float) $stock->avg_cost : 0,
                    'balance_after'  => $balanceAfter,
                    'reference_type' => QuickSale::class,
                    'reference_id'   => $sale->id,
                    'notes'          => 'بيع سريع — ' . $sale->reference_number,
                    'created_by'     => Auth::id(),
                ]);
            }

            // 4. إضافة الإيراد للخزينة — سجل حركة مقبوضات
            $this->treasuryService->addFunds(
                treasuryId:    (int) $data['treasury_id'],
                amount:        $totalAmount,
                description:   'بيع سريع — ' . $sale->reference_number,
                referenceType: QuickSale::class,
                referenceId:   $sale->id,
                createdBy:     Auth::id(),
            );

            return $sale;
        });
    }

    // ── فحص الفترة المالية ───────────────────────────────────────────────────

    private function checkFiscalPeriod(string $date): void
    {
        $period = FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($period && $period->is_locked) {
            throw new Exception('الفترة المالية مقفولة — لا يمكن تسجيل معاملات فيها');
        }
    }
}
