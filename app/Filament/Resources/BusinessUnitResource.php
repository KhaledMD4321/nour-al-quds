<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessUnitResource\Pages;
use App\Models\BusinessUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BusinessUnitResource extends Resource
{
    protected static ?string $model = BusinessUnit::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'الوحدات التشغيلية';

    protected static ?string $modelLabel = 'وحدة تشغيلية';

    protected static ?string $pluralModelLabel = 'الوحدات التشغيلية';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الوحدة التشغيلية')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الوحدة')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('النوع')
                        ->options([
                            BusinessUnit::TYPE_SHOWROOM => 'معرض',
                            BusinessUnit::TYPE_DISTRIBUTION => 'توزيع',
                        ])
                        ->required()
                        ->native(false),

                    Toggle::make('is_active')
                        ->label('نشطة')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        BusinessUnit::TYPE_SHOWROOM => 'معرض',
                        BusinessUnit::TYPE_DISTRIBUTION => 'توزيع',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        BusinessUnit::TYPE_SHOWROOM => 'info',
                        BusinessUnit::TYPE_DISTRIBUTION => 'warning',
                        default => 'gray',
                    }),

                ToggleColumn::make('is_active')
                    ->label('نشطة'),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('لا توجد وحدات تشغيلية')
            ->emptyStateDescription('ابدأ بإضافة وحدة تشغيلية جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessUnits::route('/'),
            'create' => Pages\CreateBusinessUnit::route('/create'),
            'edit' => Pages\EditBusinessUnit::route('/{record}/edit'),
        ];
    }
}
