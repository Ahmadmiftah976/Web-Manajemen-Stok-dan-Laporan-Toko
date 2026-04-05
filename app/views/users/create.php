<?php
/**
 * Lokasi: app/views/users/create.php
 * Deskripsi: Form tambah pengguna baru.
 */
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<div class="page-header">
    <div>
        <h1>Tambah Pengguna</h1>
        <div class="page-header-subtitle">Buat akun baru untuk kasir atau pemilik</div>
    </div>
    <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/users/store" novalidate>
            <?= Csrf::field() ?>

            <div class="row g-3">
                <!-- Nama -->
                <div class="col-12">
                    <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           placeholder="Contoh: Ahmad Miftah" required
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                </div>

                <!-- Email -->
                <div class="col-12">
                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="Contoh: kasir@majmainsight.com" required
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <div class="form-text">Digunakan untuk login ke sistem.</div>
                </div>

                <!-- Password -->
                <div class="col-12">
                    <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Minimal 8 karakter" required minlength="8">
                </div>

                <!-- Role -->
                <div class="col-12">
                    <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                    <select id="role" name="role" class="form-select">
                        <option value="kasir" <?= ($old['role'] ?? 'kasir') === 'kasir' ? 'selected' : '' ?>>Kasir — Akses terbatas (kasir saja)</option>
                        <option value="pemilik" <?= ($old['role'] ?? '') === 'pemilik' ? 'selected' : '' ?>>Pemilik — Akses penuh (semua fitur)</option>
                    </select>
                    <div class="form-text">Kasir hanya dapat mengakses halaman kasir. Pemilik memiliki akses ke semua fitur.</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" style="font-size:13px; padding:8px 24px;">
                    Simpan Pengguna
                </button>
                <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary" style="font-size:13px; padding:8px 24px;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
