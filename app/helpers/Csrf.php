<?php
/**
 * app/helpers/Csrf.php
 * Proteksi CSRF (Cross-Site Request Forgery).
 *
 * Menggunakan pola Double Submit Cookie agar kompatibel dengan
 * environment serverless (Vercel) di mana session file tidak
 * persisten antar instance.
 *
 * Cara pakai:
 *   Di view (dalam form):  <?= Csrf::field() ?>
 *   Di controller (POST):  Csrf::verify();
 */

class Csrf
{
    private static string $tokenKey    = '_csrf_token';
    private static string $cookieName  = '_csrf';

    /**
     * Ambil token CSRF saat ini.
     * Token disimpan di cookie (agar stateless, cocok untuk serverless).
     */
    public static function token(): string
    {
        // Jika cookie sudah ada, gunakan nilai yang ada
        if (!empty($_COOKIE[self::$cookieName])) {
            return $_COOKIE[self::$cookieName];
        }

        // Buat token baru dan simpan di cookie
        $token = bin2hex(random_bytes(32));
        self::setCookie($token);
        return $token;
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
     * Membandingkan nilai form dengan nilai cookie (Double Submit Cookie pattern).
     * Jika tidak valid, hentikan eksekusi dengan error 419.
     */
    public static function verify(): void
    {
        $submitted = $_POST[self::$tokenKey]      ?? '';
        $expected  = $_COOKIE[self::$cookieName]  ?? '';

        if (empty($expected) || !hash_equals($expected, $submitted)) {
            http_response_code(419);
            die(json_encode([
                'status'  => 'error',
                'message' => 'Token CSRF tidak valid. Muat ulang halaman dan coba lagi.',
            ]));
        }

        // Regenerate token setelah berhasil digunakan (one-time token)
        $newToken = bin2hex(random_bytes(32));
        self::setCookie($newToken);
    }

    /**
     * Set cookie CSRF dengan atribut keamanan yang sesuai.
     */
    private static function setCookie(string $token): void
    {
        $secure   = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $sameSite = 'Strict';

        setcookie(self::$cookieName, $token, [
            'expires'  => 0,          // session cookie (hapus saat browser ditutup)
            'path'     => '/',
            'secure'   => $secure,    // hanya HTTPS di production
            'httponly' => false,      // perlu bisa dibaca JS jika diperlukan
            'samesite' => $sameSite,
        ]);

        // Langsung tersedia untuk request ini
        $_COOKIE[self::$cookieName] = $token;
    }
}