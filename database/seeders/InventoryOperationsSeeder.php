<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\Inventory\InventoryService;
use Illuminate\Database\Seeder;

class InventoryOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $warehouseFrom = Warehouse::find(1);  // مخزن المعرض
        $warehouseTo = Warehouse::find(2);  // مخزن التوزيع الرئيسي
        $bu = BusinessUnit::first();
        $service = app(InventoryService::class);

        // ── تحويل 1: من مخزن المعرض إلى مخزن التوزيع ──────────────────────────
        $transfer = StockTransfer::create([
            'reference_number' => StockTransfer::generateReference(),
            'from_warehouse_id' => $warehouseFrom->id,
            'to_warehouse_id' => $warehouseTo->id,
            'business_unit_id' => $bu->id,
            'transfer_date' => '2026-02-01',
            'status' => 'draft',
            'notes' => 'تحويل تجريبي من المعرض للتوزيع',
            'created_by' => $admin->id,
        ]);

        // منتج 3: خلاط حوض حمام إيديال (متاح qty=35)
        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => 3,
            'quantity' => 5,
            'unit_cost' => 0, // يُحدَّث عند التأكيد
        ]);

        // منتج 4: خلاط مطبخ جروهي (متاح qty=8)
        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => 4,
            'quantity' => 3,
            'unit_cost' => 0,
        ]);

        // تأكيد التحويل
        $service->confirmTransfer($transfer);

        // ── تسوية 1: تسوية مخزون في مخزن المعرض ────────────────────────────────
        $adjustment = StockAdjustment::create([
            'reference_number' => StockAdjustment::generateReference(),
            'warehouse_id' => $warehouseFrom->id,
            'business_unit_id' => $bu->id,
            'adjustment_date' => '2026-02-15',
            'status' => 'draft',
            'reason' => 'جرد دوري',
            'notes' => 'تسوية تجريبية بعد الجرد',
            'created_by' => $admin->id,
        ]);

        // منتج 1: حوض حمام — زيادة (expected=10, actual=12, diff=+2)
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => 1,
            'expected_quantity' => 10,
            'actual_quantity' => 12,
            'difference' => 2,
            'reason' => 'surplus',
        ]);

        // منتج 2: مرحاض — نقص (expected=5, actual=4, diff=-1)
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => 2,
            'expected_quantity' => 5,
            'actual_quantity' => 4,
            'difference' => -1,
            'reason' => 'shortage',
        ]);

        // منتج 5: دش جروهي — مطابق (expected=12, actual=12, diff=0)
        // Note: after transfer above, product 5 is unchanged in مخزن المعرض
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => 5,
            'expected_quantity' => 12,
            'actual_quantity' => 12,
            'difference' => 0,
            'reason' => null,
        ]);

        // تأكيد التسوية
        $service->confirmAdjustment($adjustment);

        $this->command->info('✅ تم إنشاء عمليات المخزون التجريبية:');
        $this->command->info("   {$transfer->reference_number} — تحويل 5+3 وحدات من المعرض للتوزيع");
        $this->command->info("   {$adjustment->reference_number} — تسوية جرد دوري (زيادة/نقص/مطابق)");
    }
}
