<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Company;
use App\Models\LookupType;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الشركات والأصناف';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'الأصناف';

    protected static ?string $modelLabel = 'صنف';

    protected static ?string $pluralModelLabel = 'الأصناف';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

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
        return ['name', 'code', 'company.name', 'category.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'كود'       => $record->code,
            'المصنّع'   => $record->company?->name ?? '—',
            'التصنيف'   => $record->category?->name ?? '—',
        ];
    }

    // ─── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الصنف')
                ->schema([
                    TextInput::make('code')
                        ->label('كود الصنف')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('يُولَّد تلقائياً')
                        ->maxLength(20),

                    TextInput::make('name')
                        ->label('اسم الصنف')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: حوض حمام 60 سم أبيض'),

                    Select::make('company_id')
                        ->label('المصنّع')
                        ->options(fn (): array => Company::orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->placeholder('بدون مصنّع'),

                    Select::make('category_id')
                        ->label('التصنيف')
                        ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->placeholder('بدون تصنيف'),

                    Select::make('unit_of_measure')
                        ->label('وحدة القياس')
                        ->options(fn (): array => LookupType::getOptions('unit_of_measure'))
                        ->default(fn (): ?string => LookupType::getDefault('unit_of_measure'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder('اختر الوحدة'),

                    TextInput::make('list_price')
                        ->label('سعر القائمة (ج.م.)')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->step(0.01)
                        ->prefix('ج.م.'),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('صورة الصنف والملاحظات')
                ->schema([
                    FileUpload::make('image')
                        ->label('صورة الصنف')
                        ->image()
                        ->directory('products')
                        ->visibility('public')
                        ->nullable()
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->collapsed(),
        ]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/product-placeholder.png'))
                    ->width(40)
                    ->height(40),

                TextColumn::make('code')
                    ->label('الكود')
                    ->sortable()
                    ->searchable()
                    ->width(110)
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('الصنف')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('company.name')
                    ->label('المصنّع')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('unit_of_measure')
                    ->label('الوحدة')
                    ->formatStateUsing(fn (?string $state): string =>
                        LookupType::getLabel('unit_of_measure', $state) ?? ($state ?? '—')
                    )
                    ->sortable(),

                TextColumn::make('list_price')
                    ->label('السعر')
                    ->money('EGP', locale: 'ar_EG')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y')
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

                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->placeholder('الكل'),

                SelectFilter::make('unit_of_measure')
                    ->label('وحدة القياس')
                    ->options(fn (): array => LookupType::getOptions('unit_of_measure'))
                    ->placeholder('الكل'),

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
                DeleteAction::make()->label('أرشفة'),
                RestoreAction::make()->label('استعادة'),
                ForceDeleteAction::make()
                    ->label('حذف نهائي')
                    ->visible(fn (): bool => auth()->user()->hasRole('super_admin')),
            ])
            ->defaultSort('name')
            ->striped();
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view'   => Pages\ViewProduct::route('/{record}'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
