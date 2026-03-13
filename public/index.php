<?php
/**
 * public/index.php
 * ─────────────────────────────────────────────
 * SATU-SATUNYA entry point untuk seluruh aplikasi MajmaInsight.
 * Web server (Apache/Nginx) harus mengarahkan semua request ke file ini.
 *
 * Urutan bootstrap:
 *   1. Load konfigurasi (.env, konstanta, timezone)
 *   2. Autoload core & helper classes
 *   3. Mulai session
 *   4. Jalankan Router
 * ─────────────────────────────────────────────
 */

// ── 1. Konfigurasi ────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/app.php';

// ── 2. Autoload ───────────────────────────────────────────────────────────────
// Core classes (Database, Model, Controller, Router)
foreach (glob(CORE_PATH . '/*.php') as $file) {
    require_once $file;
}

// Helper classes (Auth, Csrf, Format, Response)
foreach (glob(APP_PATH . '/helpers/*.php') as $file) {
    require_once $file;
}

// ── 3. Session ────────────────────────────────────────────────────────────────
$sessionName = env('SESSION_NAME', 'majma_session');
session_name($sessionName);

session_set_cookie_params([
    'lifetime' => 0,             // Tutup browser = session berakhir
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,         // Set true jika pakai HTTPS
    'httponly' => true,          // Tidak bisa diakses JavaScript
    'samesite' => 'Strict',      // Proteksi CSRF tambahan
]);

session_start();

// ── 4. Router ─────────────────────────────────────────────────────────────────
$router = new Router();
$router->dispatch();