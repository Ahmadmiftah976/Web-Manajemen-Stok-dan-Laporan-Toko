<?php
/**
 * Lokasi: app/views/kasir/index.php
 * Deskripsi: Antarmuka Mesin Kasir / Point of Sale
 */
?>

<!-- Sembunyikan header bawaan layout dan buat header ringkas -->
<style>
    .page-header { display: none !important; }
    .kasir-header { display: flex !important; }
</style>

<div class="page-header kasir-header">
    <div>
        <h1>Kasir</h1>
        <div class="page-header-subtitle">Pilih produk dan proses transaksi</div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="text-end">
            <div class="fs-12 text-muted-custom">Total Pendapatan Hari Ini</div>
            <div class="fw-700 text-success"><?= Format::rupiah($todaySummary['total_revenue']) ?></div>
        </div>
        <div class="text-end border-start ps-3">
            <div class="fs-12 text-muted-custom">Transaksi Hari Ini</div>
            <div class="fw-700"><?= (int)$todaySummary['total_trx'] ?></div>
        </div>
    </div>
</div>

<div class="pos-container">
    
    <!-- Bagian Kiri: Katalog Produk -->
    <div class="pos-products">
        <div class="pos-products-header">
            <form method="GET" action="<?= APP_URL ?>/kasir" class="d-flex flex-grow-1 gap-2 m-0 w-100">
                <select name="warehouse" class="form-select flex-shrink-0 auto-submit" style="width: 180px;">
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $warehouseId == $w['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($w['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="category" class="form-select flex-shrink-0 auto-submit" style="width: 140px;">
                    <option value="">Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="input-group flex-grow-1">
                    <span class="input-group-text bg-white border-end-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama / SKU..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <!-- Sembunyikan tombol filter, kita gunakan auto-submit JS jika select diubah. Untuk text pencet Enter. -->
                <button type="submit" class="btn btn-primary d-none">Filter</button>
            </form>
        </div>

        <div class="pos-products-grid" id="productsGrid">
            <?php if (empty($products)): ?>
                <div class="empty-state" style="grid-column: 1 / -1; align-self: center;">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <h3>Tidak ada produk</h3>
                    <p>Produk belum ditambahkan, sedang kosong, atau tidak cocok dengan pencarian.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <?php $noStock = $p['available_stock'] <= 0; ?>
                    <div class="pos-product-card <?= $noStock ? 'disabled' : '' ?>" 
                         data-id="<?= $p['id'] ?>" 
                         data-name="<?= htmlspecialchars($p['name']) ?>" 
                         data-price="<?= $p['harga_jual'] ?>"
                         data-hbeli="<?= $p['harga_beli'] ?>"
                         data-stock="<?= $p['available_stock'] ?>">
                        
                        <?php if ($noStock): ?>
                            <div class="pos-product-out">HABIS</div>
                        <?php endif; ?>

                        <div class="pos-product-title"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="pos-product-price"><?= Format::rupiah($p['harga_jual']) ?></div>
                        <div class="pos-product-stock">Sisa stok: <?= $p['available_stock'] ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bagian Kanan: Keranjang Belanja -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            Keranjang
            <button class="btn-clear-cart" id="btnClearCart">Kosongkan</button>
        </div>
        
        <ul class="pos-cart-items" id="cartItems">
            <!-- Diisi oleh JS -->
        </ul>

        <div class="pos-cart-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subtotalValue">Rp 0</span>
            </div>
            <div class="summary-row">
                <span>Diskon (Rp)</span>
                <input type="text" id="discountInput" class="form-control form-control-sm" placeholder="0">
            </div>
            <div class="summary-row total-row">
                <span>Total</span>
                <span id="totalValue">Rp 0</span>
            </div>

            <div class="mt-3 mb-2">
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check payment-method-radio" name="payment_method" id="payTunai" value="tunai" checked>
                    <label class="btn btn-outline-primary flex-fill d-flex align-items-center justify-content-center py-2" for="payTunai">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <path d="M6 12h.01M18 12h.01"/>
                        </svg>
                        <span class="fw-600">Tunai</span>
                    </label>

                    <input type="radio" class="btn-check payment-method-radio" name="payment_method" id="payQris" value="qris">
                    <label class="btn btn-outline-success flex-fill d-flex align-items-center justify-content-center py-2" for="payQris">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="me-2">
                            <!-- Tiga kotak sudut khas QR Code -->
                            <rect x="1" y="1" width="8" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
                            <rect x="3" y="3" width="4" height="4"/>
                            <rect x="15" y="1" width="8" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
                            <rect x="17" y="3" width="4" height="4"/>
                            <rect x="1" y="15" width="8" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
                            <rect x="3" y="17" width="4" height="4"/>
                            <!-- Data dots -->
                            <rect x="11" y="1" width="2" height="2"/>
                            <rect x="11" y="5" width="2" height="2"/>
                            <rect x="11" y="11" width="2" height="2"/>
                            <rect x="15" y="11" width="2" height="2"/>
                            <rect x="19" y="11" width="2" height="2"/>
                            <rect x="11" y="15" width="2" height="2"/>
                            <rect x="15" y="15" width="2" height="2"/>
                            <rect x="19" y="15" width="2" height="2"/>
                            <rect x="15" y="19" width="2" height="2"/>
                            <rect x="11" y="19" width="2" height="2"/>
                            <rect x="19" y="19" width="4" height="4"/>
                        </svg>
                        <span class="fw-600">QRIS</span>
                    </label>
                </div>
            </div>

            <div id="cashPaymentDetails">
                <div class="summary-row mt-2">
                    <span>Bayar Tunai</span>
                    <input type="text" id="paidInput" class="form-control" placeholder="0" style="font-size: 16px; height: 36px;">
                </div>
                <div class="summary-row mb-0">
                    <span>Kembali</span>
                    <span id="changeValue" class="fw-700">-</span>
                </div>
            </div>
        </div>

        <div class="pos-cart-actions">
            <!-- Simpan nilai URL dasar JS untuk fetch api call -->
            <script>const APP_URL = "<?= APP_URL ?>";</script>
            <!-- Midtrans Snap.js -->
            <script src="<?= MIDTRANS_SNAP_JS ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
            <button class="btn btn-primary btn-pay" id="btnPay" disabled>BAYAR (TUNAI)</button>
        </div>
    </div>
</div>
