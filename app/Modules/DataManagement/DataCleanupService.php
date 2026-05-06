<?php

namespace App\Modules\DataManagement;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataCleanupService
{
    // ══════════════════════════════════════════════════════════════════
    // 1. اكتشاف التكرارات
    // ══════════════════════════════════════════════════════════════════

    /**
     * عملاء بنفس الاسم أو التليفون
     */
    public function findDuplicateCustomers(): Collection
    {
        // تكرار بالاسم
        $byName = Customer::select('name')
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        // تكرار بالتليفون
        $byPhone = Customer::select('phone')
            ->whereNull('deleted_at')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        $groups = collect();

        foreach ($byName as $name) {
            $customers = Customer::where('name', $name)->whereNull('deleted_at')->get();
            $groups->push(['type' => 'name', 'key' => $name, 'customers' => $customers]);
        }

        foreach ($byPhone as $phone) {
            $customers = Customer::where('phone', $phone)->whereNull('deleted_at')->get();
            $groups->push(['type' => 'phone', 'key' => $phone, 'customers' => $customers]);
        }

        return $groups;
    }

    /**
     * منتجات بنفس الاسم
     */
    public function findDuplicateProducts(): Collection
    {
        $byName = Product::select('name')
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        return $byName->map(fn ($name) => [
            'name'     => $name,
            'products' => Product::where('name', $name)->whereNull('deleted_at')->get(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // 2. دمج السجلات
    // ══════════════════════════════════════════════════════════════════

    /**
     * دمج عميلين — نقل كل معاملات المكرر للأصلي ثم أرشفة المكرر
     */
    public function mergeCustomers(int $keepId, int $mergeId): void
    {
        if ($keepId === $mergeId) {
            throw new \InvalidArgumentException('لا يمكن دمج عميل مع نفسه');
        }

        DB::transaction(function () use ($keepId, $mergeId) {
            // نقل الفواتير
            Invoice::where('customer_id', $mergeId)->update(['customer_id' => $keepId]);

            // نقل سندات القبض
            \App\Models\Receipt::where('customer_id', $mergeId)->update(['customer_id' => $keepId]);

            // نقل الشيكات الواردة
            \App\Models\Cheque::where('customer_id', $mergeId)->update(['customer_id' => $keepId]);

            // أرشفة المكرر
            Customer::find($mergeId)?->delete();

            Log::info('Customers merged', ['kept' => $keepId, 'merged' => $mergeId, 'user_id' => auth()->id()]);
        });
    }

    /**
     * دمج موردين
     */
    public function mergeSuppliers(int $keepId, int $mergeId): void
    {
        if ($keepId === $mergeId) {
            throw new \InvalidArgumentException('لا يمكن دمج مورد مع نفسه');
        }

        DB::transaction(function () use ($keepId, $mergeId) {
            \App\Models\PurchaseInvoice::where('supplier_id', $mergeId)->update(['supplier_id' => $keepId]);
            \App\Models\Payment::where('supplier_id', $mergeId)->update(['supplier_id' => $keepId]);
            \App\Models\Cheque::where('supplier_id', $mergeId)->update(['supplier_id' => $keepId]);

            Supplier::find($mergeId)?->delete();

            Log::info('Suppliers merged', ['kept' => $keepId, 'merged' => $mergeId, 'user_id' => auth()->id()]);
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // 3. أرشفة البيانات غير النشطة
    // ══════════════════════════════════════════════════════════════════

    /**
     * أرشفة منتجات بدون حركة من X شهر
     */
    public function archiveInactiveProducts(int $monthsThreshold = 12): int
    {
        $since = today()->subMonths($monthsThreshold);

        $activeIds = StockMovement::where('created_at', '>=', $since)
            ->distinct()
            ->pluck('product_id');

        // منتجات بدون مخزون وبدون حركة
        $toArchive = Product::whereNull('deleted_at')
            ->whereNotIn('id', $activeIds)
            ->whereDoesntHave('stockItems', fn ($q) => $q->where('quantity', '>', 0))
            ->get();

        $count = 0;
        foreach ($toArchive as $product) {
            $product->delete(); // soft delete
            $count++;
        }

        Log::info('Inactive products archived', ['count' => $count, 'threshold_months' => $monthsThreshold, 'user_id' => auth()->id()]);

        return $count;
    }

    /**
     * أرشفة عملاء بدون معاملات من X شهر
     */
    public function archiveInactiveCustomers(int $monthsThreshold = 12): int
    {
        $since = today()->subMonths($monthsThreshold);

        $activeIds = Invoice::where('invoice_date', '>=', $since)
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        $toArchive = Customer::whereNull('deleted_at')
            ->whereNotIn('id', $activeIds)
            ->get();

        $count = 0;
        foreach ($toArchive as $customer) {
            $customer->delete();
            $count++;
        }

        Log::info('Inactive customers archived', ['count' => $count, 'threshold_months' => $monthsThreshold, 'user_id' => auth()->id()]);

        return $count;
    }

    // ══════════════════════════════════════════════════════════════════
    // 4. إحصائيات التنظيف
    // ══════════════════════════════════════════════════════════════════

    public function getCleanupStats(): array
    {
        return [
            'duplicate_customers' => Customer::select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->count(),

            'duplicate_products'  => Product::select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->count(),

            'archived_customers'  => Customer::onlyTrashed()->count(),
            'archived_products'   => Product::onlyTrashed()->count(),
            'archived_suppliers'  => Supplier::onlyTrashed()->count(),

            'zero_stock_products' => Stock::where('quantity', '<=', 0)->count(),
        ];
    }
}
