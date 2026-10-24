<?php
$root=dirname(__DIR__);chdir($root);
if(!is_file('.env')||!is_file('database/silpj-v3.sqlite')||!is_file('vendor/autoload.php')){fwrite(STDERR,"Jalankan PASANG_SILPJ_V3.bat atau php scripts/install-v3.php terlebih dahulu.\n");exit(1);}
if(!function_exists('proc_open')){fwrite(STDERR,"Aktifkan proc_open atau jalankan php artisan serve --port=8003.\n");exit(1);}
$port=null;for($i=8003;$i<=8020;$i++){$s=@stream_socket_server('tcp://127.0.0.1:'.$i,$errno,$error);if($s){fclose($s);$port=$i;break;}}
if(!$port){fwrite(STDERR,"Port 8003-8020 terpakai.\n");exit(1);}
echo "SILPJ V3: http://127.0.0.1:$port\nBiarkan terminal terbuka. Ctrl+C untuk berhenti.\n";
$p=proc_open([PHP_BINARY,'-d','max_input_vars=5000','-d','upload_max_filesize=12M','-d','post_max_size=24M','-S','127.0.0.1:'.$port,$root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[STDIN,STDOUT,STDERR],$pipes,$root.'/public');
if(!is_resource($p))exit(1);
// Keep backup checks alive for the lifetime of the local server.
$backup=null; $nextBackup=0;
register_shutdown_function(function () use (&$backup, &$p) {
    foreach ([$backup,$p] as $child) {
        if (is_resource($child)) { $state=proc_get_status($child); if($state['running']) proc_terminate($child); proc_close($child); }
    }
});
while (true) {
    $state=proc_get_status($p);
    if (!$state['running']) { $code=$state['exitcode']; proc_close($p); $p=null; exit($code < 0 ? 1 : $code); }
    if (is_resource($backup)) {
        $b=proc_get_status($backup);
        if (!$b['running']) { if($b['exitcode']!==0) fwrite(STDERR,"Backup otomatis gagal; periksa log. Akan dicoba lagi.\n"); proc_close($backup); $backup=null; }
    }
    if (!is_resource($backup) && time()>=$nextBackup) {
        $backup=proc_open([PHP_BINARY,'artisan','silpj:backup-due','--no-interaction'],[STDIN,STDOUT,STDERR],$backupPipes,$root);
        if(!is_resource($backup)) fwrite(STDERR,"Tidak dapat menjalankan backup otomatis.\n");
        $nextBackup=time()+3600;
    }
    usleep(500000);
}

