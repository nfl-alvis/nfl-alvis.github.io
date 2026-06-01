<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
  redirect_to(nav_target_for_user(current_user()));
}

if (is_post()) {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    set_flash('error', 'Email dan kata sandi wajib diisi.');
    redirect_to('login.php');
  }

  $user = authenticate_user($email, $password);
  if (!$user) {
    set_flash('error', 'Email atau kata sandi tidak cocok.');
    redirect_to('login.php');
  }

  if (user_needs_email_verification($user)) {
    set_flash('error', 'Email belum diverifikasi. Silakan cek email Anda atau kirim ulang verifikasi.');
    redirect_to('login.php');
  }

  login_user($user);
  set_flash('success', 'Berhasil masuk ke akun Anda.');
  redirect_to(nav_target_for_user($user));
}

render_layout('Masuk', function (?array $user = null): void {
?>
  <div class="auth-shell">
    <section class="auth-left">
      <div>
        <h2>Setiap rasa adalah cerita Nusantara.</h2>
        <p>Masuk untuk membuka katalog lengkap, melihat detail toko, dan mengakses dashboard sesuai role Anda.</p>
      </div>
    </section>
    <section class="auth-right">
      <div class="auth-card">
        <a href="<?= e(base_path('index.php')) ?>" class="inline-link">Kembali ke Beranda</a>
        <h1>Masuk</h1>
        <p>Gunakan akun Anda untuk melanjutkan.</p>
        <form method="post" class="form-panel">
          <label>
            Email
            <input type="email" name="email" placeholder="contoh@pusakarasa.id" required />
          </label>
          <label>
            Kata Sandi
            <input type="password" name="password" placeholder="Masukkan kata sandi" required />
          </label>
          <button type="submit">Masuk</button>
        </form>
        <div class="auth-divider"><span>atau</span></div>
        <a class="google-login-btn" href="<?= e(base_path('auth/google-login.php')) ?>">
          <img src="<?= e(base_path('assets/image/icons/google.svg')) ?>" alt="" aria-hidden="true" />
          Login dengan Google
        </a>
        <p class="auth-helper"><a href="<?= e(base_path('auth/forgot-password.php')) ?>">Lupa password?</a> &middot; <a href="<?= e(base_path('auth/resend-verification.php')) ?>">Kirim ulang verifikasi</a></p>
        <p class="auth-helper">Belum punya akun? <a href="<?= e(base_path('register.php')) ?>">Daftar di sini</a></p>
      </div>
    </section>
  </div>
<?php
}, ['hide_header' => true, 'hide_footer' => true, 'app_css' => true]);
