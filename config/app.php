<?php
/**
 * config/app.php
 * Membaca file .env dan mendefinisikan konfigurasi global aplikasi.
 * File ini di-require pertama kali oleh public/index.php.
 */

// ── 1. Load .env ──────────────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';

if (!file_exists($envFile)) {
    die('File .env tidak ditemukan. Salin .env.example menjadi .env dan isi konfigurasinya.');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // Abaikan baris komentar
    if (str_starts_with(trim($line), '#')) continue;

    if (str_contains($line, '=')) {
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\"'");   // hapus spasi dan tanda kutip

        // Set ke $_ENV dan getenv()
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// ── 2. Helper env() ───────────────────────────────────────────────────────────
/**
 * Ambil nilai environment variable dengan nilai default opsional.
 *
 * Contoh: env('APP_NAME', 'MyApp')
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== null) ? $value : $default;
}

// ── 3. Konfigurasi PHP runtime ────────────────────────────────────────────────
// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Makassar'));

// Error reporting: tampilkan error di development, sembunyikan di production
if (env('APP_ENV', 'development') === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ── 4. Konstanta global ───────────────────────────────────────────────────────
define('APP_NAME',     env('APP_NAME', 'MajmaInsight'));
define('APP_ENV',      env('APP_ENV',  'development'));
define('APP_URL',      rtrim(env('APP_URL', ''), '/'));

// Path absolut ke root proyek (satu level di atas public/)
define('BASE_PATH',    dirname(__DIR__));
define('APP_PATH',     BASE_PATH . '/app');
define('CONFIG_PATH',  BASE_PATH . '/config');
define('CORE_PATH',    BASE_PATH . '/core');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('VIEW_PATH',    APP_PATH  . '/views');