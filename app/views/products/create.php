<?php
/**
 * Lokasi: app/views/products/create.php
 * Deskripsi: Form tambah produk baru.
 * Variabel: $categories, $warehouses
 */
?>

<div class="page-header">
    <div>
        <h1>Tambah Produk Baru</h1>
        <div class="page-header-subtitle">Isi data produk untuk menambahkan ke katalog</div>
    </div>
    <a href="<?= APP_URL ?>/products" class="btn btn-outline-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
        </svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/products/store" novalidate>
            <?= Csrf::field() ?>

            <div class="row g-3">
                <!-- Nama Produk -->
                <div class="col-12">
                    <label class="form-label" for="name">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                        placeholder="Contoh: Baju Koko Putih Polos - M" required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <!-- SKU -->
                <div class="col-md-6">
                    <label class="form-label" for="sku">SKU <span class="text-danger">*</span></label>
                    <input type="text" id="sku" name="sku" class="form-control" placeholder="Contoh: BK-PUTIH-M"
                        required value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>" style="text-transform:uppercase;">
                    <div class="form-text">Kode unik produk (otomatis huruf kapital)</div>
                </div>

                <!-- Kategori -->
                <div class="col-12">
                    <label class="form-label">Kategori</label>
                    <input type="hidden" id="category" name="category"
                        value="<?= htmlspecialchars($_POST['category'] ?? '') ?>">

                    <!-- Category Picker Card -->
                    <div class="category-picker" id="categoryPicker">
                        <!-- Search -->
                        <div class="category-picker-search">
                            <span class="search-icon">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg>
                            </span>
                            <input type="text" id="categorySearch" placeholder="Cari kategori..." autocomplete="off">
                        </div>

                        <!-- Scrollable List -->
                        <div class="category-picker-list" id="categoryList">
                            <?php if (empty($categories)): ?>
                                <div class="category-picker-empty" id="categoryEmpty">
                                    <svg viewBox="0 0 24 24">
                                        <path
                                            d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                                        <line x1="7" y1="7" x2="7.01" y2="7" />
                                    </svg>
                                    <div>Belum ada kategori. Tambahkan kategori baru!</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <div class="category-picker-item" data-value="<?= htmlspecialchars($cat['category']) ?>">
                                        <span class="check-indicator">
                                            <svg viewBox="0 0 24 24">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </span>
                                        <span class="category-name"><?= htmlspecialchars($cat['category']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="category-picker-empty" id="categoryEmpty" style="display:none;">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" />
                                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                    </svg>
                                    <div>Kategori tidak ditemukan</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Fixed Footer: Tambah Kategori Button -->
                        <div class="category-picker-footer">
                            <button type="button" id="btnShowAddCategory">
                                <svg viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                Tambah Kategori Baru
                            </button>
                        </div>
                    </div>

                    <!-- Add Category Card (hidden by default) -->
                    <div class="category-add-card" id="categoryAddCard" style="display:none;">
                        <div class="category-add-card-title">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                                <line x1="7" y1="7" x2="7.01" y2="7" />
                            </svg>
                            Daftarkan Kategori Baru
                        </div>
                        <input type="text" id="newCategoryInput" placeholder="Ketik nama kategori baru..."
                            autocomplete="off">
                        <div class="category-add-card-actions">
                            <button type="button" class="btn btn-primary btn-sm" id="btnSaveCategory">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Simpan
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                id="btnCancelCategory">Batal</button>
                        </div>
                    </div>

                    <!-- Inline Notification Area -->
                    <div id="categoryNotifArea"></div>

                    <!-- Selected Badge -->
                    <div id="categoryBadgeArea"></div>
                </div>

                <!-- Harga Beli -->
                <div class="col-md-6">
                    <label class="form-label" for="harga_beli">Harga Beli (Rp) <span
                            class="text-danger">*</span></label>
                    <input type="number" id="harga_beli" name="harga_beli" class="form-control" placeholder="0" min="0"
                        step="100" required value="<?= htmlspecialchars($_POST['harga_beli'] ?? '') ?>">
                </div>

                <!-- Harga Jual -->
                <div class="col-md-6">
                    <label class="form-label" for="harga_jual">Harga Jual (Rp) <span
                            class="text-danger">*</span></label>
                    <input type="number" id="harga_jual" name="harga_jual" class="form-control" placeholder="0" min="0"
                        step="100" required value="<?= htmlspecialchars($_POST['harga_jual'] ?? '') ?>">
                </div>

                <!-- Stok Minimum -->
                <div class="col-md-6">
                    <label class="form-label" for="stok_minimum">Stok Minimum</label>
                    <input type="number" id="stok_minimum" name="stok_minimum" class="form-control" placeholder="5"
                        min="0" value="<?= htmlspecialchars($_POST['stok_minimum'] ?? '5') ?>">
                    <div class="form-text">Notifikasi muncul saat stok di bawah angka ini</div>
                </div>

                <!-- ═══ Stok Awal Section ═══ -->
                <div class="col-12 mt-2">
                    <div class="initial-stock-section">
                        <div class="initial-stock-header">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                            <span>Stok Awal</span>
                        </div>

                        <div class="row g-3">
                            <!-- Gudang -->
                            <div class="col-md-6">
                                <label class="form-label" for="initial_warehouse_id">Gudang</label>
                                <select id="initial_warehouse_id" name="initial_warehouse_id" class="form-select">
                                    <?php if (empty($warehouses)): ?>
                                        <option value="">— Belum ada gudang —</option>
                                    <?php else: ?>
                                        <?php foreach ($warehouses as $w): ?>
                                            <option value="<?= $w['id'] ?>" <?= (($_POST['initial_warehouse_id'] ?? '') == $w['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($w['name']) ?> — <?= htmlspecialchars($w['location']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Jumlah Stok Awal -->
                            <div class="col-md-6">
                                <label class="form-label" for="initial_stock">Jumlah Stok Awal</label>
                                <input type="number" id="initial_stock" name="initial_stock" class="form-control"
                                    placeholder="0" min="0" value="<?= htmlspecialchars($_POST['initial_stock'] ?? '0') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="col-12">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                        placeholder="Deskripsi produk (opsional)"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Margin info -->
            <div class="product-margin-info mt-3" id="marginInfo" style="display:none;">
                <div class="d-flex align-items-center gap-2">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="16" x2="12" y2="12" />
                        <line x1="12" y1="8" x2="12.01" y2="8" />
                    </svg>
                    <span>Margin: <strong id="marginValue">-</strong></span>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Simpan Produk
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

    // ═══════════════════════════════════════════════════════════════
    // CATEGORY PICKER LOGIC
    // ═══════════════════════════════════════════════════════════════
    (function () {
        const hiddenInput = document.getElementById('category');
        const searchInput = document.getElementById('categorySearch');
        const listContainer = document.getElementById('categoryList');
        const emptyState = document.getElementById('categoryEmpty');
        const btnShowAdd = document.getElementById('btnShowAddCategory');
        const addCard = document.getElementById('categoryAddCard');
        const newCatInput = document.getElementById('newCategoryInput');
        const btnSave = document.getElementById('btnSaveCategory');
        const btnCancel = document.getElementById('btnCancelCategory');
        const notifArea = document.getElementById('categoryNotifArea');
        const badgeArea = document.getElementById('categoryBadgeArea');

        // ── Helper: Show notification ─────────────────────────────
        function showNotif(message, type) {
            const icons = {
                success: '<circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/>',
                warning: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
                info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
            };
            notifArea.innerHTML = `
            <div class="category-notif category-notif-${type}">
                <svg viewBox="0 0 24 24">${icons[type]}</svg>
                <span>${message}</span>
            </div>`;
            setTimeout(() => { notifArea.innerHTML = ''; }, 4000);
        }

        // ── Helper: Update selected badge ─────────────────────────
        function updateBadge() {
            const val = hiddenInput.value;
            if (val) {
                badgeArea.innerHTML = `
                <div class="category-selected-badge">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Terpilih: ${val}
                </div>`;
            } else {
                badgeArea.innerHTML = '';
            }
        }

        // ── Helper: Get all item elements ─────────────────────────
        function getItems() {
            return listContainer.querySelectorAll('.category-picker-item');
        }

        // ── Select / Deselect ─────────────────────────────────────
        function selectItem(item) {
            // Deselect all
            getItems().forEach(el => el.classList.remove('selected'));
            // Select this one
            item.classList.add('selected');
            hiddenInput.value = item.getAttribute('data-value');
            updateBadge();
        }

        // Attach click listeners to all items
        function attachItemListeners() {
            getItems().forEach(item => {
                item.addEventListener('click', function () {
                    if (this.classList.contains('selected')) {
                        // Deselect
                        this.classList.remove('selected');
                        hiddenInput.value = '';
                        updateBadge();
                    } else {
                        selectItem(this);
                    }
                });
            });
        }
        attachItemListeners();

        // ── Search / Filter ───────────────────────────────────────
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            getItems().forEach(item => {
                const name = item.getAttribute('data-value').toLowerCase();
                const match = name.includes(query);
                item.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        });

        // ── Show Add Category Card ────────────────────────────────
        btnShowAdd.addEventListener('click', function () {
            addCard.style.display = '';
            newCatInput.value = '';
            newCatInput.focus();
        });

        // ── Cancel Add ────────────────────────────────────────────
        btnCancel.addEventListener('click', function () {
            addCard.style.display = 'none';
            newCatInput.value = '';
        });

        // ── Save New Category ─────────────────────────────────────
        btnSave.addEventListener('click', function () {
            const raw = newCatInput.value.trim();
            const val = raw.toUpperCase();

            // Validasi kosong
            if (!val) {
                showNotif('Nama kategori tidak boleh kosong.', 'warning');
                newCatInput.focus();
                return;
            }

            // Cek duplikat (case-insensitive)
            const existing = Array.from(getItems()).map(el => el.getAttribute('data-value').toUpperCase());
            if (existing.includes(val)) {
                showNotif(`Kategori "${val}" sudah ada, silakan pilih dari daftar.`, 'info');
                newCatInput.value = '';
                newCatInput.focus();

                // Highlight & scroll to the existing item
                getItems().forEach(item => {
                    if (item.getAttribute('data-value').toUpperCase() === val) {
                        selectItem(item);
                        item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
                return;
            }

            // Tambah ke list
            const newItem = document.createElement('div');
            newItem.className = 'category-picker-item';
            newItem.setAttribute('data-value', val);
            newItem.innerHTML = `
            <span class="check-indicator">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
            <span class="category-name">${val}</span>`;

            // Insert before empty state
            listContainer.insertBefore(newItem, emptyState);

            // Re-attach listeners & select the new item
            attachItemListeners();
            selectItem(newItem);
            newItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Hide empty state if it was shown
            emptyState.style.display = 'none';

            // Reset search
            searchInput.value = '';
            getItems().forEach(item => item.style.display = '');

            // Close add card
            addCard.style.display = 'none';
            newCatInput.value = '';

            showNotif(`Kategori "${val}" berhasil ditambahkan!`, 'success');
        });

        // Enter key on new category input = save
        newCatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnSave.click();
            }
        });

        // ── Init: pre-select if value exists ──────────────────────
        if (hiddenInput.value) {
            getItems().forEach(item => {
                if (item.getAttribute('data-value') === hiddenInput.value) {
                    item.classList.add('selected');
                }
            });
            updateBadge();
        }
    })();
</script>