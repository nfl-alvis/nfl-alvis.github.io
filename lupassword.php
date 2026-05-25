<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect_to(nav_target_for_user(current_user()));
}

render_layout('Lupa Password', function (?array $user = null): void {
    ?>
    <div class="auth-shell">
      <section class="auth-left">
        <div>
          <h2>Kami siap bantu memulihkan akun Anda.</h2>
          <p>Masukkan email yang terdaftar untuk melanjutkan proses pengaturan ulang kata sandi.</p>
        </div>
      </section>
      <section class="auth-right">
        <div class="auth-card">
          <a href="<?= e(base_path('index.php')) ?>" class="inline-link">Kembali ke Beranda</a>
          <h1>Lupa Password</h1>
          <p>Gunakan email akun PusakaRasa Anda.</p>
          <form class="form-panel">
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
