<?php
/**
 * app/views/stock/add.php
 * Form stok masuk / keluar.
 * Variabel: $warehouses, $products
 */
?>

<div class="page-header">
    <div>
        <h1>Stok Masuk / Keluar</h1>
        <div class="page-header-subtitle">Catat perubahan jumlah stok di gudang</div>
    </div>
    <a href="<?= APP_URL ?>/stock" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
        </svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/stock/add" novalidate>
            <?= Csrf::field() ?>

            <div class="row g-3">
                <!-- Tipe -->
                <div class="col-12">
                    <label class="form-label">Tipe <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_masuk" value="masuk"
                                checked>
                            <label class="form-check-label fw-600" for="type_masuk">
                                <span class="text-success">▲</span> Stok Masuk
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_keluar" value="keluar">
                            <label class="form-check-label fw-600" for="type_keluar">
                                <span class="text-danger">▼</span> Stok Keluar
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_koreksi" value="koreksi">
                            <label class="form-check-label fw-600" for="type_koreksi">
                                <span class="text-warning">●</span> Koreksi
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Produk -->
                <div class="col-12">
                    <label class="form-label" for="product_id">Produk <span class="text-danger">*</span></label>
                    <select id="product_id" name="product_id" class="form-select" required>
                        <option value="">— Pilih Produk —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (isset($selectedProductId) && $selectedProductId === $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gudang -->
                <div class="col-12">
                    <label class="form-label" for="warehouse_id">Gudang <span class="text-danger">*</span></label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        <option value="">— Pilih Gudang —</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= (isset($selectedWarehouseId) && $selectedWarehouseId === $w['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($w['name']) ?> — <?= htmlspecialchars($w['location']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jumlah -->
                <div class="col-md-6">
                    <label class="form-label" for="quantity" id="quantity_label">Jumlah <span
                            class="text-danger">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" placeholder="0" min="1"
                        required>
                    <div id="koreksi_help" class="form-text text-warning" style="display:none;">
                        Masukkan jumlah stok yang ada di toko/gudang.
                    </div>
                </div>

                <!-- Catatan -->
                <div class="col-12">
                    <label class="form-label" for="notes">Catatan</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2"
                        placeholder="Catatan opsional (contoh: restok dari supplier)"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Simpan
                </button>
                <a href="<?= APP_URL ?>/stock" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#product_id').select2({
            theme: 'bootstrap-5',
            placeholder: '— Cari Produk —',
            width: '100%'
        });

        $('#warehouse_id').select2({
            theme: 'bootstrap-5',
            placeholder: '— Pilih Gudang —',
            width: '100%'
        });

        // Dynamic label/placeholder saat tipe berubah
        const radios = document.querySelectorAll('input[name="type"]');
        const qtyLabel = document.getElementById('quantity_label');
        const qtyInput = document.getElementById('quantity');
        const koreksiHelp = document.getElementById('koreksi_help');

        function updateQuantityUI() {
            const selected = document.querySelector('input[name="type"]:checked').value;
            if (selected === 'koreksi') {
                qtyLabel.innerHTML = 'Jumlah Stok Sesungguhnya <span class="text-danger">*</span>';
                qtyInput.placeholder = 'Stok aktual di gudang';
                qtyInput.min = '0';
                koreksiHelp.style.display = '';
            } else {
                qtyLabel.innerHTML = 'Jumlah <span class="text-danger">*</span>';
                qtyInput.placeholder = '0';
                qtyInput.min = '1';
                koreksiHelp.style.display = 'none';
            }
        }

        radios.forEach(r => r.addEventListener('change', updateQuantityUI));
        updateQuantityUI();
    });
</script>