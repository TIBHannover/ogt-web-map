-- Initialize the SQLite database with all tables and a default admin user

-- Users Table
CREATE TABLE IF NOT EXISTS "users" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "name" VARCHAR(255) NOT NULL,
    "email" VARCHAR(255) NOT NULL,
    "email_verified_at" DATETIME NULL,
    "password" VARCHAR(255) NOT NULL,
    "remember_token" VARCHAR(100) NULL,
    "created_at" DATETIME NULL,
    "updated_at" DATETIME NULL,
    "is_admin" BOOLEAN NOT NULL DEFAULT 0,
    "wikidata_id" VARCHAR(255) NULL,
    "wikidata_username" VARCHAR(255) NULL,
    "wikidata_token" TEXT NULL,
    "wikidata_token_secret" TEXT NULL
);

-- Create unique index on email
CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" ("email");

-- Password Resets Table
CREATE TABLE IF NOT EXISTS "password_resets" (
    "email" VARCHAR(255) NOT NULL,
    "token" VARCHAR(255) NOT NULL,
    "created_at" DATETIME NULL
);

CREATE INDEX IF NOT EXISTS "password_resets_email_index" ON "password_resets" ("email");

-- Failed Jobs Table
CREATE TABLE IF NOT EXISTS "failed_jobs" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "uuid" VARCHAR(255) NOT NULL,
    "connection" TEXT NOT NULL,
    "queue" TEXT NOT NULL,
    "payload" TEXT NOT NULL,
    "exception" TEXT NOT NULL,
    "failed_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS "failed_jobs_uuid_unique" ON "failed_jobs" ("uuid");

-- Jobs Table
CREATE TABLE IF NOT EXISTS "jobs" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "queue" VARCHAR(255) NOT NULL,
    "payload" TEXT NOT NULL,
    "attempts" INTEGER NOT NULL,
    "reserved_at" INTEGER NULL,
    "available_at" INTEGER NOT NULL,
    "created_at" INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS "jobs_queue_index" ON "jobs" ("queue");

-- Wikidata Entry Requests Table
CREATE TABLE IF NOT EXISTS "wikidata_entry_requests" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "description" TEXT NOT NULL,
    "type" VARCHAR(255) NOT NULL,
    "coordinates" VARCHAR(255) NULL,
    "coordinates_qualifier" VARCHAR(255) NULL,
    "victims" TEXT NULL,
    "perpetrators" TEXT NULL,
    "time_point" VARCHAR(255) NULL,
    "time_point_qualifier" VARCHAR(255) NULL,
    "time_period_start" VARCHAR(255) NULL,
    "time_period_start_qualifier" VARCHAR(255) NULL,
    "time_period_end" VARCHAR(255) NULL,
    "time_period_end_qualifier" VARCHAR(255) NULL,
    "victim_count" INTEGER NULL,
    "survivor_count" INTEGER NULL,
    "death_count" INTEGER NULL,
    "arrest_count" INTEGER NULL,
    "victim_count_qualifier" VARCHAR(255) NULL,
    "survivor_count_qualifier" VARCHAR(255) NULL,
    "death_count_qualifier" VARCHAR(255) NULL,
    "arrest_count_qualifier" VARCHAR(255) NULL,
    "cause" TEXT NULL,
    "selected_causes" TEXT NULL,
    "image_url" VARCHAR(255) NULL,
    "image_filename" VARCHAR(255) NULL,
    "sources" TEXT NULL,
    "wikidata_id" VARCHAR(255) NULL,
    "created_entities" TEXT NULL,
    "status" VARCHAR(255) NOT NULL CHECK(status IN ('pending', 'in_review', 'revision_needed', 'approved', 'rejected', 'error', 'wikidata_submitted', 'wikidata_published', 'wikidata_failed')),
    "submitted_by" VARCHAR(255) NULL,
    "approved_by" VARCHAR(255) NULL,
    "rejected_by" VARCHAR(255) NULL,
    "rejection_reason" TEXT NULL,
    "error_message" TEXT NULL,
    "gender" TEXT NULL,
    "nationality" TEXT NULL,
    "place_of_birth" TEXT NULL,
    "place_of_death" TEXT NULL,
    "place_of_detention" TEXT NULL,
    "status_token" VARCHAR(255) NULL,
    "status_history" TEXT NULL,
    "status_updated_at" DATETIME NULL,
    "comment" TEXT NULL,
    "created_at" DATETIME NULL,
    "updated_at" DATETIME NULL,
    "email" VARCHAR(255) NULL,
    "notify_on_publish" BOOLEAN DEFAULT 0,
    "notification_sent_at" DATETIME NULL
);

-- Wikidata Entity Relationships (if used in your application)
CREATE TABLE IF NOT EXISTS "wikidata_entity_relationships" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "entity_id" VARCHAR(255) NOT NULL,
    "related_entity_id" VARCHAR(255) NOT NULL,
    "relationship_type" VARCHAR(255) NOT NULL,
    "created_at" DATETIME NULL,
    "updated_at" DATETIME NULL
);

-- Migrations Table to track which migrations have been run
CREATE TABLE IF NOT EXISTS "migrations" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "migration" VARCHAR(255) NOT NULL,
    "batch" INTEGER NOT NULL
);

-- Insert all migrations to prevent them from running again
INSERT INTO "migrations" ("migration", "batch") VALUES
('2014_10_12_000000_create_users_table', 1),
('2014_10_12_100000_create_password_resets_table', 1),
('2019_08_19_000000_create_failed_jobs_table', 1),
('2023_04_24_091507_create_wikidata_entry_requests_table', 1),
('2023_04_28_074616_create_jobs_table', 1),
('2023_05_13_072008_add_wikidata_fields_to_users_table', 1),
('2023_06_02_144713_add_wikidata_fields_to_entry_requests', 1),
('2023_06_24_000000_add_wikidata_published_status', 1),
('2023_06_30_000000_fix_status_check_constraint', 1),
('2023_07_22_000000_add_coordinates_qualifier_to_wikidata_entry_requests', 1),
('2023_08_07_073219_add_missing_fields_to_wikidata_entry_requests', 1),
('2023_08_08_131727_add_email_notification_fields_to_wikidata_entry_requests', 1),
('2023_08_20_070406_add_notification_sent_at_to_wikidata_entry_requests_table', 1),
('2023_08_25_000000_update_status_constraints', 1),
('2023_10_10_000000_add_time_qualifiers_to_wikidata_entry_requests', 1),
('2023_11_22_000001_add_status_token_to_entry_requests', 1),
('2025_06_30_000000_fix_status_check_constraint', 1),
('2025_08_26_000000_ensure_all_columns_exist', 1);

-- Create default admin user
-- Password is 'admin123' (hashed) - change this in production!
INSERT INTO "users" ("name", "email", "password", "is_admin", "created_at", "updated_at")
VALUES (
    'Admin',
    'admin@gestapo-terror-orte.de',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
);
