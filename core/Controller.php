<?php
/**
 * core/Controller.php
 * Base class untuk semua Controller.
 * Menyediakan helper untuk render view, redirect, dan JSON response.
 */

class Controller
{
    // ── View ──────────────────────────────────────────────────────────────────

    /**
     * Render file view dengan data yang di-extract sebagai variabel lokal.
     *
     * Contoh:
     *   $this->view('products/index', ['products' => $products])
     *
     * Akan me-render: app/views/products/index.php
     * dengan variabel $products tersedia di dalam view.
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        // Jadikan semua key array sebagai variabel
        extract($data);

        // Path lengkap ke file view
        $viewFile = VIEW_PATH . '/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            $this->abort(404, "View tidak ditemukan: $viewPath");
        }

        // Tangkap output view ke dalam buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();   // $content akan tersedia di layout

        // Render layout yang membungkus view
        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';

        if (!file_exists($layoutFile)) {
            // Jika layout tidak ada, tampilkan view langsung
            echo $content;
            return;
        }

        require $layoutFile;
    }

    /**
     * Render view tanpa layout (digunakan untuk partial/AJAX fragment).
     */
    protected function viewOnly(string $viewPath, array $data = []): void
    {
        extract($data);
        $viewFile = VIEW_PATH . '/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            $this->abort(404, "View tidak ditemukan: $viewPath");
        }

        require $viewFile;
    }

    // ── Redirect ──────────────────────────────────────────────────────────────

    /**
     * Redirect ke URL tertentu.
     * Gunakan path relatif dari APP_URL.
     *
     * Contoh: $this->redirect('/dashboard')
     */
    protected function redirect(string $path): void
    {
        $url = str_starts_with($path, 'http') ? $path : APP_URL . $path;
        header("Location: $url");
        exit;
    }

    /**
     * Redirect kembali ke halaman sebelumnya.
     */
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/dashboard';
        header("Location: $referer");
        exit;
    }

    // ── JSON Response ─────────────────────────────────────────────────────────

    /**
     * Kirim response JSON (untuk endpoint AJAX).
     *
     * Contoh: $this->json(['status' => 'success', 'data' => $product])
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Shortcut JSON sukses.
     */
    protected function jsonSuccess(string $message, array $data = [], int $code = 200): void
    {
        $this->json(['status' => 'success', 'message' => $message, 'data' => $data], $code);
    }

    /**
     * Shortcut JSON error.
     */
    protected function jsonError(string $message, int $code = 400): void
    {
        $this->json(['status' => 'error', 'message' => $message], $code);
    }

    // ── Request Helpers ───────────────────────────────────────────────────────

    /**
     * Ambil nilai dari $_POST dengan nilai default opsional.
     * Otomatis di-trim dan di-htmlspecialchars untuk keamanan dasar.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = trim($_POST[$key]);

        return $value === '' ? $default : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Ambil nilai dari $_GET.
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        if (!isset($_GET[$key])) {
            return $default;
        }

        $value = trim($_GET[$key]);

        return $value === '' ? $default : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Cek apakah request method adalah POST.
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Cek apakah request adalah AJAX (XMLHttpRequest).
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // ── Flash Message ─────────────────────────────────────────────────────────

    /**
     * Set flash message ke session (tampil sekali di halaman berikutnya).
     *
     * Contoh: $this->flash('success', 'Produk berhasil disimpan!')
     * Tipe: success | error | warning | info
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    // ── Error Pages ───────────────────────────────────────────────────────────

    /**
     * Tampilkan halaman error dan hentikan eksekusi.
     */
    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $messages = [
            403 => 'Akses Ditolak',
            404 => 'Halaman Tidak Ditemukan',
            500 => 'Terjadi Kesalahan Server',
        ];
        $title = $messages[$code] ?? 'Error';
        echo "<!DOCTYPE html><html><head><title>$code $title</title></head>
              <body><h1>$code - $title</h1><p>" . htmlspecialchars($message) . "</p>
              <a href='" . APP_URL . "'>Kembali ke Beranda</a></body></html>";
        exit;
    }
}
