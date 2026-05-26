<?php

declare(strict_types=1);

function render_store_sidebar(?array $user, array $store, string $activePage): void
{
    $userName = (string) ($user['name'] ?? 'Store Admin');
    $userEmail = (string) ($user['email'] ?? 'store@pusaka.id');
    $storeSlug = (string) ($store['slug'] ?? '');
    $links = [
        ['key' => 'dashboard', 'path' => 'store-dashboard.php', 'icon' => 'fa-house', 'label' => 'Dashboard'],
        ['key' => 'profile', 'path' => 'store-profile.php', 'icon' => 'fa-store', 'label' => 'Profil Toko'],
        ['key' => 'add-product', 'path' => 'store-add-product.php', 'icon' => 'fa-plus', 'label' => 'Tambah Produk'],
        ['key' => 'products', 'path' => 'store-products.php', 'icon' => 'fa-box-open', 'label' => 'Produk Saya'],
    ];
    ?>
    <aside class="sidebar admin-sidebar store-admin-sidebar">
      <div class="sidebar-brand">
        <span class="sidebar-brand-mark">PR</span>
        <div class="sidebar-brand-copy">
          <div class="sidebar-brand-name">PusakaRasa</div>
          <div class="sidebar-brand-role">Store Admin</div>
        </div>
      </div>

      <nav class="sidebar-nav" aria-label="Navigasi store admin">
        <div class="nav-label">Menu Toko</div>
        <?php foreach ($links as $link): ?>
          <a href="<?= e(base_path($link['path'])) ?>" class="nav-link <?= $activePage === $link['key'] ? 'active' : '' ?>" title="<?= e($link['label']) ?>">
            <span class="nav-link-icon"><i class="fa-solid <?= e($link['icon']) ?>" aria-hidden="true"></i></span>
            <span class="nav-link-label"><?= e($link['label']) ?></span>
          </a>
        <?php endforeach; ?>

        <div class="nav-divider"></div>

        <a href="<?= e(base_path('store.php?slug=' . $storeSlug)) ?>" class="nav-link" title="Halaman Toko">
          <span class="nav-link-icon"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
          <span class="nav-link-label">Halaman Toko</span>
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
