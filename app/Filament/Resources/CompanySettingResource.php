<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettingResource\Pages;
use App\Models\CompanySetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanySettingResource extends Resource
{
    protected static ?string $model = CompanySetting::class;

    // Filament 5.5 types
    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'إعدادات الشركة';

    protected static ?string $modelLabel = 'إعدادات الشركة';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الشركة')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الشركة')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    FileUpload::make('logo')
                        ->label('الشعار')
                        ->image()
                        ->directory('company')
                        ->visibility('public')
                        ->columnSpanFull(),

                    Textarea::make('address')
                        ->label('العنوان')
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->maxLength(50),

                    TextInput::make('tax_number')
                        ->label('الرقم الضريبي')
                        ->maxLength(100),

                    TextInput::make('default_currency')
                        ->label('العملة الافتراضية')
                        ->default('EGP')
                        ->maxLength(10)
                        ->required(),
                ])
                ->columns(2),

            Section::make('إعدادات الفاتورة')
                ->schema([
                    Textarea::make('invoice_header')
                        ->label('ترويسة الفاتورة')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('invoice_footer')
                        ->label('شروط البيع')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditCompanySetting::route('/'),
        ];
    }
}
