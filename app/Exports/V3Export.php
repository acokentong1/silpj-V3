<?php

namespace App\Exports;

use App\Services\ReportQuery;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class V3Export extends DefaultValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(private int $schoolId, private array $filters = []) {}

    public function headings(): array
    {
        return ['Kegiatan', 'Tahun', 'Sumber dana', 'Nomor bukti', 'Tanggal', 'Rekanan', 'Subtotal', 'Bruto', 'Potongan', 'Netto', 'Status'];
    }

    public function query()
    {
        return ReportQuery::query($this->schoolId, $this->filters)->orderBy('id');
    }

    public function map($p): array
    {
        return [$p->activity->nama, $p->tahun, $p->activity->budget->sumber_dana, $p->nomor_bukti, $p->tanggal, $p->vendor->nama, (string) $p->subtotal, (string) $p->bruto, (string) $p->potongan, (string) $p->netto, $p->status];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (in_array($cell->getColumn(), ['G','H','I','J']) && is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            $cell->getStyle()->getNumberFormat()->setFormatCode('#,##0.00');
            return true;
        }
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

return parent::bindValue($cell, $value);
    }
}
