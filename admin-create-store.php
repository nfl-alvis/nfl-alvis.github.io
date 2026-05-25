<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post()) {
    try {
        $name = trim($_POST['name'] ?? '');
        $coverImage = save_uploaded_store_image($_FILES['cover_image'] ?? []);

        $stmt = db()->prepare(
            'INSERT INTO stores
             (name, slug, region, address, whatsapp, instagram, description, cover_image, is_active, created_at, updated_at)
             VALUES
             (:name, :slug, :region, :address, :whatsapp, :instagram, :description, :cover_image, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => slugify($name . '-' . substr((string) time(), -4)),
            'region' => trim($_POST['region'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'cover_image' => $coverImage,
        ]);

        set_flash('success', 'Toko baru berhasil dibuat.');
        redirect_to('admin-dashboard.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Data gagal disimpan. Cek input toko yang mungkin duplikat atau tidak valid.');
        redirect_to('admin-create-store.php');
    }
}

render_layout('Tambah Toko Baru', function (?array $user = null): void {
    ?>
    <div class="create-store-shell">
      <aside class="create-store-sidebar">
        <div class="create-store-sidebar-top">
          <div class="create-store-brand">PusakaRasa</div>
          <div class="create-store-role">Super Admin Dashboard</div>
        </div>

        <nav class="create-store-nav">
          <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="create-store-nav-link">
            <span class="create-store-nav-icon">🏠</span>
            Dashboard
          </a>
          <a href="<?= e(base_path('admin-create-store.php')) ?>" class="create-store-nav-link active">
            <span class="create-store-nav-icon">🏪</span>
            Tambah Toko
          </a>
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>" class="create-store-nav-link">
            <span class="create-store-nav-icon">👤</span>
            Buat Store Admin
          </a>

          <div class="create-store-divider"></div>

          <a href="<?= e(base_path('index.php')) ?>" class="create-store-nav-link">
            <span class="create-store-nav-icon">🌐</span>
            Beranda
          </a>
          <a href="<?= e(base_path('katalog.php')) ?>" class="create-store-nav-link">
            <span class="create-store-nav-icon">📦</span>
            Katalog
          </a>
          <a href="<?= e(base_path('logout.php')) ?>" class="create-store-nav-link create-store-logout">
            <span class="create-store-nav-icon">🚪</span>
            Keluar
          </a>
        </nav>

        <div class="create-store-sidebar-user">
          <div class="create-store-avatar">SA</div>
          <div>
            <div class="create-store-user-name"><?= e($user['name'] ?? 'Super Admin') ?></div>
            <div class="create-store-user-role"><?= e($user['email'] ?? 'admin@pusaka.id') ?></div>
          </div>
        </div>
      </aside>

      <main class="create-store-main">
        <div class="create-store-topbar">
          <div class="create-store-topbar-left">
            <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="create-store-back-btn" aria-label="Kembali ke dashboard">←</a>
            <div>
              <div class="create-store-title">Tambah Toko Baru</div>
              <div class="create-store-subtitle">Isi data toko sebelum dipublikasikan ke platform</div>
            </div>
          </div>
          <div class="create-store-pill">Super Admin</div>
        </div>

        <div class="create-store-content-grid">
          <section class="create-store-form-card">
            <div class="create-store-form-head">
              <div>
                <div class="create-store-form-title">Informasi Toko</div>
                <div class="create-store-form-meta">Field bertanda * wajib diisi sebelum menyimpan</div>
              </div>
              <span class="create-store-badge">Form Baru</span>
            </div>

            <div class="create-store-progress">
              <div class="create-store-step done">
                <div class="create-store-step-num">✓</div>
                <div class="create-store-step-label">Data Utama</div>
              </div>
              <div class="create-store-step active">
                <div class="create-store-step-num">2</div>
                <div class="create-store-step-label">Kontak</div>
              </div>
              <div class="create-store-step">
                <div class="create-store-step-num">3</div>
                <div class="create-store-step-label">Media</div>
              </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="create-store-form">
              <div class="create-store-section-divider">
                <span>Data Utama</span>
              </div>

              <div class="create-store-grid-2">
                <label class="create-store-field">
                  <span class="create-store-field-label">Nama Toko <span class="req">*</span></span>
                  <input type="text" name="name" required placeholder="Rumah Makan Nusantara" />
                </label>
                <label class="create-store-field">
                  <span class="create-store-field-label">Wilayah <span class="req">*</span></span>
                  <input type="text" name="region" required placeholder="Jawa Timur" />
                </label>
              </div>

              <label class="create-store-field">
                <span class="create-store-field-label">Alamat Lengkap <span class="req">*</span></span>
                <textarea name="address" required placeholder="Jl. Contoh No. 12, Kecamatan, Kota, Provinsi…"></textarea>
              </label>

              <div class="create-store-section-divider">
                <span>Kontak</span>
              </div>

              <div class="create-store-grid-2">
                <label class="create-store-field">
                  <span class="create-store-field-label">WhatsApp <span class="req">*</span></span>
                  <input type="text" name="whatsapp" required placeholder="6281234567890" />
                  <span class="create-store-field-hint">Format internasional tanpa tanda +</span>
                </label>
                <label class="create-store-field">
                  <span class="create-store-field-label">Instagram <span class="req">*</span></span>
                  <input type="text" name="instagram" required placeholder="@namatoko" />
                </label>
              </div>

              <div class="create-store-section-divider">
                <span>Media &amp; Deskripsi</span>
              </div>

              <label class="create-store-field">
                <span class="create-store-field-label">Foto Toko</span>
                <label class="create-store-file-drop">
                  <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" />
                  <div class="create-store-file-icon">📸</div>
                  <div class="create-store-file-text">
                    <strong>Klik untuk upload</strong> atau drag &amp; drop
                  </div>
                  <div class="create-store-file-sub">JPG, PNG, WEBP &middot; Maks. 2 MB</div>
                </label>
              </label>

              <label class="create-store-field">
                <span class="create-store-field-label">Deskripsi <span class="req">*</span></span>
                <textarea name="description" required placeholder="Deskripsi singkat tentang toko dan spesialisasinya…"></textarea>
              </label>

              <div class="create-store-submit-bar">
                <p class="create-store-submit-note">Data akan langsung masuk ke daftar toko aktif setelah disimpan.</p>
                <button type="submit" class="create-store-submit-btn">
                  <span class="create-store-btn-icon">✓</span>
                  Tambah Toko
                </button>
              </div>
            </form>
          </section>

          <aside class="create-store-info-stack">
            <div class="create-store-info-card">
              <div class="create-store-preview-box">
                <div class="create-store-preview-icon">🏪</div>
                <div class="create-store-preview-label">Preview foto toko</div>
              </div>
              <div class="create-store-info-title">
                <span class="create-store-info-icon">✅</span>
                Checklist Sebelum Simpan
              </div>
              <ul class="create-store-checklist">
                <li><span class="create-store-check-dot">1</span> Nama &amp; wilayah sudah diisi</li>
                <li><span class="create-store-check-dot">2</span> Nomor WA format internasional</li>
                <li><span class="create-store-check-dot">3</span> Foto toko telah diupload</li>
                <li><span class="create-store-check-dot">4</span> Deskripsi singkat &amp; jelas</li>
                <li><span class="create-store-check-dot">5</span> Instagram pakai awalan @</li>
              </ul>
            </div>

            <div class="create-store-info-card">
              <div class="create-store-info-title">
                <span class="create-store-info-icon">💡</span>
                Tips
              </div>
              <div class="create-store-tip-box">
                Toko yang baru dibuat langsung masuk ke daftar <strong>aktif</strong>. Pastikan semua informasi sudah benar sebelum menyimpan agar tidak perlu edit ulang.
              </div>
            </div>

            <div class="create-store-info-card create-store-info-card-green">
              <div class="create-store-info-title">
                <span class="create-store-info-icon">🔒</span>
                Akses Super Admin
              </div>
              <ul class="create-store-checklist">
                <li><span class="create-store-check-dot create-store-check-dot-green">✓</span> Buat &amp; kelola semua toko</li>
                <li><span class="create-store-check-dot create-store-check-dot-green">✓</span> Assign store admin</li>
                <li><span class="create-store-check-dot create-store-check-dot-green">✓</span> Lihat seluruh katalog</li>
              </ul>
            </div>
          </aside>
        </div>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body create-store-body']);
