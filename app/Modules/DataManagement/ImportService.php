<?php

namespace App\Modules\DataManagement;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportService
{
    /**
     * نتيجة الاستيراد
     */
    public function parseFile(UploadedFile $file): array
    {
        $rows = Excel::toCollection(new RawImport(), $file)->first() ?? collect();

        return [
            'rows'  => $rows,
            'count' => $rows->count(),
        ];
    }

    /**
     * Validate + Preview — بدون حفظ
     */
    public function validate(string $type, Collection $rows): array
    {
        $valid   = [];
        $invalid = [];

        foreach ($rows as $index => $row) {
            $rowNum  = $index + 2; // +2 لأن الصف 1 هو العنوان
            $rowArr  = $row->toArray();
            $errors  = $this->validateRow($type, $rowArr);

            if (empty($errors)) {
                $valid[] = ['row' => $rowNum, 'data' => $rowArr];
            } else {
                $invalid[] = ['row' => $rowNum, 'data' => $rowArr, 'errors' => $errors];
            }
        }

        return compact('valid', 'invalid');
    }

    /**
     * التنفيذ الفعلي للاستيراد — بعد التحقق
     */
    public function import(string $type, array $validRows, int $userId): array
    {
        $imported = 0;
        $updated  = 0;
        $batchId  = uniqid('import_');

        DB::transaction(function () use ($type, $validRows, $userId, $batchId, &$imported, &$updated) {
            foreach ($validRows as $item) {
                $data = $item['data'];
                $data['_import_batch'] = $batchId;

                $result = match($type) {
                    'customers' => $this->importCustomerRow($data, $userId),
                    'suppliers' => $this->importSupplierRow($data, $userId),
                    'products'  => $this->importProductRow($data, $userId),
                    default     => null,
                };

                if ($result === 'created') $imported++;
                if ($result === 'updated') $updated++;
            }
        });

        Log::info('Import completed', [
            'type'     => $type,
            'imported' => $imported,
            'updated'  => $updated,
            'user_id'  => $userId,
        ]);

        return compact('imported', 'updated');
    }

    // ── Column templates ──────────────────────────────────────────────

    public static function getTemplate(string $type): array
    {
        return match($type) {
            'customers' => ['الكود', 'الاسم', 'التليفون', 'العنوان', 'النوع (individual/company/trader)', 'حد الائتمان', 'الرصيد الافتتاحي', 'خصم1%', 'خصم2%', 'خصم3%'],
            'suppliers' => ['الكود', 'الاسم', 'التليفون', 'العنوان', 'الرصيد الافتتاحي'],
            'products'  => ['الكود', 'الاسم', 'الاسم بالإنجليزي', 'كود المصنّع', 'وحدة القياس (piece/meter/box/set/carton)', 'الحد الأدنى للمخزون'],
            default     => [],
        };
    }

    // ── Validation ────────────────────────────────────────────────────

    private function validateRow(string $type, array $row): array
    {
        $errors = [];

        switch ($type) {
            case 'customers':
                if (empty($row[0])) $errors[] = 'الكود مطلوب';
                if (empty($row[1])) $errors[] = 'الاسم مطلوب';
                if (!empty($row[4]) && !in_array($row[4], ['individual', 'company', 'trader'])) {
                    $errors[] = 'النوع غير صحيح — يجب individual أو company أو trader';
                }
                break;

            case 'suppliers':
                if (empty($row[0])) $errors[] = 'الكود مطلوب';
                if (empty($row[1])) $errors[] = 'الاسم مطلوب';
                break;

            case 'products':
                if (empty($row[0])) $errors[] = 'الكود مطلوب';
                if (empty($row[1])) $errors[] = 'الاسم مطلوب';
                $validUnits = ['piece', 'meter', 'box', 'set', 'carton'];
                if (!empty($row[4]) && !in_array($row[4], $validUnits)) {
                    $errors[] = 'وحدة القياس غير صحيحة';
                }
                break;
        }

        return $errors;
    }

    // ── Import row methods ────────────────────────────────────────────

    private function importCustomerRow(array $row, int $userId): string
    {
        $existing = Customer::withTrashed()->where('code', $row[0])->first();

        $data = [
            'code'              => $row[0],
            'name'              => $row[1],
            'phone'             => $row[2] ?? null,
            'address'           => $row[3] ?? null,
            'type'              => $row[4] ?? 'individual',
            'credit_limit'      => (float) ($row[5] ?? 0),
            'opening_balance'   => (float) ($row[6] ?? 0),
            'default_discount_1'=> (float) ($row[7] ?? 0),
            'default_discount_2'=> (float) ($row[8] ?? 0),
            'default_discount_3'=> (float) ($row[9] ?? 0),
        ];

        if ($existing) {
            $existing->restore();
            $existing->update($data);
            return 'updated';
        }

        Customer::create($data);
        return 'created';
    }

    private function importSupplierRow(array $row, int $userId): string
    {
        $existing = Supplier::withTrashed()->where('code', $row[0])->first();

        $data = [
            'code'            => $row[0],
            'name'            => $row[1],
            'phone'           => $row[2] ?? null,
            'address'         => $row[3] ?? null,
            'opening_balance' => (float) ($row[4] ?? 0),
        ];

        if ($existing) {
            $existing->restore();
            $existing->update($data);
            return 'updated';
        }

        Supplier::create($data);
        return 'created';
    }

    private function importProductRow(array $row, int $userId): string
    {
        $existing = Product::withTrashed()->where('code', $row[0])->first();

        // جلب company_id من كود المصنّع
        $companyId = null;
        if (!empty($row[3])) {
            $company   = Company::where('name', 'like', '%' . $row[3] . '%')->first();
            $companyId = $company?->id;
        }

        $data = [
            'code'            => $row[0],
            'name'            => $row[1],
            'name_en'         => $row[2] ?? null,
            'company_id'      => $companyId,
            'unit_of_measure' => $row[4] ?? 'piece',
            'min_stock_level' => (float) ($row[5] ?? 0),
            'is_active'       => true,
        ];

        if ($existing) {
            $existing->restore();
            $existing->update($data);
            return 'updated';
        }

        Product::create($data);
        return 'created';
    }
}

// ── Inline ToCollection Import class ──────────────────────────────────────────

class RawImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): Collection
    {
        return $rows;
    }
}
