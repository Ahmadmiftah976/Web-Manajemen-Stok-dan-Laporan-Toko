<?php
/**
 * Lokasi: app/views/users/edit.php
 * Deskripsi: Form edit data pengguna + opsional reset password.
 * Variabel: $user
 */
?>

<div class="page-header">
    <div>
        <h1>Edit Pengguna</h1>
        <div class="page-header-subtitle">Ubah data akun <?= htmlspecialchars($user['name']) ?></div>
    </div>
    <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/users/update" novalidate>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">

            <div class="row g-3">
                <!-- Nama -->
                <div class="col-12">
                    <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?= htmlspecialchars($user['name']) ?>">
                </div>

                <!-- Email -->
                <div class="col-12">
                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($user['email']) ?>">
                </div>

                <!-- Role -->
                <div class="col-12">
                    <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                    <select id="role" name="role" class="form-select">
                        <option value="kasir" <?= $user['role'] === 'kasir' ? 'selected' : '' ?>>Kasir — Akses terbatas</option>
                        <option value="pemilik" <?= $user['role'] === 'pemilik' ? 'selected' : '' ?>>Pemilik — Akses penuh</option>
                    </select>
                </div>

                <!-- Password (opsional) -->
                <div class="col-12" style="margin-top:20px;">
                    <div style="padding:16px; background:var(--accent-50, #eff6ff); border:1px solid var(--accent-100, #dbeafe); border-radius:8px;">
                        <label class="form-label" for="password" style="margin-bottom:4px;">
                            🔒 Reset Password <span style="font-size:11px; color:var(--text-muted); font-weight:normal;">(opsional)</span>
                        </label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Kosongkan jika tidak ingin mengubah password" minlength="8">
                        <div class="form-text">Isi field ini hanya jika ingin mengganti password. Minimal 8 karakter.</div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Info akun -->
            <div style="font-size:12px; color:var(--text-muted); margin-bottom:16px;">
                Terdaftar sejak <?= date('d M Y, H:i', strtotime($user['created_at'])) ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" style="font-size:13px; padding:8px 24px;">
                    Simpan Perubahan
                </button>
                <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary" style="font-size:13px; padding:8px 24px;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
