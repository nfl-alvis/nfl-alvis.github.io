<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/store-sidebar.php';

require_role(ROLE_STORE_ADMIN);
ensure_store_operational_columns();

$user = current_user();
$storeId = (int) ($user['store_id'] ?? 0);

if (!$storeId) {
  set_flash('error', 'Akun store admin belum terhubung ke toko.');
  redirect_to('index.php');
}

$storeStmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch();

if (is_post()) {
  try {
    $operatingHours = operating_schedule_from_post($_POST['operating_hours'] ?? []);
    $stmt = db()->prepare(
      'UPDATE stores
             SET name = :name,
                 region = :region,
                 address = :address,
                 whatsapp = :whatsapp,
                 instagram = :instagram,
                 description = :description,
                 operating_hours = :operating_hours,
                 is_open = :is_open,
                 updated_at = NOW()
             WHERE id = :id'
    );
    $stmt->execute([
      'name' => trim($_POST['name'] ?? ''),
      'region' => trim($_POST['region'] ?? ''),
      'address' => trim($_POST['address'] ?? ''),
      'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
      'instagram' => trim($_POST['instagram'] ?? ''),
      'description' => trim($_POST['description'] ?? ''),
      'operating_hours' => $operatingHours,
      'is_open' => operating_schedule_is_open_today($operatingHours) ? 1 : 0,
      'id' => $storeId,
    ]);
    set_flash('success', 'Informasi toko berhasil diperbarui.');
    redirect_to('store-profile.php');
  } catch (Throwable $exception) {
    set_flash('error', 'Informasi toko gagal diperbarui.');
    redirect_to('store-profile.php');
  }
}

