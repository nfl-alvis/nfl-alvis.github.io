<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

$uploadedCoverPath = null;

if (is_post()) {
    try {
        ensure_store_operational_columns();
        $name = trim($_POST['name'] ?? '');
        $coverImage = save_uploaded_store_image($_FILES['cover_image'] ?? []);
        $uploadedCoverPath = str_starts_with($coverImage, 'uploads/stores/') ? $coverImage : null;

        $stmt = db()->prepare(
            'INSERT INTO stores
             (name, slug, region, address, whatsapp, instagram, description, operating_hours, cover_image, is_open, created_at, updated_at)
             VALUES
             (:name, :slug, :region, :address, :whatsapp, :instagram, :description, :operating_hours, :cover_image, :is_open, NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => slugify($name . '-' . substr((string) time(), -4)),
            'region' => trim($_POST['region'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'operating_hours' => operating_schedule_from_post($_POST['operating_hours'] ?? []),
            'is_open' => ($_POST['is_open'] ?? '1') === '1' ? 1 : 0,
            'cover_image' => $coverImage,
        ]);

        set_flash('success', 'Toko baru berhasil dibuat.');
        redirect_to('admin-stores.php');
    } catch (Throwable $exception) {
        if ($uploadedCoverPath !== null) {
            $uploadedFile = __DIR__ . '/' . $uploadedCoverPath;
            if (is_file($uploadedFile)) {
                unlink($uploadedFile);
            }
        }

        $message = $exception instanceof PDOException
            ? 'Data toko gagal disimpan. Pastikan nama toko tidak duplikat lalu coba kembali.'
            : $exception->getMessage();
        set_flash('error', $message);
        redirect_to('admin-create-store.php');
    }
}

render_layout('Tambah Toko Baru', function (?array $user = null): void {
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'store-create'); ?>

      <main class="main create-store-main">
        <div class="create-store-topbar">
          <div class="create-store-topbar-left">
            <a href="<?= e(base_path('admin-stores.php')) ?>" class="create-store-back-btn" aria-label="Kembali ke daftar toko">&larr;</a>
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
                  <select name="region" required>
                    <?php render_province_options(); ?>
                  </select>
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
                  <span class="social-input social-input--whatsapp">
                    <span class="social-input-icon"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
                    <span class="social-input-divider" aria-hidden="true"></span>
                    <input type="text" name="whatsapp" required placeholder="6281234567890" />
                  </span>
                  <span class="create-store-field-hint">Format internasional tanpa tanda +</span>
                </label>
                <label class="create-store-field">
                  <span class="create-store-field-label">Instagram <span class="req">*</span></span>
                  <span class="social-input social-input--instagram">
                    <span class="social-input-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                    <span class="social-input-divider" aria-hidden="true"></span>
                    <input type="text" name="instagram" required placeholder="@namatoko" />
                  </span>
                </label>
              </div>

              <div class="create-store-section-divider">
                <span>Operasional</span>
              </div>

              <div class="create-store-grid-2">
                <div class="create-store-field create-store-operating-hours">
                  <span class="create-store-field-label">Jam Operasional <span class="req">*</span></span>
                  <?php render_operating_hours_selects(null); ?>
                  <span class="create-store-field-hint">Pilih jam buka untuk masing-masing hari.</span>
                </div>
                <label class="create-store-field">
                  <span class="create-store-field-label">Status Toko <span class="req">*</span></span>
                  <select name="is_open" required>
                    <option value="1">Buka</option>
                    <option value="0">Tutup</option>
                  </select>
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
                <p class="create-store-submit-note">Status buka atau tutup akan langsung tampil pada halaman publik.</p>
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
                Pilih status <strong>Buka</strong> atau <strong>Tutup</strong> sesuai kesiapan toko melayani pelanggan.
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
