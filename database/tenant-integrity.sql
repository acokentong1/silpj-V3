CREATE TRIGGER tenant_v3_activities_budget_id_insert BEFORE INSERT ON v3_activities
WHEN NEW.budget_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_budgets WHERE id=NEW.budget_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_activities_budget_id_update BEFORE UPDATE ON v3_activities
WHEN NEW.budget_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_budgets WHERE id=NEW.budget_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_activities_created_by_insert BEFORE INSERT ON v3_activities
WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.created_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_activities_created_by_update BEFORE UPDATE ON v3_activities
WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.created_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_activity_id_insert BEFORE INSERT ON v3_purchases
WHEN NEW.activity_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_activities WHERE id=NEW.activity_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_activity_id_update BEFORE UPDATE ON v3_purchases
WHEN NEW.activity_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_activities WHERE id=NEW.activity_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_vendor_id_insert BEFORE INSERT ON v3_purchases
WHEN NEW.vendor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_vendors WHERE id=NEW.vendor_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_vendor_id_update BEFORE UPDATE ON v3_purchases
WHEN NEW.vendor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_vendors WHERE id=NEW.vendor_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_created_by_insert BEFORE INSERT ON v3_purchases
WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.created_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_created_by_update BEFORE UPDATE ON v3_purchases
WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.created_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_submitted_by_insert BEFORE INSERT ON v3_purchases
WHEN NEW.submitted_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.submitted_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_submitted_by_update BEFORE UPDATE ON v3_purchases
WHEN NEW.submitted_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.submitted_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_approved_by_insert BEFORE INSERT ON v3_purchases
WHEN NEW.approved_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.approved_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_purchases_approved_by_update BEFORE UPDATE ON v3_purchases
WHEN NEW.approved_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.approved_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_documents_purchase_id_insert BEFORE INSERT ON v3_documents
WHEN NEW.purchase_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_purchases WHERE id=NEW.purchase_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_documents_purchase_id_update BEFORE UPDATE ON v3_documents
WHEN NEW.purchase_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_purchases WHERE id=NEW.purchase_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_documents_created_by_insert BEFORE INSERT ON v3_documents
WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.created_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_documents_created_by_update BEFORE UPDATE ON v3_documents
WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.created_by AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_events_purchase_id_insert BEFORE INSERT ON v3_events
WHEN NEW.purchase_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_purchases WHERE id=NEW.purchase_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_events_purchase_id_update BEFORE UPDATE ON v3_events
WHEN NEW.purchase_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM v3_purchases WHERE id=NEW.purchase_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_events_actor_id_insert BEFORE INSERT ON v3_events
WHEN NEW.actor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.actor_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_events_actor_id_update BEFORE UPDATE ON v3_events
WHEN NEW.actor_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.actor_id AND school_profile_id=NEW.school_profile_id)
BEGIN SELECT RAISE(ABORT, 'Relasi lintas sekolah ditolak'); END;

CREATE TRIGGER tenant_v3_activities_school_immutable BEFORE UPDATE OF school_profile_id ON v3_activities
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_vendors_school_immutable BEFORE UPDATE OF school_profile_id ON v3_vendors
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_budgets_school_immutable BEFORE UPDATE OF school_profile_id ON v3_budgets
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_purchases_school_immutable BEFORE UPDATE OF school_profile_id ON v3_purchases
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_documents_school_immutable BEFORE UPDATE OF school_profile_id ON v3_documents
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_events_school_immutable BEFORE UPDATE OF school_profile_id ON v3_events
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_users_school_immutable BEFORE UPDATE OF school_profile_id ON users
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_items_purchase_immutable BEFORE UPDATE OF purchase_id ON v3_items
WHEN NEW.purchase_id IS NOT OLD.purchase_id
BEGIN SELECT RAISE(ABORT, 'Induk transaksi tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_components_purchase_immutable BEFORE UPDATE OF purchase_id ON v3_components
WHEN NEW.purchase_id IS NOT OLD.purchase_id
BEGIN SELECT RAISE(ABORT, 'Induk transaksi tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_attachments_purchase_immutable BEFORE UPDATE OF purchase_id ON v3_attachments
WHEN NEW.purchase_id IS NOT OLD.purchase_id
BEGIN SELECT RAISE(ABORT, 'Induk transaksi tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_documents_purchase_immutable BEFORE UPDATE OF purchase_id ON v3_documents
WHEN NEW.purchase_id IS NOT OLD.purchase_id
BEGIN SELECT RAISE(ABORT, 'Induk transaksi tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_events_purchase_immutable BEFORE UPDATE OF purchase_id ON v3_events
WHEN NEW.purchase_id IS NOT OLD.purchase_id
BEGIN SELECT RAISE(ABORT, 'Induk transaksi tidak dapat dipindahkan'); END;

CREATE TRIGGER tenant_v3_sequences_school_immutable BEFORE UPDATE OF school_profile_id ON v3_sequences
WHEN NEW.school_profile_id IS NOT OLD.school_profile_id
BEGIN SELECT RAISE(ABORT, 'Sekolah pemilik tidak dapat dipindahkan'); END;
