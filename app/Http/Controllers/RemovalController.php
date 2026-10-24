<?php
namespace App\Http\Controllers;

use App\Models\V3\Activity;
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemovalController extends SchoolController
{
    public function purchase(Request $request, int $id)
    {
        $this->editor();
        $data=$request->validate(['reason'=>'required|string|min:5|max:2000','expected_revision'=>'required|integer|min:1']);
        $activityId=DB::transaction(function() use($id,$data) {
            $p=V3Workflow::purchase($id,true);
            if((int)$p->revision!==(int)$data['expected_revision']) {
                throw ValidationException::withMessages(['hapus'=>'Transaksi sudah berubah. Muat ulang halaman sebelum menghapus.']);
            }
            V3Workflow::assertEditable($p);
            // Cancellation preserves documents, evidence, notices and audit references.
            app(V3Workflow::class)->transition($id,'batalkan',$data['reason']);
            V3Workflow::event('hapus_dari_daftar',$p,$data['reason'],['activity_id'=>$p->activity_id]);
            return $p->activity_id;
        },3);
        return redirect()->route('v3.activity',$activityId)->with('success','Transaksi dihapus dari daftar aktif. Riwayatnya tersedia pada filter Dibatalkan dan laporan.');
    }

    public function activity(Request $request, int $id)
    {
        $this->editor();
        $data=$request->validate(['reason'=>'required|string|min:5|max:2000']);
        $year=DB::transaction(function() use($id,$data) {
            $a=Activity::where('school_profile_id',$this->sid())->lockForUpdate()->findOrFail($id);
            if($a->purchases()->where('status','!=','dibatalkan')->exists()) {
                throw ValidationException::withMessages(['hapus'=>'Kegiatan masih memiliki transaksi aktif. Selesaikan atau batalkan transaksi tersebut terlebih dahulu.']);
            }
            $a->update(['is_archived'=>true]);
            V3Workflow::event('hapus_kegiatan_dari_daftar',null,$data['reason'],['activity_id'=>$a->id,'nama'=>$a->nama]);
            return $a->budget->tahun;
        },3);
        return redirect()->route('dashboard',['tahun'=>$year])->with('success','Kegiatan dihapus dari daftar aktif. Buka filter Arsip untuk melihat atau mengaktifkannya kembali.');
    }
}
