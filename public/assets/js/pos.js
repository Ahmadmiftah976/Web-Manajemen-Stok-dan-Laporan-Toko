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
        if(cart.length === 0) return;
        if(confirm('Kosongkan keranjang?')) {
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

        let paid = 0;
        let change = 0;

        if (paymentMethod === 'tunai') {
            paid = parseNumber(paidInput.value);
            if (paid < total) {
                alert('Uang yang dimasukkan kurang dari total belanja.');
                paidInput.focus();
                return;
            }
            change = paid - total;
        } else if (paymentMethod === 'qris') {
            paid = total;
            change = 0;
            // TODO: Nanti integrasi API Midtrans SNAP akan dipanggil di sini untuk generate QRIS
        }

        const payload = {
            warehouse_id: parseInt(warehouseSelect.value),
            total_amount: total,
            discount_amount: discountVal,
            amount_paid: paid,
            change_amount: change,
            payment_method: paymentMethod,
            notes: '',
            cart: cart.map(i => ({
                id: i.id,
                qty: i.qty,
                price: i.price,
                harga_beli: i.harga_beli
            }))
        };

        btnPay.disabled = true;
        btnPay.innerHTML = '<span class="spinner-btn" style="display:inline-block"></span> Memproses...';

        try {
            const response = await fetch(APP_URL + '/kasir/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Arahkan ke halaman struk
                window.location.href = APP_URL + `/kasir/receipt?id=${result.data.transaction_id}`;
            } else {
                alert('Gagal: ' + result.message);
                btnPay.disabled = false;
                btnPay.innerHTML = paymentMethod === 'qris' ? 'BAYAR (QRIS)' : 'BAYAR (TUNAI)';
            }

        } catch (error) {
            console.error('Checkout error:', error);
            alert('Terjadi kesalahan koneksi server.');
            btnPay.disabled = false;
            btnPay.innerHTML = paymentMethod === 'qris' ? 'BAYAR (QRIS)' : 'BAYAR (TUNAI)';
        }
    });

    // Initial render
    renderCart();
});
