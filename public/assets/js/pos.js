/**
 * Lokasi: public/assets/js/pos.js
 * Deskripsi: Logika keranjang belanja Kasir (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    // State
    let cart = [];
    let discount = 0;
    let amountPaid = 0;
    let paymentMethod = 'tunai';

    // DOM Elements
    const productsGrid = document.getElementById('productsGrid');
    const cartItemsList = document.getElementById('cartItems');
    const subtotalEl = document.getElementById('subtotalValue');
    const discountInput = document.getElementById('discountInput');
    const totalEl = document.getElementById('totalValue');
    const changeEl = document.getElementById('changeValue');
    const paidInput = document.getElementById('paidInput');
    const btnPay = document.getElementById('btnPay');
    const warehouseSelect = document.querySelector('select[name="warehouse"]');
    const cashPaymentDetails = document.getElementById('cashPaymentDetails');
    const paymentRadios = document.querySelectorAll('.payment-method-radio');

    // Helpers
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    };

    const parseNumber = (val) => {
        const parsed = parseInt(val.replace(/[^0-9]/g, ''));
        return isNaN(parsed) ? 0 : parsed;
    };

    // Auto-select isi input saat fokus (memudahkan kasir ngetik)
    const selectOnFocus = (e) => e.target.select();
    discountInput.addEventListener('focus', selectOnFocus);
    paidInput.addEventListener('focus', selectOnFocus);

    // Filter Trigger (Auto submit form saat select berubah)
    document.querySelectorAll('.auto-submit').forEach(el => {
        el.addEventListener('change', (e) => {
            // Jika ganti gudang, beri tahu kasir bahwa keranjang akan direst (opsional, karena browser refresh form)
            e.target.closest('form').submit();
        });
    });

    // Toggle Metode Pembayaran
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            paymentMethod = e.target.value;
            if (paymentMethod === 'qris') {
                cashPaymentDetails.style.display = 'none';
                btnPay.innerHTML = 'BAYAR (QRIS)';
            } else {
                cashPaymentDetails.style.display = 'block';
                btnPay.innerHTML = 'BAYAR (TUNAI)';
            }
            renderCart();
        });
    });

    // ── 1. Interaksi Katalog Produk ──────────────────────────────────
    productsGrid.addEventListener('click', (e) => {
        const card = e.target.closest('.pos-product-card');
        if (!card || card.classList.contains('disabled')) return;

        const product = {
            id: parseInt(card.dataset.id),
            name: card.dataset.name,
            price: parseFloat(card.dataset.price),
            harga_beli: parseFloat(card.dataset.hbeli),
            stock: parseInt(card.dataset.stock)
        };

        addToCart(product);
    });

    // ── 2. Logika Keranjang ──────────────────────────────────────────
    function addToCart(product) {
        const existingItem = cart.find(item => item.id === product.id);

        if (existingItem) {
            if (existingItem.qty < product.stock) {
                existingItem.qty++;
            } else {
                alert(`Stok maksimal untuk ${product.name} hanya ${product.stock}`);
            }
        } else {
            cart.push({ ...product, qty: 1 });
        }

        renderCart();
    }

    function updateQty(id, delta) {
        const index = cart.findIndex(item => item.id === id);
        if (index > -1) {
            const item = cart[index];
            const newQty = item.qty + delta;

            if (newQty <= 0) {
                cart.splice(index, 1);
            } else if (newQty > item.stock) {
                alert(`Stok tidak mencukupi. Sisa stok: ${item.stock}`);
            } else {
                item.qty = newQty;
            }
            renderCart();
        }
    }

    function clearCart() {
        if (cart.length === 0) return;
        if (confirm('Kosongkan keranjang?')) {
            cart = [];
            discountInput.value = '';
            paidInput.value = '';
            renderCart();
        }
    }

    // Bind Clear Cart
    document.getElementById('btnClearCart')?.addEventListener('click', clearCart);

    // ── 3. Render Keranjang & Hitungan ───────────────────────────────
    function renderCart() {
        cartItemsList.innerHTML = '';

        let subtotal = 0;

        if (cart.length === 0) {
            cartItemsList.innerHTML = '<div class="pos-cart-empty">Belum ada item.<br>Klik produk di sebelah kiri.</div>';
            btnPay.disabled = true;
        } else {
            btnPay.disabled = false;

            cart.forEach(item => {
                const itemSubtotal = item.price * item.qty;
                subtotal += itemSubtotal;

                const li = document.createElement('li');
                li.className = 'pos-cart-item';
                li.innerHTML = `
                    <div class="cart-item-info">
                        <div class="cart-item-title">${item.name}</div>
                        <div class="cart-item-price">${formatRupiah(item.price)}</div>
                    </div>
                    <div class="cart-item-actions">
                        <button class="cart-qty-btn dec" data-id="${item.id}">-</button>
                        <div class="cart-qty-val">${item.qty}</div>
                        <button class="cart-qty-btn inc" data-id="${item.id}" ${item.qty >= item.stock ? 'disabled' : ''}>+</button>
                    </div>
                `;
                cartItemsList.appendChild(li);
            });
        }

        // Kalkulasi Total
        subtotalEl.textContent = formatRupiah(subtotal);

        // Ambil nilai diskon dari input
        discount = parseNumber(discountInput.value);
        if (discount > subtotal) {
            discount = subtotal; // Diskon tak boleh lebih dari belanja
            discountInput.value = formatRupiah(discount).replace('Rp', '').trim();
        }

        const total = subtotal - discount;
        totalEl.textContent = formatRupiah(total);

        if (paymentMethod === 'qris') {
            btnPay.disabled = cart.length === 0;
            amountPaid = total;
        } else {
            // Kalkulasi Kembalian
            amountPaid = parseNumber(paidInput.value);
            let change = amountPaid - total;

            // Cek validitas bayar
            if (amountPaid === 0) {
                changeEl.textContent = '-';
                changeEl.style.color = 'var(--text-secondary)';
                btnPay.disabled = cart.length === 0; // Tetap aktif agar tau dia belum bayar kalau dipencet
            } else if (change < 0) {
                changeEl.textContent = 'Kurang ' + formatRupiah(Math.abs(change));
                changeEl.style.color = 'var(--danger-600)';
            } else {
                changeEl.textContent = formatRupiah(change);
                changeEl.style.color = 'var(--success-600)';
            }
        }
    }

    // Event Delegasi keranjang
    cartItemsList.addEventListener('click', (e) => {
        if (e.target.classList.contains('dec')) {
            updateQty(parseInt(e.target.dataset.id), -1);
        } else if (e.target.classList.contains('inc')) {
            updateQty(parseInt(e.target.dataset.id), 1);
        }
    });

    // Event Input uang dan diskon
    discountInput.addEventListener('input', (e) => {
        // Format otomatis sat diketik (biar enak dibaca)
        let val = parseNumber(e.target.value);
        e.target.value = val === 0 ? '' : formatRupiah(val).replace('Rp', '').replace(',00', '').trim();
        renderCart();
    });

    paidInput.addEventListener('input', (e) => {
        let val = parseNumber(e.target.value);
        e.target.value = val === 0 ? '' : formatRupiah(val).replace('Rp', '').replace(',00', '').trim();
        renderCart();
    });

    // ── 4. Checkout Handler ──────────────────────────────────────────
    btnPay.addEventListener('click', async () => {
        if (cart.length === 0) return;

        let subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        let discountVal = parseNumber(discountInput.value);
        let total = subtotal - discountVal;

        const cartPayload = cart.map(i => ({
            id: i.id,
            qty: i.qty,
            price: i.price,
            harga_beli: i.harga_beli
        }));

        btnPay.disabled = true;
        btnPay.innerHTML = '<span class="spinner-btn" style="display:inline-block"></span> Memproses...';

        // ─── Alur TUNAI ─────────────────────────────────────────────
        if (paymentMethod === 'tunai') {
            let paid = parseNumber(paidInput.value);
            if (paid < total) {
                alert('Uang yang dimasukkan kurang dari total belanja.');
                paidInput.focus();
                btnPay.disabled = false;
                btnPay.innerHTML = 'BAYAR (TUNAI)';
                return;
            }

            const payload = {
                warehouse_id: parseInt(warehouseSelect.value),
                total_amount: total,
                discount_amount: discountVal,
                amount_paid: paid,
                change_amount: paid - total,
                payment_method: 'tunai',
                notes: '',
                cart: cartPayload
            };

            try {
                const response = await fetch(APP_URL + '/kasir/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    window.location.href = APP_URL + `/kasir/receipt?id=${result.data.transaction_id}`;
                } else {
                    alert('Gagal: ' + result.message);
                    btnPay.disabled = false;
                    btnPay.innerHTML = 'BAYAR (TUNAI)';
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert('Terjadi kesalahan koneksi server.');
                btnPay.disabled = false;
                btnPay.innerHTML = 'BAYAR (TUNAI)';
            }
            return;
        }

        // ─── Alur QRIS (Midtrans Snap) ──────────────────────────────
        if (paymentMethod === 'qris') {
            const payload = {
                warehouse_id: parseInt(warehouseSelect.value),
                total_amount: total,
                discount_amount: discountVal,
                cart: cartPayload
            };

            try {
                // 1. Request Snap Token dari backend
                const response = await fetch(APP_URL + '/payment/create-qris', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status !== 'success') {
                    alert('Gagal membuat pembayaran QRIS: ' + result.message);
                    btnPay.disabled = false;
                    btnPay.innerHTML = 'BAYAR (QRIS)';
                    return;
                }

                const { snap_token, order_id, transaction_id } = result.data;

                // 2. Background polling: cek status pembayaran otomatis
                //    Berjalan selama popup Snap terbuka, redirect jika sudah paid
                let snapPollingInterval = setInterval(async () => {
                    try {
                        const res = await fetch(APP_URL + `/payment/status?order_id=${order_id}`);
                        const data = await res.json();
                        if (data.status === 'success' && data.data.payment_status === 'paid') {
                            clearInterval(snapPollingInterval);
                            snapPollingInterval = null;
                            window.location.href = APP_URL + `/kasir/receipt?id=${transaction_id}`;
                        }
                    } catch (e) {
                        console.error('Background polling error:', e);
                    }
                }, 3000);

                // 3. Buka pop-up Midtrans Snap
                window.snap.pay(snap_token, {
                    onSuccess: function (result) {
                        if (snapPollingInterval) clearInterval(snapPollingInterval);
                        window.location.href = APP_URL + `/kasir/receipt?id=${transaction_id}`;
                    },
                    onPending: function (result) {
                        if (snapPollingInterval) clearInterval(snapPollingInterval);
                        showWaitingUI(snap_token, order_id, transaction_id);
                    },
                    onError: function (result) {
                        if (snapPollingInterval) clearInterval(snapPollingInterval);
                        alert('Pembayaran gagal. Silakan coba lagi.');
                        btnPay.disabled = false;
                        btnPay.innerHTML = 'BAYAR (QRIS)';
                    },
                    onClose: function () {
                        if (snapPollingInterval) clearInterval(snapPollingInterval);
                        showWaitingUI(snap_token, order_id, transaction_id);
                    }
                });

            } catch (error) {
                console.error('QRIS checkout error:', error);
                alert('Terjadi kesalahan koneksi server.');
                btnPay.disabled = false;
                btnPay.innerHTML = 'BAYAR (QRIS)';
            }
        }
    });

    // ── 5. Waiting UI (setelah popup Snap ditutup) ──────────────────
    let activePollingInterval = null;

    function showWaitingUI(snapToken, orderId, transactionId) {
        // Hentikan polling sebelumnya jika ada
        if (activePollingInterval) clearInterval(activePollingInterval);

        // Tampilkan tombol aksi di area pos-cart-actions
        const actionsArea = document.querySelector('.pos-cart-actions');
        actionsArea.innerHTML = `
            <div class="qris-waiting-panel" style="display:flex; flex-direction:column; gap:8px; width:100%;">
                <div style="text-align:center; font-size:13px; color:#64748b; padding:6px 0;">
                    <span class="spinner-btn" style="display:inline-block; width:14px; height:14px; vertical-align:middle; margin-right:6px;"></span>
                    Menunggu pembayaran QRIS...
                </div>
                <button class="btn btn-primary btn-pay" id="btnReopenQR" style="font-size:14px; padding:10px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-2px; margin-right:6px;">
                        <rect x="1" y="1" width="8" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
                        <rect x="3" y="3" width="4" height="4"/>
                        <rect x="15" y="1" width="8" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
                        <rect x="17" y="3" width="4" height="4"/>
                        <rect x="1" y="15" width="8" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/>
                        <rect x="3" y="17" width="4" height="4"/>
                    </svg>
                    Tampilkan Ulang QR Code
                </button>
                <button class="btn btn-pay" id="btnCancelQris" style="font-size:13px; padding:8px; background:#e2e8f0; color:#475569;">
                    Batalkan Transaksi
                </button>
            </div>
        `;

        // Event: Buka ulang Snap popup
        document.getElementById('btnReopenQR').addEventListener('click', () => {
            if (activePollingInterval) clearInterval(activePollingInterval);
            window.snap.pay(snapToken, {
                onSuccess: function () {
                    window.location.href = APP_URL + `/kasir/receipt?id=${transactionId}`;
                },
                onPending: function () {
                    startBackgroundPolling(orderId, transactionId);
                    showWaitingUI(snapToken, orderId, transactionId);
                },
                onError: function () {
                    alert('Pembayaran gagal.');
                    resetToNormal();
                },
                onClose: function () {
                    showWaitingUI(snapToken, orderId, transactionId);
                }
            });
        });

        // Event: Batalkan transaksi
        document.getElementById('btnCancelQris').addEventListener('click', async () => {
            if (!confirm('Yakin ingin membatalkan transaksi QRIS ini?')) return;
            if (activePollingInterval) clearInterval(activePollingInterval);

            try {
                await fetch(APP_URL + '/payment/cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });
            } catch (e) {
                // Tetap lanjutkan reset meskipun cancel gagal
                console.error('Cancel error:', e);
            }

            resetToNormal();
        });

        // Mulai polling di background
        startBackgroundPolling(orderId, transactionId);
    }

    function startBackgroundPolling(orderId, transactionId) {
        if (activePollingInterval) clearInterval(activePollingInterval);

        let attempts = 0;
        const maxAttempts = 100; // ~5 menit

        activePollingInterval = setInterval(async () => {
            attempts++;
            try {
                const res = await fetch(APP_URL + `/payment/status?order_id=${orderId}`);
                const data = await res.json();

                if (data.status === 'success' && data.data.payment_status === 'paid') {
                    clearInterval(activePollingInterval);
                    window.location.href = APP_URL + `/kasir/receipt?id=${transactionId}`;
                } else if (data.data && data.data.payment_status === 'failed') {
                    clearInterval(activePollingInterval);
                    alert('Pembayaran QRIS dibatalkan atau expired.');
                    resetToNormal();
                }
            } catch (e) {
                console.error('Polling error:', e);
            }

            if (attempts >= maxAttempts) {
                clearInterval(activePollingInterval);
                alert('Waktu menunggu pembayaran habis.');
                resetToNormal();
            }
        }, 3000);
    }

    function resetToNormal() {
        if (activePollingInterval) {
            clearInterval(activePollingInterval);
            activePollingInterval = null;
        }
        // Kembalikan tombol bayar ke keadaan semula
        const actionsArea = document.querySelector('.pos-cart-actions');
        actionsArea.innerHTML = `
            <script>const APP_URL = "${APP_URL}";</script>
            <script src="${document.querySelector('script[data-client-key]')?.src || ''}" data-client-key="${document.querySelector('script[data-client-key]')?.getAttribute('data-client-key') || ''}"></script>
            <button class="btn btn-primary btn-pay" id="btnPay" ${cart.length === 0 ? 'disabled' : ''}>BAYAR (QRIS)</button>
        `;
        // Reload halaman agar state bersih (keranjang tetap kosong setelah transaksi dibatalkan)
        window.location.reload();
    }

    // Initial render
    renderCart();
});

