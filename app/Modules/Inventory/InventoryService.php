<?php

namespace App\Modules\Inventory;

use App\Models\FiscalPeriod;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    // ── تأكيد تحويل المخزون ──────────────────────────────────────────────────

    public function confirmTransfer(StockTransfer $transfer): void
    {
        if (! $transfer->isDraft()) {
            throw new Exception('التحويل مش في حالة مسودة — مش ممكن يتأكد تاني');
        }

        if ($transfer->from_warehouse_id === $transfer->to_warehouse_id) {
            throw new Exception('مخزن المصدر والوجهة لازم يكونوا مختلفين');
        }

        $this->checkFiscalPeriod($transfer->transfer_date);

        if ($transfer->items()->count() === 0) {
            throw new Exception('التحويل لازم يحتوي على بند واحد على الأقل قبل التأكيد');
        }

        DB::transaction(function () use ($transfer) {
            $transfer->items()->with('product')->each(function ($item) use ($transfer) {

                // ── نقص من مخزن المصدر ──
                $fromStock = Stock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $fromStock || (float) $fromStock->quantity < (float) $item->quantity) {
                    $productName = $item->product->name ?? "#{$item->product_id}";
                    throw new Exception("كمية {$productName} في مخزن المصدر غير كافية");
                }

                $fromBalanceAfter = (float) $fromStock->quantity - (float) $item->quantity;
                $unitCost         = (float) $fromStock->avg_cost;

                $this->decreaseStockRecord($fromStock, (float) $item->quantity, $fromBalanceAfter);

                StockMovement::create([
                    'warehouse_id'   => $transfer->from_warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'transfer_out',
                    'quantity'       => (float) $item->quantity,
                    'unit_cost'      => $unitCost,
                    'balance_after'  => $fromBalanceAfter,
                    'reference_type' => StockTransfer::class,
                    'reference_id'   => $transfer->id,
                    'notes'          => 'تحويل خارج — ' . $transfer->reference_number,
                    'created_by'     => $transfer->created_by,
                ]);

                // حفظ unit_cost في البند (avg_cost of source warehouse at time of transfer)
                $item->update(['unit_cost' => $unitCost]);

                // ── زيادة في مخزن الوجهة ──
                $toStock = Stock::where('warehouse_id', $transfer->to_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $oldQty      = $toStock ? (float) $toStock->quantity : 0;
                $oldCost     = $toStock ? (float) $toStock->avg_cost  : 0;
                $inQty       = (float) $item->quantity;
                $newAvgCost  = ($oldQty + $inQty) > 0
                    ? (($oldQty * $oldCost) + ($inQty * $unitCost)) / ($oldQty + $inQty)
                    : $unitCost;
                $toBalanceAfter = $oldQty + $inQty;

                $this->increaseStockRecord(
                    $transfer->to_warehouse_id,
                    $item->product_id,
                    $toStock,
                    $toBalanceAfter,
                    $newAvgCost
                );

                StockMovement::create([
                    'warehouse_id'   => $transfer->to_warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'transfer_in',
                    'quantity'       => $inQty,
                    'unit_cost'      => $unitCost,
                    'balance_after'  => $toBalanceAfter,
                    'reference_type' => StockTransfer::class,
                    'reference_id'   => $transfer->id,
                    'notes'          => 'تحويل داخل — ' . $transfer->reference_number,
                    'created_by'     => $transfer->created_by,
                ]);
            });

            $transfer->update([
                'status'       => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);
        });
    }

    // ── تأكيد تسوية المخزون ─────────────────────────────────────────────────

    public function confirmAdjustment(StockAdjustment $adjustment): void
    {
        if (! $adjustment->isDraft()) {
            throw new Exception('التسوية مش في حالة مسودة — مش ممكن تتأكد تاني');
        }

        $this->checkFiscalPeriod($adjustment->adjustment_date);

        if ($adjustment->items()->count() === 0) {
            throw new Exception('التسوية لازم تحتوي على بند واحد على الأقل قبل التأكيد');
        }

        DB::transaction(function () use ($adjustment) {
            $adjustment->items()->with('product')->each(function ($item) use ($adjustment) {

                $stock = Stock::where('warehouse_id', $adjustment->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $diff = (float) $item->difference;
                if ($diff === 0.0) return; // no change needed

                $oldQty      = $stock ? (float) $stock->quantity : 0;
                $unitCost    = $stock ? (float) $stock->avg_cost  : 0;
                $balanceAfter = $oldQty + $diff;

                if ($balanceAfter < 0) {
                    $productName = $item->product->name ?? "#{$item->product_id}";
                    throw new Exception("الكمية النهائية لـ {$productName} ستصبح سالبة — غير مسموح");
                }

                if ($diff > 0) {
                    // زيادة
                    $this->increaseStockRecord(
                        $adjustment->warehouse_id,
                        $item->product_id,
                        $stock,
                        $balanceAfter,
                        $unitCost   // keep same avg_cost for surplus
                    );
                    $movType = 'adjustment_plus';
                } else {
                    // نقص
                    $this->decreaseStockRecord($stock, abs($diff), $balanceAfter);
                    $movType = 'adjustment_minus';
                }

                StockMovement::create([
                    'warehouse_id'   => $adjustment->warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => $movType,
                    'quantity'       => abs($diff),
                    'unit_cost'      => $unitCost,
                    'balance_after'  => $balanceAfter,
                    'reference_type' => StockAdjustment::class,
                    'reference_id'   => $adjustment->id,
                    'notes'          => 'تسوية مخزون — ' . $adjustment->reference_number,
                    'created_by'     => $adjustment->created_by,
                ]);
            });

            $adjustment->update([
                'status'       => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);
        });
    }

    // ── الحصول على الكمية الحالية في المخزن (للـ expected_quantity) ──────────

    public function fillExpectedQuantity(int $warehouseId, int $productId): float
    {
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        return $stock ? (float) $stock->quantity : 0.0;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function increaseStockRecord(
        int $warehouseId,
        int $productId,
        ?Stock $stock,
        float $newQty,
        float $newAvgCost
    ): void {
        if ($stock) {
            $stock->update([
                'quantity'     => $newQty,
                'avg_cost'     => round($newAvgCost, 4),
                'last_updated' => now(),
            ]);
        } else {
            Stock::create([
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId,
                'quantity'     => $newQty,
                'avg_cost'     => round($newAvgCost, 4),
                'last_updated' => now(),
            ]);
        }
    }

    private function decreaseStockRecord(Stock $stock, float $decreaseQty, float $balanceAfter): void
    {
        $stock->update([
            'quantity'     => $balanceAfter,
            'last_updated' => now(),
        ]);
    }

    // ── فحص الفترة المالية ───────────────────────────────────────────────────

    private function checkFiscalPeriod($date): void
    {
        $period = FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($period && $period->is_locked) {
            throw new Exception('الفترة المالية مقفولة — لا يمكن تسجيل معاملات فيها');
        }
    }
}
