<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', fn(Blueprint $t) => $t->unsignedInteger('session_version')->default(1));
  Schema::table('v3_activities', fn(Blueprint $t) => $t->boolean('is_archived')->default(false));
  Schema::table('v3_attachments', function(Blueprint $t) { $t->unsignedInteger('revision')->default(1); $t->boolean('is_active')->default(true); });
  // Preserve the current revision of existing evidence; future edits require confirmation.
  DB::statement('UPDATE v3_attachments SET revision = (SELECT revision FROM v3_purchases WHERE id = v3_attachments.purchase_id)');
  Schema::table('v3_receipts', function(Blueprint $t) { $t->timestamp('cancelled_at')->nullable(); $t->text('cancel_reason')->nullable(); });
  Schema::create('v3_periods', function(Blueprint $t) { $t->id(); $t->foreignId('school_profile_id')->constrained()->restrictOnDelete(); $t->unsignedSmallInteger('tahun'); $t->unsignedTinyInteger('bulan'); $t->boolean('is_closed')->default(true); $t->foreignId('actor_id')->constrained('users'); $t->text('reason'); $t->timestamps(); $t->unique(['school_profile_id','tahun','bulan']); });
  Schema::create('v3_cash_entries', function(Blueprint $t) { $t->id(); $t->foreignId('school_profile_id')->constrained()->restrictOnDelete(); $t->foreignId('purchase_id')->nullable()->constrained('v3_purchases')->restrictOnDelete(); $t->uuid('token')->unique(); $t->date('tanggal'); $t->string('direction'); $t->string('sumber_dana'); $t->decimal('amount',18,2); $t->string('method'); $t->string('reference')->nullable(); $t->text('description'); $t->foreignId('created_by')->constrained('users'); $t->timestamp('cancelled_at')->nullable(); $t->text('cancel_reason')->nullable(); $t->timestamps(); });
  Schema::create('v3_notices', function(Blueprint $t) { $t->id(); $t->foreignId('school_profile_id')->constrained()->restrictOnDelete(); $t->foreignId('purchase_id')->constrained('v3_purchases')->restrictOnDelete(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $t->string('message'); $t->timestamp('read_at')->nullable(); $t->timestamps(); $t->index(['user_id','read_at']); });
 }
 public function down(): void {
  foreach(['v3_notices','v3_cash_entries','v3_periods'] as $table) Schema::dropIfExists($table);
  Schema::table('v3_receipts', fn(Blueprint $t) => $t->dropColumn(['cancelled_at','cancel_reason']));
  Schema::table('v3_attachments', fn(Blueprint $t) => $t->dropColumn(['revision','is_active']));
  Schema::table('v3_activities', fn(Blueprint $t) => $t->dropColumn('is_archived'));
  Schema::table('users', fn(Blueprint $t) => $t->dropColumn('session_version'));
 }
};
