<?php

namespace App\Filament\Widgets;

use App\Services\QuickAccessRegistry;
use Filament\Widgets\Widget;

class QuickAccessBar extends Widget
{
    protected static ?int        $sort       = 0;
    protected int|string|array  $columnSpan = 'full';
    protected string             $view       = 'filament.widgets.quick-access-bar';

    public bool  $showCustomizer = false;
    public array $tempSelection  = [];

    public function getActiveActions(): array
    {
        return QuickAccessRegistry::activeForUser(auth()->user());
    }

    public function getAvailableActions(): array
    {
        return QuickAccessRegistry::forUser(auth()->user());
    }

    public function getCurrentSelection(): array
    {
        $user = auth()->user();
        if ($user->quick_access === null) {
            return array_keys(array_slice(QuickAccessRegistry::forUser($user), 0, 8, true));
        }
        return $user->quick_access;
    }

    public function openCustomizer(): void
    {
        $this->showCustomizer = true;
        $this->tempSelection  = $this->getCurrentSelection();
    }

    public function closeCustomizer(): void
    {
        $this->showCustomizer = false;
        $this->tempSelection  = [];
    }

    public function toggleShortcut(string $key): void
    {
        if (in_array($key, $this->tempSelection)) {
            $this->tempSelection = array_values(array_diff($this->tempSelection, [$key]));
        } else {
            $this->tempSelection[] = $key;
        }
    }

    public function saveCustomization(): void
    {
        $user = auth()->user();
        $user->update(['quick_access' => $this->tempSelection]);
        $this->showCustomizer = false;
        $this->dispatch('$refresh');
    }

    public function resetToDefaults(): void
    {
        $user = auth()->user();
        $user->update(['quick_access' => null]);
        $this->showCustomizer = false;
        $this->dispatch('$refresh');
    }
}
