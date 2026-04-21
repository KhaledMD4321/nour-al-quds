<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|\UnitEnum|null $navigationGroup = 'الشركات والأصناف';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'المصنّعين';

    protected static ?string $modelLabel = 'مصنّع';

    protected static ?string $pluralModelLabel = 'المصنّعين';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

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
        return ['name', 'representative', 'country'];
    }

    // ─── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات المصنّع')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم المصنّع')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: إيديال ستاندرد'),

                    TextInput::make('country')
                        ->label('بلد المنشأ')
                        ->maxLength(100)
                        ->placeholder('مثال: مصر'),

                    TextInput::make('phone')
                        ->label('التليفون')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('مثال: 0225678900'),

                    TextInput::make('representative')
                        ->label('المندوب')
                        ->maxLength(255)
                        ->placeholder('اسم مندوب المبيعات'),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
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
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                TextColumn::make('name')
                    ->label('المصنّع')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label('البلد')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label('التليفون')
                    ->placeholder('—'),

                TextColumn::make('representative')
                    ->label('المندوب')
                    ->placeholder('—'),

                // TODO: restore when Product model exists (Phase 3.3)
                // TextColumn::make('products_count')
                //     ->label('عدد الأصناف')
                //     ->counts('products')
                //     ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])
            ->defaultSort('name')
            ->striped();
    }

    // ─── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view'   => Pages\ViewCompany::route('/{record}'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
