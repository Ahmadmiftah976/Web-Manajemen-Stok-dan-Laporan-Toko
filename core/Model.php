<?php
/**
 * core/Model.php
 * Base class untuk semua Model.
 * Menyediakan instance PDO dan helper query yang umum digunakan.
 */

class Model
{
    protected PDO $db;
    protected string $table  = '';      // Wajib di-override di child class
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Query Helpers ─────────────────────────────────────────────────────────

    /**
     * Jalankan query SELECT dan kembalikan semua baris.
     *
     * Contoh: $this->query("SELECT * FROM products WHERE is_active = :active", [':active' => true])
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Jalankan query SELECT dan kembalikan satu baris saja.
     */
    protected function queryOne(string $sql, array $params = []): array|false
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Jalankan query INSERT/UPDATE/DELETE.
     * Mengembalikan jumlah baris yang terpengaruh.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ── CRUD Generik ──────────────────────────────────────────────────────────

    /**
     * Ambil semua baris dari tabel.
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->query("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    /**
     * Cari satu baris berdasarkan primary key.
     */
    public function find(int $id): array|false
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id",
            [':id' => $id]
        );
    }

    /**
     * Insert baris baru dan kembalikan ID yang baru dibuat.
     *
     * $data = ['name' => 'Baju Koko', 'sku' => 'BK-001', ...]
     */
    public function insert(array $data): int
    {
        $columns     = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders) RETURNING {$this->primaryKey}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Update baris berdasarkan primary key.
     *
     * $data = ['name' => 'Baju Koko Baru']
     */
    public function update(int $id, array $data): int
    {
        $setParts = array_map(fn($k) => "$k = :$k", array_keys($data));
        $setClause = implode(', ', $setParts);

        $data[':id'] = $id;

        return $this->execute(
            "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id",
            $data
        );
    }

    /**
     * Hapus baris berdasarkan primary key (hard delete).
     * Untuk soft delete, override di child class dengan update is_active = false.
     */
    public function delete(int $id): int
    {
        return $this->execute(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id",
            [':id' => $id]
        );
    }

    /**
     * Cek apakah baris dengan kondisi tertentu ada.
     *
     * Contoh: $this->exists(['email' => 'admin@example.com'])
     */
    public function exists(array $conditions): bool
    {
        $whereParts = array_map(fn($k) => "$k = :$k", array_keys($conditions));
        $where = implode(' AND ', $whereParts);

        $result = $this->queryOne(
            "SELECT 1 FROM {$this->table} WHERE $where LIMIT 1",
            $conditions
        );

        return $result !== false;
    }

    // ── Transaction Helpers ───────────────────────────────────────────────────

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollback(): void
    {
        $this->db->rollBack();
    }
}