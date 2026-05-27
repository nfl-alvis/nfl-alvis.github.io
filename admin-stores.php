<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);
ensure_store_operational_columns();

$listingQuery = $_GET;
unset($listingQuery['edit']);
$listingPath = 'admin-stores.php' . ($listingQuery ? '?' . http_build_query($listingQuery) : '');
$listingUrl = base_path($listingPath);

if (is_post() && ($_POST['action'] ?? '') === 'edit_store') {
    $storeId = (int) ($_POST['id'] ?? 0);

    try {
        $name = trim($_POST['name'] ?? '');
        db()->prepare(
            'UPDATE stores
             SET name = :name, slug = :slug, region = :region, address = :address, whatsapp = :whatsapp, instagram = :instagram,
                 description = :description, operating_hours = :operating_hours, is_open = :is_open,
                 cover_image = :cover_image, updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $storeId,
            'name' => $name,
            'slug' => slugify($name . '-' . substr((string) time(), -4)),
            'region' => trim($_POST['region'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'operating_hours' => operating_schedule_from_post($_POST['operating_hours'] ?? []),
            'is_open' => ($_POST['is_open'] ?? '0') === '1' ? 1 : 0,
            'cover_image' => save_uploaded_store_image($_FILES['cover_image'] ?? [], trim($_POST['current_cover_image'] ?? '')),
        ]);
        set_flash('success', 'Toko berhasil diperbarui.');
        redirect_to($listingPath);
    } catch (Throwable $exception) {
        set_flash('error', 'Gagal memperbarui toko.');
        redirect_to('admin-stores.php?' . http_build_query(array_merge($listingQuery, ['edit' => $storeId])));
    }
}

if (is_post() && ($_POST['action'] ?? '') === 'toggle_store_status') {
    try {
        $isOpen = ($_POST['is_open'] ?? '0') === '1' ? 1 : 0;
        db()->prepare('UPDATE stores SET is_open = :is_open, updated_at = NOW() WHERE id = :id')->execute([
            'id' => (int) ($_POST['id'] ?? 0),
            'is_open' => $isOpen,
        ]);
        set_flash('success', $isOpen === 1 ? 'Status toko diubah menjadi buka.' : 'Status toko diubah menjadi tutup.');
    } catch (Throwable $exception) {
        set_flash('error', 'Status buka/tutup toko gagal diperbarui.');
    }
    redirect_to($listingPath);
}

$storeSearch = trim($_GET['store_search'] ?? '');
$storeSort = trim($_GET['store_sort'] ?? 'created_desc');
$storePage = max(1, (int) ($_GET['store_page'] ?? 1));
$storePerPage = max(1, (int) ($_GET['store_per_page'] ?? 8));

$stores = array_values(array_filter(all_stores_with_admins(), static function (array $item) use ($storeSearch): bool {
    if ($storeSearch === '') {
        return true;
    }

    $haystack = strtolower(($item['name'] ?? '') . ' ' . ($item['region'] ?? '') . ' ' . ($item['admins'] ?? ''));
    return strpos($haystack, strtolower($storeSearch)) !== false;
}));

usort($stores, static function (array $a, array $b) use ($storeSort): int {
    return match ($storeSort) {
        'name_asc' => strcmp((string) $a['name'], (string) $b['name']),
        'name_desc' => strcmp((string) $b['name'], (string) $a['name']),
        default => strcmp((string) $b['created_at'], (string) $a['created_at']),
    };
});

$storesPage = paginate_array($stores, $storePage, $storePerPage);
$editingStore = null;
$editStoreId = (int) ($_GET['edit'] ?? 0);

if ($editStoreId > 0) {
    $stmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editStoreId]);
    $editingStore = $stmt->fetch() ?: null;

    if (!$editingStore) {
        set_flash('error', 'Toko tidak ditemukan.');
        redirect_to($listingPath);
    }
}

