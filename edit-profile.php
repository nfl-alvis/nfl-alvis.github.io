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

    if ($name === '' || $email === '') {
        set_flash('error', 'Nama dan email wajib diisi.');
        redirect_to('edit-profile.php');
    }

    if ($password !== '' && $password !== $passwordConfirm) {
        set_flash('error', 'Konfirmasi kata sandi tidak sesuai.');
        redirect_to('edit-profile.php');
    }

    try {
        update_current_user_profile((int) $user['id'], $name, $email, $password !== '' ? $password : null);
        set_flash('success', 'Profil berhasil diperbarui.');
        redirect_to('edit-profile.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Profil gagal diperbarui. Pastikan email belum dipakai akun lain.');
        redirect_to('edit-profile.php');
    }
}

render_layout('Edit Profil', function (?array $currentUser = null) use ($user): void {
    ?>
    <section class="page-intro compact">
      <div class="page-intro-copy">
        <span class="eyebrow">Akun Saya</span>
        <h2>Kelola profil dengan tampilan yang lebih ringkas.</h2>
        <p>Perbarui identitas akun, email, dan kata sandi dari satu panel yang lebih fokus dan nyaman dipakai.</p>
      </div>
      <div class="page-intro-stats">
        <div class="intro-stat-card">
          <strong><?= e($user['role'] === ROLE_USER ? 'User' : 'Admin') ?></strong>
          <span>tipe akun</span>
        </div>
        <div class="intro-stat-card">
          <strong><?= e($user['is_active'] ? 'Aktif' : 'Nonaktif') ?></strong>
          <span>status akun</span>
        </div>
      </div>
    </section>

    <section class="profile-edit-shell">
      <article class="detail-panel profile-summary-card">
        <div class="profile-summary-top">
          <div class="profile-avatar xl"><?= e(strtoupper(substr((string) $user['name'], 0, 1))) ?></div>
          <div>
            <h3><?= e($user['name']) ?></h3>
            <p><?= e($user['email']) ?></p>
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
        <h3>Form Edit Profil</h3>
        <form method="post" class="form-panel" style="margin-top: 18px;">
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
          <button type="submit">Simpan Perubahan</button>
        </form>
      </article>
    </section>
    <?php
});
