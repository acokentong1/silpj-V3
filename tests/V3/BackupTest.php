<?php

namespace Tests\V3;

use App\Services\BackupService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    private string $temporary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporary = sys_get_temp_dir().'/silpj-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->temporary.'/framework', 0700);
        $this->app->useStoragePath($this->temporary);
        config(['database.connections.silpj_v3.database' => $this->temporary.'/test.sqlite']);
        touch($this->temporary.'/test.sqlite');
        DB::purge();
        config(['filesystems.disks.local.root' => $this->temporary.'/app/private']);
        Storage::forgetDisk('local');
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::disconnect();
        File::deleteDirectory($this->temporary);
        parent::tearDown();
    }

    public function test_backup_roundtrip_restores_database_assets_and_cleans_temporary_files(): void
    {
        $service = app(BackupService::class);
        DB::table('school_profiles')->insert(['nama_sekolah' => 'Original']);
        Storage::disk('local')->put('v3/test.txt', 'original');
        $archive = $service->create();
        $this->assertSame('silpj-v3', $service->validate($archive)['product']);
        DB::table('school_profiles')->update(['nama_sekolah' => 'Changed']);
        Storage::disk('local')->put('v3/test.txt', 'changed');
        Artisan::call('down');
        try {
            $service->restore($archive);
        } finally {
            Artisan::call('up');
        }
        $this->assertSame('Original', DB::table('school_profiles')->value('nama_sekolah'));
        $this->assertSame('original', Storage::disk('local')->get('v3/test.txt'));
        $this->assertSame([], glob($this->temporary.'/app/private/v3-backups/*.sqlite'));
        $this->assertSame([], glob($this->temporary.'/app/private/restore-*'));
        $service->prune(1);
        $this->assertCount(1, glob($this->temporary.'/app/private/v3-backups/*.zip'));
    }

    public function test_tampered_backup_is_rejected(): void
    {
        $service = app(BackupService::class);
        $archive = $service->create();
        $zip = new \ZipArchive;
        $zip->open($archive);
        $zip->addFromString('database/silpj-v3.sqlite', 'tampered');
        $zip->close();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Checksum');
        $service->validate($archive);
    }

    public function test_path_traversal_is_rejected(): void
    {
        $service = app(BackupService::class);
        $archive = $service->create();
        $zip = new \ZipArchive;
        $zip->open($archive);
        $zip->addFromString('../outside.txt', 'bad');
        $zip->close();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak aman');
        $service->validate($archive);
    }

    public function test_restore_requires_maintenance(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('maintenance');
        app(BackupService::class)->restore('missing.zip');
    }

    public function test_failed_backup_cleans_staging_files(): void
    {
        DB::beginTransaction();
        try {
            app(BackupService::class)->create();
            $this->fail('VACUUM in transaction must fail');
        } catch (QueryException $e) {
            $this->assertSame([], glob($this->temporary.'/app/private/v3-backups/*'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_encrypted_backup_requires_correct_password(): void {
        config(['silpj.backup_password'=>'test-long-password-only']);
        $service=app(BackupService::class);$path=$service->create();
        $this->assertSame('silpj-v3',$service->validate($path)['product']);
        config(['silpj.backup_password'=>'incorrect-password']);
        $this->expectException(\RuntimeException::class);$service->validate($path);
    }
    public function test_external_copy_and_status_are_written(): void {
        config(['silpj.backup_copy_directory'=>$this->temporary.'/external']);
        $path=app(BackupService::class)->create();
        $this->assertSame(hash_file('sha256',$path),hash_file('sha256',$this->temporary.'/external/'.basename($path)));
        $status=json_decode(file_get_contents(storage_path('app/private/backup-status.json')),true);
        $this->assertTrue($status['ok']);
    }
    public function test_missing_referenced_logo_rejects_restore_without_changing_live_database(): void {
        DB::table('school_profiles')->insert(['nama_sekolah'=>'Original','logo_pemda'=>'v3/missing.png']);
        $path=app(BackupService::class)->create();
        Artisan::call('down');
        try {app(BackupService::class)->restore($path);$this->fail('Missing logo must be rejected');}
        catch(\RuntimeException $e){$this->assertStringContainsString('Aset',$e->getMessage());}
        finally{Artisan::call('up');}
        $this->assertSame('Original',DB::table('school_profiles')->value('nama_sekolah'));
    }
}
