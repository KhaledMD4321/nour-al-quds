<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceListVersionResource\Pages;
use App\Filament\Resources\PriceListVersionResource\RelationManagers;
use App\Models\Company;
use App\Models\PriceListVersion;
use App\Modules\Catalog\PriceListService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Concerns\HasModuleGuard;
use Filament\Tables\Table;

class PriceListVersionResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'catalog';

    protected static ?string $model = PriceListVersion::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الشركات والأصناف';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'قوائم الأسعار';

    protected static ?string $modelLabel = 'قائمة أسعار';

    protected static ?string $pluralModelLabel = 'قوائم الأسعار';

    protected static ?string $recordTitleAttribute = 'version_number';

    protected static ?int $navigationSort = 4;

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('showroom_manager')
            || $user->hasRole('distribution_manager');
    }

    // ─── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات القائمة')
                ->schema([
                    Select::make('company_id')
                        ->label('المصنّع')
                        ->options(fn (): array => Company::orderBy('name')->pluck('name', 'id')->toArray())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder('اختر المصنّع')
                        ->disabled(fn (?PriceListVersion $record): bool => $record !== null),

                    TextInput::make('version_number')
                        ->label('رقم الإصدار')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('يتولّد تلقائياً'),

                    DatePicker::make('effective_date')
                        ->label('تاريخ السريان')
                        ->required()
                        ->default(now())
                        ->displayFormat('d/m/Y'),

                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active'   => 'نشطة',
                            'archived' => 'مؤرشفة',
                        ])
                        ->default('active')
                        ->disabled()
                        ->dehydrated(),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

        ]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('المصنّع')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version_number')
                    ->label('الإصدار')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (int $state): string => "v{$state}"),

                TextColumn::make('effective_date')
                    ->label('تاريخ السريان')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('عدد الأصناف')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'   => 'نشطة',
                        'archived' => 'مؤرشفة',
                        default    => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'archived' => 'gray',
                        default    => 'gray',
                    }),

                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('المصنّع')
                    ->options(fn (): array => Company::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->placeholder('الكل'),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active'   => 'نشطة',
                        'archived' => 'مؤرشفة',
                    ])
                    ->placeholder('الكل'),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),

                Action::make('archive')
                    ->label('أرشفة')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('أرشفة قائمة الأسعار')
                    ->modalDescription('هل أنت متأكد؟ القائمة هتتأرشف ومش هتظهر في الفواتير الجديدة. الفواتير القديمة مش هتتأثر.')
                    ->action(function (PriceListVersion $record): void {
                        app(PriceListService::class)->archiveVersion($record->id);
                        Notification::make()
                            ->title('تم أرشفة قائمة الأسعار بنجاح')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (PriceListVersion $record): bool => $record->status === 'active'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد قوائم أسعار')
            ->emptyStateDescription('ابدأ بإضافة قائمة أسعار جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    // ─── Relations ─────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceListItemsRelationManager::class,
        ];
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPriceListVersions::route('/'),
            'create' => Pages\CreatePriceListVersion::route('/create'),
            'view'   => Pages\ViewPriceListVersion::route('/{record}'),
            'edit'   => Pages\EditPriceListVersion::route('/{record}/edit'),
        ];
    }
}
