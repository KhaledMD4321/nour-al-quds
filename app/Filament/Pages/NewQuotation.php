<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class NewQuotation extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'عرض سعر جديد';

    protected static ?string $title = 'إنشاء عرض سعر';

    protected string $view = 'filament.pages.new-quotation';
}
