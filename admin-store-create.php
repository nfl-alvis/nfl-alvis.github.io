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
    redirect_to('admin-store-create.php');
  }
}

render_layout('Tambah Toko Baru', function (?array $user = null): void {
  $userName = (string) ($user['name'] ?? 'Super Admin');
  $userEmail = (string) ($user['email'] ?? 'admin@pusaka.id');
?>
  <div class="shell">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-brand-name">PusakaRasa</div>
        <div class="sidebar-brand-role">Super Admin Dashboard</div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>
        <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="nav-link">
          <span class="nav-link-icon">🏠</span>
          Dashboard
        </a>
        <a href="<?= e(base_path('admin-store-create.php')) ?>" class="nav-link active">
          <span class="nav-link-icon">🏪</span>
          Tambah Toko
        </a>
        <a href="<?= e(base_path('admin-store-admin-create.php')) ?>" class="nav-link">
          <span class="nav-link-icon">👤</span>
          Buat Store Admin
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Platform</div>

        <a href="<?= e(base_path('index.php')) ?>" class="nav-link">
          <span class="nav-link-icon">🌐</span>
          Beranda
        </a>
        <a href="<?= e(base_path('katalog.php')) ?>" class="nav-link">
          <span class="nav-link-icon">📦</span>
          Katalog
        </a>

        <div class="nav-divider"></div>

        <a href="<?= e(base_path('logout.php')) ?>" class="nav-link" style="margin-top:auto;color:#c0645a;">
          <span class="nav-link-icon" style="font-size:14px">🚪</span>
          Keluar
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="sidebar-avatar"><?= e(strtoupper(substr($userName, 0, 2))) ?></div>
          <div>
            <div class="sidebar-user-name"><?= e($userName) ?></div>
            <div class="sidebar-user-role"><?= e($userEmail) ?></div>
          </div>
        </div>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="topbar-left">
          <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="back-btn" title="Kembali ke Dashboard">&#8592;</a>
          <div>
            <div class="topbar-heading">Tambah Toko Baru</div>
            <div class="topbar-sub">Isi data toko sebelum dipublikasikan ke platform</div>
          </div>
        </div>
        <div class="pill-role">Super Admin</div>
      </div>

      <div class="content-grid">
        <div class="form-card">
          <div class="form-card-head">
            <div>
              <div class="form-card-title">Informasi Toko</div>
              <div class="form-card-meta">Field bertanda <span class="req">*</span> wajib diisi sebelum menyimpan</div>
            </div>
            <span class="badge-new">Form Baru</span>
          </div>


          <form method="post" enctype="multipart/form-data">
            <div class="form-body">
              <div class="sec-divider">
                <span class="sec-divider-label">Data Utama</span>
              </div>

              <div class="grid-2">
                <div class="field-wrap">
                  <label class="field-label" for="store-name">Nama Toko <span class="req">*</span></label>
                  <input id="store-name" type="text" name="name" required placeholder="Rumah Makan Nusantara" />
                </div>
                <div class="field-wrap">
                  <label class="field-label" for="store-region">Wilayah <span class="req">*</span></label>
                  <input id="store-region" type="text" name="region" required placeholder="Jawa Timur" />
                </div>
              </div>

              <div class="field-wrap">
                <label class="field-label" for="store-address">Alamat Lengkap <span class="req">*</span></label>
                <textarea id="store-address" name="address" required placeholder="Jl. Contoh No. 12, Kecamatan, Kota, Provinsi…"></textarea>
              </div>

              <div class="sec-divider">
                <span class="sec-divider-label">Kontak</span>
              </div>

              <div class="grid-2">
                <div class="field-wrap">
                  <label class="field-label" for="store-whatsapp">WhatsApp <span class="req">*</span></label>
                  <input id="store-whatsapp" type="text" name="whatsapp" required placeholder="6281234567890" />
                  <span class="field-hint">Format internasional tanpa tanda +</span>
                </div>
                <div class="field-wrap">
                  <label class="field-label" for="store-instagram">Instagram <span class="req">*</span></label>
                  <input id="store-instagram" type="text" name="instagram" required placeholder="@namatoko" />
                </div>
              </div>

              <div class="sec-divider">
                <span class="sec-divider-label">Media &amp; Deskripsi</span>
              </div>

              <div class="field-wrap">
                <label class="field-label" for="store-cover">Foto Toko</label>
                <label class="file-drop" for="store-cover">
                  <input id="store-cover" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" />
                  <div class="file-drop-icon">📸</div>
                  <div class="file-drop-text">
                    <strong>Klik untuk upload</strong> atau drag &amp; drop
                  </div>
                  <div class="file-drop-sub">JPG, PNG, WEBP &middot; Maks. 2 MB</div>
                </label>
              </div>

              <div class="field-wrap">
                <label class="field-label" for="store-description">Deskripsi <span class="req">*</span></label>
                <textarea id="store-description" name="description" required placeholder="Deskripsi singkat tentang toko dan spesialisasinya…" style="min-height:120px"></textarea>
              </div>
            </div>

            <div class="submit-bar">
              <button type="submit" class="btn-submit">
                <span class="btn-icon">✓</span>
                Tambah Toko
              </button>
            </div>
          </form>
        </div>

        <aside class="info-stack">
          <div class="info-card">
            <div class="preview-box">
              <div class="preview-box-icon">🏪</div>
              <div class="preview-box-label">Preview foto toko</div>
            </div>

            <div class="info-card-title">
              <span class="info-title-icon">✅</span>
              Checklist Sebelum Simpan
            </div>
            <ul class="checklist">
              <li><span class="check-dot">1</span> Nama &amp; wilayah sudah diisi</li>
              <li><span class="check-dot">2</span> Nomor WA format internasional</li>
              <li><span class="check-dot">3</span> Foto toko telah diupload</li>
              <li><span class="check-dot">4</span> Deskripsi singkat &amp; jelas</li>
              <li><span class="check-dot">5</span> Instagram pakai awalan @</li>
            </ul>
          </div>

          <div class="info-card">
            <div class="info-card-title">
              <span class="info-title-icon">💡</span>
              Tips
            </div>
            <div class="tip-box">
              Toko yang baru dibuat langsung masuk ke daftar <strong>aktif</strong>. Pastikan semua informasi sudah benar sebelum menyimpan agar tidak perlu edit ulang.
            </div>
          </div>

          <div class="info-card info-card-green">
            <div class="info-card-title">
              <span class="info-title-icon">🔒</span>
              Akses Super Admin
            </div>
            <ul class="checklist">
              <li><span class="check-dot check-dot-green">✓</span> Buat &amp; kelola semua toko</li>
              <li><span class="check-dot check-dot-green">✓</span> Assign store admin</li>
              <li><span class="check-dot check-dot-green">✓</span> Lihat seluruh katalog</li>
            </ul>
          </div>
        </aside>
      </div>
    </main>
  </div>
<?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body create-store-page']);
