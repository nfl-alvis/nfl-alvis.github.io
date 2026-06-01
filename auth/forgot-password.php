<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/mail.php';

if (is_logged_in()) {
    redirect_to(nav_target_for_user(current_user()));
}

if (is_post()) {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Masukkan email yang valid.');
        redirect_to('auth/forgot-password.php');
    }

    $user = find_user_by_email($email, true);

    if ($user && (int) ($user['email_verified'] ?? 0) === 1) {
        try {
            $token = create_password_reset_token((int) $user['id']);
            send_password_reset_email($user['email'], $user['name'], $token);
        } catch (Throwable $exception) {
            set_flash('success', 'Jika email terdaftar dan sudah terverifikasi, link reset password akan dikirim.');
            redirect_to('login.php');
        }
    }

    set_flash('success', 'Jika email terdaftar dan sudah terverifikasi, link reset password akan dikirim.');
    redirect_to('login.php');
}

render_layout('Lupa Password', function (?array $user = null): void {
    ?>
    <div class="auth-shell">
      <section class="auth-left">
        <div>
          <h2>Kami siap bantu memulihkan akun Anda.</h2>
          <p>Masukkan email yang terdaftar untuk menerima tautan pengaturan ulang kata sandi.</p>
        </div>
      </section>
      <section class="auth-right">
        <div class="auth-card">
          <a href="<?= e(base_path('login.php')) ?>" class="inline-link">Kembali ke Login</a>
          <h1>Lupa Password</h1>
          <p>Gunakan email akun PusakaRasa Anda.</p>
          <form method="post" class="form-panel">
            <label>
              Email Terdaftar
              <input type="email" name="email" placeholder="contoh@pusakarasa.id" required />
            </label>
            <button type="submit">Kirim Tautan Reset</button>
          </form>
          <p class="auth-helper">Ingat kata sandi Anda? <a href="<?= e(base_path('login.php')) ?>">Kembali masuk</a></p>
        </div>
      </section>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'app_css' => true, 'body_class' => 'login-page']);
