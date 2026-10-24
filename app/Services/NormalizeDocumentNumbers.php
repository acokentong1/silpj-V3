<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class NormalizeDocumentNumbers
{
    public static function run(): void
    {
        DB::transaction(function () {
            $docs = DB::table('v3_documents')->where('status', 'terbit')->orderBy('id')->get();
            $mapping = [];
            foreach ($docs as $doc) {
                if (!preg_match('~^(NOPES|BAPB|KUITANSI)-[0-9]+/(.+)/([0-9]{4})$~', $doc->nomor, $parts)) {
                    continue;
                }
                $key = ['school_profile_id'=>$doc->school_profile_id, 'jenis'=>'B', 'tahun'=>(int)$parts[3]];
                $last = (int) DB::table('v3_sequences')->where('school_profile_id',$doc->school_profile_id)->where('tahun',$key['tahun'])->max('last');
                DB::table('v3_sequences')->insertOrIgnore($key + ['last'=>$last]);
                $seq = DB::table('v3_sequences')->where($key)->lockForUpdate()->first();
                $number = max($last, (int)$seq->last);
                do {
                    $number++;
                    $new = 'B-'.$number.'/'.$parts[2].'/'.$parts[3];
                } while (DB::table('v3_documents')->where('school_profile_id',$doc->school_profile_id)->where('nomor',$new)->exists());
                DB::table('v3_sequences')->where('id',$seq->id)->update(['last'=>$number]);
                DB::table('v3_document_number_changes')->insert([
                    'document_id'=>$doc->id, 'old_number'=>$doc->nomor, 'new_number'=>$new,
                    'old_snapshot'=>$doc->snapshot, 'changed_at'=>now(),
                ]);
                DB::table('v3_documents')->where('id',$doc->id)->update(['nomor'=>$new]);
                $mapping[$doc->school_profile_id][$doc->nomor] = $new;
            }
            foreach ($docs as $doc) {
                $snapshot = json_decode($doc->snapshot, true, 512, JSON_THROW_ON_ERROR);
                $changed = false;
                foreach (['nopes_nomor','bapb_nomor'] as $field) {
                    $old = $snapshot[$field] ?? null;
                    if ($old && isset($mapping[$doc->school_profile_id][$old])) {
                        $snapshot[$field] = $mapping[$doc->school_profile_id][$old];
                        $changed = true;
                    }
                }
                if ($changed) {
                    DB::table('v3_document_number_changes')->insertOrIgnore([
                        'document_id'=>$doc->id, 'old_number'=>$doc->nomor, 'new_number'=>$doc->nomor,
                        'old_snapshot'=>$doc->snapshot, 'changed_at'=>now(),
                    ]);
                    DB::table('v3_documents')->where('id',$doc->id)->update(['snapshot'=>json_encode($snapshot,JSON_THROW_ON_ERROR)]);
                }
            }
        });
    }
}
