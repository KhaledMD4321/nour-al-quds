<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class QuickSalePage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'بيع سريع';

    protected static ?string $title = 'بيع سريع';

    protected string $view = 'filament.pages.quick-sale';
}
