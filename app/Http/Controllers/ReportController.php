<?php

namespace App\Http\Controllers;

use App\Exports\V3Export;
use App\Models\V3\Activity;
use App\Models\V3\Budget;
use App\Services\ReportQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends SchoolController
{
    public function reports(Request $r)
    {
        $filters = ReportQuery::filters($r);

        return view('v3.reports', ['rows' => ReportQuery::query($this->sid(), $filters)->latest()->orderByDesc('id')->paginate(10)->withQueryString(), 'activities' => Activity::where('school_profile_id', $this->sid())->orderBy('nama')->get(), 'funds' => Budget::where('school_profile_id', $this->sid())->distinct()->pluck('sumber_dana'), 'events' => DB::table('v3_events')->join('users', 'users.id', '=', 'v3_events.actor_id')->where('v3_events.school_profile_id', $this->sid())->select('v3_events.*', 'users.name')->orderByDesc('v3_events.id')->paginate(10, ['*'], 'events_page')->withQueryString()]);
    }

    public function excel(Request $r)
    {
        $filters = ReportQuery::filters($r);

        return Excel::download(new V3Export($this->sid(), $filters), 'rekap-silpj-v3.xlsx');
    }

    public function csv(Request $r)
    {
        $filters = ReportQuery::filters($r);
        $sid = $this->sid();

        return response()->streamDownload(function () use ($filters, $sid) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Kegiatan', 'Tahun', 'Sumber dana', 'Nomor bukti', 'Tanggal', 'Rekanan', 'Subtotal', 'Bruto', 'Potongan', 'Netto', 'Status'], ';');
            ReportQuery::query($sid, $filters)->orderBy('id')->chunk(200, function ($rows) use ($f) {
                foreach ($rows as $p) {
                    $values = [$p->activity->nama, $p->tahun, $p->activity->budget->sumber_dana, $p->nomor_bukti, $p->tanggal, $p->vendor->nama, $p->subtotal, $p->bruto, $p->potongan, $p->netto, $p->status];
                    $values = array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/u', (string) $v) ? "'".$v : $v, $values);
                    fputcsv($f, $values, ';');
                }
            });
            fclose($f);
        }, 'rekap-silpj-v3.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
