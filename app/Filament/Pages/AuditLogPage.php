<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditLogPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null   $navigationGroup = 'الإدارة';
    protected static ?int                    $navigationSort  = 99;
    protected static ?string                 $title           = 'سجل العمليات';
    protected static ?string                 $navigationLabel = 'سجل العمليات';
    protected string                         $view            = 'filament.pages.audit-log';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * آخر 100 عملية مالية مجمّعة ومرتبة بالتاريخ
     */
    public function getRecentOperations(): Collection
    {
        $rows = collect();

        // قيود يومية
        DB::table('journal_entries')
            ->join('users', 'users.id', '=', 'journal_entries.created_by')
            ->whereNull('journal_entries.deleted_at')
            ->select(
                'journal_entries.created_at',
                DB::raw("'قيد يومي'           AS type"),
                'journal_entries.entry_number AS reference',
                DB::raw('NULL                  AS amount'),
                'users.name                   AS user_name',
                'journal_entries.description  AS notes'
            )
            ->orderByDesc('journal_entries.created_at')
            ->limit(20)
            ->each(fn ($r) => $rows->push($r));

        // سندات قبض
        DB::table('receipts')
            ->leftJoin('users', 'users.id', '=', 'receipts.created_by')
            ->whereNull('receipts.deleted_at')
            ->select(
                'receipts.created_at',
                DB::raw("'سند قبض'             AS type"),
                'receipts.receipt_number       AS reference',
                'receipts.amount',
                'users.name                   AS user_name',
                'receipts.notes'
            )
            ->orderByDesc('receipts.created_at')
            ->limit(20)
            ->each(fn ($r) => $rows->push($r));

        // سندات صرف
        DB::table('payments')
            ->leftJoin('users', 'users.id', '=', 'payments.created_by')
            ->whereNull('payments.deleted_at')
            ->select(
                'payments.created_at',
                DB::raw("'سند صرف'             AS type"),
                'payments.payment_number       AS reference',
                'payments.amount',
                'users.name                   AS user_name',
                'payments.notes'
            )
            ->orderByDesc('payments.created_at')
            ->limit(20)
            ->each(fn ($r) => $rows->push($r));

        // شيكات
        DB::table('cheques')
            ->leftJoin('users', 'users.id', '=', 'cheques.created_by')
            ->whereNull('cheques.deleted_at')
            ->select(
                'cheques.created_at',
                DB::raw("'شيك'                AS type"),
                'cheques.cheque_number         AS reference',
                'cheques.amount',
                'users.name                   AS user_name',
                DB::raw("cheques.bank_name     AS notes")
            )
            ->orderByDesc('cheques.created_at')
            ->limit(20)
            ->each(fn ($r) => $rows->push($r));

        // فواتير مبيعات
        DB::table('invoices')
            ->leftJoin('users', 'users.id', '=', 'invoices.created_by')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.type', 'sale')
            ->select(
                'invoices.created_at',
                DB::raw("'فاتورة بيع'          AS type"),
                'invoices.reference_number     AS reference',
                'invoices.total_amount         AS amount',
                'users.name                   AS user_name',
                DB::raw("invoices.status       AS notes")
            )
            ->orderByDesc('invoices.created_at')
            ->limit(20)
            ->each(fn ($r) => $rows->push($r));

        return $rows->sortByDesc('created_at')->values()->take(100);
    }
}
