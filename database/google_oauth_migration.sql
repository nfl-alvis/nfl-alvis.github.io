ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL AFTER profile_image;
ALTER TABLE users ADD COLUMN IF NOT EXISTS picture TEXT NULL AFTER google_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_provider ENUM('local','google') NOT NULL DEFAULT 'local' AFTER picture;
