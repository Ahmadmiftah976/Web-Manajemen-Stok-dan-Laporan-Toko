<?php
/**
 * app/controllers/AuthController.php
 * Menangani login dan logout.
 */

require_once APP_PATH . '/models/User.php';

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ── GET /login ────────────────────────────────────────────────────────────

    /**
     * Tampilkan halaman login.
     * Jika sudah login, langsung redirect ke dashboard.
     */
    public function loginPage(): void
    {
        // Jika sudah login, tidak perlu ke halaman login lagi
        if (Auth::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        // Cek apakah ada pesan "session expired"
        $expired = isset($_GET['expired']) && $_GET['expired'] == '1';

        $this->view('auth/login', [
            'title'   => 'Login — ' . APP_NAME,
            'expired' => $expired,
        ], 'auth');   // Gunakan layout auth (tanpa sidebar)
    }

    // ── POST /login ───────────────────────────────────────────────────────────

    /**
     * Proses form login.
     */
    public function login(): void
    {
        // Verifikasi CSRF token
        Csrf::verify();

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        // ── Validasi input dasar ───────────────────────────────────────────
        if (empty($email) || empty($password)) {
            $this->flash('error', 'Email dan password wajib diisi.');
            $this->redirect('/login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Format email tidak valid.');
            $this->redirect('/login');
        }

        // ── Verifikasi kredensial ──────────────────────────────────────────
        
        $user = $this->userModel->verifyCredentials($email, $password);

        if (!$user) {
            // Pesan generik — jangan beritahu apakah email atau password yang salah
            $this->flash('error', 'Email atau password salah.');
            $this->redirect('/login');
        }

        // ── Login berhasil ─────────────────────────────────────────────────
        Auth::login($user);

        // Redirect ke URL yang sebelumnya dituju (jika ada), atau ke dashboard
        $intended = $_SESSION['intended'] ?? '/dashboard';
        unset($_SESSION['intended']);

        $this->redirect($intended);
    }

    // ── GET /logout ───────────────────────────────────────────────────────────

    /**
     * Logout — hapus session dan redirect ke login.
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}