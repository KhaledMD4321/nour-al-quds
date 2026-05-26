<?php

namespace App\Modules\Reports;

use App\Models\BusinessUnit;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ══════════════════════════════════════════════════════════════════
    // 1. أعمار الديون — Customer Aging
    // ══════════════════════════════════════════════════════════════════

    /**
     * حساب أعمار ديون العملاء
     * يُرجع كل عميل عنده رصيد مستحق مقسّم على فترات
     */
    public function customerAging(?int $businessUnitId = null, ?string $asOfDate = null): Collection
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate) : today();

        $query = Invoice::with('customer')
            ->where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid'])
            ->where('invoice_date', '<=', $asOf)
            ->whereRaw('total_amount > paid_amount');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        $invoices = $query->get();

        return $invoices
            ->groupBy('customer_id')
            ->map(function ($group) use ($asOf) {
                $customer = $group->first()->customer;
                $buckets = ['current' => 0, 'days_30' => 0, 'days_60' => 0, 'days_90' => 0, 'over_90' => 0];

                foreach ($group as $inv) {
                    $due = $inv->due_date ?? $inv->invoice_date;
                    $overdue = (int) $due->diffInDays($asOf, false);
                    $balance = (float) $inv->total_amount - (float) $inv->paid_amount;

                    if ($overdue <= 0) {
                        $buckets['current'] += $balance;
                    } elseif ($overdue <= 30) {
                        $buckets['days_30'] += $balance;
                    } elseif ($overdue <= 60) {
                        $buckets['days_60'] += $balance;
                    } elseif ($overdue <= 90) {
                        $buckets['days_90'] += $balance;
                    } else {
                        $buckets['over_90'] += $balance;
                    }
                }

                $total = array_sum($buckets);

                return (object) [
                    'customer_id' => $customer->id,
                    'customer_code' => $customer->code,
                    'customer_name' => $customer->name,
                    'current' => $buckets['current'],
                    'days_30' => $buckets['days_30'],
                    'days_60' => $buckets['days_60'],
                    'days_90' => $buckets['days_90'],
                    'over_90' => $buckets['over_90'],
                    'total' => $total,
                ];
            })
            ->filter(fn ($r) => $r->total > 0.01)
            ->sortByDesc('total')
            ->values();
    }

    /**
     * حساب أعمار ديون الموردين
     */
    public function supplierAging(?int $businessUnitId = null, ?string $asOfDate = null): Collection
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate) : today();

        $query = PurchaseInvoice::with('supplier')
            ->whereIn('status', ['confirmed', 'partial_paid'])
            ->where('invoice_date', '<=', $asOf)
            ->whereRaw('total_amount > paid_amount');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        $invoices = $query->get();

        return $invoices
            ->groupBy('supplier_id')
            ->map(function ($group) use ($asOf) {
                $supplier = $group->first()->supplier;
                $buckets = ['current' => 0, 'days_30' => 0, 'days_60' => 0, 'days_90' => 0, 'over_90' => 0];

                foreach ($group as $inv) {
                    $due = $inv->due_date ?? $inv->invoice_date;
                    $overdue = (int) $due->diffInDays($asOf, false);
                    $balance = (float) $inv->total_amount - (float) $inv->paid_amount;

                    if ($overdue <= 0) {
                        $buckets['current'] += $balance;
                    } elseif ($overdue <= 30) {
                        $buckets['days_30'] += $balance;
                    } elseif ($overdue <= 60) {
                        $buckets['days_60'] += $balance;
                    } elseif ($overdue <= 90) {
                        $buckets['days_90'] += $balance;
                    } else {
                        $buckets['over_90'] += $balance;
                    }
                }

                $total = array_sum($buckets);

                return (object) [
                    'supplier_id' => $supplier->id,
                    'supplier_code' => $supplier->code,
                    'supplier_name' => $supplier->name,
                    'current' => $buckets['current'],
                    'days_30' => $buckets['days_30'],
                    'days_60' => $buckets['days_60'],
                    'days_90' => $buckets['days_90'],
                    'over_90' => $buckets['over_90'],
                    'total' => $total,
                ];
            })
            ->filter(fn ($r) => $r->total > 0.01)
            ->sortByDesc('total')
            ->values();
    }

    // ══════════════════════════════════════════════════════════════════
    // 2. الأرباح والخسائر — Profit & Loss
    // ══════════════════════════════════════════════════════════════════

    /**
     * حساب P&L لوحدة معينة أو كل الوحدات
     */
    public function profitLoss(?int $businessUnitId, string $fromDate, string $toDate): object
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        // ── الإيرادات من فواتير المبيعات المؤكدة ──
        $salesQuery = Invoice::where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'])
            ->whereBetween('invoice_date', [$from, $to]);
        if ($businessUnitId) {
            $salesQuery->where('business_unit_id', $businessUnitId);
        }

        $grossRevenue = (float) $salesQuery->sum('total_amount');
        $discounts = (float) $salesQuery->sum('discount_amount');

        // ── مرتجعات المبيعات ──
        $returnQuery = Invoice::where('type', 'sale_return')
            ->whereIn('status', ['confirmed', 'delivered', 'paid'])
            ->whereBetween('invoice_date', [$from, $to]);
        if ($businessUnitId) {
            $returnQuery->where('business_unit_id', $businessUnitId);
        }
        $salesReturns = (float) $returnQuery->sum('total_amount');

        $netRevenue = $grossRevenue - $salesReturns;

        // ── تكلفة المبيعات من حركات المخزون ──
        $cogQuery = StockMovement::where('type', 'out')
            ->where('reference_type', 'invoice')
            ->whereBetween('created_at', [$from, $to]);
        if ($businessUnitId) {
            $cogQuery->whereHas('warehouse', fn ($q) => $q->where('business_unit_id', $businessUnitId));
        }
        $costOfGoods = (float) $cogQuery->join('stock', function ($j) {
            $j->on('stock_movements.warehouse_id', '=', 'stock.warehouse_id')
                ->on('stock_movements.product_id', '=', 'stock.product_id');
        })->selectRaw('SUM(stock_movements.quantity * stock.avg_cost) as total_cost')
            ->value('total_cost') ?? 0;

        $grossProfit = $netRevenue - $costOfGoods;

        // ── المصروفات من سندات الصرف ──
        $expenseQuery = Payment::whereBetween('payment_date', [$from, $to]);
        if ($businessUnitId) {
            $expenseQuery->where('business_unit_id', $businessUnitId);
        }
        $totalExpenses = (float) $expenseQuery->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;

        return (object) [
            'gross_revenue' => $grossRevenue,
            'discounts' => $discounts,
            'sales_returns' => $salesReturns,
            'net_revenue' => $netRevenue,
            'cost_of_goods' => $costOfGoods,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'gross_margin' => $netRevenue > 0 ? round(($grossProfit / $netRevenue) * 100, 2) : 0,
            'net_margin' => $netRevenue > 0 ? round(($netProfit / $netRevenue) * 100, 2) : 0,
        ];
    }

    /**
     * P&L لكل وحدة على حدة + الموحّد
     */
    public function profitLossConsolidated(string $fromDate, string $toDate): object
    {
        $units = BusinessUnit::all();
        $byUnit = [];

        foreach ($units as $unit) {
            $byUnit[$unit->id] = [
                'unit' => $unit,
                'report' => $this->profitLoss($unit->id, $fromDate, $toDate),
            ];
        }

        // الموحّد = مجموع كل الوحدات (بدون استبعاد البينيات في هذا الإصدار)
        $consolidated = $this->profitLoss(null, $fromDate, $toDate);

        return (object) [
            'by_unit' => $byUnit,
            'consolidated' => $consolidated,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // 3. تقارير المخزون — Inventory Reports
    // ══════════════════════════════════════════════════════════════════

    /**
     * أرصدة المخزون الحالية
     */
    public function stockBalance(?int $warehouseId = null, ?int $businessUnitId = null): Collection
    {
        $query = Stock::with(['product', 'warehouse.businessUnit'])
            ->where('quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($businessUnitId) {
            $query->whereHas('warehouse', fn ($q) => $q->where('business_unit_id', $businessUnitId));
        }

        return $query->orderBy('warehouse_id')->get()->map(fn ($s) => (object) [
            'warehouse_name' => $s->warehouse->name,
            'product_code' => $s->product->code,
            'product_name' => $s->product->name,
            'quantity' => (float) $s->quantity,
            'avg_cost' => (float) $s->avg_cost,
            'total_value' => round((float) $s->quantity * (float) $s->avg_cost, 2),
            'min_stock_level' => (float) $s->product->min_stock_level,
            'below_min' => $s->quantity < $s->product->min_stock_level,
        ]);
    }

    /**
     * الأصناف الراكدة (بدون حركة من X يوم)
     */
    public function slowMovingStock(int $daysThreshold = 90, ?int $warehouseId = null): Collection
    {
        $since = today()->subDays($daysThreshold);

        $activeProductIds = StockMovement::where('created_at', '>=', $since)
            ->distinct()
            ->pluck('product_id');

        $query = Stock::with(['product', 'warehouse'])
            ->where('quantity', '>', 0)
            ->whereNotIn('product_id', $activeProductIds);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(fn ($s) => (object) [
            'warehouse_name' => $s->warehouse->name,
            'product_code' => $s->product->code,
            'product_name' => $s->product->name,
            'quantity' => (float) $s->quantity,
            'avg_cost' => (float) $s->avg_cost,
            'total_value' => round((float) $s->quantity * (float) $s->avg_cost, 2),
        ]);
    }

    /**
     * حركة صنف معين
     */
    public function productMovement(int $productId, string $fromDate, string $toDate, ?int $warehouseId = null): Collection
    {
        $query = StockMovement::with(['warehouse', 'product'])
            ->where('product_id', $productId)
            ->whereBetween('created_at', [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay(),
            ]);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderBy('created_at')->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // 4. تقارير المبيعات — Sales Reports
    // ══════════════════════════════════════════════════════════════════

    /**
     * ملخص مبيعات بالفترة والوحدة
     */
    public function salesSummary(string $fromDate, string $toDate, ?int $businessUnitId = null): object
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = Invoice::where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'])
            ->whereBetween('invoice_date', [$from, $to]);

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return (object) [
            'count' => $query->count(),
            'total_amount' => (float) $query->sum('total_amount'),
            'paid_amount' => (float) $query->sum('paid_amount'),
            'outstanding' => (float) $query->sum(DB::raw('total_amount - paid_amount')),
        ];
    }

    /**
     * مبيعات مجمّعة بالعميل
     */
    public function salesByCustomer(string $fromDate, string $toDate, ?int $businessUnitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = Invoice::with('customer')
            ->select('customer_id', DB::raw('COUNT(*) as invoice_count'), DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'))
            ->where('type', 'sale')
            ->whereIn('status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'])
            ->whereBetween('invoice_date', [$from, $to])
            ->groupBy('customer_id')
            ->orderByDesc('total');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->get()->map(fn ($r) => (object) [
            'customer_name' => $r->customer?->name ?? '—',
            'invoice_count' => $r->invoice_count,
            'total' => (float) $r->total,
            'paid' => (float) $r->paid,
            'outstanding' => (float) $r->total - (float) $r->paid,
        ]);
    }

    /**
     * مبيعات مجمّعة بالمنتج
     */
    public function salesByProduct(string $fromDate, string $toDate, ?int $businessUnitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->select(
                'products.code as product_code',
                'products.name as product_name',
                DB::raw('SUM(invoice_items.quantity) as total_qty'),
                DB::raw('SUM(invoice_items.total) as total_revenue')
            )
            ->where('invoices.type', 'sale')
            ->whereIn('invoices.status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'])
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->groupBy('products.id', 'products.code', 'products.name')
            ->orderByDesc('total_revenue');

        if ($businessUnitId) {
            $query->where('invoices.business_unit_id', $businessUnitId);
        }

        return $query->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // 5. تقارير المشتريات — Purchase Reports
    // ══════════════════════════════════════════════════════════════════

    /**
     * ملخص مشتريات بالفترة
     */
    public function purchasesSummary(string $fromDate, string $toDate, ?int $businessUnitId = null): object
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = PurchaseInvoice::whereIn('status', ['confirmed', 'paid'])
            ->whereBetween('invoice_date', [$from, $to]);

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return (object) [
            'count' => $query->count(),
            'total_amount' => (float) $query->sum('total_amount'),
            'paid_amount' => (float) $query->sum('paid_amount'),
            'outstanding' => (float) $query->sum(DB::raw('total_amount - paid_amount')),
        ];
    }

    /**
     * مشتريات مجمّعة بالمورد
     */
    public function purchasesBySupplier(string $fromDate, string $toDate, ?int $businessUnitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = PurchaseInvoice::with('supplier')
            ->select('supplier_id', DB::raw('COUNT(*) as invoice_count'), DB::raw('SUM(total_amount) as total'), DB::raw('SUM(paid_amount) as paid'))
            ->whereIn('status', ['confirmed', 'paid'])
            ->whereBetween('invoice_date', [$from, $to])
            ->groupBy('supplier_id')
            ->orderByDesc('total');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->get()->map(fn ($r) => (object) [
            'supplier_name' => $r->supplier?->name ?? '—',
            'invoice_count' => $r->invoice_count,
            'total' => (float) $r->total,
            'paid' => (float) $r->paid,
            'outstanding' => (float) $r->total - (float) $r->paid,
        ]);
    }

    /**
     * مشتريات مجمّعة بالمنتج
     */
    public function purchasesByProduct(string $fromDate, string $toDate, ?int $businessUnitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id')
            ->join('products', 'products.id', '=', 'purchase_invoice_items.product_id')
            ->select(
                'products.code as product_code',
                'products.name as product_name',
                DB::raw('SUM(purchase_invoice_items.quantity) as total_qty'),
                DB::raw('SUM(purchase_invoice_items.total) as total_cost')
            )
            ->whereIn('purchase_invoices.status', ['confirmed', 'paid'])
            ->whereBetween('purchase_invoices.invoice_date', [$from, $to])
            ->groupBy('products.id', 'products.code', 'products.name')
            ->orderByDesc('total_cost');

        if ($businessUnitId) {
            $query->where('purchase_invoices.business_unit_id', $businessUnitId);
        }

        return $query->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // 6. هامش الربح — Gross Margin
    // ══════════════════════════════════════════════════════════════════

    /**
     * هامش الربح لكل صنف خلال فترة: الإيراد − تكلفة المبيعات.
     * التكلفة من حركات المخزون (صادر/فاتورة) × متوسط التكلفة الحالي،
     * بنفس منطق تقرير الأرباح والخسائر (مصدر واحد للتكلفة).
     */
    public function grossMarginByProduct(string $fromDate, string $toDate, ?int $businessUnitId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        // ── الإيراد + الكمية لكل صنف ──
        $revenueQuery = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('companies', 'companies.id', '=', 'products.company_id')
            ->where('invoices.type', 'sale')
            ->whereIn('invoices.status', ['confirmed', 'delivered', 'partial_paid', 'partially_paid', 'paid'])
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->groupBy('invoice_items.product_id', 'products.code', 'products.name', 'companies.name')
            ->selectRaw("invoice_items.product_id, products.code as product_code, products.name as product_name, COALESCE(companies.name, 'بدون مصنّع') as manufacturer, SUM(invoice_items.quantity) as qty, SUM(invoice_items.total) as revenue");

        if ($businessUnitId) {
            $revenueQuery->where('invoices.business_unit_id', $businessUnitId);
        }

        // ── التكلفة لكل صنف (حركات صادر مرتبطة بفواتير × متوسط التكلفة) ──
        $cogsQuery = StockMovement::where('type', 'out')
            ->where('reference_type', 'invoice')
            ->whereBetween('created_at', [$from, $to]);

        if ($businessUnitId) {
            $cogsQuery->whereHas('warehouse', fn ($q) => $q->where('business_unit_id', $businessUnitId));
        }

        $cogs = $cogsQuery
            ->join('stock', function ($j) {
                $j->on('stock_movements.warehouse_id', '=', 'stock.warehouse_id')
                    ->on('stock_movements.product_id', '=', 'stock.product_id');
            })
            ->groupBy('stock_movements.product_id')
            ->selectRaw('stock_movements.product_id as product_id, SUM(stock_movements.quantity * stock.avg_cost) as cogs')
            ->pluck('cogs', 'product_id');

        return $revenueQuery->get()->map(function ($r) use ($cogs) {
            $revenue = (float) $r->revenue;
            $cost = (float) ($cogs[$r->product_id] ?? 0);
            $profit = round($revenue - $cost, 2);

            return (object) [
                'product_code' => $r->product_code,
                'product_name' => $r->product_name,
                'manufacturer' => $r->manufacturer,
                'qty' => (float) $r->qty,
                'revenue' => round($revenue, 2),
                'cogs' => round($cost, 2),
                'gross_profit' => $profit,
                'margin_pct' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
            ];
        })->sortByDesc('gross_profit')->values();
    }

    /**
     * هامش الربح مجمّعاً بالمصنّع — يبني فوق grossMarginByProduct.
     */
    public function grossMarginByManufacturer(string $fromDate, string $toDate, ?int $businessUnitId = null): Collection
    {
        return $this->grossMarginByProduct($fromDate, $toDate, $businessUnitId)
            ->groupBy('manufacturer')
            ->map(function (Collection $rows, $name) {
                $revenue = (float) $rows->sum('revenue');
                $cogs = (float) $rows->sum('cogs');
                $profit = round($revenue - $cogs, 2);

                return (object) [
                    'manufacturer' => $name,
                    'products' => $rows->count(),
                    'qty' => (float) $rows->sum('qty'),
                    'revenue' => round($revenue, 2),
                    'cogs' => round($cogs, 2),
                    'gross_profit' => $profit,
                    'margin_pct' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('gross_profit')
            ->values();
    }
}
