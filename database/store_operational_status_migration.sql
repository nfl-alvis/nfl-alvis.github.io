ALTER TABLE stores
  ADD COLUMN IF NOT EXISTS operating_hours VARCHAR(500) NOT NULL DEFAULT 'Setiap hari, 08.00 - 21.00 WIB' AFTER description;
ALTER TABLE stores
  MODIFY COLUMN operating_hours VARCHAR(500) NOT NULL DEFAULT 'Setiap hari, 08.00 - 21.00 WIB';

ALTER TABLE stores
  ADD COLUMN IF NOT EXISTS is_open TINYINT(1) NOT NULL DEFAULT 1 AFTER cover_image;

SET @stores_has_is_active = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND COLUMN_NAME = 'is_active'
);

SET @stores_status_migration = IF(
  @stores_has_is_active > 0,
  'UPDATE stores SET is_open = is_active',
  'SELECT 1'
);

PREPARE migrate_stores_status FROM @stores_status_migration;
EXECUTE migrate_stores_status;
DEALLOCATE PREPARE migrate_stores_status;

ALTER TABLE stores DROP COLUMN IF EXISTS is_active;
