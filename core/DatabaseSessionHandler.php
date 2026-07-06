<?php
/**
 * core/DatabaseSessionHandler.php
 *
 * Custom PHP Session Handler berbasis PostgreSQL.
 * Menggantikan penyimpanan session file default PHP agar sesi
 * tetap persisten di environment serverless seperti Vercel,
 * di mana setiap request bisa ditangani instance berbeda.
 *
 * Cara pakai: Daftarkan sebelum session_start() di public/index.php
 */

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Dipanggil saat session dibuka — koneksi DB sudah ada.
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Dipanggil saat session ditutup.
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Baca data session dari database.
     */
    public function read(string $id): string|false
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT data FROM sessions WHERE id = :id AND last_activity > :expiry'
            );
            $stmt->execute([
                ':id'     => $id,
                ':expiry' => time() - (int) env('SESSION_LIFETIME', 7200),
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['data'] : '';
        } catch (PDOException $e) {
            error_log('[Session] read error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Tulis/update data session ke database.
     */
    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO sessions (id, data, last_activity)
                 VALUES (:id, :data, :ts)
                 ON CONFLICT (id) DO UPDATE
                 SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity'
            );
            return $stmt->execute([
                ':id'   => $id,
                ':data' => $data,
                ':ts'   => time(),
            ]);
        } catch (PDOException $e) {
            error_log('[Session] write error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus session dari database (logout / session_destroy).
     */
    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('[Session] destroy error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus session yang sudah kedaluwarsa (Garbage Collection).
     */
    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM sessions WHERE last_activity < :expiry'
            );
            $stmt->execute([':expiry' => time() - $max_lifetime]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('[Session] gc error: ' . $e->getMessage());
            return false;
        }
    }
}
