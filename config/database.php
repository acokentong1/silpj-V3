<?php
return [
 'default'=>'silpj_v3',
 'connections'=>['silpj_v3'=>[
  'driver'=>'sqlite',
  'database'=>env('APP_ENV') === 'testing' ? ':memory:' : database_path('silpj-v3.sqlite'),
  'prefix'=>'', 'foreign_key_constraints'=>true, 'busy_timeout'=>10000,
  'journal_mode'=>'WAL', 'synchronous'=>'FULL', 'transaction_mode'=>'IMMEDIATE',
 ]],
 'migrations'=>['table'=>'migrations','update_date_on_publish'=>true],
];
