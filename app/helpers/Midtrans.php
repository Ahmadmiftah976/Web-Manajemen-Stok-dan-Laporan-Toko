<?php
/**
 * app/helpers/Midtrans.php
 * Helper untuk komunikasi dengan API Midtrans Snap.
 * Menggunakan cURL murni tanpa library/composer.
 */

class Midtrans
{
    /**
     * Membuat Snap Token untuk transaksi baru.
     *
     * @param array $params Parameter transaksi sesuai format Midtrans Snap API
     * @return array ['snap_token' => string, 'redirect_url' => string] atau ['error' => string]
     */
    public static function createSnapToken(array $params): array
    {
        $serverKey = MIDTRANS_SERVER_KEY;

        if (empty($serverKey)) {
            return ['error' => 'MIDTRANS_SERVER_KEY belum dikonfigurasi di .env'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => MIDTRANS_SNAP_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ],
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('[Midtrans] cURL Error: ' . $curlError);
            return ['error' => 'Gagal menghubungi server Midtrans: ' . $curlError];
        }

        $result = json_decode($response, true);

        if ($httpCode !== 201 || empty($result['token'])) {
            $errMsg = $result['error_messages'][0] ?? ($result['message'] ?? 'Unknown error');
            error_log('[Midtrans] API Error (HTTP ' . $httpCode . '): ' . $errMsg);
            return ['error' => 'Midtrans API Error: ' . $errMsg];
        }

        return [
            'snap_token'   => $result['token'],
            'redirect_url' => $result['redirect_url'] ?? ''
        ];
    }

    /**
     * Memverifikasi signature dari notifikasi webhook Midtrans.
     * Signature = SHA512(order_id + status_code + gross_amount + server_key)
     *
     * @param array $notification Data JSON dari body webhook
     * @return bool
     */
    public static function verifySignature(array $notification): bool
    {
        $orderId     = $notification['order_id'] ?? '';
        $statusCode  = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $serverKey   = MIDTRANS_SERVER_KEY;

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $receivedSignature = $notification['signature_key'] ?? '';

        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Memeriksa status transaksi langsung ke Midtrans API.
     *
     * @param string $orderId Order ID yang ingin dicek
     * @return array Data status dari Midtrans
     */
    public static function checkStatus(string $orderId): array
    {
        $serverKey = MIDTRANS_SERVER_KEY;
        $baseUrl = MIDTRANS_IS_PRODUCTION
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $baseUrl . '/v2/' . $orderId . '/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('[Midtrans] checkStatus cURL Error: ' . $curlError);
            return ['error' => $curlError];
        }

        return json_decode($response, true) ?: ['error' => 'Invalid response'];
    }
}
