<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use App\Modules\Accounting\AccountingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        /** @var JournalEntry $entry */
        $entry = $this->getRecord();

        $actions = [];

        if (auth()->user()?->isSuperAdmin() || auth()->user()?->can('accounting.journal.reverse')) {
            $actions[] = Action::make('reverse')
                ->label('عكس القيد')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('عكس القيد')
                ->modalDescription(
                    'سيتم إنشاء قيد عكسي لـ #'.$entry->entry_number
                    .' بنفس المبالغ مع عكس المدين والدائن. هل أنت متأكد؟'
                )
                ->action(function () use ($entry) {
                    try {
                        $rev = app(AccountingService::class)->reverseEntry($entry->id, auth()->id());
                        Notification::make()
                            ->success()
                            ->title('تم إنشاء القيد العكسي: '.$rev->entry_number)
                            ->send();
                        $this->redirect(JournalEntryResource::getUrl('view', ['record' => $rev]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('خطأ')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                });
        }

        return $actions;
    }
}
