<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('v3_purchases', function (Blueprint $t) {
            $t->timestamp('submitted_at')->nullable();
            $t->index(['school_profile_id', 'status', 'submitted_at']);
        });
        DB::statement("UPDATE v3_purchases SET submitted_at = (SELECT MAX(created_at) FROM v3_events WHERE purchase_id = v3_purchases.id AND action = 'ajukan')");
        Schema::create('v3_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained('v3_purchases')->restrictOnDelete();
            $t->foreignId('nopes_id')->constrained('v3_documents')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->uuid('token')->unique();
            $t->date('tanggal');
            $t->json('items');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['purchase_id', 'revision', 'nopes_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v3_receipts');
        Schema::table('v3_purchases', function (Blueprint $t) {
            $t->dropIndex(['school_profile_id', 'status', 'submitted_at']);
            $t->dropColumn('submitted_at');
        });
    }
};
