/**
 * auth.js — Script khusus halaman Login
 * Diload hanya di layouts/auth.php
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Toggle visibilitas password ───────────────────────
    const toggleBtn = document.querySelector('.auth-toggle-pw');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const input  = document.getElementById('password');
            const isText = input.type === 'text';

            input.type = isText ? 'password' : 'text';

            // Ganti icon
            this.innerHTML = isText
                ? `<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`
                : `<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
        });
    }

    // ── Loading state saat form di-submit ─────────────────
    const form    = document.querySelector('.auth-form');
    const btn     = document.querySelector('.auth-btn-submit');
    const spinner = document.querySelector('.auth-btn-submit .spinner');
    const btnText = document.querySelector('.auth-btn-submit .btn-text');
    const btnIcon = document.querySelector('.auth-btn-submit .btn-icon');

    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            if (spinner) spinner.style.display = 'block';
            if (btnText) btnText.textContent    = 'Memproses...';
            if (btnIcon) btnIcon.style.display  = 'none';
        });
    }

    // ── Auto-focus email field ────────────────────────────
    const emailInput = document.getElementById('email');
    if (emailInput && !emailInput.value) {
        emailInput.focus();
    }

});