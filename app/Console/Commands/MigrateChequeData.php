<?php

namespace App\Console\Commands;

use App\Models\Cheque;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Console\Command;

class MigrateChequeData extends Command
{
    protected $signature = 'cheques:migrate-data';

    protected $description = 'ترحيل بيانات الشيكات من cheque_details في receipts/payments إلى جدول cheques';

    public function handle(): int
    {
        $this->info('ترحيل شيكات من سندات القبض...');
        $receipts = Receipt::where('payment_method', 'cheque')
            ->whereNotNull('cheque_details')
            ->get();

        $receiptsCount = 0;
        foreach ($receipts as $r) {
            if (Cheque::where('receipt_id', $r->id)->exists()) {
                $this->line("  ↷ تجاهل — مرحَّل مسبقاً: {$r->receipt_number}");

                continue;
            }

            $details = is_array($r->cheque_details)
                ? $r->cheque_details
                : json_decode($r->cheque_details, true) ?? [];

            Cheque::create([
                'cheque_number' => $details['cheque_number'] ?? 'N/A',
                'bank_name' => $details['bank_name'] ?? 'N/A',
                'amount' => $r->amount,
                'issue_date' => $r->receipt_date,
                'due_date' => $details['due_date'] ?? $r->receipt_date,
                'direction' => 'incoming',
                'status' => 'pending',
                'customer_id' => $r->customer_id,
                'business_unit_id' => $r->business_unit_id,
                'receipt_id' => $r->id,
                'created_by' => $r->created_by,
            ]);

            $this->line("  ← سند قبض {$r->receipt_number}");
            $receiptsCount++;
        }

        $this->info("تم ترحيل {$receiptsCount} شيك وارد.");
        $this->newLine();

        $this->info('ترحيل شيكات من سندات الصرف...');
        $payments = Payment::where('payment_method', 'cheque')
            ->whereNotNull('cheque_details')
            ->get();

        $paymentsCount = 0;
        foreach ($payments as $p) {
            if (Cheque::where('payment_id', $p->id)->exists()) {
                $this->line("  ↷ تجاهل — مرحَّل مسبقاً: {$p->payment_number}");

                continue;
            }

            $details = is_array($p->cheque_details)
                ? $p->cheque_details
                : json_decode($p->cheque_details, true) ?? [];

            Cheque::create([
                'cheque_number' => $details['cheque_number'] ?? 'N/A',
                'bank_name' => $details['bank_name'] ?? 'N/A',
                'amount' => $p->amount,
                'issue_date' => $p->payment_date,
                'due_date' => $details['due_date'] ?? $p->payment_date,
                'direction' => 'outgoing',
                'status' => 'pending',
                'supplier_id' => $p->supplier_id,
                'business_unit_id' => $p->business_unit_id,
                'payment_id' => $p->id,
                'created_by' => $p->created_by,
            ]);

            $this->line("  ← سند صرف {$p->payment_number}");
            $paymentsCount++;
        }

        $this->info("تم ترحيل {$paymentsCount} شيك صادر.");
        $this->newLine();
        $this->info('إجمالي الشيكات في قاعدة البيانات: '.Cheque::count());

        return self::SUCCESS;
    }
}
