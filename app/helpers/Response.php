<?php
/**
 * app/helpers/Response.php
 * Helper untuk memudahkan pengiriman HTTP response (JSON, Redirect, dll).
 */

class Response
{
    /**
     * Mengirimkan response JSON dan menghentikan eksekusi script.
     * 
     * @param array $data Data array yang akan di-encode ke JSON
     * @param int $statusCode HTTP Status Code (default 200)
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Melakukan redirect ke URL tertentu.
     * 
     * @param string $path Path relatif dari APP_URL, misal: '/kasir'
     */
    public static function redirect(string $path): void
    {
        // Pastikan path berawalan slash
        $path = '/' . ltrim($path, '/');
        header('Location: ' . APP_URL . $path);
        exit;
    }
}
