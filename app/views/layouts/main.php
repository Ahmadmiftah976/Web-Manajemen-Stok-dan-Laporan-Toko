<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- CSS Aplikasi -->
    <?php $cssVersion = '20260310'; ?>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css?v=<?= $cssVersion ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/layout.css?v=<?= $cssVersion ?>">

    <!-- CSS tambahan per halaman (opsional, di-set dari controller) -->
    <?php if (!empty($extraCss)): ?>
        <?php foreach ((array)$extraCss as $css): ?>
            <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/<?= htmlspecialchars($css) ?>?v=<?= $cssVersion ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<!-- ═══════════════════ SIDEBAR ═══════════════════════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <a href="<?= APP_URL ?>/dashboard" class="sidebar-brand-inner">
            <div class="sidebar-logo">
                <img src="<?= APP_URL ?>/assets/img/logo.jpg" alt="Logo">
            </div>
            <div>
                <div class="sidebar-brand-text">Majma<span>Insight</span></div>
                <div class="sidebar-brand-sub">Manajemen Bisnis</div>
            </div>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?= strtoupper(substr(Auth::user('name'), 0, 1)) ?>
        </div>
        <div style="overflow:hidden;">
            <div class="sidebar-user-name"><?= htmlspecialchars(Auth::user('name')) ?></div>
            <div class="sidebar-user-role">
                <?= Auth::isPemilik() ? 'Pemilik' : 'Kasir' ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        $uri  = str_replace($base, '', $uri);
        $active = fn($path) => str_starts_with($uri, $path) ? 'active' : '';
        ?>

        <div class="nav-section-label">Utama</div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/dashboard" class="<?= $active('/dashboard') ?>">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
        </div>

        <?php if (Auth::isPemilik()): ?>
        <div class="nav-section-label">Inventori</div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/products" class="<?= $active('/products') ?>">
                <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Produk
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/stock" class="<?= $active('/stock') ?>">
                <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Manajemen Stok
            </a>
        </div>
        <?php endif; ?>

        <div class="nav-section-label">Transaksi</div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/kasir" class="<?= $active('/kasir') ?>">
                <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Kasir
            </a>
        </div>

        <?php if (Auth::isPemilik()): ?>
        <div class="nav-section-label">Laporan</div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/reports/sales" class="<?= $active('/reports/sales') ?>">
                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Laporan Penjualan
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/reports/profit" class="<?= $active('/reports/profit') ?>">
                <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Laporan Laba Rugi
            </a>
        </div>

        <div class="nav-section-label">Pengaturan</div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/users" class="<?= $active('/users') ?>">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Pengguna
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="<?= APP_URL ?>/logout" onclick="return confirm('Yakin ingin keluar?')">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>

</aside>

<!-- ═══════════════════ MAIN ═══════════════════════════════ -->
<div class="main-wrapper">

    <header class="topnav">
        <div class="topnav-left">
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <span class="topnav-page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
        </div>

        <div class="topnav-right">
            <?php
            // Muat model Stock untuk cek notifikasi stok rendah
            if (!class_exists('Stock')) {
                require_once APP_PATH . '/models/Stock.php';
            }
            $lowStockItems = (new Stock())->getLowStock();
            $lowStockCount = count($lowStockItems);
            ?>
            <?php if (Auth::isPemilik()): ?>
            <div class="notif-wrapper">
                <button class="btn-notif" onclick="this.parentElement.classList.toggle('open')" title="<?= $lowStockCount ?> produk stok menipis">
                    <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($lowStockCount > 0): ?>
                        <span class="notif-badge"><?= $lowStockCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="notif-dropdown">
                    <div class="notif-dropdown-header">Notifikasi Stok</div>
                    <?php if ($lowStockCount > 0): ?>
                        <?php foreach ($lowStockItems as $item): ?>
                        <a href="<?= APP_URL ?>/stock/add?product_id=<?= $item['id'] ?>&warehouse_id=<?= $item['warehouse_id'] ?>" class="notif-dropdown-item">
                            <div class="notif-item-title"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="notif-item-detail">
                                <?= htmlspecialchars($item['warehouse_name']) ?> —
                                Stok: <strong class="text-danger"><?= (int) $item['total_stock'] ?></strong>
                                / Min: <?= (int) $item['stok_minimum'] ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-dropdown-empty">Semua stok aman</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="topnav-user">
                <div class="topnav-avatar">
                    <?= strtoupper(substr(Auth::user('name'), 0, 1)) ?>
                </div>
                <span class="topnav-user-name">
                    <?= htmlspecialchars(Auth::user('name')) ?>
                </span>
            </div>
        </div>
    </header>

    <main class="page-content">

        <?php if (isset($_SESSION['flash'])): ?>
            <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <?php $alertClass = match($flash['type']) {
                'success' => 'alert-success',
                'error'   => 'alert-danger',
                'warning' => 'alert-warning',
                default   => 'alert-info',
            }; ?>
            <div class="flash-alert alert <?= $alertClass ?> fade show d-flex align-items-center justify-content-between pe-3" role="alert">
                <div class="mb-0 flex-grow-1"><?= htmlspecialchars($flash['message']) ?></div>
                <button type="button" class="btn-close position-static m-0" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>

    </main>
</div>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- JS Global -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<!-- JS tambahan per halaman (opsional, di-set dari controller) -->
<?php if (!empty($extraJs)): ?>
    <?php foreach ((array)$extraJs as $js): ?>
        <script src="<?= APP_URL ?>/assets/js/<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>