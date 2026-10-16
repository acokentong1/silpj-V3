<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  if(DB::connection()->getDriverName()!=='sqlite')throw new RuntimeException('Migrasi isolasi sekolah ini memerlukan SQLite.');
  $checks=[
   'SELECT COUNT(*) AS total FROM v3_activities AS child WHERE child.budget_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_budgets AS parent WHERE parent.id=child.budget_id AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_activities AS child WHERE child.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users AS parent WHERE parent.id=child.created_by AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_purchases AS child WHERE child.activity_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_activities AS parent WHERE parent.id=child.activity_id AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_purchases AS child WHERE child.vendor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_vendors AS parent WHERE parent.id=child.vendor_id AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_purchases AS child WHERE child.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users AS parent WHERE parent.id=child.created_by AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_purchases AS child WHERE child.submitted_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users AS parent WHERE parent.id=child.submitted_by AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_purchases AS child WHERE child.approved_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users AS parent WHERE parent.id=child.approved_by AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_documents AS child WHERE child.purchase_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_purchases AS parent WHERE parent.id=child.purchase_id AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_documents AS child WHERE child.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users AS parent WHERE parent.id=child.created_by AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_events AS child WHERE child.purchase_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_purchases AS parent WHERE parent.id=child.purchase_id AND parent.school_profile_id=child.school_profile_id)',
   'SELECT COUNT(*) AS total FROM v3_events AS child WHERE child.actor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users AS parent WHERE parent.id=child.actor_id AND parent.school_profile_id=child.school_profile_id)'
  ];
  foreach($checks as $sql)if(DB::selectOne($sql)->total>0)throw new RuntimeException('Relasi lintas sekolah ditemukan pada data lama. Perbaiki sebelum melanjutkan migrasi.');
  DB::unprepared(file_get_contents(database_path('tenant-integrity.sql')));
 }
 public function down(): void {
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_sequences_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_activities_budget_id_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_activities_budget_id_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_activities_created_by_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_activities_created_by_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_activity_id_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_activity_id_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_vendor_id_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_vendor_id_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_created_by_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_created_by_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_submitted_by_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_submitted_by_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_approved_by_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_approved_by_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_documents_purchase_id_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_documents_purchase_id_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_documents_created_by_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_documents_created_by_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_events_purchase_id_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_events_purchase_id_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_events_actor_id_insert');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_events_actor_id_update');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_activities_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_vendors_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_budgets_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_purchases_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_documents_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_events_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_users_school_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_items_purchase_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_components_purchase_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_attachments_purchase_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_documents_purchase_immutable');
  DB::unprepared('DROP TRIGGER IF EXISTS tenant_v3_events_purchase_immutable');
 }
};
