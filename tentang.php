<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

render_layout('Tentang Kami', function (?array $user = null): void {
    ?>
    <section class="hero" id="beranda">
      <h2>Warisan Rasa Nusantara.</h2>
      <p>
        PusakaRasa hadir sebagai wujud komitmen kami, dalam mendokumentasikan
        kekayaan kuliner Nusantara, sekaligus mendukung keberlanjutan UMKM agar
        kuliner Indonesia tetap hidup dan berkembang.
      </p>
    </section>

    <div class="container">
      <div class="image-container">
        <img src="<?= e(base_path('assets/image/login-bg.png')) ?>" alt="Food collage" />
      </div>
      <div class="list-container">
        <div class="list-content">
          <h3>Visi & Misi</h3>
          <ul>
            <li>Mendokumentasikan kuliner khas daerah dengan akurat</li>
            <li>Memberdayakan UMKM melalui promosi digital</li>
            <li>Menjadi sumber inspirasi kuliner generasi muda</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="section-title">Nilai Yang Kami Junjung</div>

    <div class="cards-container">
      <div class="cards">
        <h4>Edukasikan</h4>
        <p>
          Menyediakan informasi lengkap tentang sejarah, bahan, dan cara
          pembuatan kuliner tradisional Indonesia.
        </p>
      </div>
      <div class="cards">
        <h4>Promosikan</h4>
        <p>
          Mendukung UMKM kuliner lokal dengan memberikan platform untuk
          mempromosikan produk mereka kepada audiens yang lebih luas.
        </p>
      </div>
      <div class="cards">
        <h4>Lestarikan</h4>
        <p>
          Melestarikan warisan kuliner Indonesia agar tetap dikenal dan
          dinikmati oleh generasi mendatang.
        </p>
      </div>
    </div>
    <?php
}, ['body_class' => 'about-page', 'tentang_css' => true]);
