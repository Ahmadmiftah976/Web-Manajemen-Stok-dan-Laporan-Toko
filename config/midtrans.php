<?php
/**
 * config/midtrans.php
 * Konfigurasi integrasi Midtrans Payment Gateway.
 * Dibaca oleh PaymentController dan helper terkait.
 */

return [
    'server_key'  => env('MIDTRANS_SERVER_KEY', ''),
    'client_key'  => env('MIDTRANS_CLIENT_KEY', ''),
    'is_production' => env('MIDTRANS_ENV', 'sandbox') === 'production',
    'is_sanitized'  => true,   // Sanitasi input Midtrans
    'is_3ds'        => true,   // Aktifkan 3D Secure

    // URL Snap berdasarkan environment
    'snap_url' => env('MIDTRANS_ENV', 'sandbox') === 'production'
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',
];