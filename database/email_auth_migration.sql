ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) NULL AFTER password_hash;
ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL AFTER profile_image;
ALTER TABLE users ADD COLUMN IF NOT EXISTS picture TEXT NULL AFTER google_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_provider ENUM('local','google') NOT NULL DEFAULT 'local' AFTER picture;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER auth_provider;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verify_token VARCHAR(255) NULL AFTER email_verified;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verify_expires DATETIME NULL AFTER email_verify_token;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL AFTER email_verify_expires;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL AFTER reset_token;

UPDATE users
SET email_verified = 1
WHERE email_verified = 0
  AND email_verify_token IS NULL
  AND auth_provider = 'local';
