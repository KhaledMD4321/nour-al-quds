<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxRateResource\Pages;
use App\Models\TaxRate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'الضرائب';

    protected static ?string $modelLabel = 'ضريبة';

    protected static ?string $pluralModelLabel = 'الضرائب';

    protected static ?int $navigationSort = 4;

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    // ─── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الضريبة')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الضريبة')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('rate')
                        ->label('النسبة %')
                        ->numeric()
                        ->suffix('%')
                        ->required()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01),

                    Toggle::make('is_default')
                        ->label('الضريبة الافتراضية')
                        ->helperText('يتم تطبيقها تلقائياً على الفواتير الجديدة'),

                    Toggle::make('is_active')
                        ->label('نشطة')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الضريبة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rate')
                    ->label('النسبة')
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('افتراضية')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                ToggleColumn::make('is_active')
                    ->label('نشطة'),
            ])
            ->actions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('لا توجد ضرائب')
            ->emptyStateDescription('ابدأ بإضافة ضريبة جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxRates::route('/'),
            'create' => Pages\CreateTaxRate::route('/create'),
            'edit' => Pages\EditTaxRate::route('/{record}/edit'),
        ];
    }
}
