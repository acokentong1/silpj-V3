<?php
use Illuminate\Support\Facades\{Artisan,DB};
use App\Models\User;
Artisan::command('silpj:system-admin {email} {--revoke}',function(){
 $user=User::where('email',$this->argument('email'))->first();
 if(!$user){$this->error('Akun tidak ditemukan. Buat akun melalui pengaturan awal atau admin sekolah.');return 1;}
 if(!$user->is_active){$this->error('Aktifkan akun sebelum mengubah kewenangan sistem.');return 1;}
 $grant=!$this->option('revoke');
 if(!$this->confirm(($grant?'Berikan':'Cabut').' kewenangan administrator sistem untuk '.$user->email.'?',false))return 1;
 DB::transaction(function()use($user,$grant){
  $user->is_system_admin=$grant;$user->save();
  DB::table('system_events')->insert(['actor_id'=>$user->id,'action'=>$grant?'cli_berikan_admin_sistem':'cli_cabut_admin_sistem',
   'details'=>json_encode(['source'=>'trusted_server_console','target_user_id'=>$user->id]),'created_at'=>now(),'updated_at'=>now()]);
 });
 $this->info('Kewenangan sistem diperbarui. Hak akses data sekolah tetap dibatasi ke sekolah akun.');return 0;
})->purpose('Mengelola administrator sistem dari konsol server tepercaya');
