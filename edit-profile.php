<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$user = current_user();
if (!$user) {
  redirect_to('login.php');
}

if (is_post()) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $passwordConfirm = $_POST['password_confirm'] ?? '';
  $currentImage = trim((string) ($user['profile_image'] ?? ''));

  if ($name === '' || $email === '') {
    set_flash('error', 'Nama dan email wajib diisi.');
    redirect_to('edit-profile.php');
  }

  if ($password !== '' && $password !== $passwordConfirm) {
    set_flash('error', 'Konfirmasi kata sandi tidak sesuai.');
    redirect_to('edit-profile.php');
  }

  try {
    $profileImage = save_uploaded_profile_image($_FILES['profile_image'] ?? [], $currentImage);
    update_current_user_profile(
      (int) $user['id'],
      $name,
      $email,
      $password !== '' ? $password : null,
      $profileImage !== $currentImage ? $profileImage : null
    );
    set_flash('success', 'Profil berhasil diperbarui.');
    redirect_to('edit-profile.php');
  } catch (Throwable $exception) {
    $message = $exception instanceof RuntimeException && $exception->getMessage() === 'Email sudah dipakai akun lain.'
      ? 'Email sudah dipakai akun lain.'
      : 'Foto profil atau profil gagal diperbarui. Coba unggah ulang foto dengan format JPG, PNG, WEBP, atau GIF.';
    set_flash('error', $message);
    redirect_to('edit-profile.php');
  }
}

