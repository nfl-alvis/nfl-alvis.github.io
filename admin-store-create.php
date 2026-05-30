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
    $operatingHours = operating_schedule_from_post($_POST['operating_hours'] ?? []);
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
      'operating_hours' => $operatingHours,
      'is_open' => operating_schedule_is_open_today($operatingHours) ? 1 : 0,
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
    redirect_to('admin-store-create.php');
  }
}

render_layout('Tambah Toko Baru', function (?array $user = null): void {
?>
  <div class="shell">
    <?php render_admin_sidebar($user, 'store-create'); ?>

    <main class="main">
      <div class="topbar">
        <div class="topbar-left">
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
                  <select id="store-region" name="region" required>
                    <?php render_province_options(); ?>
                  </select>
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
                  <div class="social-input social-input--whatsapp">
                    <span class="social-input-icon"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
                    <span class="social-input-divider" aria-hidden="true"></span>
                    <input id="store-whatsapp" type="text" name="whatsapp" required placeholder="6281234567890" />
                  </div>
                  <span class="field-hint">Format internasional tanpa tanda +</span>
                </div>
                <div class="field-wrap">
                  <label class="field-label" for="store-instagram">Instagram <span class="req">*</span></label>
                  <div class="social-input social-input--instagram">
                    <span class="social-input-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                    <span class="social-input-divider" aria-hidden="true"></span>
                    <input id="store-instagram" type="text" name="instagram" required placeholder="@namatoko" />
                  </div>
                </div>
              </div>

              <div class="sec-divider">
                <span class="sec-divider-label">Operasional</span>
              </div>

              <div class="grid-2">
                <div class="field-wrap create-store-operating-hours">
                  <span class="field-label">Jam Operasional <span class="req">*</span></span>
                  <?php render_operating_hours_selects(null); ?>
                  <span class="field-hint">Status buka/tutup toko otomatis mengikuti jam operasional hari ini.</span>
                </div>
              </div>

              <div class="sec-divider">
                <span class="sec-divider-label">Media &amp; Deskripsi</span>
              </div>

              <div class="field-wrap">
                <label class="field-label" for="store-cover">Foto Toko</label>
                <label class="file-drop" for="store-cover">
                  <input id="store-cover" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" />
                  <div class="file-drop-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></div>
                  <div class="file-drop-text">
                    <strong>Klik untuk upload</strong> atau drag &amp; drop
                  </div>
                  <div class="file-drop-sub" id="storeCoverHint">JPG, PNG, WEBP &middot; Maks. 2 MB</div>
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
          <article class="store-profile-preview create-store-live-preview" aria-label="Pratinjau toko">
            <div class="store-profile-cover">
              <img id="storePreviewCover" src="<?= e(base_path('assets/image/PusakaRasa.webp')) ?>" alt="Pratinjau foto toko" />
              <span class="store-profile-status" id="storePreviewStatus">Buka</span>
            </div>
            <div class="store-profile-preview-body">
              <h2 id="storePreviewName">Nama Toko</h2>
              <div class="store-profile-region"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span id="storePreviewRegion">Wilayah</span></div>

              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-solid fa-align-left" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Deskripsi</div>
                  <p id="storePreviewDescription">Deskripsi singkat toko akan tampil di sini.</p>
                </div>
              </div>
              <div class="store-profile-preview-divider"></div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-solid fa-map-pin" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Alamat</div>
                  <p id="storePreviewAddress">Alamat lengkap toko</p>
                </div>
              </div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">WhatsApp</div>
                  <p id="storePreviewWhatsapp">6281234567890</p>
                </div>
              </div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Instagram</div>
                  <p id="storePreviewInstagram">@namatoko</p>
                </div>
              </div>
              <div class="store-profile-preview-row">
                <span class="store-profile-preview-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
                <div>
                  <div class="store-profile-preview-label">Jam Operasional</div>
                  <p id="storePreviewHours">Buka jam 08:00 - 21:00 setiap hari</p>
                </div>
              </div>
            </div>
          </article>

        </aside>
      </div>
    </main>
  </div>
  <script>
    (() => {
      const fields = {
        name: document.getElementById('store-name'),
        region: document.getElementById('store-region'),
        address: document.getElementById('store-address'),
        whatsapp: document.getElementById('store-whatsapp'),
        instagram: document.getElementById('store-instagram'),
        description: document.getElementById('store-description'),
        cover: document.getElementById('store-cover')
      };

      const preview = {
        cover: document.getElementById('storePreviewCover'),
        status: document.getElementById('storePreviewStatus'),
        name: document.getElementById('storePreviewName'),
        region: document.getElementById('storePreviewRegion'),
        address: document.getElementById('storePreviewAddress'),
        whatsapp: document.getElementById('storePreviewWhatsapp'),
        instagram: document.getElementById('storePreviewInstagram'),
        description: document.getElementById('storePreviewDescription'),
        hours: document.getElementById('storePreviewHours'),
        coverHint: document.getElementById('storeCoverHint')
      };

      const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

      const setText = (element, value, fallback) => {
        if (!element) return;
        element.textContent = String(value || '').trim() || fallback;
      };

      const selectedText = (select) => {
        if (!select || select.selectedIndex < 0) return '';
        const option = select.options[select.selectedIndex];
        return option?.value ? option.textContent : '';
      };

      const rowSlot = (row) => {
        const status = row.querySelector('.operating-hours-status')?.value || 'Buka';

        if (status === 'Tutup' || status === '24 Jam') {
          return status;
        }

        const inputs = row.querySelectorAll('.operating-hours-input');
        const open = inputs[0]?.value || '08:00';
        const close = inputs[1]?.value || '21:00';
        return open + ' - ' + close;
      };

      const operatingSummary = () => {
        const rows = Array.from(document.querySelectorAll('.operating-hours-row'));
        if (!rows.length) return { text: 'Buka jam 08:00 - 21:00 setiap hari', isOpen: true };

        const slots = rows.map(rowSlot);
        const uniqueSlots = Array.from(new Set(slots));
        const dayIndex = (new Date().getDay() + 6) % 7;
        const today = days[dayIndex] || 'Senin';
        const todaySlot = slots[dayIndex] || '08:00 - 21:00';
        const isOpen = todaySlot !== 'Tutup';

        if (uniqueSlots.length === 1) {
          const slot = uniqueSlots[0];
          if (slot === 'Tutup') return { text: 'Tutup setiap hari', isOpen: false };
          if (slot === '24 Jam') return { text: 'Buka 24 jam setiap hari', isOpen: true };
          return { text: 'Buka jam ' + slot + ' setiap hari', isOpen: true };
        }

        if (todaySlot === 'Tutup') return { text: 'Tutup hari ini (' + today + ')', isOpen: false };
        if (todaySlot === '24 Jam') return { text: 'Buka 24 jam hari ini (' + today + ')', isOpen: true };
        return { text: 'Buka jam ' + todaySlot + ' hari ini (' + today + ')', isOpen: true };
      };

      const syncPreview = () => {
        setText(preview.name, fields.name?.value, 'Nama Toko');
        setText(preview.region, selectedText(fields.region), 'Wilayah');
        setText(preview.address, fields.address?.value, 'Alamat lengkap toko');
        setText(preview.whatsapp, fields.whatsapp?.value, '6281234567890');
        setText(preview.instagram, fields.instagram?.value, '@namatoko');
        setText(preview.description, fields.description?.value, 'Deskripsi singkat toko akan tampil di sini.');

        const hours = operatingSummary();
        setText(preview.hours, hours.text, 'Buka jam 08:00 - 21:00 setiap hari');

        if (preview.status) {
          preview.status.textContent = hours.isOpen ? 'Buka' : 'Tutup';
          preview.status.classList.toggle('is-closed', !hours.isOpen);
        }
      };

      Object.values(fields).forEach((field) => {
        if (!field || field === fields.cover) return;
        field.addEventListener('input', syncPreview);
        field.addEventListener('change', syncPreview);
      });

      document.querySelectorAll('.operating-hours-status, .operating-hours-input').forEach((field) => {
        field.addEventListener('input', syncPreview);
        field.addEventListener('change', syncPreview);
      });

      fields.cover?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        if (preview.coverHint) {
          preview.coverHint.textContent = file.name;
        }

        const reader = new FileReader();
        reader.addEventListener('load', (readerEvent) => {
          if (preview.cover) {
            preview.cover.src = String(readerEvent.target.result || '');
          }
        });
        reader.readAsDataURL(file);
      });

      syncPreview();
    })();
  </script>
<?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body create-store-page']);
