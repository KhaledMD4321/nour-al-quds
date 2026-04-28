<?php

namespace App\Filament\Resources\Quotations;

use App\Filament\Resources\Quotations\Pages\ListQuotations;
use App\Filament\Resources\Quotations\Pages\ViewQuotation;
use App\Models\Invoice;
use App\Modules\Sales\InvoiceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class QuotationResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-document-magnifying-glass';
    protected static string|\UnitEnum|null   $navigationGroup = 'المبيعات';
    protected static ?int                    $navigationSort  = 5;
    protected static ?string                 $navigationLabel = 'عروض الأسعار';
    protected static ?string                 $modelLabel      = 'عرض سعر';
    protected static ?string                 $pluralModelLabel = 'عروض الأسعار';

    // ── Read-only (العرض ينشأ من صفحة عرض سعر جديد) ─────────────────────────────
    public static function canCreate(): bool        { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    // ── تصفية: عروض الأسعار فقط ─────────────────────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'quotation');
    }

    // ── Form (فارغ — للعرض فقط) ──────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('رقم العرض')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('businessUnit.name')
                    ->label('الوحدة')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('payment_type')
                    ->label('الدفع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash'   => 'نقدي',
                        'credit' => 'آجل',
                        'cheque' => 'شيك',
                        default  => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash'   => 'success',
                        'credit' => 'warning',
                        'cheque' => 'info',
                        default  => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'     => 'نشط',
                        'cancelled' => 'ملغى',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('المحرر')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('today')
                    ->label('اليوم')
                    ->query(fn (Builder $query) => $query->whereDate('invoice_date', today())),

                Filter::make('active')
                    ->label('النشطة فقط')
                    ->query(fn (Builder $query) => $query->where('status', 'draft'))
                    ->default(),
            ])
            ->actions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Invoice $record) => route('quotation.pdf', $record->id))
                    ->openUrlInNewTab(),

                Action::make('convert')
                    ->label('تحويل لفاتورة')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('تحويل عرض السعر لفاتورة')
                    ->modalDescription('سيتم إنشاء فاتورة بيع جديدة وإلغاء عرض السعر. هل أنت متأكد؟')
                    ->action(function (Invoice $record) {
                        try {
                            $invoice = app(InvoiceService::class)->convertToInvoice($record);
                            Notification::make()
                                ->title('تم التحويل بنجاح')
                                ->body('تم إنشاء الفاتورة: ' . $invoice->reference_number)
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('خطأ في التحويل')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view')
                    ->label('تفاصيل')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Invoice $record) => static::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => ListQuotations::route('/'),
            'view'  => ViewQuotation::route('/{record}'),
        ];
    }
}
