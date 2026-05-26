CREATE TABLE IF NOT EXISTS stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  region VARCHAR(120) NOT NULL,
  address TEXT NOT NULL,
  whatsapp VARCHAR(30) NOT NULL,
  instagram VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  operating_hours VARCHAR(120) NOT NULL DEFAULT 'Setiap hari, 08.00 - 21.00 WIB',
  cover_image VARCHAR(255) NOT NULL,
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) NULL,
  role ENUM('user', 'store_admin', 'super_admin') NOT NULL DEFAULT 'user',
  store_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  type ENUM('Makanan', 'Minuman') NOT NULL,
  region VARCHAR(120) NOT NULL,
  short_description TEXT NOT NULL,
  long_description TEXT NOT NULL,
  price_display VARCHAR(40) NOT NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  review_count INT NOT NULL DEFAULT 0,
  tag_label VARCHAR(60) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  base_rating_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  base_review_count INT NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_products_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS store_visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  session_key VARCHAR(64) NOT NULL,
  visit_date DATE NOT NULL,
  visited_at DATETIME NOT NULL,
  UNIQUE KEY uniq_store_session_day (store_id, session_key, visit_date),
  CONSTRAINT fk_store_visits_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS product_views (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  store_id INT NOT NULL,
  session_key VARCHAR(64) NOT NULL,
  view_date DATE NOT NULL,
  viewed_at DATETIME NOT NULL,
  UNIQUE KEY uniq_product_session_day (product_id, session_key, view_date),
  CONSTRAINT fk_product_views_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_product_views_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  stars TINYINT NOT NULL,
  review_text TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT chk_reviews_stars CHECK (stars BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE products ADD COLUMN IF NOT EXISTS base_rating_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER image_path;
ALTER TABLE products ADD COLUMN IF NOT EXISTS base_review_count INT NOT NULL DEFAULT 0 AFTER base_rating_total;
ALTER TABLE products MODIFY COLUMN rating DECIMAL(2,1) NOT NULL DEFAULT 0.0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) NULL AFTER password_hash;
ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NOT NULL;
ALTER TABLE stores ADD COLUMN IF NOT EXISTS operating_hours VARCHAR(120) NOT NULL DEFAULT 'Setiap hari, 08.00 - 21.00 WIB' AFTER description;
ALTER TABLE stores ADD COLUMN IF NOT EXISTS is_open TINYINT(1) NOT NULL DEFAULT 1 AFTER cover_image;
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

INSERT INTO stores (name, slug, region, address, whatsapp, instagram, description, operating_hours, cover_image, is_open, created_at, updated_at)
SELECT 'RM Minang Pusako', 'rm-minang-pusako', 'Sumatera Barat', 'Jl. Veteran No. 18, Padang', '628123456789', '@rmminangpusako', 'Rumah makan Minang dengan rendang dan sate Padang sebagai menu unggulan.', 'Setiap hari, 08.00 - 21.00 WIB', 'assets/image/Rendang.jpeg', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM stores WHERE slug = 'rm-minang-pusako');

INSERT INTO stores (name, slug, region, address, whatsapp, instagram, description, operating_hours, cover_image, is_open, created_at, updated_at)
SELECT 'Dapur Jawa Lestari', 'dapur-jawa-lestari', 'Yogyakarta', 'Jl. Malioboro No. 23, Yogyakarta', '6281398765432', '@dapurjawalestari', 'Toko kuliner tradisional Jawa yang fokus pada gudeg, wedang, dan menu rumahan.', 'Senin - Minggu, 07.00 - 20.00 WIB', 'assets/image/Gudeg.jpg', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM stores WHERE slug = 'dapur-jawa-lestari');

INSERT INTO stores (name, slug, region, address, whatsapp, instagram, description, operating_hours, cover_image, is_open, created_at, updated_at)
SELECT 'Kedai Segar Nusantara', 'kedai-segar-nusantara', 'Jawa Barat', 'Jl. Braga No. 12, Bandung', '6281777011223', '@kedaisegarnusantara', 'Kedai minuman dan makanan ringan khas Nusantara dengan sajian segar untuk keluarga.', 'Setiap hari, 10.00 - 22.00 WIB', 'assets/image/Cendol.jpeg', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM stores WHERE slug = 'kedai-segar-nusantara');

DELETE FROM users
WHERE email NOT IN ('admin@pusakarasa.test', 'store@pusakarasa.test', 'user@pusakarasa.test');

