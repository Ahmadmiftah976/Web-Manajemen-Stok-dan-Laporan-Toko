<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body class="auth-body">

    <div class="auth-card">

        <div class="auth-brand">
            <div class="auth-brand-icon">
                <img src="<?= APP_URL ?>/assets/img/logo.jpg" alt="Logo">
            </div>
            <div class="auth-brand-name">Majma<span>Insight</span></div>
            <div class="auth-brand-tagline">Sistem Manajemen Stok &amp; Laporan Penjualan</div>
        </div>

        <div class="auth-divider"></div>

        <?php if (isset($_SESSION['flash'])): ?>
            <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="auth-alert <?= htmlspecialchars($flash['type']) ?>">
                <span class="auth-alert-icon">
                    <?php if ($flash['type'] === 'error'): ?>&#10060;
                    <?php elseif ($flash['type'] === 'success'): ?>&#10004;
                    <?php else: ?>&#9888;
                    <?php endif; ?>
                </span>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($expired ?? false): ?>
            <div class="auth-alert warning">
                <span class="auth-alert-icon">&#9201;</span>
                Sesi Anda telah berakhir. Silakan login kembali.
            </div>
        <?php endif; ?>

        <?= $content ?>

        <div class="auth-footer">
            &copy; <?= date('Y') ?> <?= APP_NAME ?> &mdash; All rights reserved
        </div>

    </div>

    <script src="<?= APP_URL ?>/assets/js/auth.js"></script>

</body>
</html>