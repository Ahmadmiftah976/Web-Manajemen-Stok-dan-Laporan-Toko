<?php
/**
 * Lokasi: app/views/products/edit.php
 * Deskripsi: Form edit produk.
 * Variabel: $product, $categories
 */
?>

<div class="page-header">
    <div>
        <h1>Edit Produk</h1>
        <div class="page-header-subtitle">Perbarui data produk: <?= htmlspecialchars($product['name']) ?></div>
    </div>
    <a href="<?= APP_URL ?>/products" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/products/update" novalidate>
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">

            <div class="row g-3">
                <!-- Nama Produk -->
                <div class="col-12">
                    <label class="form-label" for="name">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Baju Koko Putih Polos - M" required value="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <!-- SKU -->
                <div class="col-md-6">
                    <label class="form-label" for="sku">SKU <span class="text-danger">*</span></label>
                    <input type="text" id="sku" name="sku" class="form-control" placeholder="Contoh: BK-PUTIH-M" required value="<?= htmlspecialchars($product['sku']) ?>" style="text-transform:uppercase;">
                    <div class="form-text">Kode unik produk (otomatis huruf kapital)</div>
                </div>

                <!-- Kategori -->
                <div class="col-md-6">
                    <label class="form-label" for="category">Kategori</label>
                    <input type="text" id="category" name="category" class="form-control" placeholder="Contoh: Polos, Batik, Bordir" list="category-list" value="<?= htmlspecialchars($product['category'] ?? '') ?>">
                    <datalist id="category-list">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- Harga Beli -->
                <div class="col-md-6">
                    <label class="form-label" for="harga_beli">Harga Beli (Rp) <span class="text-danger">*</span></label>
                    <input type="number" id="harga_beli" name="harga_beli" class="form-control" placeholder="0" min="0" step="100" required value="<?= (int) $product['harga_beli'] ?>">
                </div>

                <!-- Harga Jual -->
                <div class="col-md-6">
                    <label class="form-label" for="harga_jual">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" id="harga_jual" name="harga_jual" class="form-control" placeholder="0" min="0" step="100" required value="<?= (int) $product['harga_jual'] ?>">
                </div>

                <!-- Stok Minimum -->
                <div class="col-md-6">
                    <label class="form-label" for="stok_minimum">Stok Minimum</label>
                    <input type="number" id="stok_minimum" name="stok_minimum" class="form-control" placeholder="5" min="0" value="<?= (int) $product['stok_minimum'] ?>">
                    <div class="form-text">Notifikasi muncul saat stok di bawah angka ini</div>
                </div>

                <!-- Deskripsi -->
                <div class="col-12">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Deskripsi produk (opsional)"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Margin info -->
            <div class="product-margin-info mt-3" id="marginInfo" style="display:none;">
                <div class="d-flex align-items-center gap-2">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Margin: <strong id="marginValue">-</strong></span>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Simpan Perubahan
                </button>
                <a href="<?= APP_URL ?>/products" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
// Hitung margin otomatis saat harga diubah
const beli = document.getElementById('harga_beli');
const jual = document.getElementById('harga_jual');
const marginInfo = document.getElementById('marginInfo');
const marginValue = document.getElementById('marginValue');

function calcMargin() {
    const b = parseFloat(beli.value) || 0;
    const j = parseFloat(jual.value) || 0;
    if (b > 0 && j > 0) {
        const margin = j - b;
        const pct = ((margin / b) * 100).toFixed(1);
        marginInfo.style.display = 'block';
        marginValue.textContent = formatRupiah(margin) + ' (' + pct + '%)';
        marginValue.style.color = margin >= 0 ? '#16a34a' : '#dc2626';
    } else {
        marginInfo.style.display = 'none';
    }
}

beli.addEventListener('input', calcMargin);
jual.addEventListener('input', calcMargin);
calcMargin(); // run on load
</script>
