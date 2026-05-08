<?php

namespace App\Filament\Resources\PurchaseReturns;

use App\Filament\Resources\PurchaseReturns\Pages\CreatePurchaseReturn;
use App\Filament\Resources\PurchaseReturns\Pages\EditPurchaseReturn;
use App\Filament\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Resources\PurchaseReturns\RelationManagers\ItemsRelationManager;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Modules\Sales\ReturnService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Concerns\HasModuleGuard;
use Throwable;

class PurchaseReturnResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'purchases';

    protected static ?string $model = PurchaseReturn::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-arrow-uturn-left';
    protected static string|\UnitEnum|null   $navigationGroup = 'المشتريات';
    protected static ?int                    $navigationSort  = 2;
    protected static ?string                 $navigationLabel  = 'مرتجعات المشتريات';
    protected static ?string                 $modelLabel       = 'مرتجع مشتريات';
    protected static ?string                 $pluralModelLabel = 'مرتجعات المشتريات';
    protected static ?string                 $recordTitleAttribute = 'reference_number';

    // ── Form ─────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات المرتجع')
                ->schema([
                    Grid::make(3)->schema([

                        TextInput::make('reference_number')
                            ->label('رقم المرتجع')
                            ->default(fn () => PurchaseReturn::generateReference())
                            ->disabled()
                            ->dehydrated(),

                        Select::make('purchase_invoice_id')
                            ->label('فاتورة الشراء الأصلية')
                            ->relationship(
                                'purchaseInvoice',
                                'reference_number',
                                fn ($query) => $query->where('status', 'confirmed')
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $inv = PurchaseInvoice::find($state);
                                    if ($inv) {
                                        $set('supplier_id',      $inv->supplier_id);
                                        $set('warehouse_id',     $inv->warehouse_id);
                                        $set('business_unit_id', $inv->business_unit_id);
                                    }
                                }
                            }),

                        DatePicker::make('return_date')
                            ->label('تاريخ المرتجع')
                            ->required()
                            ->default(now()),
                    ]),

                    Grid::make(3)->schema([
                        Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->required(),

                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->required(),

                        Select::make('business_unit_id')
                            ->label('الوحدة التشغيلية')
                            ->relationship('businessUnit', 'name')
                            ->required(),
                    ]),

                    Textarea::make('reason')
                        ->label('سبب المرتجع')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('رقم المرتجع')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('purchaseInvoice.reference_number')
                    ->label('فاتورة الشراء')
                    ->searchable(),

                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('return_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'     => 'مسودة',
                        'confirmed' => 'مؤكد',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'warning',
                        'confirmed' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('createdBy.name')
                    ->label('المحرر')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('تأكيد المرتجع')
                    ->icon('heroicon-o-check-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseReturn $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد مرتجع المشتريات')
                    ->modalDescription('سيتم خصم الكميات من المخزون. هل أنت متأكد؟')
                    ->action(function (PurchaseReturn $record) {
                        try {
                            app(ReturnService::class)->confirmPurchaseReturn($record);
                            Notification::make()
                                ->title('تم تأكيد المرتجع بنجاح')
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('خطأ في التأكيد')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->color('gray')
                    ->visible(fn (PurchaseReturn $record): bool => $record->isDraft())
                    ->url(fn (PurchaseReturn $record): string =>
                        static::getUrl('edit', ['record' => $record])
                    ),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد مرتجعات مشتريات')
            ->emptyStateDescription('ابدأ بإضافة مرتجع جديد.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->striped();
    }

    // ── Relations ─────────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturn::route('/create'),
            'edit'   => EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
