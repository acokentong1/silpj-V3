<?php
// Isolated synthetic benchmark: uses a new in-memory SQLite connection only.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.connections.benchmark'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
$db=Illuminate\Support\Facades\DB::connection('benchmark');
$db->statement('CREATE TABLE amounts (id INTEGER PRIMARY KEY, bruto TEXT NOT NULL)');
for($batch=0;$batch<100;$batch++){
 $rows=[];for($i=0;$i<500;$i++)$rows[]=['bruto'=>'10000.01'];$db->table('amounts')->insert($rows);
}
unset($rows);
foreach(['eager','batch500'] as $mode){
 gc_collect_cycles();memory_reset_peak_usage();$base=memory_get_usage();$start=hrtime(true);
 $values=$mode==='eager'?$db->table('amounts')->pluck('bruto'):$db->table('amounts')->lazyById(500)->pluck('bruto');
 $total=$values->reduce(fn($sum,$value)=>bcadd($sum,(string)$value,2),'0.00');
 printf("%s | rows=50000 | total=%s | peak_delta=%d bytes | duration=%.3f s\n",$mode,$total,memory_get_peak_usage()-$base,(hrtime(true)-$start)/1e9);
 if($total!=='500000500.00')throw new RuntimeException('Incorrect total');
 unset($values);
}
