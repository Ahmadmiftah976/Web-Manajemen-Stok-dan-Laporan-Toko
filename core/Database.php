<?php
/**
 * core/Database.php
 * Singleton untuk koneksi PDO ke PostgreSQL.
 *
 * Cara pakai:
 *   $db  = Database::getInstance();
 *   $pdo = $db->getConnection();
 */

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Constructor private — hanya bisa dibuat lewat getInstance().
     */
    private function __construct()
    {
        $config = require CONFIG_PATH . '/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['dbname']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            // Log error dan tampilkan pesan generik agar kredensial tidak bocor
            error_log('[Database] Koneksi gagal: ' . $e->getMessage());
            die(json_encode([
                'status'  => 'error',
                'message' => 'Koneksi database gagal. Periksa konfigurasi .env Anda.',
            ]));
        }
    }

    /**
     * Mencegah clone instance.
     */
    private function __clone() {}

    /**
     * Mengembalikan satu-satunya instance Database (Singleton).
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Mengembalikan objek PDO untuk digunakan query.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}