render_layout('Manajemen Toko', function (?array $user = null) use ($storeSearch, $storeSort, $storePerPage, $storesPage, $editingStore, $listingQuery, $listingUrl): void {
    $userName = (string) ($user['name'] ?? 'Super Admin');
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'stores'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Manajemen Toko</div>
            <div class="topbar-sub">Kelola mitra toko, admin, dan jumlah produknya.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> &bull; Super Admin</div>
        </div>

        <section class="management-layout">
          <article class="table-card management-table-card">
            <div class="table-card-head">
              <div>
                <h3>Daftar Toko</h3>
                <div class="table-meta">Menampilkan <?= e((string) count($storesPage['items'])) ?> dari <?= e((string) $storesPage['total']) ?> data</div>
              </div>
              <a class="action-button-link" href="<?= e(base_path('admin-store-create.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i>Tambah Toko Baru</a>
            </div>

            <form class="form-panel management-filters" method="get">
              <label>Cari
                <input type="search" name="store_search" value="<?= e($storeSearch) ?>" placeholder="Nama toko, wilayah, admin" />
              </label>
              <label>Sorting
                <select name="store_sort">
                  <option value="created_desc" <?= $storeSort === 'created_desc' ? 'selected' : '' ?>>Terbaru</option>
                  <option value="name_asc" <?= $storeSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                  <option value="name_desc" <?= $storeSort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                </select>
              </label>
              <label>Baris
                <select name="store_per_page">
                  <?php foreach ([5, 10, 20, 50] as $amount): ?>
                    <option value="<?= e((string) $amount) ?>" <?= $storePerPage === $amount ? 'selected' : '' ?>><?= e((string) $amount) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Terapkan</button>
              <a class="filter-reset" href="<?= e(base_path('admin-stores.php')) ?>">Reset</a>
            </form>

            <div class="table-scroll">
              <table class="data-table">
                <thead>
                  <tr><th>No</th><th>Toko</th><th>Wilayah</th><th>Jam Operasional</th><th>Status</th><th>Admin</th><th>Produk</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($storesPage['items'] as $index => $item): ?>
                    <tr>
                      <td><?= e((string) ($storesPage['offset'] + $index + 1)) ?></td>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['region']) ?></td>
                      <td><?= e(operating_hours_display($item['operating_hours'] ?? '')) ?></td>
                      <td><span class="store-status-badge <?= (int) ($item['is_open'] ?? 1) === 1 ? 'is-open' : 'is-closed' ?>"><?= (int) ($item['is_open'] ?? 1) === 1 ? 'Buka' : 'Tutup' ?></span></td>
                      <td><?= e($item['admins'] ?: '-') ?></td>
                      <td><?= e((string) $item['product_count']) ?></td>
                      <td>
                        <div class="table-actions">
                          <a class="inline-link" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['edit' => $item['id']])))) ?>">Edit</a>
                          <form method="post">
                            <input type="hidden" name="action" value="toggle_store_status" />
                            <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>" />
                            <input type="hidden" name="is_open" value="<?= (int) ($item['is_open'] ?? 1) === 1 ? '0' : '1' ?>" />
                            <button type="submit" class="inline-link"><?= (int) ($item['is_open'] ?? 1) === 1 ? 'Tutup' : 'Buka' ?></button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="table-pagination">
              <span class="table-meta">Halaman <?= e((string) $storesPage['page']) ?> dari <?= e((string) $storesPage['total_pages']) ?></span>
              <div class="page-nav">
                <a class="page-btn" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['store_page' => max(1, $storesPage['page'] - 1)])))) ?>">&larr;</a>
                <?php for ($p = max(1, $storesPage['page'] - 2); $p <= min($storesPage['total_pages'], $storesPage['page'] + 2); $p++): ?>
                  <a class="page-btn <?= $p === $storesPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['store_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['store_page' => min($storesPage['total_pages'], $storesPage['page'] + 1)])))) ?>">&rarr;</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>

    <?php if ($editingStore): ?>
      <div class="store-product-modal-backdrop" id="adminStoreEditModal" data-close-url="<?= e($listingUrl) ?>">
        <section class="admin-product-edit-modal admin-store-edit-modal" role="dialog" aria-modal="true" aria-labelledby="adminStoreEditTitle">
          <article class="form-card">
            <div class="form-card-head">
              <div>
                <div class="form-card-title" id="adminStoreEditTitle">Edit Toko</div>
                <div class="form-card-meta">Perbarui data <?= e($editingStore['name']) ?> tanpa keluar dari daftar toko</div>
              </div>
              <a class="store-product-modal-close" href="<?= e($listingUrl) ?>" aria-label="Tutup form edit">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              </a>
            </div>

            <form method="post" enctype="multipart/form-data" action="<?= e($listingUrl) ?>">
              <input type="hidden" name="action" value="edit_store" />
              <input type="hidden" name="id" value="<?= e((string) $editingStore['id']) ?>" />
              <input type="hidden" name="current_cover_image" value="<?= e($editingStore['cover_image']) ?>" />

              <div class="form-body">
                <div class="sec-divider">
                  <span class="sec-divider-label">Data Utama</span>
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="admin-store-name">Nama Toko <span class="req">*</span></label>
                    <input id="admin-store-name" type="text" name="name" value="<?= e($editingStore['name']) ?>" required />
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="admin-store-region">Wilayah <span class="req">*</span></label>
                    <select id="admin-store-region" name="region" required>
                      <?php render_province_options($editingStore['region'] ?? ''); ?>
                    </select>
                  </div>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="admin-store-address">Alamat <span class="req">*</span></label>
                  <textarea id="admin-store-address" name="address" required><?= e($editingStore['address']) ?></textarea>
                </div>

                <div class="sec-divider">
                  <span class="sec-divider-label">Kontak</span>
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="admin-store-whatsapp">WhatsApp <span class="req">*</span></label>
                    <div class="social-input social-input--whatsapp">
                      <span class="social-input-icon"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
                      <span class="social-input-divider" aria-hidden="true"></span>
                      <input id="admin-store-whatsapp" type="text" name="whatsapp" value="<?= e($editingStore['whatsapp']) ?>" required />
                    </div>
                    <span class="field-hint">Format internasional tanpa tanda +</span>
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="admin-store-instagram">Instagram <span class="req">*</span></label>
                    <div class="social-input social-input--instagram">
                      <span class="social-input-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                      <span class="social-input-divider" aria-hidden="true"></span>
                      <input id="admin-store-instagram" type="text" name="instagram" value="<?= e($editingStore['instagram']) ?>" required />
                    </div>
                  </div>
                </div>

                <div class="sec-divider">
                  <span class="sec-divider-label">Operasional</span>
                </div>

                <div class="grid-2">
                  <div class="field-wrap admin-store-operating-hours">
                    <span class="field-label">Jam Operasional <span class="req">*</span></span>
                    <?php render_operating_hours_selects($editingStore['operating_hours'] ?? ''); ?>
                    <span class="field-hint">Pilih jam buka untuk masing-masing hari.</span>
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="admin-store-open">Status Toko <span class="req">*</span></label>
                    <select id="admin-store-open" name="is_open" required>
                      <option value="1" <?= (int) ($editingStore['is_open'] ?? 1) === 1 ? 'selected' : '' ?>>Buka</option>
                      <option value="0" <?= (int) ($editingStore['is_open'] ?? 1) === 0 ? 'selected' : '' ?>>Tutup</option>
                    </select>
                  </div>
                </div>

                <div class="sec-divider">
                  <span class="sec-divider-label">Media &amp; Deskripsi</span>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="admin-store-cover">Foto Toko</label>
                  <label class="file-drop" for="admin-store-cover">
                    <input id="admin-store-cover" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" />
                    <div class="file-drop-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></div>
                    <div class="file-drop-text">
                      <strong>Klik untuk upload</strong> atau drag &amp; drop
                    </div>
                    <div class="file-drop-sub">JPG, PNG, WEBP &middot; Kosongkan jika tidak diganti</div>
                  </label>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="admin-store-description">Deskripsi <span class="req">*</span></label>
                  <textarea id="admin-store-description" name="description" required><?= e($editingStore['description']) ?></textarea>
                </div>
              </div>

              <div class="submit-bar">
                <p class="submit-note">Perubahan akan langsung tampil pada halaman publik toko.</p>
                <div class="admin-product-edit-actions">
                  <a class="filter-reset" href="<?= e($listingUrl) ?>">Batal</a>
                  <button type="submit" class="btn-submit">
                    <span class="btn-icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                    Simpan Perubahan
                  </button>
                </div>
              </div>
            </form>
          </article>
        </section>
      </div>
      <script>
        (() => {
          const modal = document.getElementById('adminStoreEditModal');
          if (!modal) return;
          document.body.classList.add('has-store-product-modal');
          modal.querySelector('input[name="name"]').focus();
          modal.addEventListener('click', (event) => {
            if (event.target === modal) window.location.href = modal.dataset.closeUrl;
          });
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') window.location.href = modal.dataset.closeUrl;
          });
        })();
      </script>
    <?php endif; ?>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