INSERT INTO users (id, name, email, password_hash, role, store_id, is_active, created_at, updated_at)
VALUES (1, 'Super Admin', 'admin@pusakarasa.test', '$2y$12$VZRKzP.9abIlWzXHGpXVxOkxuijYgJZ4wPLCgFI8UsqRwhaDE9VVG', 'super_admin', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), password_hash = VALUES(password_hash), role = VALUES(role), store_id = VALUES(store_id), is_active = VALUES(is_active), updated_at = NOW();

INSERT INTO users (id, name, email, password_hash, role, store_id, is_active, created_at, updated_at)
VALUES (2, 'Admin Toko Minang', 'store@pusakarasa.test', '$2y$12$2YcqgPTT0x1Mn21xRi.U1uRyMgRzdV5n3WbRlOjBa.eDeV3zHJilW', 'store_admin', (SELECT id FROM stores WHERE slug = 'rm-minang-pusako' LIMIT 1), 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), password_hash = VALUES(password_hash), role = VALUES(role), store_id = VALUES(store_id), is_active = VALUES(is_active), updated_at = NOW();

INSERT INTO users (id, name, email, password_hash, role, store_id, is_active, created_at, updated_at)
VALUES (3, 'Pengunjung Demo', 'user@pusakarasa.test', '$2y$12$eq2N0zlTRlieU18zf7qINuCZkupheI.Crr/Ogilz1xCiDy8fnCmqm', 'user', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), password_hash = VALUES(password_hash), role = VALUES(role), store_id = VALUES(store_id), is_active = VALUES(is_active), updated_at = NOW();

ALTER TABLE users AUTO_INCREMENT = 4;

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'rm-minang-pusako' LIMIT 1), 'Rendang', 'rendang', 'Makanan', 'Sumatera Barat', 'Daging sapi dimasak lama dengan rempah khas Minang.', 'Rendang adalah ikon kuliner Nusantara yang kaya santan, rempah, dan proses memasak perlahan hingga bumbu meresap sempurna.', '45.000', 0, 0, '#pedas', 'assets/image/Rendang.jpeg', 0, 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'rendang');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'rm-minang-pusako' LIMIT 1), 'Sate Padang', 'sate-padang', 'Makanan', 'Sumatera Barat', 'Sate sapi dengan kuah kental rempah khas Padang.', 'Sate Padang disajikan dengan saus gurih pedas khas yang berpadu dengan daging lembut dan lontong.', '28.000', 0, 0, '#gurih', 'assets/image/Sate.jpg', 0, 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sate-padang');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'dapur-jawa-lestari' LIMIT 1), 'Gudeg', 'gudeg', 'Makanan', 'Yogyakarta', 'Nangka muda dimasak manis dengan santan dan telur pindang.', 'Gudeg menawarkan rasa manis gurih khas Jawa dengan sambal krecek dan lauk pelengkap yang menghangatkan.', '30.000', 0, 0, '#manis', 'assets/image/Gudeg.jpg', 0, 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'gudeg');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'kedai-segar-nusantara' LIMIT 1), 'Es Cendol', 'es-cendol', 'Minuman', 'Jawa Barat', 'Minuman segar dari santan, gula merah, dan tepung beras hijau.', 'Cendol menjadi pilihan minuman tradisional yang ringan, segar, dan cocok untuk penikmat minuman manis khas pasar.', '12.000', 0, 0, '#segar', 'assets/image/Cendol.jpeg', 0, 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'es-cendol');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'kedai-segar-nusantara' LIMIT 1), 'Pempek', 'pempek', 'Makanan', 'Sumatera Selatan', 'Ikan tenggiri kenyal dengan kuah cuko khas.', 'Pempek menghadirkan tekstur kenyal dengan cuko manis asam pedas yang khas Palembang.', '22.000', 0, 0, '#ikan', 'assets/image/Pempek.png', 0, 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'pempek');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'dapur-jawa-lestari' LIMIT 1), 'Wedang Jahe', 'wedang-jahe', 'Minuman', 'Jawa Tengah', 'Minuman jahe hangat untuk menemani malam yang dingin.', 'Wedang jahe disajikan hangat dengan aroma rempah yang kuat dan rasa manis yang lembut.', '14.000', 0, 0, '#hangat', 'assets/image/wedangJahe.png', 0, 0, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'wedang-jahe');

UPDATE products p
LEFT JOIN (
  SELECT product_id, ROUND(AVG(stars), 1) AS rating, COUNT(*) AS review_count
  FROM reviews
  GROUP BY product_id
) r ON r.product_id = p.id
SET p.rating = COALESCE(r.rating, 0),
    p.review_count = COALESCE(r.review_count, 0),
    p.base_rating_total = 0,
    p.base_review_count = 0;
