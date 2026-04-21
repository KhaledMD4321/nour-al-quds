<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ListChartOfAccounts extends Page
{
    protected static string $resource = ChartOfAccountResource::class;

    protected static ?string $title = 'شجرة الحسابات';

    protected string $view = 'filament.resources.chart-of-account-resource.list-chart-of-accounts';

    /** IDs of currently expanded accounts */
    public array $expandedIds = [];

    // Note: authorization is handled automatically by the
    // mountCanAuthorizeResourceAccess() Livewire trait hook (CanAuthorizeResourceAccess).
    // No manual mount() override is needed here.

    // ─── Toggle ────────────────────────────────────────────────────────────────

    public function toggleExpand(int $id): void
    {
        if (in_array($id, $this->expandedIds)) {
            $this->expandedIds = array_values(
                array_filter($this->expandedIds, fn (int $i): bool => $i !== $id)
            );
        } else {
            $this->expandedIds[] = $id;
        }
    }

    public function expandAll(): void
    {
        $this->expandedIds = ChartOfAccount::whereHas('children')
            ->pluck('id')
            ->toArray();
    }

    public function collapseAll(): void
    {
        $this->expandedIds = [];
    }

    // ─── Data ──────────────────────────────────────────────────────────────────

    /**
     * Build the visible flat list from the full accounts tree.
     * An account is visible when it is a root OR when its parent is both
     * visible and expanded.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getVisibleAccounts(): array
    {
        $allAccounts = ChartOfAccount::with('businessUnit')
            ->orderBy('code')
            ->get();

        // All IDs that are somebody's parent (i.e. the account has children)
        $parentIds = $allAccounts
            ->pluck('parent_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $visible    = [];
        $visibleIds = [];          // tracks which IDs made it into the visible list

        foreach ($allAccounts as $account) {
            $isRoot        = $account->parent_id === null;
            $parentVisible = in_array($account->parent_id, $visibleIds);
            $parentExpanded = in_array($account->parent_id, $this->expandedIds);

            if ($isRoot || ($parentVisible && $parentExpanded)) {
                $visibleIds[] = $account->id;

                $visible[] = [
                    'id'          => $account->id,
                    'code'        => $account->code,
                    'name'        => $account->name,
                    'type'        => $account->type,
                    'level'       => $account->level,
                    'parent_id'   => $account->parent_id,
                    'has_children'=> in_array($account->id, $parentIds),
                    'is_expanded' => in_array($account->id, $this->expandedIds),
                    'is_active'   => $account->is_active,
                    'unit_name'   => $account->businessUnit?->name,
                ];
            }
        }

        return $visible;
    }

    // ─── Header actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('إضافة حساب')
                ->icon('heroicon-o-plus')
                ->url(ChartOfAccountResource::getUrl('create')),
        ];
    }
}
