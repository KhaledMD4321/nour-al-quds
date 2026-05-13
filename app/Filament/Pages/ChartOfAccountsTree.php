<?php

namespace App\Filament\Pages;

use App\Models\ChartOfAccount;
use App\Models\BusinessUnit;
use App\Models\JournalEntryLine;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ChartOfAccountsTree extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null  $navigationGroup = 'المحاسبة';
    protected static ?int                   $navigationSort  = 1;
    protected static ?string                $title           = 'شجرة الحسابات';
    protected static ?string                $navigationLabel = 'شجرة الحسابات';
    protected string         $view            = 'filament.pages.chart-of-accounts-tree';

    public bool    $showAll    = true;
    public ?string $filterType = null;
    public ?int    $editingId  = null;
    public array   $editData   = [];
    public array   $newAccount = [
        'code'             => '',
        'name'             => '',
        'type'             => 'asset',
        'parent_id'        => null,
        'business_unit_id' => null,
    ];
    public bool $showAddForm = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('accounting.journal.view') ?? false;
    }

    /**
     * Build the top-level tree (parent_id = null), recursively expanded.
     */
    public function getTree(): array
    {
        $query = ChartOfAccount::whereNull('parent_id')
            ->where('is_active', true)
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->orderBy('code');

        return $query->get()->map(fn ($account) => $this->buildNode($account))->toArray();
    }

    protected function buildNode(ChartOfAccount $account): array
    {
        $children = ChartOfAccount::where('parent_id', $account->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn ($child) => $this->buildNode($child))
            ->toArray();

        $movementCount = JournalEntryLine::where('account_id', $account->id)->count();

        return [
            'id'             => $account->id,
            'code'           => $account->code,
            'name'           => $account->name,
            'type'           => $account->type,
            'level'          => $account->level ?? 1,
            'business_unit'  => $account->businessUnit?->name ?? 'عام',
            'has_movements'  => $movementCount > 0,
            'movement_count' => $movementCount,
            'children'       => $children,
        ];
    }

    public function getAccountTypes(): array
    {
        return [
            'asset'     => ['label' => 'أصول',        'color' => '#2563eb', 'bg' => '#dbeafe'],
            'liability' => ['label' => 'خصوم',        'color' => '#d97706', 'bg' => '#fef3c7'],
            'equity'    => ['label' => 'حقوق ملكية',  'color' => '#7c3aed', 'bg' => '#ede9fe'],
            'revenue'   => ['label' => 'إيرادات',     'color' => '#059669', 'bg' => '#dcfce7'],
            'expense'   => ['label' => 'مصروفات',     'color' => '#dc2626', 'bg' => '#fef2f2'],
        ];
    }

    public function startEdit(int $id): void
    {
        $account        = ChartOfAccount::findOrFail($id);
        $this->editingId = $id;
        $this->editData  = [
            'name'             => $account->name,
            'type'             => $account->type,
            'business_unit_id' => $account->business_unit_id,
            'is_active'        => $account->is_active,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editData  = [];
    }

    public function saveEdit(): void
    {
        $account = ChartOfAccount::findOrFail($this->editingId);
        $account->update($this->editData);
        $this->editingId = null;
        $this->editData  = [];
        Notification::make()->success()->title('تم تحديث الحساب')->send();
    }

    public function toggleAddForm(?int $parentId = null): void
    {
        $this->showAddForm                 = ! $this->showAddForm;
        $this->newAccount['parent_id']     = $parentId;

        if ($parentId) {
            $parent = ChartOfAccount::find($parentId);
            if ($parent) {
                $this->newAccount['type'] = $parent->type;
                $lastChild = ChartOfAccount::where('parent_id', $parentId)
                    ->orderByDesc('code')
                    ->first();
                $this->newAccount['code'] = $lastChild
                    ? (string) ((int) $lastChild->code + 1)
                    : $parent->code . '1';
            }
        }
    }

    public function createAccount(): void
    {
        if (empty($this->newAccount['code']) || empty($this->newAccount['name'])) {
            Notification::make()->danger()->title('الكود والاسم مطلوبين')->send();
            return;
        }

        if (ChartOfAccount::where('code', $this->newAccount['code'])->exists()) {
            Notification::make()->danger()->title('الكود موجود بالفعل')->send();
            return;
        }

        $parent = $this->newAccount['parent_id']
            ? ChartOfAccount::find($this->newAccount['parent_id'])
            : null;

        ChartOfAccount::create([
            'code'             => $this->newAccount['code'],
            'name'             => $this->newAccount['name'],
            'type'             => $this->newAccount['type'],
            'parent_id'        => $this->newAccount['parent_id'],
            'business_unit_id' => $this->newAccount['business_unit_id'] ?: null,
            'level'            => $parent ? ($parent->level + 1) : 1,
            'is_active'        => true,
        ]);

        $this->showAddForm = false;
        $this->newAccount  = [
            'code'             => '',
            'name'             => '',
            'type'             => 'asset',
            'parent_id'        => null,
            'business_unit_id' => null,
        ];

        Notification::make()->success()->title('تم إنشاء الحساب')->send();
    }

    public function archiveAccount(int $id): void
    {
        $account = ChartOfAccount::findOrFail($id);

        $movements = JournalEntryLine::where('account_id', $id)->count();
        if ($movements > 0) {
            Notification::make()->warning()
                ->title('لا يمكن أرشفة هذا الحساب')
                ->body("عليه {$movements} حركة محاسبية. أرشف الحركات أولاً.")
                ->send();
            return;
        }

        $children = ChartOfAccount::where('parent_id', $id)->where('is_active', true)->count();
        if ($children > 0) {
            Notification::make()->warning()
                ->title('لا يمكن أرشفة هذا الحساب')
                ->body("تحته {$children} حساب فرعي نشط.")
                ->send();
            return;
        }

        $account->update(['is_active' => false]);
        Notification::make()->success()->title('تم أرشفة الحساب')->send();
    }

    public function getBusinessUnits(): array
    {
        return BusinessUnit::pluck('name', 'id')->toArray();
    }
}
