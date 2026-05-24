<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PriceListVersionResource;
use App\Models\Company;
use App\Modules\Catalog\PriceListService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ImportPriceListPage extends Page
{
    protected static ?string $title = 'رفع قائمة أسعار';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'الشركات والأصناف';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'رفع لستة أسعار';

    protected string $view = 'filament.pages.import-price-list';

    // ─── Form state ────────────────────────────────────────────────────────────

    /** Schema form state — company selector + file upload. */
    public array $data = [
        'company_id' => null,
        'excel_file' => null,
    ];

    // ─── Preview state ─────────────────────────────────────────────────────────

    public bool $showPreview = false;

    public array $preview = [];

    public ?string $tempFilePath = null;

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

                Section::make('ملف الأسعار')
                    ->description('ارفع ملف Excel أو CSV — أول صف = عناوين الأعمدة')
                    ->schema([
                        FileUpload::make('excel_file')
                            ->label('ملف Excel / CSV')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->maxSize(10240)
                            ->disk('local')
                            ->directory('temp/price-list-imports')
                            ->helperText('الصف الأول = عناوين الأعمدة (يُتجاهل تلقائياً)'),
                    ]),
            ]);
    }

    // ─── Actions ───────────────────────────────────────────────────────────────

    /** المرحلة الأولى: قراءة الملف وعرض المعاينة — بدون حفظ. */
    public function previewFile(): void
    {
        // getState() validates all components and saves uploaded files to disk.
        // Throws \Filament\Support\Exceptions\Halt on validation failure.
        $state = $this->form->getState();

        // FileUpload returns a string (single file path relative to disk root)
        $uploadedPath = $state['excel_file'] ?? null;

        if (! $uploadedPath) {
            Notification::make()
                ->title('ارفع ملف Excel أو CSV')
                ->danger()
                ->send();

            return;
        }

        // Resolve absolute path — FileUpload stores relative to disk root
        $relativePath = is_array($uploadedPath) ? array_values($uploadedPath)[0] : $uploadedPath;
        $filePath = Storage::disk('local')->path($relativePath);

        if (! file_exists($filePath)) {
            Notification::make()
                ->title('الملف مش موجود — حاول ترفع الملف تاني')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = app(PriceListService::class);
            $this->preview = $service->previewImport($filePath, (int) $state['company_id']);
            $this->tempFilePath = $filePath;
            $this->showPreview = true;

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
            $result = $service->confirmImport(
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

            $this->reset(['showPreview', 'preview', 'tempFilePath']);
            $this->data = ['company_id' => null, 'excel_file' => null];

            $this->redirect(
                PriceListVersionResource::getUrl('index')
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
