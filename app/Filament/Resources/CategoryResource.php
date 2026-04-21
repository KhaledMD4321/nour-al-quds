<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الشركات والأصناف';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'التصنيفات';

    protected static ?string $modelLabel = 'تصنيف';

    protected static ?string $pluralModelLabel = 'التصنيفات';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    // ─── Access ────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || $user->hasRole('showroom_manager')
            || $user->hasRole('distribution_manager');
    }

    // ─── Global Search ─────────────────────────────────────────────────────────

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    // ─── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات التصنيف')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم التصنيف')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: أحواض')
                        ->columnSpanFull(),

                    Select::make('parent_id')
                        ->label('التصنيف الأب')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('بدون — تصنيف رئيسي')
                        ->nullable(),

                    TextInput::make('sort_order')
                        ->label('ترتيب العرض')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Toggle::make('is_active')
                        ->label('نشط')
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
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                TextColumn::make('name')
                    ->label('التصنيف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label('التصنيف الأب')
                    ->placeholder('رئيسي')
                    ->sortable(),

                TextColumn::make('full_path')
                    ->label('المسار الكامل')
                    ->getStateUsing(fn (Category $record): string => $record->full_path)
                    ->toggleable(isToggledHiddenByDefault: true),

                // TODO: restore when Product model exists (Phase 3.3)
                // TextColumn::make('products_count')
                //     ->label('عدد الأصناف')
                //     ->counts('products')
                //     ->sortable(),

                TextColumn::make('children_count')
                    ->label('تصنيفات فرعية')
                    ->counts('children')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('التصنيف الأب')
                    ->relationship('parent', 'name')
                    ->placeholder('الكل')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),

                TrashedFilter::make()
                    ->label('المؤرشف'),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),

                // Safe soft-delete: catch the booted() guard exception
                DeleteAction::make()
                    ->label('أرشفة')
                    ->action(function (Category $record): void {
                        try {
                            $record->delete();
                            Notification::make()
                                ->title('تم أرشفة التصنيف بنجاح')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                RestoreAction::make()->label('استعادة'),

                ForceDeleteAction::make()
                    ->label('حذف نهائي')
                    ->visible(fn (): bool => auth()->user()->hasRole('super_admin')),
            ])
            ->defaultSort('sort_order', 'asc')
            ->striped();
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view'   => Pages\ViewCategory::route('/{record}'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
