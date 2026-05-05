<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Modules\DataManagement\PeriodRollbackService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class PeriodManagerPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null   $navigationGroup = 'إدارة البيانات';
    protected static ?int                    $navigationSort  = 32;
    protected static ?string                 $title           = 'مدير الفترات المالية';
    protected static ?string                 $navigationLabel = 'مدير الفترات';
    protected string                         $view            = 'filament.pages.period-manager';

    public ?string $rollback_from    = null;
    public ?string $rollback_to      = null;
    public ?string $confirm_password = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('accounting.lock_period');
    }

    public function mount(): void
    {
        $this->rollback_from = today()->startOfMonth()->toDateString();
        $this->rollback_to   = today()->toDateString();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('rollback_from')
                ->label('من تاريخ (Rollback)')
                ->displayFormat('Y-m-d')
                ->live(),

            DatePicker::make('rollback_to')
                ->label('إلى تاريخ (Rollback)')
                ->displayFormat('Y-m-d')
                ->live(),

            TextInput::make('confirm_password')
                ->label('كلمة السر للتأكيد')
                ->password()
                ->helperText('مطلوبة لتنفيذ Rollback'),
        ])->columns(3);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_rollback')
                ->label('معاينة Rollback')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->action(fn () => $this->previewRollback()),

            Action::make('rollback')
                ->label('تنفيذ Rollback ⚠')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('⚠ تحذير — Rollback نهائي')
                ->modalDescription('سيتم حذف جميع المعاملات في الفترة المحددة بشكل نهائي. هذا الإجراء لا يمكن التراجع عنه.')
                ->action(fn () => $this->executeRollback())
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
        ];
    }

    public function previewRollback(): void
    {
        if (!$this->rollback_from || !$this->rollback_to) {
            Notification::make()->warning()->title('الرجاء تحديد الفترة أولاً')->send();
            return;
        }

        $service = app(PeriodRollbackService::class);
        $preview = $service->previewRollback($this->rollback_from, $this->rollback_to);

        $total = array_sum($preview);

        Notification::make()
            ->warning()
            ->title('معاينة Rollback')
            ->body(
                "فواتير مبيعات: {$preview['invoices']} | " .
                "فواتير مشتريات: {$preview['purchases']} | " .
                "سندات قبض: {$preview['receipts']} | " .
                "سندات صرف: {$preview['payments']} | " .
                "قيود: {$preview['entries']} | " .
                "الإجمالي: {$total} معاملة"
            )
            ->persistent()
            ->send();
    }

    public function executeRollback(): void
    {
        if (!$this->rollback_from || !$this->rollback_to) {
            Notification::make()->warning()->title('الرجاء تحديد الفترة أولاً')->send();
            return;
        }

        if (! \Hash::check($this->confirm_password ?? '', auth()->user()->password)) {
            Notification::make()->danger()->title('كلمة السر غير صحيحة')->send();
            return;
        }

        try {
            $service = app(PeriodRollbackService::class);
            $result  = $service->rollback($this->rollback_from, $this->rollback_to, auth()->id());

            $total = array_sum($result);

            Notification::make()
                ->success()
                ->title('تم تنفيذ Rollback')
                ->body("تم حذف {$total} معاملة من الفترة المحددة")
                ->persistent()
                ->send();

            $this->confirm_password = null;
        } catch (\Exception $e) {
            Notification::make()->danger()->title('خطأ في Rollback')->body($e->getMessage())->send();
        }
    }

    public function lockPeriod(int $id): void
    {
        try {
            app(PeriodRollbackService::class)->lockPeriod($id, auth()->id());
            Notification::make()->success()->title('تم قفل الفترة')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    public function unlockPeriod(int $id): void
    {
        try {
            app(PeriodRollbackService::class)->unlockPeriod($id, auth()->id());
            Notification::make()->success()->title('تم فتح الفترة')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    public function getPeriods(): \Illuminate\Database\Eloquent\Collection
    {
        return FiscalPeriod::orderByDesc('year')->orderByDesc('month')->get();
    }
}
