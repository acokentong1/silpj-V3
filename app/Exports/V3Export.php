<?php
namespace App\Exports;
use App\Models\V3\Purchase;
use Maatwebsite\Excel\Concerns\{FromCollection,WithHeadings,WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\{Cell,DataType,DefaultValueBinder};
class V3Export extends DefaultValueBinder implements FromCollection,WithHeadings,WithCustomValueBinder {
 public function __construct(private int $schoolId){}
 public function headings():array{return ['Kegiatan','Tahun','Sumber dana','Nomor bukti','Tanggal','Rekanan','Subtotal','Bruto','Potongan','Netto','Status'];}
 public function collection(){return Purchase::where('school_profile_id',$this->schoolId)->with('activity.budget','vendor')->orderBy('id')->get()->map(fn($p)=>[$p->activity->nama,$p->tahun,$p->activity->budget->sumber_dana,$p->nomor_bukti,$p->tanggal,$p->vendor->nama,(float)$p->subtotal,(float)$p->bruto,(float)$p->potongan,(float)$p->netto,$p->status]);}
 public function bindValue(Cell $cell,$value):bool {if(is_string($value)){$cell->setValueExplicit($value,DataType::TYPE_STRING);return true;}return parent::bindValue($cell,$value);}
}
