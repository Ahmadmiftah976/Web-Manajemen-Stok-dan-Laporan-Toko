/**
 * app.js — Script global MajmaInsight
 * Diload di semua halaman (di layouts/main.php)
 */

// ── Sidebar toggle (mobile) ───────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Tutup sidebar saat klik di luar area sidebar (mobile)
document.addEventListener('click', function (e) {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;

    if (window.innerWidth <= 768
        && !sidebar.contains(e.target)
        && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// ── Auto-dismiss flash alert ──────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const flash = document.querySelector('.flash-alert.alert-success');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.4s ease';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 400);
        }, 3500);
    }
});

// ── Format Rupiah (untuk input angka) ────────────────────
function formatRupiah(angka) {
    return 'Rp ' + parseInt(angka || 0)
        .toLocaleString('id-ID');
}

// ── Konfirmasi hapus ──────────────────────────────────────
function confirmDelete(message = 'Yakin ingin menghapus data ini?') {
    return confirm(message);
}

// ── Tutup notifikasi dropdown saat klik di luar ───────────
document.addEventListener('click', function (e) {
    const wrapper = document.querySelector('.notif-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        wrapper.classList.remove('open');
    }
});