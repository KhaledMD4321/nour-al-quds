<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\OpeningBalance;
use App\Models\Product;
use App\Models\Supplier;
use App\Modules\DataManagement\OpeningBalanceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class OpeningBalancesPage extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'الأرصدة الافتتاحية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'أرصدة افتتاحية';

    protected static ?string $slug = 'opening-balances';

    protected string $view = 'filament.pages.opening-balances';

    // ─── Shared state ──────────────────────────────────────────────────────────
    public string $activeTab = 'customers';

    public ?string $balance_date = null;

    // ─── Customer tab ──────────────────────────────────────────────────────────
    public ?int $customer_id = null;

    public float $customer_amount = 0;

    // ─── Supplier tab ─────────────────────────────────────────────────────────
    public ?int $supplier_id = null;

    public float $supplier_amount = 0;

    // ─── Stock tab ────────────────────────────────────────────────────────────
    public ?int $warehouse_id = null;

    public ?int $stock_product_id = null;

    public float $stock_quantity = 0;

    public float $stock_unit_cost = 0;

    public $stock_excel_file = null;

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    // ─── Mount ────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->balance_date = now()->toDateString();
    }

    // ─── Customer balance ─────────────────────────────────────────────────────

    public function saveCustomerBalance(): void
    {
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'customer_amount' => 'required|numeric|min:0',
            'balance_date' => 'required|date',
        ], [
            'customer_id.required' => 'اختر العميل',
            'customer_amount.required' => 'ادخل المبلغ',
        ]);

        app(OpeningBalanceService::class)->setCustomerBalance(
            $this->customer_id,
            $this->customer_amount,
            $this->balance_date,
            auth()->id()
        );

        $name = Customer::find($this->customer_id)?->name;
        Notification::make()->title("تم تسجيل رصيد افتتاحي للعميل: {$name}")->success()->send();

        $this->customer_id = null;
        $this->customer_amount = 0;
    }

    // ─── Supplier balance ─────────────────────────────────────────────────────

    public function saveSupplierBalance(): void
    {
        $this->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_amount' => 'required|numeric|min:0',
            'balance_date' => 'required|date',
        ], [
            'supplier_id.required' => 'اختر المورد',
            'supplier_amount.required' => 'ادخل المبلغ',
        ]);

        app(OpeningBalanceService::class)->setSupplierBalance(
            $this->supplier_id,
            $this->supplier_amount,
            $this->balance_date,
            auth()->id()
        );

        $name = Supplier::find($this->supplier_id)?->name;
        Notification::make()->title("تم تسجيل رصيد افتتاحي للمورد: {$name}")->success()->send();

        $this->supplier_id = null;
        $this->supplier_amount = 0;
    }

    // ─── Stock balance (single item) ──────────────────────────────────────────

    public function saveStockBalance(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'stock_product_id' => 'required|exists:products,id',
            'stock_quantity' => 'required|numeric|min:0.001',
            'stock_unit_cost' => 'required|numeric|min:0.01',
            'balance_date' => 'required|date',
        ], [
            'warehouse_id.required' => 'اختر المخزن',
            'stock_product_id.required' => 'اختر الصنف',
            'stock_quantity.required' => 'ادخل الكمية',
            'stock_unit_cost.required' => 'ادخل التكلفة',
        ]);

        app(OpeningBalanceService::class)->setStockBalance(
            $this->warehouse_id,
            $this->stock_product_id,
            $this->stock_quantity,
            $this->stock_unit_cost,
            $this->balance_date,
            auth()->id()
        );

        $name = Product::find($this->stock_product_id)?->name;
        Notification::make()->title("تم تسجيل رصيد افتتاحي للصنف: {$name}")->success()->send();

        $this->stock_product_id = null;
        $this->stock_quantity = 0;
        $this->stock_unit_cost = 0;
    }

    // ─── Stock balance (Excel import) ─────────────────────────────────────────

    public function importStockExcel(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'stock_excel_file' => 'required',
            'balance_date' => 'required|date',
        ], [
            'warehouse_id.required' => 'اختر المخزن',
            'stock_excel_file.required' => 'ارفع ملف Excel',
        ]);

        try {
            $path = storage_path('app/private/'.$this->stock_excel_file);
            $file = new UploadedFile($path, 'stock.xlsx', null, null, true);

            $results = app(OpeningBalanceService::class)->importStockFromExcel(
                $file,
                $this->warehouse_id,
                $this->balance_date,
                auth()->id()
            );

            $msg = "تم إضافة {$results['added']} صنف";
            if ($results['skipped'] > 0) {
                $msg .= " — {$results['skipped']} صف تم تجاهله";
            }

            Notification::make()->title($msg)->success()->persistent()->send();

            if (! empty($results['errors'])) {
                Notification::make()
                    ->title('تفاصيل الأخطاء')
                    ->body(implode("\n", array_slice($results['errors'], 0, 10)))
                    ->warning()
                    ->persistent()
                    ->send();
            }

            $this->stock_excel_file = null;

        } catch (\Exception $e) {
            Notification::make()->title('خطأ: '.$e->getMessage())->danger()->send();
        }
    }

    // ─── Computed property — existing balances summary ────────────────────────

    public function getExistingBalancesProperty(): array
    {
        return [
            'customers' => OpeningBalance::where('type', 'customer')
                ->get()
                ->map(fn ($ob) => [
                    'name' => $ob->reference_name,
                    'amount' => $ob->debit,
                    'date' => $ob->balance_date->format('d/m/Y'),
                ]),

            'suppliers' => OpeningBalance::where('type', 'supplier')
                ->get()
                ->map(fn ($ob) => [
                    'name' => $ob->reference_name,
                    'amount' => $ob->credit,
                    'date' => $ob->balance_date->format('d/m/Y'),
                ]),

            'stock_count' => OpeningBalance::where('type', 'stock')->count(),
            'stock_value' => (float) OpeningBalance::where('type', 'stock')->sum('debit'),
        ];
    }
}
