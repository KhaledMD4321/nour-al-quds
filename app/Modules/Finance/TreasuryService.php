<?php

namespace App\Modules\Finance;

use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TreasuryService
{
    /**
     * إضافة أموال للخزينة (مقبوض / تحويل وارد)
     *
     * بيتنادى من: QuickSaleService, Receipts, Cheque collection, TreasuryTransfer
     */
    public function addFunds(
        int     $treasuryId,
        float   $amount,
        string  $description,
        ?string $referenceType   = null,
        ?int    $referenceId     = null,
        ?int    $createdBy       = null,
        ?string $transactionDate = null,
        string  $type            = 'receipt'
    ): TreasuryTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('المبلغ لازم يكون أكبر من صفر');
        }

        return DB::transaction(function () use (
            $treasuryId, $amount, $description,
            $referenceType, $referenceId, $createdBy, $transactionDate, $type
        ) {
            $treasury = Treasury::lockForUpdate()->findOrFail($treasuryId);

            if (! $treasury->is_active) {
                throw new RuntimeException("الخزينة \"{$treasury->name}\" غير نشطة");
            }

            $newBalance = (float) $treasury->current_balance + $amount;
            $treasury->update(['current_balance' => round($newBalance, 2)]);

            return TreasuryTransaction::create([
                'treasury_id'      => $treasury->id,
                'type'             => $type,
                'amount'           => $amount,
                'balance_after'    => round($newBalance, 2),
                'transaction_date' => $transactionDate ?? today()->toDateString(),
                'description'      => $description,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'created_by'       => $createdBy ?? Auth::id(),
            ]);
        });
    }

    /**
     * خصم من الخزينة (مدفوع / تحويل صادر)
     *
     * بيتنادى من: Payments, Cheque issuance, TreasuryTransfer
     */
    public function deductFunds(
        int     $treasuryId,
        float   $amount,
        string  $description,
        ?string $referenceType   = null,
        ?int    $referenceId     = null,
        ?int    $createdBy       = null,
        ?string $transactionDate = null,
        string  $type            = 'payment'
    ): TreasuryTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('المبلغ لازم يكون أكبر من صفر');
        }

        return DB::transaction(function () use (
            $treasuryId, $amount, $description,
            $referenceType, $referenceId, $createdBy, $transactionDate, $type
        ) {
            $treasury = Treasury::lockForUpdate()->findOrFail($treasuryId);

            if (! $treasury->is_active) {
                throw new RuntimeException("الخزينة \"{$treasury->name}\" غير نشطة");
            }

            if ((float) $treasury->current_balance < $amount) {
                throw new RuntimeException(
                    "رصيد الخزينة \"{$treasury->name}\" غير كافٍ. " .
                    "الرصيد الحالي: " . number_format((float) $treasury->current_balance, 2) . " ج.م، " .
                    "المطلوب: " . number_format($amount, 2) . " ج.م"
                );
            }

            $newBalance = (float) $treasury->current_balance - $amount;
            $treasury->update(['current_balance' => round($newBalance, 2)]);

            return TreasuryTransaction::create([
                'treasury_id'      => $treasury->id,
                'type'             => $type,
                'amount'           => $amount,
                'balance_after'    => round($newBalance, 2),
                'transaction_date' => $transactionDate ?? today()->toDateString(),
                'description'      => $description,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'created_by'       => $createdBy ?? Auth::id(),
            ]);
        });
    }

    /**
     * تحويل بين خزينتين
     *
     * بيولّد حركتين متربطتين: transfer_out من المصدر + transfer_in للهدف
     * كلهم في DB::transaction واحد مع lockForUpdate على الاتنين.
     *
     * @return array{out: TreasuryTransaction, in: TreasuryTransaction}
     */
    public function transfer(
        int     $fromTreasuryId,
        int     $toTreasuryId,
        float   $amount,
        string  $description,
        ?int    $createdBy       = null,
        ?string $transactionDate = null
    ): array {
        if ($fromTreasuryId === $toTreasuryId) {
            throw new InvalidArgumentException('ما ينفعش تحويل من الخزينة لنفسها');
        }

        return DB::transaction(function () use (
            $fromTreasuryId, $toTreasuryId, $amount, $description, $createdBy, $transactionDate
        ) {
            $out = $this->deductFunds(
                treasuryId:      $fromTreasuryId,
                amount:          $amount,
                description:     'تحويل صادر: ' . $description,
                referenceType:   TreasuryTransaction::class,
                referenceId:     null, // هيتحدث بعد ما نعمل الـ in
                createdBy:       $createdBy,
                transactionDate: $transactionDate,
                type:            'transfer_out',
            );

            $in = $this->addFunds(
                treasuryId:      $toTreasuryId,
                amount:          $amount,
                description:     'تحويل وارد: ' . $description,
                referenceType:   TreasuryTransaction::class,
                referenceId:     $out->id, // ربط الواردة بالصادرة
                createdBy:       $createdBy,
                transactionDate: $transactionDate,
                type:            'transfer_in',
            );

            // ربط الحركة الصادرة بالواردة (تحديث reference_id)
            $out->update(['reference_id' => $in->id]);

            return ['out' => $out, 'in' => $in];
        });
    }

    /**
     * الرصيد اللحظي لخزينة
     */
    public function getBalance(int $treasuryId): float
    {
        return (float) Treasury::findOrFail($treasuryId)->current_balance;
    }
}
