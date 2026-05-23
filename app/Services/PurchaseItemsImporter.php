<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Modules\Purchases\PurchaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchaseItemsImporter
{
    /**
     * استيراد بنود من ملف Excel/CSV إلى فاتورة مشتريات
     *
     * الأعمدة المتوقعة (بالترتيب أو بالاسم):
     *   product_name | barcode | quantity | unit_cost
     *
     * @throws \Exception
     */
    public function importFromFile(PurchaseInvoice $invoice, string $filePath): array
    {
        $rows = $this->parseFile($filePath);

        if ($rows->isEmpty()) {
            throw new \Exception('الملف فارغ أو لا يحتوي على بيانات صالحة');
        }

        $imported = 0;
        $skipped  = [];

        DB::transaction(function () use ($invoice, $rows, &$imported, &$skipped) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // +2 لأن السطر الأول header

                // ابحث عن المنتج بالاسم أو بالكود
                $product = $this->resolveProduct($row);
                if (! $product) {
                    $skipped[] = "سطر {$rowNum}: المنتج غير موجود — \"{$row['product_name']}\"";
                    continue;
                }

                $quantity = (float) ($row['quantity'] ?? 0);
                $unitCost = (float) ($row['unit_cost'] ?? 0);

                if ($quantity <= 0) {
                    $skipped[] = "سطر {$rowNum}: الكمية يجب أن تكون أكبر من صفر";
                    continue;
                }

                if ($unitCost <= 0) {
                    $skipped[] = "سطر {$rowNum}: سعر الوحدة يجب أن يكون أكبر من صفر";
                    continue;
                }

                // إذا كان المنتج موجوداً في الفاتورة بالفعل — أضف الكمية
                $existing = $invoice->items()
                    ->where('product_id', $product->id)
                    ->first();

                if ($existing) {
                    $newQty   = (float) $existing->quantity + $quantity;
                    $newTotal = round($newQty * $unitCost, 2);
                    $existing->update([
                        'quantity'  => $newQty,
                        'unit_cost' => $unitCost,
                        'total'     => $newTotal,
                    ]);
                } else {
                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'product_id'          => $product->id,
                        'quantity'            => $quantity,
                        'unit_cost'           => $unitCost,
                        'total'               => round($quantity * $unitCost, 2),
                    ]);
                }

                $imported++;
            }
        });

        // إعادة حساب الإجماليات بعد الاستيراد
        app(PurchaseService::class)->recalculateTotals($invoice);

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
        ];
    }

    /**
     * نسخ بنود من فاتورة مشتريات سابقة
     */
    public function copyFromInvoice(PurchaseInvoice $target, PurchaseInvoice $source): int
    {
        $source->load('items.product');

        if ($source->items->isEmpty()) {
            throw new \Exception('الفاتورة المصدر لا تحتوي على بنود');
        }

        $copied = 0;

        DB::transaction(function () use ($target, $source, &$copied) {
            foreach ($source->items as $item) {
                // تجنب التكرار — إذا الصنف موجود بالفعل تخطى
                $alreadyExists = $target->items()
                    ->where('product_id', $item->product_id)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $target->id,
                    'product_id'          => $item->product_id,
                    'quantity'            => $item->quantity,
                    'unit_cost'           => $item->unit_cost,
                    'total'               => $item->total,
                ]);

                $copied++;
            }
        });

        app(PurchaseService::class)->recalculateTotals($target);

        return $copied;
    }

    /**
     * توليد محتوى CSV للقالب
     */
    public static function templateCsv(): string
    {
        $header = ['اسم الصنف', 'الكمية', 'سعر الوحدة (ج.م.)'];
        $sample = [
            ['مثال: أنبوب بولي 1 بوصة', '100', '12.50'],
            ['مثال: صنبور كروم 1/2', '50', '35.00'],
        ];

        $lines = [];
        $lines[] = implode(',', array_map(fn ($v) => '"' . $v . '"', $header));
        foreach ($sample as $row) {
            $lines[] = implode(',', array_map(fn ($v) => '"' . $v . '"', $row));
        }

        return "\xEF\xBB\xBF" . implode("\n", $lines); // BOM للعربية في Excel
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function parseFile(string $filePath): Collection
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            return $this->parseCsv($filePath);
        }

        return $this->parseSpreadsheet($filePath);
    }

    private function parseCsv(string $filePath): Collection
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \Exception('تعذّر فتح الملف');
        }

        // تخطى BOM إن وجد
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = null;
        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map('trim', $line);
                continue;
            }
            if (count($line) < 2) continue;

            $rows[] = $this->mapRow($headers, $line);
        }

        fclose($handle);
        return collect($rows);
    }

    private function parseSpreadsheet(string $filePath): Collection
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];
        $headers     = null;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            if ($headers === null) {
                $headers = $cells;
                continue;
            }

            if (empty(array_filter($cells))) continue;

            $rows[] = $this->mapRow($headers, $cells);
        }

        return collect($rows);
    }

    /**
     * ربط الأعمدة بالأسماء المعروفة (بالعربي أو الإنجليزي)
     */
    private function mapRow(array $headers, array $cells): array
    {
        $map = [];
        foreach ($headers as $i => $header) {
            $map[$header] = $cells[$i] ?? '';
        }

        // محاولة العثور على الاسم العربي أو الإنجليزي
        $productName = $map['اسم الصنف']
            ?? $map['product_name']
            ?? $map['الصنف']
            ?? $map['Product Name']
            ?? ($cells[0] ?? '');

        $quantity = $map['الكمية']
            ?? $map['quantity']
            ?? $map['Quantity']
            ?? ($cells[1] ?? 0);

        $unitCost = $map['سعر الوحدة (ج.م.)']
            ?? $map['سعر الوحدة']
            ?? $map['unit_cost']
            ?? $map['Unit Cost']
            ?? $map['Price']
            ?? ($cells[2] ?? 0);

        return [
            'product_name' => trim((string) $productName),
            'quantity'     => (float) str_replace(',', '', (string) $quantity),
            'unit_cost'    => (float) str_replace(',', '', (string) $unitCost),
        ];
    }

    private function resolveProduct(array $row): ?Product
    {
        $name = $row['product_name'] ?? '';
        if (empty($name)) return null;

        // بحث مباشر أولاً
        $product = Product::where('name', $name)->first();
        if ($product) return $product;

        // بحث جزئي
        return Product::where('name', 'ILIKE', '%' . $name . '%')->first();
    }
}
