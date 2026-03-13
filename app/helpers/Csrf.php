<?php
/**
 * app/helpers/Csrf.php
 * Proteksi CSRF (Cross-Site Request Forgery).
 *
 * Cara pakai:
 *   Di view (dalam form):  <?= Csrf::field() ?>
 *   Di controller (POST):  Csrf::verify();
 */

class Csrf
{
    private static string $tokenKey = '_csrf_token';

    /**
     * Ambil token CSRF saat ini (buat baru jika belum ada).
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::$tokenKey])) {
            $_SESSION[self::$tokenKey] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$tokenKey];
    }

    /**
     * Kembalikan hidden input field HTML yang berisi token.
     * Sisipkan ini di setiap form POST.
     *
     * Contoh output:
     *   <input type="hidden" name="_csrf_token" value="abc123...">
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::$tokenKey . '" value="' . self::token() . '">';
    }

    /**
     * Verifikasi token dari request POST.
     * Jika tidak valid, hentikan eksekusi dengan error 419.
     */
    public static function verify(): void
    {
        $submitted = $_POST[self::$tokenKey] ?? '';
        $expected  = $_SESSION[self::$tokenKey] ?? '';

        if (empty($expected) || !hash_equals($expected, $submitted)) {
            http_response_code(419);
            die(json_encode([
                'status'  => 'error',
                'message' => 'Token CSRF tidak valid. Muat ulang halaman dan coba lagi.',
            ]));
        }

        // Regenerate token setelah berhasil digunakan (one-time token)
        $_SESSION[self::$tokenKey] = bin2hex(random_bytes(32));
    }
}