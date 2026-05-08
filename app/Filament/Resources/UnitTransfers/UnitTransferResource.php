<?php

namespace App\Filament\Resources\UnitTransfers;

use App\Filament\Resources\UnitTransfers\Pages\CreateUnitTransfer;
use App\Filament\Resources\UnitTransfers\Pages\EditUnitTransfer;
use App\Filament\Resources\UnitTransfers\Pages\ListUnitTransfers;
use App\Filament\Resources\UnitTransfers\RelationManagers\ItemsRelationManager;
use App\Models\UnitTransfer;
use App\Models\Warehouse;
use App\Modules\Sales\UnitTransferService;
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

class UnitTransferResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'internal_ops';

    protected static ?string $model = UnitTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null   $navigationGroup = 'العمليات الداخلية';
    protected static ?int                    $navigationSort  = 1;
    protected static ?string                 $navigationLabel  = 'التحويلات بين الوحدات';
    protected static ?string                 $modelLabel       = 'تحويل داخلي';
    protected static ?string                 $pluralModelLabel = 'التحويلات الداخلية';
    protected static ?string                 $recordTitleAttribute = 'reference_number';

    // ── Form ─────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات التحويل')
                ->schema([
                    Grid::make(3)->schema([

                        TextInput::make('reference_number')
                            ->label('رقم التحويل')
                            ->default(fn () => UnitTransfer::generateReference())
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('transfer_date')
                            ->label('تاريخ التحويل')
                            ->required()
                            ->default(now()),

                        Select::make('transfer_price_type')
                            ->label('أساس التسعير')
                            ->options([
                                'avg_cost'   => 'متوسط التكلفة',
                                'list_price' => 'سعر القائمة',
                                'custom'     => 'سعر مخصص',
                            ])
                            ->required()
                            ->default('avg_cost'),
                    ]),
                ]),

            Section::make('الوحدة المصدر')
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('from_business_unit_id')
                            ->label('الوحدة المصدر')
                            ->relationship('fromBusinessUnit', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('from_warehouse_id', null);
                            }),

                        Select::make('from_warehouse_id')
                            ->label('مخزن المصدر')
                            ->required()
                            ->options(function (callable $get): array {
                                $unitId = $get('from_business_unit_id');
                                if (! $unitId) return [];

                                return Warehouse::where('business_unit_id', $unitId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->live(),
                    ]),
                ]),

            Section::make('الوحدة الوجهة')
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('to_business_unit_id')
                            ->label('الوحدة الوجهة')
                            ->relationship('toBusinessUnit', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('to_warehouse_id', null);
                            }),

                        Select::make('to_warehouse_id')
                            ->label('مخزن الوجهة')
                            ->required()
                            ->options(function (callable $get): array {
                                $unitId = $get('to_business_unit_id');
                                if (! $unitId) return [];

                                return Warehouse::where('business_unit_id', $unitId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),
                    ]),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('رقم التحويل')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('fromBusinessUnit.name')
                    ->label('من')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('toBusinessUnit.name')
                    ->label('إلى')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('transfer_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('transfer_price_type')
                    ->label('أساس التسعير')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'avg_cost'   => 'متوسط التكلفة',
                        'list_price' => 'سعر القائمة',
                        'custom'     => 'مخصص',
                        default      => $state,
                    })
                    ->badge()
                    ->color('info'),

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
                        'confirmed' => 'success',
                        default     => 'gray',
                    }),

                TextColumn::make('createdBy.name')
                    ->label('المحرر')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('تأكيد التحويل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UnitTransfer $record): bool => $record->isDraft())
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد التحويل الداخلي')
                    ->modalDescription('سيتم توليد فاتورة بيع وفاتورة شراء داخليتين وتحديث المخزون. هل أنت متأكد؟')
                    ->action(function (UnitTransfer $record) {
                        try {
                            app(UnitTransferService::class)->confirmTransfer($record);
                            Notification::make()
                                ->title('تم تأكيد التحويل بنجاح')
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
                    ->visible(fn (UnitTransfer $record): bool => $record->isDraft())
                    ->url(fn (UnitTransfer $record): string =>
                        static::getUrl('edit', ['record' => $record])
                    ),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('لا توجد تحويلات')
            ->emptyStateDescription('ابدأ بإضافة تحويل جديد.')
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
            'index'  => ListUnitTransfers::route('/'),
            'create' => CreateUnitTransfer::route('/create'),
            'edit'   => EditUnitTransfer::route('/{record}/edit'),
        ];
    }
}
