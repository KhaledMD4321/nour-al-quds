<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Modules\Catalog\PriceListService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPriceListPage extends Page
{
    protected static ?string $title                                 = 'رفع قائمة أسعار';
    protected static string|\BackedEnum|null $navigationIcon       = 'heroicon-o-arrow-up-tray';
    protected static string|\UnitEnum|null   $navigationGroup      = 'الشركات والأصناف';
    protected static ?int                    $navigationSort        = 5;
    protected static ?string                 $navigationLabel       = 'رفع لستة أسعار';
    protected string                         $view                  = 'filament.pages.import-price-list';

    // ─── Form state ────────────────────────────────────────────────────────────

    /** Schema form state — company selector only. */
    public array $data = ['company_id' => null];

    /** Standalone Livewire file upload — separate from Schema form. */
    public ?TemporaryUploadedFile $excelFile = null;

    // ─── Preview state ─────────────────────────────────────────────────────────

    public bool    $showPreview    = false;
    public array   $preview        = [];
    public ?string $tempFilePath   = null;

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('showroom_manager')
            || $user->hasRole('distribution_manager');
    }

    // ─── Schema form ───────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('اختيار المصنّع')
                    ->schema([
                        Select::make('company_id')
                            ->label('المصنّع')
                            ->options(fn (): array => Company::orderBy('name')->pluck('name', 'id')->toArray())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('اختر المصنّع'),
                    ]),
            ]);
    }

    // ─── Actions ───────────────────────────────────────────────────────────────

    /** المرحلة الأولى: قراءة الملف وعرض المعاينة — بدون حفظ. */
    public function previewFile(): void
    {
        $this->validate([
            'data.company_id' => 'required|integer|exists:companies,id',
            'excelFile'       => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'data.company_id.required' => 'اختر المصنّع الأول',
            'data.company_id.exists'   => 'المصنّع المختار غير موجود',
            'excelFile.required'       => 'ارفع ملف Excel أو CSV',
            'excelFile.mimes'          => 'نوع الملف غير مدعوم — ارفع xlsx أو xls أو csv',
            'excelFile.max'            => 'حجم الملف أكبر من 10 ميجا',
        ]);

        try {
            $filePath = $this->excelFile->getRealPath();

            if (! file_exists($filePath)) {
                throw new \RuntimeException('الملف مش موجود — حاول ترفع الملف تاني');
            }

            $service         = app(PriceListService::class);
            $this->preview   = $service->previewImport($filePath, (int) $this->data['company_id']);
            $this->tempFilePath = $filePath;
            $this->showPreview  = true;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في قراءة الملف')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /** المرحلة الثانية: الحفظ الفعلي بعد مراجعة المعاينة. */
    public function confirmImport(): void
    {
        if (! $this->tempFilePath || ! file_exists($this->tempFilePath)) {
            Notification::make()
                ->title('انتهت صلاحية الملف — ارفع الملف تاني')
                ->danger()
                ->send();
            $this->cancelImport();
            return;
        }

        try {
            $service = app(PriceListService::class);
            $result  = $service->confirmImport(
                $this->tempFilePath,
                (int) $this->data['company_id'],
                auth()->id(),
            );

            Notification::make()
                ->title('✅ تم رفع قائمة الأسعار بنجاح')
                ->body($result['message'])
                ->success()
                ->persistent()
                ->send();

            $this->reset(['showPreview', 'preview', 'excelFile', 'tempFilePath']);
            $this->data = ['company_id' => null];

            $this->redirect(
                \App\Filament\Resources\PriceListVersionResource::getUrl('index')
            );

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في الحفظ')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /** إلغاء المعاينة والرجوع لنموذج الرفع. */
    public function cancelImport(): void
    {
        $this->reset(['showPreview', 'preview', 'tempFilePath']);
    }
}
