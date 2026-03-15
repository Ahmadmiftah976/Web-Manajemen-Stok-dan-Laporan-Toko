<?php
/**
 * Lokasi: app/views/users/index.php
 * Deskripsi: Daftar pengguna aktif dengan role badges dan aksi CRUD.
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
        <a class="nav-link active" href="<?= APP_URL ?>/users" style="color:var(--text-main); font-weight:600; border-color:var(--border) var(--border) #fff; background-color:#fff;">
            Pengguna Aktif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= APP_URL ?>/users/inactive" style="color:var(--text-secondary); font-weight:500; border:none; padding-bottom:9px;">
            Pengguna Nonaktif
        </a>
    </li>
</ul>

<!-- ═══ TABEL PENGGUNA ═══ -->
<div class="report-table-wrapper">
    <div class="report-table-header">
        <h3>Daftar Pengguna Aktif</h3>
        <span style="font-size:12px; color:var(--text-muted);"><?= count($users) ?> pengguna</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th class="text-center">Role</th>
                    <th>Terdaftar</th>
                    <th class="text-center" style="width:140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:30px; color:var(--text-muted);">Belum ada pengguna.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $idx => $u): ?>
                    <tr>
                        <td style="color:var(--text-muted);"><?= $idx + 1 ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:32px; height:32px; border-radius:50%; background:var(--primary-800); color:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:600; flex-shrink:0;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="text-center">
                            <?php if ($u['role'] === 'pemilik'): ?>
                                <span style="display:inline-block; padding:2px 10px; border-radius:4px; font-size:11px; font-weight:600; background:var(--accent-100); color:var(--accent-600);">PEMILIK</span>
                            <?php else: ?>
                                <span style="display:inline-block; padding:2px 10px; border-radius:4px; font-size:11px; font-weight:600; background:var(--success-100); color:var(--success-600);">KASIR</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted); font-size:12px;">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="text-center">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="<?= APP_URL ?>/users/edit?id=<?= $u['id'] ?>" 
                                   style="padding:4px 12px; font-size:12px; font-weight:600; border:1px solid var(--border); border-radius:4px; color:var(--text-secondary); text-decoration:none; transition:all 0.15s;"
                                   onmouseover="this.style.borderColor='var(--accent-500)'; this.style.color='var(--accent-600)';"
                                   onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-secondary)';">
                                    Edit
                                </a>
                                <?php if ((int)$u['id'] !== (int)Auth::user('id')): ?>
                                <form method="POST" action="<?= APP_URL ?>/users/delete" style="display:inline;"
                                      onsubmit="return confirm('Yakin ingin menonaktifkan pengguna &quot;<?= htmlspecialchars($u['name']) ?>&quot;?');">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" 
                                            style="padding:4px 12px; font-size:12px; font-weight:600; border:1px solid var(--danger-100); border-radius:4px; background:var(--danger-100); color:var(--danger-600); cursor:pointer; transition:all 0.15s;"
                                            onmouseover="this.style.background='var(--danger-600)'; this.style.color='#fff';"
                                            onmouseout="this.style.background='var(--danger-100)'; this.style.color='var(--danger-600)';">
                                        Nonaktifkan
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
