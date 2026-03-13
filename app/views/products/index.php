<?php
/**
 * Lokasi: app/views/products/index.php
 * Deskripsi: Daftar produk dengan pencarian dan filter kategori.
 * Variabel: $products, $categories, $search, $category
 */
?>

<div class="page-header">
    <div>
        <h1>Daftar Produk</h1>
        <div class="page-header-subtitle">Kelola katalog produk Anda</div>
    </div>
    <a href="<?= APP_URL ?>/products/create" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Produk
    </a>
</div>

<!-- Filter & Search -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= APP_URL ?>/products" class="filter-bar">
            <div class="input-group" style="max-width:280px;">
                <span class="input-group-text bg-white border-end-0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama atau SKU..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="category" class="form-select" style="max-width:180px;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
            <?php if ($search || $category): ?>
                <a href="<?= APP_URL ?>/products" class="btn btn-outline-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            <h3>Belum ada produk</h3>
            <p>
                <?php if ($search || $category): ?>
                    Tidak ada produk yang cocok dengan filter Anda.
                <?php else: ?>
                    Mulai tambahkan produk pertama Anda.
                <?php endif; ?>
            </p>
            <?php if (!$search && !$category): ?>
                <a href="<?= APP_URL ?>/products/create" class="btn btn-primary btn-sm">Tambah Produk</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-center">Min. Stok</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($p['name']) ?></div>
                            <?php if (!empty($p['description'])): ?>
                                <div class="fs-12 text-muted-custom"><?= htmlspecialchars(Format::truncate($p['description'], 40)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="fs-12"><?= htmlspecialchars($p['sku']) ?></code>
                        </td>
                        <td>
                            <?php if (!empty($p['category'])): ?>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category']) ?></span>
                            <?php else: ?>
                                <span class="text-muted-custom fs-12">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= Format::rupiah($p['harga_beli']) ?></td>
                        <td class="text-end fw-600"><?= Format::rupiah($p['harga_jual']) ?></td>
                        <td class="text-center"><?= (int) $p['stok_minimum'] ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?= APP_URL ?>/products/edit?id=<?= $p['id'] ?>" class="btn-action btn-action-edit" title="Edit">
                                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                                <form method="POST" action="<?= APP_URL ?>/products/delete" style="display:inline;" onsubmit="return confirmDelete('Yakin ingin menghapus produk ini?')">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-action btn-action-delete" title="Hapus">
                                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="data-count px-3 py-2 border-top">
            Menampilkan <?= count($products) ?> produk
        </div>
    <?php endif; ?>
</div>
