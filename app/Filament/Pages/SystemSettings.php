<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

class SystemSettings extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static string|\UnitEnum|null   $navigationGroup = 'الإدارة';
    protected static ?int                    $navigationSort  = 90;
    protected static ?string                 $title           = 'إعدادات النظام';
    protected static ?string                 $navigationLabel = 'إعدادات النظام';
    protected string                         $view            = 'filament.pages.system-settings';

    public string $activeTab = 'company';

    /** بيانات النموذج — مصفوفة ثنائية [group][key] = value */
    public array $formData = [];

    /** حقل رفع اللوجو (Livewire file upload) */
    public $logoUpload = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->loadSettings();
    }

    /** تحميل كل الإعدادات إلى $formData */
    public function loadSettings(): void
    {
        $settings = SystemSetting::orderBy('group')->orderBy('sort_order')->get();

        foreach ($settings as $setting) {
            $this->formData[$setting->group][$setting->key] = $setting->value ?? '';
        }
    }

    /** إعدادات مجموعة معينة مرتبة بـ sort_order */
    public function getSettingsByGroup(string $group): \Illuminate\Support\Collection
    {
        return SystemSetting::where('group', $group)
            ->orderBy('sort_order')
            ->get();
    }

    /** تبديل التبويب النشط */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /** قائمة التبويبات مع الأيقونات والتسميات */
    public function getTabs(): array
    {
        return [
            'company'        => ['label' => 'بيانات الشركة',     'icon' => 'heroicon-o-building-office'],
            'invoice'        => ['label' => 'إعدادات الفاتورة',  'icon' => 'heroicon-o-document-text'],
            'numbering'      => ['label' => 'تسلسل الأرقام',     'icon' => 'heroicon-o-hashtag'],
            'defaults'       => ['label' => 'الافتراضيات',        'icon' => 'heroicon-o-adjustments-horizontal'],
            'alerts'         => ['label' => 'التنبيهات',          'icon' => 'heroicon-o-bell'],
            'print'          => ['label' => 'إعدادات الطباعة',   'icon' => 'heroicon-o-printer'],
            'business_rules' => ['label' => 'قواعد الأعمال',      'icon' => 'heroicon-o-scale'],
        ];
    }

    /** حفظ التبويب النشط */
    public function save(): void
    {
        // رفع اللوجو إذا كان موجوداً
        if ($this->logoUpload) {
            $path = $this->logoUpload->store('logos', 'public');
            $this->formData['company']['logo'] = $path;
            $this->logoUpload = null;
        }

        // حفظ إعدادات التبويب النشط فقط
        $settings = $this->getSettingsByGroup($this->activeTab);

        foreach ($settings as $setting) {
            $value = $this->formData[$this->activeTab][$setting->key] ?? '';

            // للـ toggle: تحويل checkbox value
            if ($setting->type === 'toggle') {
                $value = $value ? '1' : '0';
            }

            SystemSetting::set("{$this->activeTab}.{$setting->key}", $value);
        }

        // مسح كامل للكاش
        SystemSetting::clearCache();

        Notification::make()
            ->title('تم الحفظ')
            ->body('تم حفظ الإعدادات بنجاح.')
            ->success()
            ->send();
    }

    /** إعادة تعيين التبويب النشط إلى القيم المحفوظة */
    public function reset(): void
    {
        $this->loadSettings();

        Notification::make()
            ->title('تم الإلغاء')
            ->body('تم إعادة تعيين الإعدادات.')
            ->warning()
            ->send();
    }

    /** مسار اللوجو الحالي لعرضه */
    public function getLogoUrl(): ?string
    {
        $logo = $this->formData['company']['logo'] ?? null;
        if (! $logo) return null;
        return Storage::url($logo);
    }
}
