<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect_to(nav_target_for_user(current_user()));
}

if (is_post()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
        set_flash('error', 'Semua field wajib diisi.');
        redirect_to('register.php');
    }

    if ($password !== $passwordConfirm) {
        set_flash('error', 'Konfirmasi kata sandi tidak sesuai.');
        redirect_to('register.php');
    }

    try {
        create_user($name, $email, $password);
    } catch (Throwable $exception) {
        set_flash('error', 'Email sudah dipakai atau data tidak valid.');
        redirect_to('register.php');
    }

    $user = authenticate_user($email, $password);
    if ($user) {
        login_user($user);
    }

    set_flash('success', 'Pendaftaran berhasil. Selamat datang di PusakaRasa.');
    redirect_to('katalog.php');
}

render_layout('Daftar', function (?array $user = null): void {
    ?>
    <div class="auth-shell">
      <section class="auth-left">
        <div>
          <h2>Bergabung dengan pecinta kuliner Nusantara.</h2>
          <p>Buat akun user biasa untuk mengakses katalog, toko, dan detail produk.</p>
        </div>
      </section>
      <section class="auth-right">
        <div class="auth-card">
          <a href="<?= e(base_path('index.php')) ?>" class="inline-link">Kembali ke Beranda</a>
          <h1>Daftar PusakaRasa</h1>
          <p>Akun yang dibuat dari form ini otomatis berperan sebagai user biasa.</p>
          <form method="post" class="form-panel">
            <label>
              Nama Lengkap
              <input type="text" name="name" placeholder="Nama lengkap" required />
            </label>
            <label>
              Email
              <input type="email" name="email" placeholder="contoh@email.com" required />
            </label>
            <label>
              Kata Sandi
              <input type="password" name="password" placeholder="Minimal 6 karakter" required />
            </label>
            <label>
              Konfirmasi Kata Sandi
              <input type="password" name="password_confirm" placeholder="Ulangi kata sandi" required />
            </label>
            <button type="submit">Daftar</button>
          </form>
          <p class="auth-helper">Sudah punya akun? <a href="<?= e(base_path('login.php')) ?>">Masuk di sini</a></p>
        </div>
      </section>
    </div>
    <?php
}, ['hide_header' => true, 'app_css' => true]);
