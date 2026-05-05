<?php

namespace App\Filament\Pages;

use App\Modules\DataManagement\ImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ImportCenterPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-arrow-up-tray';
    protected static string|\UnitEnum|null   $navigationGroup = 'إدارة البيانات';
    protected static ?int                    $navigationSort  = 31;
    protected static ?string                 $title           = 'مركز الاستيراد';
    protected static ?string                 $navigationLabel = 'مركز الاستيراد';
    protected string                         $view            = 'filament.pages.import-center';

    public string  $import_type   = 'customers';
    public ?string $uploaded_file = null;

    // نتيجة التحقق
    public array $valid_rows   = [];
    public array $invalid_rows = [];
    public bool  $validated    = false;
    public bool  $imported     = false;
    public int   $imported_count = 0;
    public int   $updated_count  = 0;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('data.import');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('import_type')
                ->label('نوع البيانات')
                ->options([
                    'customers' => 'العملاء',
                    'suppliers' => 'الموردين',
                    'products'  => 'الأصناف',
                ])
                ->default('customers')
                ->live(),

            FileUpload::make('uploaded_file')
                ->label('ملف Excel')
                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                ->disk('local')
                ->directory('imports/temp')
                ->live(),
        ])->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('validate')
                ->label('تحقق من البيانات')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->action(fn () => $this->doValidate())
                ->visible(fn () => !empty($this->uploaded_file) && !$this->validated),

            Action::make('confirm_import')
                ->label('تأكيد الاستيراد ✓')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تأكيد الاستيراد')
                ->modalDescription('سيتم استيراد ' . count($this->valid_rows) . ' صف. هذا الإجراء لا يمكن التراجع عنه.')
                ->action(fn () => $this->doImport())
                ->visible(fn () => $this->validated && !empty($this->valid_rows) && !$this->imported),

            Action::make('reset')
                ->label('بدء من جديد')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->resetImport())
                ->visible(fn () => $this->validated || $this->imported),
        ];
    }

    public function doValidate(): void
    {
        if (empty($this->uploaded_file)) {
            Notification::make()->warning()->title('الرجاء رفع ملف Excel أولاً')->send();
            return;
        }

        $service = app(ImportService::class);
        $path    = Storage::disk('local')->path('imports/temp/' . basename($this->uploaded_file));

        if (!file_exists($path)) {
            Notification::make()->danger()->title('لم يتم العثور على الملف')->send();
            return;
        }

        try {
            $uploadedFile = new \Illuminate\Http\UploadedFile($path, basename($path));
            $parsed       = $service->parseFile($uploadedFile);
            $result       = $service->validate($this->import_type, $parsed['rows']);

            $this->valid_rows   = $result['valid'];
            $this->invalid_rows = $result['invalid'];
            $this->validated    = true;

            Notification::make()
                ->success()
                ->title('تم التحقق: ' . count($this->valid_rows) . ' صف صحيح، ' . count($this->invalid_rows) . ' صف برسائل')
                ->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ في قراءة الملف')->body($e->getMessage())->send();
        }
    }

    public function doImport(): void
    {
        if (empty($this->valid_rows)) return;

        $service = app(ImportService::class);

        try {
            $result = $service->import($this->import_type, $this->valid_rows, auth()->id());

            $this->imported       = true;
            $this->imported_count = $result['imported'];
            $this->updated_count  = $result['updated'];

            Notification::make()
                ->success()
                ->title('تم الاستيراد بنجاح')
                ->body('مُضاف: ' . $result['imported'] . ' | مُحدَّث: ' . $result['updated'])
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ في الاستيراد')->body($e->getMessage())->send();
        }
    }

    public function resetImport(): void
    {
        $this->uploaded_file  = null;
        $this->valid_rows     = [];
        $this->invalid_rows   = [];
        $this->validated      = false;
        $this->imported       = false;
        $this->imported_count = 0;
        $this->updated_count  = 0;
    }

    public function getTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = ImportService::getTemplate($this->import_type);
        $csv     = implode(',', $headers) . "\n";
        $name    = 'template_' . $this->import_type . '.csv';

        return response()->streamDownload(fn () => print($csv), $name, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
