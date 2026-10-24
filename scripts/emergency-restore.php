<?php
// Trusted server console only. Does not require a working source database.
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
$options=getopt('', ['file:','reason:','confirm:']);
if(empty($options['file']) || strlen(trim($options['reason']??''))<5 || ($options['confirm']??'')!=='RESTORE') {
 fwrite(STDERR,"Stop server/worker/scheduler first. Usage: php scripts/emergency-restore.php --file=/absolute/backup.zip --reason=\"database damaged\" --confirm=RESTORE\n"); exit(1);
}
define('SILPJ_EMERGENCY_RESTORE',true);
chdir(dirname(__DIR__)); require 'vendor/autoload.php'; $app=require 'bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap();
try {
 Illuminate\Support\Facades\Artisan::call('down');
 app(App\Services\BackupService::class)->restore($options['file']);
 Illuminate\Support\Facades\Log::warning('Emergency restore completed',['reason'=>$options['reason']]);
 echo "Restore completed; maintenance remains ON. Verify data, then run php artisan up. Raw original database retained under storage/app/private/emergency-original-*.\n";
} catch(Throwable $e) {fwrite(STDERR,"Restore failed; maintenance remains ON: ".$e->getMessage()."\n");exit(1);}
