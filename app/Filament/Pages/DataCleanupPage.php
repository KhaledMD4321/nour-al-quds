<?php

namespace App\Filament\Pages;

use App\Models\ChartOfAccount;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\CustomFieldValue;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LandedCost;
use App\Models\OpeningBalance;
use App\Models\Payment;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\QuickSale;
use App\Models\QuickSaleItem;
use App\Models\Receipt;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\UnitTransfer;
use App\Models\UnitTransferItem;
use App\Modules\DataManagement\DataCleanupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataCleanupPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة البيانات';

    protected static ?int $navigationSort = 33;

    protected static ?string $title = 'أدوات تنظيف البيانات';

    protected static ?string $navigationLabel = 'تنظيف البيانات';

    protected string $view = 'filament.pages.data-cleanup';

    public int $inactive_months = 12;

    public string $deleteTarget = '';

    public string $confirmText = '';

    public string $resetConfirm = '';

    public string $masterDeleteTarget = '';

    public string $masterDeleteConfirm = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('inactive_months')
                ->label('فترة عدم النشاط')
                ->options([
                    3 => '3 أشهر',
                    6 => '6 أشهر',
                    12 => 'سنة',
                    24 => 'سنتين',
                ])
                ->default(12)
                ->live(),
        ])->columns(1);
    }

    // ── Archive actions (existing) ────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('archive_products')
                ->label('أرشفة الأصناف غير النشطة')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('سيتم أرشفة الأصناف بدون مخزون وبدون حركة منذ '.$this->inactive_months.' شهر.')
                ->action(fn () => $this->archiveProducts()),

            Action::make('archive_customers')
                ->label('أرشفة العملاء غير النشطين')
                ->icon('heroicon-o-user-minus')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('سيتم أرشفة العملاء بدون معاملات منذ '.$this->inactive_months.' شهر.')
                ->action(fn () => $this->archiveCustomers()),
        ];
    }

    public function archiveProducts(): void
    {
        $count = app(DataCleanupService::class)->archiveInactiveProducts($this->inactive_months);
        Notification::make()->success()->title("تم أرشفة {$count} صنف غير نشط")->send();
    }

    public function archiveCustomers(): void
    {
        $count = app(DataCleanupService::class)->archiveInactiveCustomers($this->inactive_months);
        Notification::make()->success()->title("تم أرشفة {$count} عميل غير نشط")->send();
    }

    public function mergeCustomers(int $keepId, int $mergeId): void
    {
        try {
            app(DataCleanupService::class)->mergeCustomers($keepId, $mergeId);
            Notification::make()->success()->title('تم دمج العميلين')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    public function mergeSuppliers(int $keepId, int $mergeId): void
    {
        try {
            app(DataCleanupService::class)->mergeSuppliers($keepId, $mergeId);
            Notification::make()->success()->title('تم دمج الموردين')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    public function getStats(): array
    {
        return app(DataCleanupService::class)->getCleanupStats();
    }

    public function getDuplicateCustomers(): Collection
    {
        return app(DataCleanupService::class)->findDuplicateCustomers();
    }

    public function getDuplicateProducts(): Collection
    {
        return app(DataCleanupService::class)->findDuplicateProducts();
    }

    // ── Selective purge ───────────────────────────────────────────────────────

    public function getDeleteTargets(): array
    {
        return [
            'test_invoices' => ['label' => 'فواتير المبيعات',          'count' => Invoice::count()],
            'test_purchases' => ['label' => 'فواتير المشتريات',         'count' => PurchaseInvoice::count()],
            'test_receipts' => ['label' => 'سندات القبض',              'count' => Receipt::withTrashed()->count()],
            'test_payments' => ['label' => 'سندات الصرف',              'count' => Payment::withTrashed()->count()],
            'test_cheques' => ['label' => 'الشيكات',                  'count' => Cheque::count()],
            'test_journals' => ['label' => 'القيود المحاسبية',          'count' => JournalEntry::count()],
            'stock_movements' => ['label' => 'حركات المخزون',            'count' => StockMovement::count()],
            'treasury_txns' => ['label' => 'حركات الخزائن',            'count' => TreasuryTransaction::count()],
        ];
    }

    public function deleteData(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            Notification::make()->danger()->title('غير مسموح')->send();

            return;
        }

        if ($this->confirmText !== 'تأكيد الحذف') {
            Notification::make()->danger()->title('اكتب "تأكيد الحذف" للمتابعة')->send();

            return;
        }

        try {
            DB::transaction(function () {
                match ($this->deleteTarget) {
                    'test_invoices' => $this->purgeInvoices(),
                    'test_purchases' => $this->purgePurchases(),
                    'test_receipts' => Receipt::withTrashed()->forceDelete(),
                    'test_payments' => Payment::withTrashed()->forceDelete(),
                    'test_cheques' => Cheque::query()->forceDelete(),
                    'test_journals' => $this->purgeJournals(),
                    'stock_movements' => StockMovement::query()->delete(),
                    'treasury_txns' => $this->purgeTreasuryTxns(),
                    default => null,
                };
            });

            $this->confirmText = '';
            $this->deleteTarget = '';
            Notification::make()->success()->title('تم حذف البيانات بنجاح')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ أثناء الحذف')->body($e->getMessage())->send();
        }
    }

    protected function purgeInvoices(): void
    {
        InvoiceItem::query()->delete();
        Invoice::withTrashed()->forceDelete();
    }

    protected function purgePurchases(): void
    {
        PurchaseInvoiceItem::query()->delete();
        LandedCost::query()->delete();
        PurchaseInvoice::withTrashed()->forceDelete();
    }

    protected function purgeJournals(): void
    {
        JournalEntryLine::query()->delete();
        JournalEntry::withTrashed()->forceDelete();
    }

    protected function purgeTreasuryTxns(): void
    {
        TreasuryTransaction::query()->delete();
        Treasury::query()->update(['current_balance' => 0]);
    }

    // ── Master data deletion ──────────────────────────────────────────────────

    public function getMasterDeleteTargets(): array
    {
        return [
            'products' => ['label' => 'الأصناف',       'count' => Product::withTrashed()->count(),       'confirm' => 'حذف الأصناف'],
            'customers' => ['label' => 'العملاء',        'count' => Customer::withTrashed()->count(),      'confirm' => 'حذف العملاء'],
            'suppliers' => ['label' => 'الموردين',       'count' => Supplier::withTrashed()->count(),      'confirm' => 'حذف الموردين'],
            'accounts' => ['label' => 'شجرة الحسابات', 'count' => ChartOfAccount::count(),               'confirm' => 'حذف الحسابات'],
            'settings' => ['label' => 'الإعدادات',     'count' => SystemSetting::count(),                'confirm' => 'حذف الإعدادات'],
        ];
    }

    public function deleteMasterData(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return;
        }

        $targets = $this->getMasterDeleteTargets();
        $targetKey = $this->masterDeleteTarget;

        if (! isset($targets[$targetKey])) {
            Notification::make()->danger()->title('اختر نوع البيانات أولاً')->send();

            return;
        }

        $expected = $targets[$targetKey]['confirm'];
        if ($this->masterDeleteConfirm !== $expected) {
            Notification::make()->danger()
                ->title("اكتب \"{$expected}\" بالضبط للمتابعة")
                ->send();

            return;
        }

        try {
            DB::transaction(function () use ($targetKey) {
                match ($targetKey) {
                    'products' => $this->purgeMasterProducts(),
                    'customers' => $this->purgeMasterCustomers(),
                    'suppliers' => $this->purgeMasterSuppliers(),
                    'accounts' => $this->purgeMasterAccounts(),
                    'settings' => SystemSetting::query()->delete(),
                    default => null,
                };
            });

            $this->masterDeleteTarget = '';
            $this->masterDeleteConfirm = '';
            Notification::make()->success()->title('تم الحذف بنجاح')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ أثناء الحذف')->body($e->getMessage())->send();
        }
    }

    protected function purgeMasterProducts(): void
    {
        // حذف كل ما يرتبط بالأصناف بالترتيب
        QuickSaleItem::query()->forceDelete();
        InvoiceItem::query()->forceDelete();
        PurchaseReturnItem::query()->forceDelete();
        PurchaseInvoiceItem::query()->forceDelete();
        StockAdjustmentItem::query()->forceDelete();
        StockTransferItem::query()->forceDelete();
        StockMovement::query()->forceDelete();
        Stock::query()->forceDelete();
        PriceListItem::query()->forceDelete();
        Product::withTrashed()->forceDelete();
    }

    protected function purgeMasterCustomers(): void
    {
        // حذف المعاملات المرتبطة بالعملاء أولاً
        InvoiceItem::query()->forceDelete();
        Invoice::withTrashed()->forceDelete();
        Receipt::withTrashed()->forceDelete();
        OpeningBalance::query()->delete();
        Customer::withTrashed()->forceDelete();
    }

    protected function purgeMasterSuppliers(): void
    {
        // حذف المعاملات المرتبطة بالموردين أولاً
        PurchaseInvoiceItem::query()->forceDelete();
        LandedCost::query()->forceDelete();
        PurchaseReturnItem::query()->forceDelete();
        PurchaseReturn::withTrashed()->forceDelete();
        PurchaseInvoice::withTrashed()->forceDelete();
        Payment::withTrashed()->forceDelete();
        Supplier::withTrashed()->forceDelete();
    }

    protected function purgeMasterAccounts(): void
    {
        JournalEntryLine::query()->forceDelete();
        JournalEntry::withTrashed()->forceDelete();
        // حذف شجرة الحسابات من الأوراق للجذر
        ChartOfAccount::whereNotNull('parent_id')->delete();
        ChartOfAccount::query()->delete();
    }

    // ── Full system reset ─────────────────────────────────────────────────────

    public function fullReset(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return;
        }

        if ($this->resetConfirm !== 'إعادة تعيين النظام') {
            Notification::make()->danger()->title('اكتب "إعادة تعيين النظام" بالضبط للمتابعة')->send();

            return;
        }

        try {
            DB::transaction(function () {
                // حذف بالترتيب العكسي للـ foreign keys
                CustomFieldValue::query()->delete();
                TreasuryTransaction::query()->delete();
                Receipt::withTrashed()->forceDelete();
                Payment::withTrashed()->forceDelete();
                Cheque::query()->forceDelete();
                InvoiceItem::query()->delete();
                Invoice::withTrashed()->forceDelete();
                QuickSaleItem::query()->delete();
                QuickSale::withTrashed()->forceDelete();
                PurchaseReturnItem::query()->delete();
                PurchaseReturn::withTrashed()->forceDelete();
                PurchaseInvoiceItem::query()->delete();
                LandedCost::query()->delete();
                PurchaseInvoice::withTrashed()->forceDelete();
                StockMovement::query()->delete();
                StockTransferItem::query()->delete();
                StockTransfer::withTrashed()->forceDelete();
                StockAdjustmentItem::query()->delete();
                StockAdjustment::withTrashed()->forceDelete();
                UnitTransferItem::query()->delete();
                UnitTransfer::withTrashed()->forceDelete();
                JournalEntryLine::query()->delete();
                JournalEntry::withTrashed()->forceDelete();
                OpeningBalance::query()->delete();

                // إعادة تعيين الأرصدة
                Stock::query()->update(['quantity' => 0]);
                Treasury::query()->update(['current_balance' => 0]);
                Customer::query()->update(['opening_balance' => 0]);
                Supplier::query()->update(['opening_balance' => 0]);
            });

            $this->resetConfirm = '';
            Notification::make()->success()->title('تم إعادة تعيين النظام بالكامل')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ أثناء إعادة التعيين')->body($e->getMessage())->send();
        }
    }
}
