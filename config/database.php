<?php
/**
 * config/database.php
 * Mengembalikan array konfigurasi koneksi database PostgreSQL.
 * Dibaca oleh core/Database.php.
 */

return [
    'driver'   => 'pgsql',
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', '5432'),
    'dbname'   => env('DB_NAME', 'majmainsight_db'),
    'user'     => env('DB_USER', 'postgres'),
    'password' => env('DB_PASS', ''),

    // Opsi PDO
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lempar exception saat error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Fetch sebagai array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                    // Gunakan prepared statement native
    ],
];