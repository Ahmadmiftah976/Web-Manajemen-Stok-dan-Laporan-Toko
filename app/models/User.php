<?php
/**
 * app/models/User.php
 * Model untuk tabel users.
 */

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Ambil semua user aktif.
     */
    public function getAllActive(): array
    {
        return $this->query(
            "SELECT id, name, email, role, created_at
             FROM   users
             WHERE  is_active = TRUE
             ORDER  BY created_at DESC"
        );
    }

    /**
     * Cari user berdasarkan email (untuk proses login).
     */
    public function findByEmail(string $email): array|false
    {
        return $this->queryOne(
            "SELECT * FROM users WHERE email = :email AND is_active = TRUE LIMIT 1",
            [':email' => $email]
        );
    }

    /**
     * Cari user berdasarkan ID (hanya kolom aman, tanpa password_hash).
     */
    public function findById(int $id): array|false
    {
        return $this->queryOne(
            "SELECT id, name, email, role, is_active, created_at
             FROM   users
             WHERE  id = :id LIMIT 1",
            [':id' => $id]
        );
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    /**
     * Verifikasi email + password.
     * Mengembalikan data user jika valid, false jika tidak.
     */
    public function verifyCredentials(string $email, string $password): array|false
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        return $user;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Buat user baru.
     * Password otomatis di-hash sebelum disimpan.
     */
    public function createUser(array $data): int
    {
        return $this->insert([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role'          => $data['role'] ?? 'kasir',
        ]);
    }

    /**
     * Update data user (tanpa password).
     */
    public function updateUser(int $id, array $data): int
    {
        return $this->update($id, [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ]);
    }

    /**
     * Ganti password user.
     */
    public function updatePassword(int $id, string $newPassword): int
    {
        return $this->update($id, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
    }

    /**
     * Nonaktifkan user (soft delete).
     */
    public function deactivate(int $id): int
    {
        return $this->update($id, ['is_active' => false]);
    }

    /**
     * Aktifkan kembali user.
     */
    public function activate(int $id): int
    {
        return $this->update($id, ['is_active' => true]);
    }

    // ── Validasi ──────────────────────────────────────────────────────────────

    /**
     * Cek apakah email sudah dipakai user lain.
     * $excludeId digunakan saat edit (abaikan email milik user itu sendiri).
     */
    public function isEmailTaken(string $email, int $excludeId = 0): bool
    {
        $result = $this->queryOne(
            "SELECT 1 FROM users WHERE email = :email AND id != :id LIMIT 1",
            [':email' => $email, ':id' => $excludeId]
        );
        return $result !== false;
    }
}
