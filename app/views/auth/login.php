<?php
/**
 * Lokasi: app/views/auth/login.php
 * Deskripsi: Form login. Di-render di dalam layouts/auth.php ($content).
 * Variabel: $title, $expired
 */
?>

<form class="auth-form" action="<?= APP_URL ?>/login" method="POST" autocomplete="off" novalidate>

    <?= Csrf::field() ?>

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label fw-semibold small" for="email">Alamat Email</label>
        <div class="auth-input-wrapper">
            <span class="auth-input-icon">
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="contoh@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
                autofocus
            >
        </div>
    </div>

    <!-- Password -->
    <div class="mb-4">
        <label class="form-label fw-semibold small" for="password">Password</label>
        <div class="auth-input-wrapper">
            <span class="auth-input-icon">
                <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                placeholder="Masukkan password"
                required
            >
            <button type="button" class="auth-toggle-pw" title="Tampilkan password" tabindex="-1">
                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
    </div>

    <!-- Submit — pakai .btn .btn-success Bootstrap + custom .auth-btn-submit -->
    <button type="submit" class="btn btn-success auth-btn-submit">
        <span class="spinner-btn"></span>
        <span class="btn-text">Masuk ke Dashboard</span>
        <span class="btn-icon">
            <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </span>
    </button>

</form>