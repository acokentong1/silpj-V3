<?php
// Run only inside this standalone V3 directory. Never loads the old .env.
$root=dirname(__DIR__);chdir($root);
function failV3(string $message):never{fwrite(STDERR,"[V3] $message\n");exit(1);}
if(PHP_VERSION_ID<80200||PHP_INT_SIZE!==8)failV3('Gunakan PHP 8.2+ 64-bit.');
foreach(['pdo_sqlite','mbstring','dom','xml','zip','gd','bcmath','fileinfo'] as $ext)if(!extension_loaded($ext))failV3("Aktifkan ekstensi PHP: $ext pada php.ini.");
if(!is_file('vendor/autoload.php'))failV3('Folder vendor belum tersedia. Jalankan composer install.');
if(is_link('database/silpj-v3.sqlite')||is_link('database')||is_link('storage'))failV3('Database dan storage harus direktori lokal V3, bukan tautan.');
if(!is_file('.env'))copy('.env.example','.env');
foreach(['storage/app/private','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache'] as $d)if(!is_dir($d))mkdir($d,0755,true);
if(!is_file('database/silpj-v3.sqlite')){ $h=fopen('database/silpj-v3.sqlite','x');if(!$h)failV3('Gagal membuat database V3 baru.');fclose($h); }
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
try{
 // Discard only this installation's cached configuration before booting.
 if(is_file('bootstrap/cache/config.php'))unlink('bootstrap/cache/config.php');
 $kernel->bootstrap();
 if(!config('app.key')){if($kernel->call('key:generate',['--force'=>true])!==0)failV3('Gagal membuat APP_KEY.');echo $kernel->output();}
 if($kernel->call('migrate',['--force'=>true])!==0)failV3('Migrasi gagal. Periksa pesan migrasi.');echo $kernel->output();
 echo "\nSILPJ V3 terpasang. Database: database/silpj-v3.sqlite\nJalankan MULAI_SILPJ_V3.bat atau php scripts/start-local.php\n";
}catch(Throwable $e){failV3($e->getMessage());}
