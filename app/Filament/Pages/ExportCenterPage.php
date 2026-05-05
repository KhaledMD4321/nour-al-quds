<?php

namespace App\Filament\Pages;

use App\Models\BusinessUnit;
use App\Modules\DataManagement\ExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCenterPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-arrow-down-tray';
    protected static string|\UnitEnum|null   $navigationGroup = 'إدارة البيانات';
    protected static ?int                    $navigationSort  = 30;
    protected static ?string                 $title           = 'مركز التصدير';
    protected static ?string                 $navigationLabel = 'مركز التصدير';
    protected string                         $view            = 'filament.pages.export-center';

    public string  $data_type        = 'customers';
    public ?string $from_date        = null;
    public ?string $to_date          = null;
    public ?int    $business_unit_id = null;
    public ?string $direction        = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('data.export');
    }

    public function mount(): void
    {
        $this->from_date = today()->startOfYear()->toDateString();
        $this->to_date   = today()->toDateString();

        if (! auth()->user()?->isSuperAdmin()) {
            $this->business_unit_id = auth()->user()?->business_unit_id;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('data_type')
                ->label('البيانات')
                ->options([
                    'بيانات أساسية' => [
                        'customers' => 'العملاء',
                        'suppliers' => 'الموردين',
                        'products'  => 'الأصناف',
                        'stock'     => 'أرصدة المخزون',
                    ],
                    'بيانات مالية' => [
                        'invoices'  => 'فواتير المبيعات',
                        'purchases' => 'فواتير المشتريات',
                        'receipts'  => 'سندات القبض',
                        'payments'  => 'سندات الصرف',
                        'cheques'   => 'الشيكات المؤجلة',
                    ],
                    'تقارير' => [
                        'aging_customers' => 'أعمار ديون العملاء',
                        'aging_suppliers' => 'أعمار ديون الموردين',
                    ],
                ])
                ->default('customers')
                ->live(),

            DatePicker::make('from_date')
                ->label('من تاريخ')
                ->displayFormat('Y-m-d')
                ->visible(fn () => in_array($this->data_type, ['invoices', 'purchases', 'receipts', 'payments']))
                ->live(),

            DatePicker::make('to_date')
                ->label('إلى تاريخ')
                ->displayFormat('Y-m-d')
                ->visible(fn () => in_array($this->data_type, ['invoices', 'purchases', 'receipts', 'payments', 'aging_customers', 'aging_suppliers']))
                ->live(),

            Select::make('direction')
                ->label('اتجاه الشيك')
                ->options(['' => 'الكل', 'incoming' => 'وارد', 'outgoing' => 'صادر'])
                ->visible(fn () => $this->data_type === 'cheques')
                ->live(),

            Select::make('business_unit_id')
                ->label('الوحدة')
                ->options(fn () => ['' => 'كل الوحدات'] + BusinessUnit::pluck('name', 'id')->toArray())
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->live(),
        ])->columns(3);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير Excel ⬇')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return $this->doExport();
                }),
        ];
    }

    public function doExport(): StreamedResponse
    {
        $service = app(ExportService::class);
        $filters = [
            'from_date'        => $this->from_date,
            'to_date'          => $this->to_date,
            'business_unit_id' => $this->business_unit_id ?: null,
            'direction'        => $this->direction ?: null,
        ];

        try {
            $path     = $service->exportToExcel($this->data_type, $filters);
            $fileName = 'export_' . $this->data_type . '_' . now()->format('Ymd') . '.xlsx';

            return response()->streamDownload(function () use ($path) {
                echo file_get_contents($path);
                @unlink($path);
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('خطأ في التصدير')
                ->body($e->getMessage())
                ->send();

            return response()->streamDownload(fn () => '', 'error.xlsx');
        }
    }

    public function getPreviewData(): \Illuminate\Support\Collection
    {
        $service = app(ExportService::class);
        $filters = [
            'from_date'        => $this->from_date,
            'to_date'          => $this->to_date,
            'business_unit_id' => $this->business_unit_id ?: null,
            'direction'        => $this->direction ?: null,
        ];

        return $service->getData($this->data_type, $filters)->take(10);
    }

    public function getPreviewHeaders(): array
    {
        return app(ExportService::class)->getHeaders($this->data_type);
    }
}
