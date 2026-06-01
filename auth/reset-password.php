<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect_to(nav_target_for_user(current_user()));
}

$email = strtolower(trim((string) ($_POST['email'] ?? $_GET['email'] ?? '')));
$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$resetUser = find_user_by_reset_token($email, $token);

if (!$resetUser) {
    set_flash('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
    redirect_to('auth/forgot-password.php');
}

if (is_post()) {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($password === '' || $passwordConfirm === '') {
        set_flash('error', 'Kata sandi baru dan konfirmasi wajib diisi.');
        redirect_to('auth/reset-password.php?' . http_build_query(['email' => $email, 'token' => $token]));
    }

    if ($password !== $passwordConfirm) {
        set_flash('error', 'Konfirmasi kata sandi tidak sesuai.');
        redirect_to('auth/reset-password.php?' . http_build_query(['email' => $email, 'token' => $token]));
    }

    if (!reset_user_password_with_token($email, $token, $password)) {
        set_flash('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
        redirect_to('auth/forgot-password.php');
    }

    set_flash('success', 'Password berhasil diperbarui. Silakan masuk dengan password baru.');
    redirect_to('login.php');
}

render_layout('Reset Password', function (?array $user = null) use ($email, $token): void {
    ?>
    <div class="auth-shell">
      <section class="auth-left">
        <div>
          <h2>Buat kata sandi baru.</h2>
          <p>Gunakan kata sandi yang kuat dan berbeda dari kata sandi sebelumnya.</p>
        </div>
      </section>
      <section class="auth-right">
        <div class="auth-card">
          <a href="<?= e(base_path('login.php')) ?>" class="inline-link">Kembali ke Login</a>
          <h1>Reset Password</h1>
          <p>Masukkan kata sandi baru untuk akun Anda.</p>
          <form method="post" class="form-panel">
            <input type="hidden" name="email" value="<?= e($email) ?>" />
            <input type="hidden" name="token" value="<?= e($token) ?>" />
            <label>
              Kata Sandi Baru
              <input type="password" name="password" placeholder="Masukkan kata sandi baru" required />
            </label>
            <label>
              Konfirmasi Kata Sandi
              <input type="password" name="password_confirm" placeholder="Ulangi kata sandi baru" required />
            </label>
            <button type="submit">Simpan Password Baru</button>
          </form>
        </div>
      </section>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'app_css' => true, 'body_class' => 'login-page']);
