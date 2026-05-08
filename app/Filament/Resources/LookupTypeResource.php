<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LookupTypeResource\Pages;
use App\Models\LookupType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LookupTypeResource extends Resource
{
    protected static ?string $model = LookupType::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'القوائم الديناميكية';

    protected static ?string $modelLabel = 'قائمة';

    protected static ?string $pluralModelLabel = 'القوائم الديناميكية';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    // ─── Access & Permissions ──────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    /** Prevent deletion of system-level lookup types. */
    public static function canDelete(Model $record): bool
    {
        return ! $record->is_system;
    }

    // ─── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات القائمة')
                ->schema([
                    TextInput::make('code')
                        ->label('الكود')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?LookupType $record): bool => $record?->is_system ?? false)
                        ->helperText('الكود البرمجي — لا يتغير بعد الإنشاء')
                        ->placeholder('مثال: unit_of_measure')
                        ->maxLength(50),

                    TextInput::make('name')
                        ->label('الاسم بالعربي')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('مثال: وحدة القياس'),

                    Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('شرح مختصر لاستخدام هذه القائمة'),
                ])
                ->columns(2),

            Section::make('القيم')
                ->schema([
                    Repeater::make('values')
                        ->relationship()
                        ->label('')
                        ->schema([
                            TextInput::make('code')
                                ->label('الكود')
                                ->required()
                                ->maxLength(50)
                                ->placeholder('piece'),

                            TextInput::make('label')
                                ->label('الاسم بالعربي')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('قطعة'),

                            TextInput::make('sort_order')
                                ->label('الترتيب')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Toggle::make('is_active')
                                ->label('نشط')
                                ->default(true),

                            Toggle::make('is_default')
                                ->label('افتراضي'),
                        ])
                        ->columns(5)
                        ->defaultItems(1)
                        ->addActionLabel('إضافة قيمة جديدة')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                ]),

        ]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('القائمة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('values_count')
                    ->label('عدد القيم')
                    ->counts('values')
                    ->sortable(),

                IconColumn::make('is_system')
                    ->label('نظامي')
                    ->boolean()
                    ->trueIcon('heroicon-s-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make()->label('تعديل'),

                // Safe delete — system types are protected
                DeleteAction::make()
                    ->label('حذف')
                    ->action(function (LookupType $record): void {
                        if ($record->is_system) {
                            Notification::make()
                                ->title('مش ممكن تحذف قائمة نظامية')
                                ->danger()
                                ->send();
                            return;
                        }
                        $record->delete();
                        Notification::make()
                            ->title('تم حذف القائمة')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('لا توجد قيم مرجعية')
            ->emptyStateDescription('ابدأ بإضافة قيمة جديدة.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLookupTypes::route('/'),
            'create' => Pages\CreateLookupType::route('/create'),
            'edit'   => Pages\EditLookupType::route('/{record}/edit'),
        ];
    }
}
