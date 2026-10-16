<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('v3_identity',function(Blueprint $t){$t->id();$t->string('product');});
  DB::table('v3_identity')->insert(['id'=>1,'product'=>'silpj-v3']);
  Schema::create('school_profiles',function(Blueprint $t){
   $t->id();foreach(['nama_sekolah','npsn','nama_pemda','nama_dinas','tempat_cetak','kepala_sekolah','nip_kepala','bendahara','nip_bendahara','pemeriksa','nip_pemeriksa','kode_surat','kode_sekolah','logo_pemda','logo_sekolah'] as $f)$t->string($f)->nullable();
   $t->text('alamat')->nullable();$t->timestamps();
  });
  Schema::create('users',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->nullable()->constrained()->restrictOnDelete();$t->string('name');$t->string('email')->unique();$t->timestamp('email_verified_at')->nullable();$t->string('password');$t->string('role')->default('user');$t->boolean('is_active')->default(true);$t->rememberToken();$t->timestamps();});
  Schema::create('password_reset_tokens',function(Blueprint $t){$t->string('email')->primary();$t->string('token');$t->timestamp('created_at')->nullable();});
  Schema::create('installation_state',function(Blueprint $t){$t->unsignedTinyInteger('id')->primary();$t->boolean('completed')->default(false);});
  DB::table('installation_state')->insert(['id'=>1,'completed'=>false]);
  Schema::create('v3_budgets',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->unsignedSmallInteger('tahun');$t->string('sumber_dana');$t->decimal('nilai',18,2);$t->unique(['school_profile_id','tahun','sumber_dana']);$t->timestamps();});
  Schema::create('v3_activities',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->foreignId('budget_id')->constrained('v3_budgets')->restrictOnDelete();$t->string('nama');$t->text('keterangan')->nullable();$t->foreignId('created_by')->constrained('users');$t->timestamps();});
  Schema::create('v3_vendors',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->string('nama');$t->text('alamat')->nullable();$t->string('penerima')->nullable();$t->string('npwp')->nullable();$t->timestamps();});
  Schema::create('v3_purchases',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->foreignId('activity_id')->constrained('v3_activities')->restrictOnDelete();$t->foreignId('vendor_id')->constrained('v3_vendors')->restrictOnDelete();$t->unsignedSmallInteger('tahun');$t->string('nomor_bukti');$t->date('tanggal');$t->string('kategori');foreach(['subtotal','bruto','potongan','netto','total_pajak'] as $f)$t->decimal($f,18,2)->default(0);$t->string('status')->default('draf');$t->unsignedInteger('revision')->default(1);$t->foreignId('created_by')->constrained('users');$t->foreignId('submitted_by')->nullable()->constrained('users');$t->foreignId('approved_by')->nullable()->constrained('users');$t->timestamp('approved_at')->nullable();$t->timestamps();$t->unique(['school_profile_id','tahun','nomor_bukti']);});
  Schema::create('v3_items',function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained('v3_purchases')->cascadeOnDelete();$t->unsignedInteger('urutan');$t->string('nama');$t->decimal('volume',12,3);$t->string('satuan',40);$t->decimal('harga',18,2);$t->decimal('jumlah',18,2);});
  Schema::create('v3_components',function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained('v3_purchases')->cascadeOnDelete();$t->string('label',60);$t->string('kategori');$t->string('metode');$t->decimal('persentase',9,4)->nullable();$t->decimal('dasar',18,2)->nullable();$t->decimal('nominal',18,2);$t->string('perlakuan');});
  Schema::create('v3_documents',function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained('v3_purchases')->restrictOnDelete();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->string('jenis');$t->string('nomor');$t->date('tanggal');$t->unsignedInteger('versi');$t->unsignedInteger('revision');$t->string('status')->default('terbit');$t->json('snapshot');$t->foreignId('created_by')->constrained('users');$t->timestamps();$t->unique(['school_profile_id','nomor']);$t->unique(['purchase_id','jenis','versi']);});
  Schema::create('v3_sequences',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->string('jenis');$t->unsignedSmallInteger('tahun');$t->unsignedInteger('last')->default(0);$t->unique(['school_profile_id','jenis','tahun']);});
  Schema::create('v3_attachments',function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained('v3_purchases')->restrictOnDelete();$t->string('name');$t->string('path');$t->string('mime');$t->string('sha256',64);$t->timestamps();});
  Schema::create('v3_events',function(Blueprint $t){$t->id();$t->foreignId('school_profile_id')->constrained()->restrictOnDelete();$t->foreignId('purchase_id')->nullable()->constrained('v3_purchases')->restrictOnDelete();$t->foreignId('actor_id')->constrained('users');$t->string('action');$t->text('reason')->nullable();$t->json('details')->nullable();$t->timestamps();});
 }
 public function down(): void {foreach(['v3_events','v3_attachments','v3_sequences','v3_documents','v3_components','v3_items','v3_purchases','v3_vendors','v3_activities','v3_budgets','installation_state','password_reset_tokens','users','school_profiles','v3_identity'] as $table)Schema::dropIfExists($table);}
};
