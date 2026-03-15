<?php
/**
 * Lokasi: app/views/users/inactive.php
 * Deskripsi: Daftar pengguna nonaktif dengan opsi Aktifkan kembali.
 */
?>

<!-- ═══ HEADER ═══ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h5 mb-1 fw-700">Manajemen Pengguna</h1>
        <p class="text-muted mb-0" style="font-size:13px;">Kelola akun kasir dan pemilik</p>
    </div>
    <a href="<?= APP_URL ?>/users/create" class="btn btn-primary" style="font-size:13px; padding:8px 20px; border-radius:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px; margin-right:4px;">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Pengguna
    </a>
</div>

<!-- ═══ TABS NAVIGASI ═══ -->
<ul class="nav nav-tabs mb-4" style="border-bottom: 1px solid var(--border);">
    <li class="nav-item">
        <a class="nav-link" href="<?= APP_URL ?>/users" style="color:var(--text-secondary); font-weight:500; border:none; padding-bottom:9px;">
            Pengguna Aktif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?= APP_URL ?>/users/inactive" style="color:var(--text-main); font-weight:600; border-color:var(--border) var(--border) #fff; background-color:#fff;">
            Pengguna Nonaktif
        </a>
    </li>
</ul>

<!-- ═══ TABEL PENGGUNA ═══ -->
<div class="report-table-wrapper">
    <div class="report-table-header" style="background-color: var(--gray-50);">
        <h3 style="color: var(--text-secondary);">Daftar Pengguna Nonaktif</h3>
        <span style="font-size:12px; color:var(--text-muted);"><?= count($users) ?> pengguna dinonaktifkan</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th class="text-center">Role</th>
                    <th>Dinonaktifkan</th>
                    <th class="text-center" style="width:140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:30px; color:var(--text-muted);">Tidak ada pengguna nonaktif.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $idx => $u): ?>
                    <tr>
                        <td style="color:var(--text-muted);"><?= $idx + 1 ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px; opacity:0.6;">
                                <div style="width:32px; height:32px; border-radius:50%; background:var(--gray-300); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:600; flex-shrink:0;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <strong style="text-decoration:line-through;"><?= htmlspecialchars($u['name']) ?></strong>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="text-center">
                            <span style="display:inline-block; padding:2px 10px; border-radius:4px; font-size:11px; font-weight:600; background:var(--gray-200); color:var(--gray-600);">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td style="color:var(--text-muted); font-size:12px;">
                            (Nonaktif)
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= APP_URL ?>/users/activate" style="display:inline;"
                                  onsubmit="return confirm('Aktifkan kembali pengguna &quot;<?= htmlspecialchars($u['name']) ?>&quot;?');">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" 
                                        style="padding:4px 12px; font-size:12px; font-weight:600; border:1px solid var(--success-100); border-radius:4px; background:var(--success-100); color:var(--success-700); cursor:pointer; transition:all 0.15s;"
                                        onmouseover="this.style.background='var(--success-500)'; this.style.color='#fff';"
                                        onmouseout="this.style.background='var(--success-100)'; this.style.color='var(--success-700)';">
                                    Aktifkan
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
