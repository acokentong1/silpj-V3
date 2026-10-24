<?php
// Copy a stopped V3 installation into an empty release directory; source stays unchanged.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$options = getopt('', ['source:', 'stopped']);
$root = dirname(__DIR__);
$source = realpath($options['source'] ?? '');
if (!$source || $source === realpath($root) || !array_key_exists('stopped', $options)) {
    fwrite(STDERR, "Stop the old server, worker and scheduler first. Usage: php scripts/copy-v3-data.php --source=\"C:\\old-silpj-V3\" --stopped\n"); exit(1);
}
$sourceDb = $source.'/database/silpj-v3.sqlite';
$targetDb = $root.'/database/silpj-v3.sqlite';
$targetAssets = $root.'/storage/app/private/v3';
if (!is_file($sourceDb) || is_link($sourceDb) || is_file($targetDb) || is_dir($targetAssets) || is_file($root.'/.env')) {
    fwrite(STDERR, "Destination must be a fresh extracted release without database, .env or v3 assets. Source database must exist and not be a symlink.\n"); exit(1);
}
function copyTreeV3(string $from, string $to): void {
    if (is_link($from)) throw new RuntimeException('Symlinks are not accepted.');
    if (is_dir($from)) {
        if (!is_dir($to) && !mkdir($to, 0700, true)) throw new RuntimeException('Cannot create destination.');
        foreach (new DirectoryIterator($from) as $entry) if (!$entry->isDot()) copyTreeV3($entry->getPathname(),$to.'/'.$entry->getFilename());
    } elseif (!copy($from,$to) || hash_file('sha256',$from)!==hash_file('sha256',$to)) throw new RuntimeException('Copy verification failed.');
}
try {
    $pdo = new PDO('sqlite:'.$sourceDb); $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    if ($pdo->query('PRAGMA integrity_check')->fetchColumn()!=='ok' || $pdo->query('SELECT product FROM v3_identity WHERE id=1')->fetchColumn()!=='silpj-v3') throw new RuntimeException('Source database is not a healthy SILPJ V3 database. Use emergency recovery for damaged databases.');
    $pdo->exec("VACUUM INTO '".str_replace("'","''",$targetDb)."'"); $pdo=null;
    if(is_dir($source.'/storage/app/private/v3')) copyTreeV3($source.'/storage/app/private/v3',$targetAssets);
    if(!is_file($source.'/.env')) throw new RuntimeException('Source .env missing; retain the original APP_KEY before continuing.');
    if(!copy($source.'/.env',$root.'/.env'))throw new RuntimeException('Could not copy configuration.');
    echo "Data copy completed. Source unchanged. Now run php scripts/install-v3.php to apply additive migrations, then verify accounts/documents before using the new release. Do not operate both copies simultaneously.\n";
} catch(Throwable $e) {
    fwrite(STDERR,"Copy failed: ".$e->getMessage()."\nSource is unchanged. Discard this partial destination and extract a fresh release before retrying.\n"); exit(1);
}
