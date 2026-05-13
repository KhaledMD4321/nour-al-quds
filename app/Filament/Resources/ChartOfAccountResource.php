<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChartOfAccountResource\Pages;
use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Concerns\HasModuleGuard;
use Filament\Tables\Table;

class ChartOfAccountResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'accounting';

    // Hidden from nav — replaced by ChartOfAccountsTree page
    public static function shouldRegisterNavigation(): bool { return false; }

    protected static ?string $model = ChartOfAccount::class;

    protected static string|\UnitEnum|null $navigationGroup = 'المحاسبة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'شجرة الحسابات';

    protected static ?string $modelLabel = 'حساب';

    protected static ?string $pluralModelLabel = 'شجرة الحسابات';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->hasRole('super_admin')
            || $user->hasPermissionTo('accounting.journal');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الحساب')
                ->schema([
                    TextInput::make('code')
                        ->label('كود الحساب')
                        ->required()
                        ->unique(ChartOfAccount::class, 'code', ignoreRecord: true)
                        ->maxLength(20),

                    TextInput::make('name')
                        ->label('اسم الحساب')
                        ->required()
                        ->maxLength(255),

                    Select::make('type')
                        ->label('النوع')
                        ->options(self::typeOptions())
                        ->required()
                        ->native(false),

                    Select::make('parent_id')
                        ->label('الحساب الأب')
                        ->options(
                            ChartOfAccount::orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($a) => [$a->id => "{$a->code} — {$a->name}"])
                                ->toArray()
                        )
                        ->nullable()
                        ->placeholder('حساب رئيسي')
                        ->searchable()
                        ->native(false),

                    Select::make('business_unit_id')
                        ->label('الوحدة التشغيلية')
                        ->options(BusinessUnit::active()->pluck('name', 'id')->toArray())
                        ->nullable()
                        ->placeholder('عام - كل الوحدات')
                        ->native(false),

                    TextInput::make('level')
                        ->label('المستوى')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(4)
                        ->default(1)
                        ->required(),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('اسم الحساب')
                    ->searchable()
                    ->formatStateUsing(function (string $state, ChartOfAccount $record): string {
                        $indent = str_repeat('— ', max(0, $record->level - 1));
                        return $indent . $state;
                    }),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::typeLabel($state))
                    ->color(fn (string $state): string => self::typeColor($state)),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->placeholder('عام')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('نشط'),
            ])
            ->defaultSort('code', 'asc')
            ->emptyStateHeading('لا توجد حسابات')
            ->emptyStateDescription('ابدأ بإضافة حساب جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(self::typeOptions()),

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة')
                    ->options(BusinessUnit::pluck('name', 'id')->toArray())
                    ->placeholder('كل الوحدات'),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('موقوف فقط'),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit'   => Pages\EditChartOfAccount::route('/{record}/edit'),
        ];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public static function typeOptions(): array
    {
        return [
            ChartOfAccount::TYPE_ASSET     => 'أصول',
            ChartOfAccount::TYPE_LIABILITY => 'خصوم',
            ChartOfAccount::TYPE_EQUITY    => 'حقوق ملكية',
            ChartOfAccount::TYPE_REVENUE   => 'إيرادات',
            ChartOfAccount::TYPE_EXPENSE   => 'مصروفات',
        ];
    }

    public static function typeLabel(string $type): string
    {
        return self::typeOptions()[$type] ?? $type;
    }

    public static function typeColor(string $type): string
    {
        return match ($type) {
            ChartOfAccount::TYPE_ASSET     => 'info',
            ChartOfAccount::TYPE_LIABILITY => 'danger',
            ChartOfAccount::TYPE_EQUITY    => 'warning',
            ChartOfAccount::TYPE_REVENUE   => 'success',
            ChartOfAccount::TYPE_EXPENSE   => 'gray',
            default                        => 'gray',
        };
    }
}
