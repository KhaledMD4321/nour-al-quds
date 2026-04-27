<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class NewInvoice extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-plus';
    protected static string|\UnitEnum|null   $navigationGroup = 'المبيعات';
    protected static ?int                    $navigationSort  = 2;
    protected static ?string                 $navigationLabel = 'فاتورة جديدة';
    protected static ?string                 $title           = 'إنشاء فاتورة مبيعات';

    protected string $view = 'filament.pages.new-invoice';
}