render_layout('Profil Toko', function (?array $currentUser = null) use ($user, $store): void {
?>
  <div class="shell">
    <?php render_store_sidebar($user, $store, 'profile'); ?>

    <main class="main">
      <div class="topbar">
        <div>
          <div class="topbar-heading">Profil Toko</div>
          <div class="topbar-sub">Perbarui informasi toko yang tampil ke publik dan pelanggan.</div>
        </div>
        <div class="pill-role"><?= e($user['name']) ?> &bull; Store Admin</div>
      </div>

      <section class="store-profile-grid">
        <aside class="store-profile-aside" aria-label="Pratinjau toko">
          <article class="store-profile-preview">
            <div class="store-profile-cover">
              <img src="<?= e($store['cover_image'] !== '' ? base_path($store['cover_image']) : base_path('assets/image/PusakaRasa.webp')) ?>" alt="<?= e($store['name']) ?>" />
              <span class="store-profile-status <?= (int) ($store['is_open'] ?? 1) === 1 ? '' : 'is-closed' ?>"><?= (int) ($store['is_open'] ?? 1) === 1 ? 'Buka' : 'Tutup' ?></span>
            </div>
            <div class="store-profile-preview-body">
              <h2><?= e($store['name']) ?></h2>
              <div class="store-profile-region"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= e($store['region']) ?></div>

              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-solid fa-align-left" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Deskripsi</div>
                  <p><?= e($store['description']) ?></p>
                </div>
              </div>
              <div class="store-profile-preview-divider"></div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-solid fa-map-pin" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Alamat</div>
                  <p><?= e($store['address']) ?></p>
                </div>
              </div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">WhatsApp</div>
                  <p><?= e($store['whatsapp']) ?></p>
                </div>
              </div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Instagram</div>
                  <p><?= e($store['instagram']) ?></p>
                </div>
              </div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Jam Operasional</div>
                  <p><?= e(operating_hours_display($store['operating_hours'] ?? '')) ?></p>
                </div>
              </div>
            </div>
            <div class="store-profile-preview-actions">
              <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Lihat Toko
              </a>
              <a class="is-whatsapp" href="https://wa.me/<?= e($store['whatsapp']) ?>" target="_blank" rel="noreferrer">
                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp
              </a>
            </div>
          </article>

        </aside>

        <article class="form-card store-profile-form-card">
          <div class="form-card-head">
            <div>
              <div class="form-card-title">Informasi Toko</div>
              <div class="form-card-meta">Field bertanda <span class="required-mark">*</span> wajib diisi sebelum menyimpan</div>
            </div>
            <span class="badge-new">Edit Profil</span>
          </div>


          <div class="store-profile-notice">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Perubahan informasi toko akan langsung tampil ke halaman publik setelah disimpan. Pastikan semua data sudah benar.</span>
          </div>

          <form method="post" class="store-profile-form">
            <div class="sec-divider"><span class="sec-divider-label">Data Utama</span></div>
            <div class="form-body">
              <div class="grid-2">
                <label class="field-wrap">
                  <span class="field-label">Nama Toko <span class="required-mark">*</span></span>
                  <input type="text" name="name" value="<?= e($store['name']) ?>" required />
                </label>
                <label class="field-wrap">
                  <span class="field-label">Wilayah <span class="required-mark">*</span></span>
                  <select name="region" required>
                    <?php render_province_options($store['region'] ?? ''); ?>
                  </select>
                </label>
              </div>
              <label class="field-wrap">
                <span class="field-label">Alamat Lengkap <span class="required-mark">*</span></span>
                <textarea name="address" required><?= e($store['address']) ?></textarea>
              </label>
            </div>

            <div class="sec-divider"><span class="sec-divider-label">Kontak &amp; Media Sosial</span></div>
            <div class="form-body">
              <div class="grid-2">
                <label class="field-wrap">
                  <span class="field-label">WhatsApp <span class="required-mark">*</span></span>
                  <span class="social-input social-input--whatsapp">
                    <span class="social-input-icon"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
                    <span class="social-input-divider" aria-hidden="true"></span>
                    <input type="text" name="whatsapp" value="<?= e($store['whatsapp']) ?>" required />
                  </span>
                  <span class="field-hint">Format internasional tanpa tanda +</span>
                </label>
                <label class="field-wrap">
                  <span class="field-label">Instagram <span class="required-mark">*</span></span>
                  <span class="social-input social-input--instagram">
                    <span class="social-input-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                    <span class="social-input-divider" aria-hidden="true"></span>
                    <input type="text" name="instagram" value="<?= e($store['instagram']) ?>" required />
                  </span>
                  <span class="field-hint">Sertakan awalan @ pada username</span>
                </label>
              </div>
            </div>

            <div class="sec-divider"><span class="sec-divider-label">Operasional Toko</span></div>
            <div class="form-body">
              <div class="grid-2">
                <div class="field-wrap store-profile-operating-hours">
                  <span class="field-label">Jam Operasional <span class="required-mark">*</span></span>
                  <?php render_operating_hours_selects($store['operating_hours'] ?? ''); ?>
                  <span class="field-hint">Pilih status, lalu isi jam buka dan jam tutup untuk masing-masing hari.</span>
                </div>
              </div>
            </div>

            <div class="sec-divider"><span class="sec-divider-label">Deskripsi Toko</span></div>
            <div class="form-body">
              <label class="field-wrap">
                <span class="field-label">Deskripsi <span class="required-mark">*</span></span>
                <textarea name="description" id="storeDescription" required><?= e($store['description']) ?></textarea>
                <span class="store-profile-counter"><span id="storeDescriptionCount"></span> karakter</span>
              </label>
            </div>

            <div class="submit-bar">
              <p class="submit-note">Data toko diperbarui secara langsung di halaman publik PusakaRasa.</p>
              <div class="store-profile-submit-actions">
                <button class="store-profile-reset" type="reset">Reset</button>
                <button class="btn-submit" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Simpan Perubahan Toko</button>
              </div>
            </div>
          </form>
        </article>
      </section>
    </main>
  </div>
  <script>
    (() => {
      const description = document.getElementById('storeDescription');
      const count = document.getElementById('storeDescriptionCount');
      if (!description || !count) return;
      const updateCount = () => {
        count.textContent = String(description.value.length);
      };
      description.addEventListener('input', updateCount);
      description.form.addEventListener('reset', () => window.setTimeout(updateCount, 0));
      updateCount();
    })();
  </script>
<?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
