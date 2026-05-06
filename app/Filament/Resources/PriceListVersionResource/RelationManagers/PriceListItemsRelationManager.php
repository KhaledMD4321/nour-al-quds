<?php

namespace App\Filament\Resources\PriceListVersionResource\RelationManagers;

use App\Models\Category;
use App\Models\LookupType;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;

class PriceListItemsRelationManager extends RelationManager
{
    protected static string  $relationship        = 'items';
    protected static ?string $title               = 'بنود الأسعار';
    protected static ?string $modelLabel          = 'بند';
    protected static ?string $pluralModelLabel    = 'البنود';

    // ─── Form ──────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('product_id')
                ->label('الصنف')
                // ── server-side search: بيفلتر حسب company + excluded ids ──
                ->searchable()
                ->required()
                ->columnSpanFull()
                ->getSearchResultsUsing(function (string $search): array {
                    $companyId = $this->getOwnerRecord()->company_id;

                    return Product::where('company_id', $companyId)
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->getOptionLabelUsing(fn (mixed $value): ?string =>
                    Product::find($value)?->name
                )
                // ── إضافة صنف جديد خالص من نفس نافذة البند ──
                ->createOptionModalHeading('إضافة صنف جديد')
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('اسم الصنف')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('مثال: حوض معلّق 60 سم'),

                    TextInput::make('notes')
                        ->label('ملاحظات')
                        ->maxLength(500)
                        ->placeholder('اختياري'),

                    // options() مباشرة — مش relationship() — عشان ده form مش مرتبط بـ PriceListItem
                    Select::make('category_id')
                        ->label('التصنيف')
                        ->options(fn (): array =>
                            Category::orderBy('name')->pluck('name', 'id')->toArray()
                        )
                        ->searchable()
                        ->placeholder('اختر التصنيف (اختياري)'),

                    Select::make('unit_of_measure')
                        ->label('وحدة القياس')
                        ->options(fn (): array => LookupType::getOptions('unit_of_measure'))
                        ->default(fn (): ?string => LookupType::getDefault('unit_of_measure'))
                        ->required()
                        ->searchable(),
                ])
                ->createOptionUsing(function (array $data): int {
                    return Product::create([
                        'name'            => $data['name'],
                        'company_id'      => $this->getOwnerRecord()->company_id,
                        'category_id'     => $data['category_id'] ?? null,
                        'unit_of_measure' => $data['unit_of_measure'] ?? 'piece',
                        'notes'           => $data['notes'] ?? null,
                        // code يتولّد تلقائياً من Product::booted()
                    ])->id;
                }),

            TextInput::make('price')
                ->label('السعر')
                ->numeric()
                ->required()
                ->minValue(0.01)
                ->step(0.0001)
                ->prefix('ج.م.'),

        ]);
    }

    // ─── Table ─────────────────────────────────────────────────────────────────

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('product.code')
                    ->label('الكود')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('الصنف')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string =>
                        strlen($column->getState()) > 50 ? $column->getState() : null
                    ),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.unit_of_measure')
                    ->label('الوحدة')
                    ->formatStateUsing(fn (?string $state): string =>
                        LookupType::getLabel('unit_of_measure', $state) ?? ($state ?? '—')
                    ),

            ])
            ->defaultSort('product.name')
            ->paginated([25, 50, 100])
            ->filters([

                Tables\Filters\Filter::make('product_name')
                    ->form([
                        TextInput::make('product_name')
                            ->label('بحث بالاسم')
                            ->placeholder('اكتب جزء من اسم الصنف…'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when(
                            $data['product_name'] ?? null,
                            fn ($q, $name) => $q->whereHas(
                                'product',
                                fn ($pq) => $pq->where('name', 'ILIKE', "%{$name}%")
                            )
                        )
                    )
                    ->indicateUsing(fn (array $data): ?string =>
                        filled($data['product_name'] ?? null)
                            ? 'اسم: ' . $data['product_name']
                            : null
                    ),

            ])
            ->headerActions([
                CreateAction::make()->label('إضافة بند'),
            ])
            ->actions([
                // Edit يعرض السعر فقط — product_id لا يتغير بالتعديل
                EditAction::make()
                    ->label('تعديل السعر')
                    ->form([
                        TextInput::make('price')
                            ->label('السعر الجديد')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.0001)
                            ->prefix('ج.م.'),
                    ]),
                DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('حذف المحدد'),
            ]);
    }
}