render_layout('Dashboard', function (?array $currentUser = null) use ($user): void {
  $profileImageUrl = user_profile_image_url($user);
  $roleLabel = match ($user['role']) {
    ROLE_SUPER_ADMIN => 'Super Admin',
    ROLE_STORE_ADMIN => 'Admin Toko',
    default => 'User',
  };
  $statusLabel = $user['is_active'] ? 'Aktif' : 'Nonaktif';
  $dashboardTarget = nav_target_for_user($user);
  $dashboardIconClass = $user['role'] === ROLE_USER ? 'fa-compass' : 'fa-table-columns';
  $dashboardLinkTitle = $user['role'] === ROLE_USER ? 'Jelajahi Katalog' : 'Dashboard Utama';
  $dashboardLinkCaption = $user['role'] === ROLE_USER ? 'Temukan makanan nusantara' : 'Masuk ke panel sesuai role';
?>
  <section class="profile-dashboard-banner">
    <div class="profile-dashboard-banner-inner">
      </a>
      <div class="profile-dashboard-copy">
        <div class="profile-dashboard-eyebrow">
          <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
          Akun Saya
        </div>
        <h1>Dashboard</h1>
        <p>Perbarui informasi akun, foto profil, dan kata sandi Anda.</p>
      </div>
    </div>
  </section>

  <section class="profile-edit-shell profile-dashboard-shell">
    <aside class="profile-panel profile-summary-card">
      <div class="summary-cover"></div>

      <div class="summary-avatar-wrap">
        <div class="summary-avatar-img <?= $profileImageUrl === '' ? 'is-default' : '' ?>">
          <?php if ($profileImageUrl !== ''): ?>
            <img src="<?= e($profileImageUrl) ?>" alt="<?= e($user['name']) ?>" />
          <?php else: ?>
            <i class="fa-regular fa-user" aria-hidden="true"></i>
          <?php endif; ?>
        </div>
        <div class="summary-badge"><?= e($statusLabel) ?></div>
      </div>

      <div class="summary-identity">
        <h3><?= e($user['name']) ?></h3>
        <p><?= e($user['email']) ?></p>
      </div>

      <div class="summary-stats">
        <div class="summary-stat">
          <strong><?= e($roleLabel) ?></strong>
          <span>tipe akun</span>
        </div>
        <div class="summary-stat">
          <strong><?= e($statusLabel) ?></strong>
          <span>status akun</span>
        </div>
      </div>

      <div class="summary-links">
        <a href="<?= e(base_path('favorites.php')) ?>" class="summary-link-item">
          <div class="summary-link-icon">
            <i class="fa-solid fa-heart" aria-hidden="true"></i>
          </div>
          <div class="summary-link-text">
            <strong>Favorit Saya</strong>
            <span>Lihat produk yang disimpan</span>
          </div>
          <i class="fa-solid fa-chevron-right summary-link-arrow" aria-hidden="true"></i>
        </a>
        <a href="<?= e(base_path($dashboardTarget)) ?>" class="summary-link-item">
          <div class="summary-link-icon green">
            <i class="fa-solid <?= e($dashboardIconClass) ?>" aria-hidden="true"></i>
          </div>
          <div class="summary-link-text">
            <strong><?= e($dashboardLinkTitle) ?></strong>
            <span><?= e($dashboardLinkCaption) ?></span>
          </div>
          <i class="fa-solid fa-chevron-right summary-link-arrow" aria-hidden="true"></i>
        </a>
        <div class="summary-link-item summary-link-static">
          <div class="summary-link-icon green">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
          </div>
          <div class="summary-link-text">
            <strong>Status Akun</strong>
            <span><?= e($user['is_active'] ? 'Aktif dan dapat mengakses platform.' : 'Akun tidak aktif.') ?></span>
          </div>
        </div>
      </div>
    </aside>

    <article class="profile-panel form-panel-wrap">
      <div class="form-card-header">
        <div>
          <div class="form-card-title">Dashboard Profil</div>
          <div class="form-card-meta">Perbarui foto profil, nama, email, dan kata sandi dari satu panel.</div>
        </div>
        <div class="form-tip-badge">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          Upload gambar langsung
        </div>
      </div>

      <div class="form-notice">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>Foto yang Anda unggah akan tampil di navbar, dropdown profil, dan area akun. Format yang didukung: JPG, PNG, WEBP.</span>
      </div>

      <form method="post" enctype="multipart/form-data" class="profile-dashboard-form">
        <div class="form-body">
          <div class="sec-divider"><span>Foto Profil</span></div>

          <div class="photo-upload">
            <div class="photo-preview <?= $profileImageUrl === '' ? 'is-default' : '' ?>" id="photoPreview">
              <?php if ($profileImageUrl !== ''): ?>
                <img src="<?= e($profileImageUrl) ?>" alt="<?= e($user['name']) ?>" />
              <?php else: ?>
                <i class="fa-regular fa-user" aria-hidden="true"></i>
              <?php endif; ?>
            </div>
            <div class="photo-upload-right">
              <label class="photo-drop-zone" for="photoInput">
                <input type="file" id="photoInput" name="profile_image" accept=".jpg,.jpeg,.png,.webp,.gif" />
                <div class="photo-drop-icon">
                  <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                </div>
                <div class="photo-drop-text">
                  <strong>Klik untuk unggah</strong> atau seret file ke sini
                </div>
                <div class="photo-drop-sub">JPG, PNG, WEBP, GIF - disarankan ukuran kecil</div>
              </label>
            </div>
          </div>

          <div class="sec-divider"><span>Informasi Akun</span></div>

          <div class="field-wrap">
            <label class="field-label" for="name">
              Nama Lengkap <span class="field-required">*</span>
            </label>
            <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" placeholder="Masukkan nama lengkap" required />
          </div>

          <div class="field-wrap">
            <label class="field-label" for="email">
              Alamat Email <span class="field-required">*</span>
            </label>
            <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" placeholder="nama@email.com" required />
          </div>

          <div class="sec-divider"><span>Keamanan Kata Sandi</span></div>

          <div class="grid-2">
            <div class="field-wrap">
              <label class="field-label" for="password">Kata Sandi Baru</label>
              <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak diubah" />
            </div>
            <div class="field-wrap">
              <label class="field-label" for="password_confirm">Konfirmasi Kata Sandi</label>
              <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi kata sandi baru" />
              <div class="field-hint" id="matchHint"></div>
            </div>
          </div>

          <div class="field-hint password-note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            Kosongkan kedua kolom di atas jika tidak ingin mengganti kata sandi.
          </div>
        </div>

        <div class="submit-bar">
          <span class="submit-note">Foto akan dipakai di navbar, dropdown profil, dan area akun.</span>
          <button type="submit" class="btn-submit">
            <span class="btn-submit-icon"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i></span>
            Simpan Perubahan
          </button>
        </div>
      </form>
    </article>
  </section>

  <script>
    (function() {
      const photoInput = document.getElementById('photoInput');
      const photoPreview = document.getElementById('photoPreview');
      const password = document.getElementById('password');
      const passwordConfirm = document.getElementById('password_confirm');
      const matchHint = document.getElementById('matchHint');

      if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function(event) {
          const file = event.target.files && event.target.files[0];

          if (!file) {
            return;
          }

          const reader = new FileReader();
          reader.addEventListener('load', function(readerEvent) {
            const imageUrl = String(readerEvent.target.result || '');
            photoPreview.classList.remove('is-default');
            photoPreview.innerHTML = '<img src="' + imageUrl + '" alt="Pratinjau foto profil" />';
          });
          reader.readAsDataURL(file);
        });
      }

      function syncPasswordHint() {
        if (!password || !passwordConfirm || !matchHint) {
          return;
        }

        if (!passwordConfirm.value) {
          matchHint.textContent = '';
          matchHint.className = 'field-hint';
          return;
        }

        const isMatch = passwordConfirm.value === password.value;
        matchHint.textContent = isMatch ? 'Kata sandi cocok' : 'Kata sandi tidak cocok';
        matchHint.className = 'field-hint ' + (isMatch ? 'is-success' : 'is-error');
      }

      if (password && passwordConfirm) {
        password.addEventListener('input', syncPasswordHint);
        passwordConfirm.addEventListener('input', syncPasswordHint);
      }
    })();
  </script>
<?php
}, ['body_class' => 'profile-dashboard-page']);
