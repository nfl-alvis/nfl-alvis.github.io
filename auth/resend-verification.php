<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/mail.php';

if (is_logged_in()) {
    redirect_to(nav_target_for_user(current_user()));
}

$prefillEmail = strtolower(trim((string) ($_GET['email'] ?? '')));

if (is_post()) {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Masukkan email yang valid.');
        redirect_to('auth/resend-verification.php');
    }

    $user = find_user_by_email($email, true);

    if ($user && user_needs_email_verification($user)) {
        try {
            $token = create_email_verification_token((int) $user['id']);
            send_verification_email($user['email'], $user['name'], $token);
        } catch (Throwable $exception) {
            set_flash('error', 'Email verifikasi belum dapat dikirim. Periksa konfigurasi email dan coba lagi.');
            redirect_to('auth/resend-verification.php?' . http_build_query(['email' => $email]));
        }
    }

    set_flash('success', 'Jika email terdaftar dan belum diverifikasi, link verifikasi baru sudah dikirim.');
    redirect_to('login.php');
}

render_layout('Kirim Ulang Verifikasi', function (?array $user = null) use ($prefillEmail): void {
    ?>
    <div class="auth-shell">
      <section class="auth-left">
        <div>
          <h2>Verifikasi email untuk mengaktifkan akun.</h2>
          <p>Minta ulang link verifikasi jika link sebelumnya hilang atau sudah kedaluwarsa.</p>
        </div>
      </section>
      <section class="auth-right">
        <div class="auth-card">
          <a href="<?= e(base_path('login.php')) ?>" class="inline-link">Kembali ke Login</a>
          <h1>Kirim Ulang Verifikasi</h1>
          <p>Masukkan email yang digunakan saat mendaftar.</p>
          <form method="post" class="form-panel">
            <label>
              Email
              <input type="email" name="email" value="<?= e($prefillEmail) ?>" placeholder="contoh@email.com" required />
            </label>
            <button type="submit">Kirim Ulang Link</button>
          </form>
          <p class="auth-helper">Sudah terverifikasi? <a href="<?= e(base_path('login.php')) ?>">Masuk di sini</a></p>
        </div>
      </section>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'app_css' => true, 'body_class' => 'login-page']);
