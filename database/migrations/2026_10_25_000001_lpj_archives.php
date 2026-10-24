<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('v3_attachments', fn(Blueprint $t) => $t->string('category')->default('lainnya'));
        Schema::create('v3_report_archives', function(Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_profile_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedSmallInteger('tahun');
            $t->json('filters'); $t->json('manifest');
            $t->string('pdf_path'); $t->string('zip_path');
            $t->string('pdf_sha256',64); $t->string('zip_sha256',64);
            $t->timestamps(); $t->index(['school_profile_id','tahun']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('v3_report_archives');
        Schema::table('v3_attachments', fn(Blueprint $t) => $t->dropColumn('category'));
    }
};
