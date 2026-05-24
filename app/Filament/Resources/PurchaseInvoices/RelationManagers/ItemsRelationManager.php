<?php

namespace App\Filament\Resources\PurchaseInvoices\RelationManagers;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Stock;
use App\Modules\Purchases\PurchaseService;
use App\Services\PurchaseItemsImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'بنود الفاتورة';

    // ── Form ───────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('product_id')
                ->label('الصنف')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set): void {
                    if ($state) {
                        $warehouseId = $this->getOwnerRecord()->warehouse_id;
                        $stock = Stock::where('product_id', $state)
                            ->where('warehouse_id', $warehouseId)
                            ->first();
                        // اقتراح آخر متوسط تكلفة من المخزن
                        if ($stock && (float) $stock->avg_cost > 0) {
                            $set('unit_cost', (float) $stock->avg_cost);
                        }
                    }
                })
                ->columnSpanFull(),

            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->step(0.001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) $state * (float) $get('unit_cost'), 2));
                }),

            TextInput::make('unit_cost')
                ->label('سعر الوحدة')
                ->numeric()
                ->required()
                ->prefix('ج.م.')
                ->minValue(0.0001)
                ->step(0.0001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                    $set('total', round((float) $get('quantity') * (float) $state, 2));
                }),

            TextInput::make('total')
                ->label('الإجمالي')
                ->prefix('ج.م.')
                ->numeric()
                ->disabled()
                ->dehydrated(),

        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('product.name')
                    ->label('الصنف')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(decimalPlaces: 3),

                TextColumn::make('unit_cost')
                    ->label('سعر الوحدة')
                    ->money('EGP'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold'),

                TextColumn::make('landed_cost_share')
                    ->label('نصيب من المصاريف')
                    ->money('EGP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('avg_cost_after')
                    ->label('متوسط التكلفة بعد')
                    ->money('EGP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->headerActions([

                // ① الإضافة الفردية العادية
                CreateAction::make()
                    ->label('إضافة بند')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),

                // ② إضافة سريعة — تبقى المودال مفتوحة بعد الحفظ (createAnother)
                Action::make('quickAdd')
                    ->label('إضافة سريعة')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->form([
                        Select::make('product_id')
                            ->label('الصنف')
                            ->options(fn () => Product::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if ($state) {
                                    $warehouseId = $this->getOwnerRecord()->warehouse_id;
                                    $stock = Stock::where('product_id', $state)
                                        ->where('warehouse_id', $warehouseId)
                                        ->first();
                                    if ($stock && (float) $stock->avg_cost > 0) {
                                        $set('unit_cost', (float) $stock->avg_cost);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(0.001)
                            ->step(0.001)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                                $set('total', round((float) $state * (float) $get('unit_cost'), 2));
                            }),

                        TextInput::make('unit_cost')
                            ->label('سعر الوحدة')
                            ->numeric()
                            ->required()
                            ->prefix('ج.م.')
                            ->minValue(0.0001)
                            ->step(0.0001)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set): void {
                                $set('total', round((float) $get('quantity') * (float) $state, 2));
                            }),

                        TextInput::make('total')
                            ->label('الإجمالي')
                            ->prefix('ج.م.')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->action(function (array $data): void {
                        $invoice = $this->getOwnerRecord();
                        $quantity = (float) ($data['quantity'] ?? 0);
                        $unitCost = (float) ($data['unit_cost'] ?? 0);
                        $productId = $data['product_id'];

                        // دمج مع بند موجود بنفس المنتج
                        $existing = $invoice->items()->where('product_id', $productId)->first();

                        if ($existing) {
                            $newQty = (float) $existing->quantity + $quantity;
                            $newTotal = round($newQty * $unitCost, 2);
                            $existing->update([
                                'quantity' => $newQty,
                                'unit_cost' => $unitCost,
                                'total' => $newTotal,
                            ]);
                        } else {
                            $invoice->items()->create([
                                'product_id' => $productId,
                                'quantity' => $quantity,
                                'unit_cost' => $unitCost,
                                'total' => round($quantity * $unitCost, 2),
                            ]);
                        }

                        app(PurchaseService::class)->recalculateTotals($invoice);

                        Notification::make()
                            ->success()
                            ->title('تم إضافة البند')
                            ->send();
                    }),

                // ③ رفع من Excel/CSV
                Action::make('importFromExcel')
                    ->label('رفع من Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->form([
                        FileUpload::make('file')
                            ->label('ملف Excel أو CSV')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain',
                            ])
                            ->disk('local')
                            ->directory('purchase-imports')
                            ->maxSize(5120)
                            ->required()
                            ->helperText('الأعمدة: اسم الصنف | الكمية | سعر الوحدة (ج.م.)'),
                    ])
                    ->action(function (array $data): void {
                        $invoice = $this->getOwnerRecord();
                        $filePath = $data['file'];

                        if (! $filePath) {
                            Notification::make()->danger()->title('لم يتم اختيار ملف')->send();

                            return;
                        }

                        try {
                            $fullPath = Storage::disk('local')->path($filePath);
                            $result = app(PurchaseItemsImporter::class)->importFromFile($invoice, $fullPath);

                            $msg = "تم استيراد {$result['imported']} بند بنجاح";
                            if (! empty($result['skipped'])) {
                                $msg .= ' — تم تخطي '.count($result['skipped']).' سطر';
                            }

                            Notification::make()
                                ->success()
                                ->title('اكتمل الاستيراد')
                                ->body($msg)
                                ->send();

                            if (! empty($result['skipped'])) {
                                Notification::make()
                                    ->warning()
                                    ->title('سطور تعذّر استيرادها')
                                    ->body(implode("\n", array_slice($result['skipped'], 0, 5)))
                                    ->persistent()
                                    ->send();
                            }

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('فشل الاستيراد')
                                ->body($e->getMessage())
                                ->send();
                        } finally {
                            // تنظيف الملف المؤقت
                            if ($filePath) {
                                Storage::disk('local')->delete($filePath);
                            }
                        }
                    }),

                // ④ تحميل قالب CSV
                Action::make('downloadTemplate')
                    ->label('قالب CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->url(fn (): string => route('purchase-invoices.items-template'))
                    ->openUrlInNewTab(),

                // ⑤ نسخ من فاتورة سابقة
                Action::make('copyFromInvoice')
                    ->label('نسخ من فاتورة سابقة')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft())
                    ->form([
                        Select::make('source_invoice_id')
                            ->label('اختر الفاتورة المصدر')
                            ->options(function (): array {
                                $currentId = $this->getOwnerRecord()->id;

                                return PurchaseInvoice::where('id', '!=', $currentId)
                                    ->orderByDesc('invoice_date')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($inv) => [
                                        $inv->id => "{$inv->reference_number} — ".
                                            ($inv->supplier->name ?? '—').' — '.
                                            $inv->invoice_date,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->helperText('سيتم نسخ الأصناف غير الموجودة في الفاتورة الحالية فقط'),
                    ])
                    ->action(function (array $data): void {
                        $invoice = $this->getOwnerRecord();
                        $source = PurchaseInvoice::find($data['source_invoice_id']);

                        if (! $source) {
                            Notification::make()->danger()->title('الفاتورة غير موجودة')->send();

                            return;
                        }

                        try {
                            $copied = app(PurchaseItemsImporter::class)->copyFromInvoice($invoice, $source);

                            Notification::make()
                                ->success()
                                ->title('تم النسخ')
                                ->body("تم نسخ {$copied} بند من الفاتورة {$source->reference_number}")
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('فشل النسخ')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDraft()),
            ])
            ->emptyStateHeading('لا توجد بنود')
            ->emptyStateDescription('أضف البنود من الأزرار أعلاه — إضافة فردية أو رفع Excel أو نسخ من فاتورة سابقة');
    }

    // ── Mutate ─────────────────────────────────────────────────────────────────

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = round(
            (float) ($data['quantity'] ?? 0) * (float) ($data['unit_cost'] ?? 0),
            2
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['total'] = round(
            (float) ($data['quantity'] ?? 0) * (float) ($data['unit_cost'] ?? 0),
            2
        );

        return $data;
    }

    // ── Recalculate after every change ─────────────────────────────────────────

    protected function afterCreate(): void
    {
        app(PurchaseService::class)->recalculateTotals($this->getOwnerRecord());
    }

    protected function afterSave(): void
    {
        app(PurchaseService::class)->recalculateTotals($this->getOwnerRecord());
    }

    protected function afterDelete(): void
    {
        app(PurchaseService::class)->recalculateTotals($this->getOwnerRecord());
    }
}
