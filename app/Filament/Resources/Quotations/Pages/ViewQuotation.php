<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Invoice;
use App\Modules\Sales\InvoiceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewQuotation extends ViewRecord
{
    protected static string  $resource = QuotationResource::class;
    protected static ?string $title    = 'تفاصيل عرض السعر';

    protected function getHeaderActions(): array
    {
        /** @var Invoice $quotation */
        $quotation = $this->getRecord();

        return [
            // ── PDF ──────────────────────────────────────────────────────────
            Action::make('pdf')
                ->label('طباعة PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('quotation.pdf', $quotation->id))
                ->openUrlInNewTab(),

            // ── تحويل لفاتورة ────────────────────────────────────────────────
            Action::make('convert')
                ->label('تحويل لفاتورة')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $quotation->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('تحويل عرض السعر لفاتورة')
                ->modalDescription('سيتم إنشاء فاتورة بيع جديدة وإلغاء عرض السعر. هل أنت متأكد؟')
                ->action(function () use ($quotation) {
                    try {
                        $invoice = app(InvoiceService::class)->convertToInvoice($quotation);
                        Notification::make()
                            ->title('تم التحويل بنجاح')
                            ->body('تم إنشاء الفاتورة: ' . $invoice->reference_number)
                            ->success()
                            ->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('خطأ في التحويل')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
