<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('school_profiles')->whereNotNull('npsn')->groupBy('npsn')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('NPSN duplikat ditemukan. Perbaiki data sekolah sebelum migrasi; tidak ada sekolah yang digabung otomatis.');
        }
        Schema::table('school_profiles', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->unique('npsn');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_system_admin')->default(false);
        });
        Schema::create('system_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('school_profile_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        // SQLite does not automatically index foreign keys. These indexes serve tenant queries.
        Schema::table('v3_activities', fn (Blueprint $t) => $t->index(['school_profile_id', 'budget_id']));
        Schema::table('v3_purchases', fn (Blueprint $t) => $t->index(['school_profile_id', 'activity_id', 'status']));
        Schema::table('v3_documents', fn (Blueprint $t) => $t->index(['school_profile_id', 'purchase_id', 'status']));
        Schema::table('v3_events', fn (Blueprint $t) => $t->index(['school_profile_id', 'purchase_id', 'id']));
    }

    public function down(): void
    {
        Schema::table('v3_events', fn (Blueprint $t) => $t->dropIndex(['school_profile_id', 'purchase_id', 'id']));
        Schema::table('v3_documents', fn (Blueprint $t) => $t->dropIndex(['school_profile_id', 'purchase_id', 'status']));
        Schema::table('v3_purchases', fn (Blueprint $t) => $t->dropIndex(['school_profile_id', 'activity_id', 'status']));
        Schema::table('v3_activities', fn (Blueprint $t) => $t->dropIndex(['school_profile_id', 'budget_id']));
        Schema::dropIfExists('system_events');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_system_admin'));
        Schema::table('school_profiles', function (Blueprint $t) {
            $t->dropUnique(['npsn']);
            $t->dropColumn('is_active');
        });
    }
};
