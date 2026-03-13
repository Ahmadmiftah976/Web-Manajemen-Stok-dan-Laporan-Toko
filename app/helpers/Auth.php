<?php
/**
 * app/helpers/Auth.php
 * Helper untuk manajemen session dan proteksi role.
 *
 * Cara pakai di Controller:
 *   Auth::check();              // Redirect ke login jika belum login
 *   Auth::checkRole('pemilik'); // Redirect 403 jika bukan pemilik
 *   Auth::user();               // Ambil data user yang sedang login
 *   Auth::login($userData);     // Set session setelah login berhasil
 *   Auth::logout();             // Hapus semua session
 */

class Auth
{
    /**
     * Pastikan user sudah login. Jika belum, redirect ke /login.
     */
    public static function check(): void
    {
        if (!self::isLoggedIn()) {
            // Simpan path relatif (tanpa base path) agar redirect setelah login tidak dobel
            $uri      = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
            if ($basePath && str_starts_with($uri, $basePath)) {
                $uri = substr($uri, strlen($basePath));
            }
            $_SESSION['intended'] = '/' . ltrim($uri, '/');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Cek apakah session sudah kedaluwarsa
        $lifetime = (int) env('SESSION_LIFETIME', 7200);
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $lifetime) {
            self::logout();
            header('Location: ' . APP_URL . '/login?expired=1');
            exit;
        }

        // Perbarui waktu aktivitas terakhir
        $_SESSION['last_activity'] = time();
    }

    /**
     * Pastikan user memiliki role tertentu.
     * Panggil setelah Auth::check().
     *
     * Contoh: Auth::checkRole('pemilik')
     */
    public static function checkRole(string $role): void
    {
        self::check();

        if (self::user('role') !== $role) {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>403 Akses Ditolak</title></head>
                  <body><h1>403 - Akses Ditolak</h1>
                  <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                  <a href='" . APP_URL . "/dashboard'>Kembali ke Dashboard</a></body></html>";
            exit;
        }
    }

    /**
     * Cek apakah user sedang login (ada session aktif).
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Ambil data user dari session.
     * Jika $key diisi, kembalikan nilai field tertentu saja.
     *
     * Contoh:
     *   Auth::user()         → ['id' => 1, 'name' => 'Ahmad', 'role' => 'pemilik']
     *   Auth::user('name')   → 'Ahmad'
     *   Auth::user('role')   → 'pemilik'
     */
    public static function user(?string $key = null): mixed
    {
        $user = [
            'id'    => $_SESSION['user_id']   ?? null,
            'name'  => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role'  => $_SESSION['user_role'] ?? null,
        ];

        if ($key !== null) {
            return $user[$key] ?? null;
        }

        return $user;
    }

    /**
     * Set session setelah login berhasil.
     *
     * $userData harus berisi: id, name, email, role
     */
    public static function login(array $userData): void
    {
        // Regenerate session ID untuk mencegah session fixation attack
        session_regenerate_id(true);

        $_SESSION['user_id']      = $userData['id'];
        $_SESSION['user_name']    = $userData['name'];
        $_SESSION['user_email']   = $userData['email'];
        $_SESSION['user_role']    = $userData['role'];
        $_SESSION['last_activity'] = time();
    }

    /**
     * Hancurkan semua data session (logout).
     */
    public static function logout(): void
    {
        $_SESSION = [];

        // Hapus cookie session
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Cek apakah user adalah pemilik.
     */
    public static function isPemilik(): bool
    {
        return self::user('role') === 'pemilik';
    }

    /**
     * Cek apakah user adalah kasir.
     */
    public static function isKasir(): bool
    {
        return self::user('role') === 'kasir';
    }
}
