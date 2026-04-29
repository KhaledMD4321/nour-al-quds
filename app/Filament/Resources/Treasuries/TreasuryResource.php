<?php

namespace App\Filament\Resources\Treasuries;

use App\Filament\Resources\Treasuries\Pages\CreateTreasury;
use App\Filament\Resources\Treasuries\Pages\EditTreasury;
use App\Filament\Resources\Treasuries\Pages\ListTreasuries;
use App\Models\BusinessUnit;
use App\Models\ChartOfAccount;
use App\Models\Treasury;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TreasuryResource extends Resource
{
    protected static ?string $model = Treasury::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null   $navigationGroup = 'الخزينة والمالية';
    protected static ?int                    $navigationSort  = 1;
    protected static ?string                 $navigationLabel  = 'الخزائن';
    protected static ?string                 $modelLabel       = 'خزينة';
    protected static ?string                 $pluralModelLabel = 'الخزائن';
    protected static ?string                 $recordTitleAttribute = 'name';

    // ── RBAC ─────────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('finance.treasury.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('finance.treasury.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('finance.treasury.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // الموظف يشوف خزائن وحدته فقط — السوبر أدمن يشوف الكل
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        if (! $user->isSuperAdmin() && $user->business_unit_id) {
            $query->where('business_unit_id', $user->business_unit_id);
        }

        return $query;
    }

    // ── Form ─────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات الخزينة')
                ->schema([
                    Grid::make(2)->schema([

                        TextInput::make('name')
                            ->label('اسم الخزينة')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('مثال: خزينة المعرض الرئيسية'),

                        Select::make('type')
                            ->label('النوع')
                            ->options([
                                'cash' => 'نقدية',
                                'bank' => 'بنك',
                            ])
                            ->required()
                            ->live()
                            ->default('cash'),

                        Select::make('business_unit_id')
                            ->label('الوحدة التابعة لها')
                            ->options(BusinessUnit::pluck('name', 'id'))
                            ->required()
                            ->default(fn () => auth()->user()->business_unit_id)
                            ->disabled(fn () => ! auth()->user()->isSuperAdmin())
                            ->dehydrated()
                            ->live(),

                        Select::make('account_id')
                            ->label('الحساب المحاسبي المرتبط')
                            ->options(function (Get $get): array {
                                $type   = $get('type');
                                $unitId = $get('business_unit_id');

                                // حسابات النقدية والبنوك فقط (تحت 1110)
                                $codes = match ($type) {
                                    'cash' => ['1111', '1112'],
                                    'bank' => ['1113', '1114', '1115'],
                                    default => ['1111', '1112', '1113', '1114', '1115'],
                                };

                                return ChartOfAccount::whereIn('code', $codes)
                                    ->when($unitId, fn ($q) => $q->where(function ($q) use ($unitId) {
                                        $q->where('business_unit_id', $unitId)
                                          ->orWhereNull('business_unit_id');
                                    }))
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->helperText('الحساب من شجرة الحسابات — رصيده يتحدث تلقائياً مع كل حركة'),

                        Toggle::make('is_active')
                            ->label('نشطة')
                            ->default(true),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                ]),

            // عرض الرصيد الحالي (في صفحة التعديل فقط)
            Section::make('الرصيد الحالي')
                ->visible(fn (?Treasury $record) => $record !== null)
                ->schema([
                    Placeholder::make('current_balance_display')
                        ->label('الرصيد')
                        ->content(fn (Treasury $record): string =>
                            number_format((float) $record->current_balance, 2) . ' ج.م'
                        ),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الخزينة')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash'  => 'نقدية',
                        'bank'  => 'بنك',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash'  => 'success',
                        'bank'  => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->sortable(),

                TextColumn::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->money('EGP')
                    ->color(fn ($state): string => (float) $state >= 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->alignment('end'),

                TextColumn::make('account.code')
                    ->label('كود الحساب')
                    ->fontFamily('mono')
                    ->color('gray')
                    ->formatStateUsing(fn ($state, Treasury $record): string =>
                        $state . ' — ' . ($record->account?->name ?? '')
                    ),

                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(['cash' => 'نقدية', 'bank' => 'بنك']),

                SelectFilter::make('business_unit_id')
                    ->label('الوحدة')
                    ->options(BusinessUnit::pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشطة')
                    ->falseLabel('معطّلة')
                    ->placeholder('الكل'),
            ])
            ->actions([
                Action::make('viewTransactions')
                    ->label('كشف الحركة')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->url(fn (Treasury $record): string =>
                        \App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource::getUrl('index') .
                        '?tableFilters[treasury_id][value]=' . $record->id
                    ),

                EditAction::make()->label('تعديل'),
            ])
            ->defaultSort('business_unit_id')
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListTreasuries::route('/'),
            'create' => CreateTreasury::route('/create'),
            'edit'   => EditTreasury::route('/{record}/edit'),
        ];
    }
}
