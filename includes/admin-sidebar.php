<?php

declare(strict_types=1);

function render_admin_sidebar(?array $user, string $activePage): void
{
    $userName = (string) ($user['name'] ?? 'Super Admin');
    $userEmail = (string) ($user['email'] ?? 'admin@pusaka.id');
    $links = [
        ['key' => 'dashboard', 'path' => 'admin-dashboard.php', 'icon' => 'fa-house', 'label' => 'Dashboard'],
        ['key' => 'users', 'path' => 'admin-users.php', 'icon' => 'fa-users', 'label' => 'Pengguna'],
        ['key' => 'stores', 'path' => 'admin-stores.php', 'icon' => 'fa-store', 'label' => 'Toko'],
        ['key' => 'products', 'path' => 'admin-products.php', 'icon' => 'fa-box-open', 'label' => 'Produk Platform'],
        ['key' => 'store-create', 'path' => 'admin-store-create.php', 'icon' => 'fa-plus', 'label' => 'Tambah Toko'],
        ['key' => 'admin-create', 'path' => 'admin-store-admin-create.php', 'icon' => 'fa-user-plus', 'label' => 'Buat Store Admin'],
    ];
    ?>
    <aside class="sidebar admin-sidebar">
      <div class="sidebar-brand">
        <span class="sidebar-brand-mark">PR</span>
        <div class="sidebar-brand-copy">
          <div class="sidebar-brand-name">PusakaRasa</div>
          <div class="sidebar-brand-role">Super Admin</div>
        </div>
      </div>

      <nav class="sidebar-nav" aria-label="Navigasi admin">
        <div class="nav-label">Menu Admin</div>
        <?php foreach ($links as $link): ?>
          <a href="<?= e(base_path($link['path'])) ?>" class="nav-link <?= $activePage === $link['key'] ? 'active' : '' ?>" title="<?= e($link['label']) ?>">
            <span class="nav-link-icon"><i class="fa-solid <?= e($link['icon']) ?>" aria-hidden="true"></i></span>
            <span class="nav-link-label"><?= e($link['label']) ?></span>
          </a>
        <?php endforeach; ?>

        <div class="nav-divider"></div>

        <a href="<?= e(base_path('index.php')) ?>" class="nav-link" title="Beranda">
          <span class="nav-link-icon"><i class="fa-solid fa-globe" aria-hidden="true"></i></span>
          <span class="nav-link-label">Beranda</span>
        </a>
        <a href="<?= e(base_path('katalog.php')) ?>" class="nav-link" title="Katalog">
          <span class="nav-link-icon"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span>
          <span class="nav-link-label">Katalog</span>
        </a>

        <div class="nav-divider"></div>

        <a href="<?= e(base_path('logout.php')) ?>" class="nav-link admin-logout" title="Keluar">
          <span class="nav-link-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
          <span class="nav-link-label">Keluar</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="sidebar-avatar"><?= e(strtoupper(substr($userName, 0, 2))) ?></div>
          <div class="sidebar-user-copy">
            <div class="sidebar-user-name"><?= e($userName) ?></div>
            <div class="sidebar-user-role"><?= e($userEmail) ?></div>
          </div>
        </div>
      </div>
    </aside>
    <?php
}
