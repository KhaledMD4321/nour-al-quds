<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Pages\SaleReturn;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Modules\Sales\InvoiceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'تفاصيل الفاتورة';

    protected function getHeaderActions(): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->getRecord();

        return [
            // ── مرتجع ────────────────────────────────────────────────────────
            Action::make('return')
                ->label('إنشاء مرتجع')
                ->icon('heroicon-o-arrow-uturn-right')
                ->color('warning')
                ->visible(fn () => $invoice->type === 'sale' &&
                    in_array($invoice->status, ['confirmed', 'delivered', 'partially_paid', 'paid'])
                )
                ->url(fn () => SaleReturn::getUrl(['invoice' => $invoice->id])),

            // ── PDF ──────────────────────────────────────────────────────────
            Action::make('pdf')
                ->label('طباعة PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('invoice.pdf', $invoice->id))
                ->openUrlInNewTab(),

            // ── تأكيد ────────────────────────────────────────────────────────
            Action::make('confirm')
                ->label('تأكيد الفاتورة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $invoice->isDraft())
                ->requiresConfirmation()
                ->modalHeading('تأكيد الفاتورة')
                ->modalDescription('سيتم خصم الكميات من المخزون. هل أنت متأكد؟')
                ->action(function () use ($invoice) {
                    try {
                        app(InvoiceService::class)->confirmInvoice($invoice);
                        $this->refreshFormData(['status']);
                        Notification::make()
                            ->title('تم تأكيد الفاتورة بنجاح')
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

            // ── إلغاء ────────────────────────────────────────────────────────
            Action::make('cancel')
                ->label('إلغاء الفاتورة')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ! $invoice->isCancelled() && ! in_array($invoice->status, ['partially_paid', 'paid']))
                ->requiresConfirmation()
                ->modalHeading('إلغاء الفاتورة')
                ->modalDescription('لا يمكن التراجع عن الإلغاء. هل أنت متأكد؟')
                ->action(function () use ($invoice) {
                    try {
                        app(InvoiceService::class)->cancelInvoice($invoice);
                        $this->refreshFormData(['status']);
                        Notification::make()
                            ->title('تم إلغاء الفاتورة')
                            ->warning()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('خطأ في الإلغاء')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
