<?php

namespace App\Filament\Pages;

use App\Modules\DataManagement\DataCleanupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class DataCleanupPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-trash';
    protected static string|\UnitEnum|null   $navigationGroup = 'إدارة البيانات';
    protected static ?int                    $navigationSort  = 33;
    protected static ?string                 $title           = 'أدوات تنظيف البيانات';
    protected static ?string                 $navigationLabel = 'تنظيف البيانات';
    protected string                         $view            = 'filament.pages.data-cleanup';

    public int $inactive_months = 12;

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
                    3  => '3 أشهر',
                    6  => '6 أشهر',
                    12 => 'سنة',
                    24 => 'سنتين',
                ])
                ->default(12)
                ->live(),
        ])->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('archive_products')
                ->label('أرشفة الأصناف غير النشطة')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('سيتم أرشفة الأصناف بدون مخزون وبدون حركة منذ ' . $this->inactive_months . ' شهر.')
                ->action(fn () => $this->archiveProducts()),

            Action::make('archive_customers')
                ->label('أرشفة العملاء غير النشطين')
                ->icon('heroicon-o-user-minus')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('سيتم أرشفة العملاء بدون معاملات منذ ' . $this->inactive_months . ' شهر.')
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

    public function getDuplicateCustomers(): \Illuminate\Support\Collection
    {
        return app(DataCleanupService::class)->findDuplicateCustomers();
    }

    public function getDuplicateProducts(): \Illuminate\Support\Collection
    {
        return app(DataCleanupService::class)->findDuplicateProducts();
    }
}
