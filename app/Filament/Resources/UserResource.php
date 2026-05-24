<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\BusinessUnit;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('settings.users');
    }

    public static function form(Schema $schema): Schema
    {
        $isCreate = $schema->getOperation() === 'create';

        return $schema->components([
            Section::make('بيانات المستخدم')
                ->schema([
                    TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email', ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->revealable()
                        ->required($isCreate)
                        ->nullable(! $isCreate)
                        ->minLength(8)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->maxLength(255),

                    Select::make('business_unit_id')
                        ->label('الوحدة التشغيلية')
                        ->options(
                            BusinessUnit::active()->pluck('name', 'id')->toArray()
                        )
                        ->nullable()
                        ->placeholder('الإدارة العليا - كل الوحدات')
                        ->native(false)
                        ->searchable(),

                    Select::make('roles')
                        ->label('الدور')
                        ->options(
                            Role::pluck('name', 'name')
                                ->mapWithKeys(fn ($name, $key) => [$key => __(self::roleLabel($name))])
                                ->toArray()
                        )
                        ->required()
                        ->native(false)
                        ->dehydrated(false) // handled in afterSave
                        ->afterStateHydrated(function (Select $component, $record): void {
                            if ($record) {
                                $component->state($record->roles->first()?->name);
                            }
                        }),

                    Toggle::make('is_active')
                        ->label('نشط')
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
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->placeholder('الإدارة العليا')
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::roleLabel($state))
                    ->color('primary'),

                ToggleColumn::make('is_active')
                    ->label('نشط'),
            ])
            ->filters([
                SelectFilter::make('business_unit_id')
                    ->label('الوحدة')
                    ->options(BusinessUnit::pluck('name', 'id')->toArray())
                    ->placeholder('كل الوحدات'),

                SelectFilter::make('roles')
                    ->label('الدور')
                    ->options(
                        Role::pluck('name', 'name')
                            ->mapWithKeys(fn ($name, $key) => [$key => self::roleLabel($name)])
                            ->toArray()
                    )
                    ->query(fn ($query, array $data) => isset($data['value']) && $data['value']
                        ? $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']))
                        : $query
                    ),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('موقوف فقط'),
            ])
            ->actions([
                EditAction::make()
                    ->label('تعديل')
                    ->hidden(fn (User $record): bool => $record->id === 1 && auth()->id() !== 1),

                DeleteAction::make()
                    ->label('حذف')
                    ->hidden(fn (User $record): bool => $record->id === 1),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('لا يوجد مستخدمون')
            ->emptyStateDescription('ابدأ بإضافة مستخدم جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /** Map role code → Arabic label */
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'الإدارة العُليا',
            'showroom_manager' => 'مدير المعرض',
            'showroom_cashier' => 'كاشير المعرض',
            'distribution_manager' => 'مدير التوزيع',
            'distribution_accountant' => 'محاسب التوزيع',
            'warehouse_keeper' => 'أمين المخزن',
            default => $role,
        };
    }
}
