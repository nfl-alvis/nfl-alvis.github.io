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
            : 'Foto profil atau profil gagal diperbarui. Coba unggah ulang foto dengan format JPG, PNG, atau WEBP.';
        set_flash('error', $message);
        redirect_to('edit-profile.php');
    }
}

render_layout('Edit Profil', function (?array $currentUser = null) use ($user): void {
    ?>
    <section class="profile-edit-shell">
      <article class="detail-panel profile-summary-card">
        <div class="profile-summary-badge">Akun Saya</div>
        <div class="profile-summary-top">
          <?php if (user_profile_image_url($user) !== ''): ?>
            <img class="profile-avatar xl profile-avatar-image" src="<?= e(user_profile_image_url($user)) ?>" alt="<?= e($user['name']) ?>" />
          <?php else: ?>
            <div class="profile-avatar xl"><?= e(user_profile_initial($user)) ?></div>
          <?php endif; ?>
          <div>
            <h3><?= e($user['name']) ?></h3>
            <p><?= e($user['email']) ?></p>
          </div>
        </div>
        <div class="profile-summary-meta">
          <div class="intro-stat-card compact">
            <strong><?= e($user['role'] === ROLE_USER ? 'User' : 'Admin') ?></strong>
            <span>tipe akun</span>
          </div>
          <div class="intro-stat-card compact">
            <strong><?= e($user['is_active'] ? 'Aktif' : 'Nonaktif') ?></strong>
            <span>status akun</span>
          </div>
        </div>
        <div class="product-store-list">
          <div class="product-mini-card">
            <strong>Status Akun</strong>
            <p><?= e($user['is_active'] ? 'Aktif dan dapat mengakses platform.' : 'Tidak aktif.') ?></p>
          </div>
          <div class="product-mini-card">
            <strong>Halaman Cepat</strong>
            <p><a class="inline-link" href="<?= e(base_path('favorites.php')) ?>">Buka favorit</a></p>
          </div>
          <?php if ($user['role'] !== ROLE_USER): ?>
            <div class="product-mini-card">
              <strong>Dashboard</strong>
              <p><a class="inline-link" href="<?= e(base_path(nav_target_for_user($user))) ?>">Masuk ke dashboard</a></p>
            </div>
          <?php endif; ?>
        </div>
      </article>

      <article class="detail-panel">
        <div class="profile-form-head">
          <div>
            <h3>Form Edit Profil</h3>
            <p>Perbarui foto profil, nama, email, dan kata sandi dari satu panel yang lebih fokus.</p>
          </div>
          <div class="profile-form-tip">Upload gambar langsung, bukan input path.</div>
        </div>

        <form method="post" enctype="multipart/form-data" class="form-panel profile-form" style="margin-top: 18px;">
          <label>
            Foto Profil
            <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp" />
          </label>
          <div class="profile-upload-note">Format yang didukung: JPG, PNG, WEBP. Ukuran file disarankan tidak terlalu besar.</div>

          <label>
            Nama Lengkap
            <input type="text" name="name" value="<?= e($user['name']) ?>" required />
          </label>
          <label>
            Email
            <input type="email" name="email" value="<?= e($user['email']) ?>" required />
          </label>
          <label>
            Kata Sandi Baru
            <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti" />
          </label>
          <label>
            Konfirmasi Kata Sandi Baru
            <input type="password" name="password_confirm" placeholder="Ulangi kata sandi baru" />
          </label>
          <div class="form-footer-actions">
            <button type="submit">Simpan Perubahan</button>
            <span class="form-footer-note">Foto akan dipakai di navbar, dropdown profil, dan area akun.</span>
          </div>
        </form>
      </article>
    </section>
    <?php
});
