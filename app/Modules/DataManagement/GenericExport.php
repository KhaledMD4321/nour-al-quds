<?php

namespace App\Modules\DataManagement;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * تصدير Excel عام — صفوف جاهزة + عناوين.
 * مُستخرَج من ExportService (كان معرّفاً inline) ليكون كل صنف في ملفه.
 */
class GenericExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(
        private Collection $data,
        private array $headers
    ) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
