CREATE TABLE IF NOT EXISTS stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  region VARCHAR(120) NOT NULL,
  address TEXT NOT NULL,
  whatsapp VARCHAR(30) NOT NULL,
  instagram VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  cover_image VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash CHAR(64) NOT NULL,
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
  rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
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

INSERT INTO stores (name, slug, region, address, whatsapp, instagram, description, cover_image, is_active, created_at, updated_at)
SELECT 'RM Minang Pusako', 'rm-minang-pusako', 'Sumatera Barat', 'Jl. Veteran No. 18, Padang', '628123456789', '@rmminangpusako', 'Rumah makan Minang dengan rendang dan sate Padang sebagai menu unggulan.', 'assets/image/Rendang.jpeg', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM stores WHERE slug = 'rm-minang-pusako');

INSERT INTO stores (name, slug, region, address, whatsapp, instagram, description, cover_image, is_active, created_at, updated_at)
SELECT 'Dapur Jawa Lestari', 'dapur-jawa-lestari', 'Yogyakarta', 'Jl. Malioboro No. 23, Yogyakarta', '6281398765432', '@dapurjawalestari', 'Toko kuliner tradisional Jawa yang fokus pada gudeg, wedang, dan menu rumahan.', 'assets/image/Gudeg.jpg', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM stores WHERE slug = 'dapur-jawa-lestari');

INSERT INTO stores (name, slug, region, address, whatsapp, instagram, description, cover_image, is_active, created_at, updated_at)
SELECT 'Kedai Segar Nusantara', 'kedai-segar-nusantara', 'Jawa Barat', 'Jl. Braga No. 12, Bandung', '6281777011223', '@kedaisegarnusantara', 'Kedai minuman dan makanan ringan khas Nusantara dengan sajian segar untuk keluarga.', 'assets/image/Cendol.jpeg', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM stores WHERE slug = 'kedai-segar-nusantara');

INSERT INTO users (name, email, password_hash, role, store_id, is_active, created_at, updated_at)
SELECT 'Super Admin', 'admin@pusakarasa.test', SHA2('Admin123!', 256), 'super_admin', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@pusakarasa.test');

INSERT INTO users (name, email, password_hash, role, store_id, is_active, created_at, updated_at)
SELECT 'Admin Toko Minang', 'store@pusakarasa.test', SHA2('Store123!', 256), 'store_admin', (SELECT id FROM stores WHERE slug = 'rm-minang-pusako' LIMIT 1), 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'store@pusakarasa.test');

INSERT INTO users (name, email, password_hash, role, store_id, is_active, created_at, updated_at)
SELECT 'Pengunjung Demo', 'user@pusakarasa.test', SHA2('User123!', 256), 'user', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'user@pusakarasa.test');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'rm-minang-pusako' LIMIT 1), 'Rendang', 'rendang', 'Makanan', 'Sumatera Barat', 'Daging sapi dimasak lama dengan rempah khas Minang.', 'Rendang adalah ikon kuliner Nusantara yang kaya santan, rempah, dan proses memasak perlahan hingga bumbu meresap sempurna.', '45.000', 4.9, 1200, '#pedas', 'assets/image/Rendang.jpeg', 5880.00, 1200, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'rendang');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'rm-minang-pusako' LIMIT 1), 'Sate Padang', 'sate-padang', 'Makanan', 'Sumatera Barat', 'Sate sapi dengan kuah kental rempah khas Padang.', 'Sate Padang disajikan dengan saus gurih pedas khas yang berpadu dengan daging lembut dan lontong.', '28.000', 4.8, 980, '#gurih', 'assets/image/Sate.jpg', 4704.00, 980, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sate-padang');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'dapur-jawa-lestari' LIMIT 1), 'Gudeg', 'gudeg', 'Makanan', 'Yogyakarta', 'Nangka muda dimasak manis dengan santan dan telur pindang.', 'Gudeg menawarkan rasa manis gurih khas Jawa dengan sambal krecek dan lauk pelengkap yang menghangatkan.', '30.000', 4.7, 860, '#manis', 'assets/image/Gudeg.jpg', 4042.00, 860, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'gudeg');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'kedai-segar-nusantara' LIMIT 1), 'Es Cendol', 'es-cendol', 'Minuman', 'Jawa Barat', 'Minuman segar dari santan, gula merah, dan tepung beras hijau.', 'Cendol menjadi pilihan minuman tradisional yang ringan, segar, dan cocok untuk penikmat minuman manis khas pasar.', '12.000', 5.0, 1500, '#segar', 'assets/image/Cendol.jpeg', 7500.00, 1500, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'es-cendol');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'kedai-segar-nusantara' LIMIT 1), 'Pempek', 'pempek', 'Makanan', 'Sumatera Selatan', 'Ikan tenggiri kenyal dengan kuah cuko khas.', 'Pempek menghadirkan tekstur kenyal dengan cuko manis asam pedas yang khas Palembang.', '22.000', 4.9, 1100, '#ikan', 'assets/image/Pempek.png', 5390.00, 1100, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'pempek');

INSERT INTO products (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
SELECT (SELECT id FROM stores WHERE slug = 'dapur-jawa-lestari' LIMIT 1), 'Wedang Jahe', 'wedang-jahe', 'Minuman', 'Jawa Tengah', 'Minuman jahe hangat untuk menemani malam yang dingin.', 'Wedang jahe disajikan hangat dengan aroma rempah yang kuat dan rasa manis yang lembut.', '14.000', 4.6, 420, '#hangat', 'assets/image/wedangJahe.png', 1932.00, 420, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'wedang-jahe');

UPDATE products
SET base_rating_total = ROUND(rating * review_count, 2),
    base_review_count = review_count
WHERE base_review_count = 0 AND review_count > 0;
