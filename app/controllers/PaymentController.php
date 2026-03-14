<?php
/**
 * app/controllers/PaymentController.php
 * Menangani pembuatan pembayaran QRIS via Midtrans Snap,
 * menerima webhook notifikasi, dan pengecekan status.
 */

require_once APP_PATH . '/helpers/Midtrans.php';
require_once APP_PATH . '/helpers/Response.php';
require_once APP_PATH . '/models/Transaction.php';

class PaymentController extends Controller
{
    private Transaction $transactionModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
    }

    /**
     * POST /payment/create-qris
     * Dipanggil oleh POS JS ketika kasir memilih QRIS.
     * Membuat transaksi "pending" lalu request Snap Token ke Midtrans.
     */
    public function createQris(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        // Pastikan user login
        Auth::check();

        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || empty($payload['cart'])) {
            Response::json(['status' => 'error', 'message' => 'Data keranjang tidak valid'], 400);
        }

        $warehouseId    = (int) ($payload['warehouse_id'] ?? 0);
        $totalAmount    = (float) ($payload['total_amount'] ?? 0);
        $discountAmount = (float) ($payload['discount_amount'] ?? 0);
        $cartItems      = $payload['cart'];

        if ($warehouseId <= 0 || $totalAmount <= 0) {
            Response::json(['status' => 'error', 'message' => 'Gudang dan total harus valid'], 400);
        }

        // Format item untuk database
        $items = [];
        foreach ($cartItems as $item) {
            $items[] = [
                'product_id' => (int) $item['id'],
                'quantity'   => (int) $item['qty'],
                'harga_jual' => (float) $item['price'],
                'harga_beli' => (float) ($item['harga_beli'] ?? 0),
                'subtotal'   => (float) $item['price'] * (int) $item['qty']
            ];
        }

        // Simpan transaksi dengan status "pending" (stok belum dipotong)
        $trxData = [
            'cashier_id'      => Auth::user('id'),
            'warehouse_id'    => $warehouseId,
            'total_amount'    => $totalAmount,
            'discount_amount' => $discountAmount,
            'amount_paid'     => $totalAmount,
            'change_amount'   => 0,
            'payment_method'  => 'qris',
            'payment_status'  => 'pending',
            'notes'           => 'Menunggu pembayaran QRIS'
        ];

        $transactionId = $this->transactionModel->createPendingTransaction($trxData, $items);

        if (!$transactionId) {
            Response::json(['status' => 'error', 'message' => 'Gagal membuat transaksi'], 500);
        }

        // Ambil transaction code untuk order_id Midtrans
        $trxInfo = $this->transactionModel->find($transactionId);
        $orderId = $trxInfo['transaction_code'];

        // Susun parameter untuk Midtrans Snap
        $midtransParams = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $totalAmount
            ],
            'enabled_payments' => ['other_qris'],
            'callbacks' => [
                'finish' => APP_URL . '/kasir/receipt?id=' . $transactionId
            ]
        ];

        // Request Snap Token
        $snapResult = Midtrans::createSnapToken($midtransParams);

        if (isset($snapResult['error'])) {
            // Batalkan transaksi pending jika gagal dapat token
            $this->transactionModel->cancelTransaction($transactionId);
            Response::json([
                'status'  => 'error',
                'message' => $snapResult['error']
            ], 500);
        }

        Response::json([
            'status' => 'success',
            'data'   => [
                'snap_token'     => $snapResult['snap_token'],
                'order_id'       => $orderId,
                'transaction_id' => $transactionId
            ]
        ]);
    }

    /**
     * POST /payment/webhook
     * Endpoint publik untuk menerima notifikasi dari Midtrans.
     * TIDAK memerlukan autentikasi user (dipanggil oleh server Midtrans).
     */
    public function webhook(): void
    {
        // Baca raw JSON body
        $rawBody = file_get_contents('php://input');
        $notification = json_decode($rawBody, true);

        if (!$notification) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
            exit;
        }

        // Log untuk debugging
        error_log('[Midtrans Webhook] Received: ' . $rawBody);

        // Verifikasi signature
        if (!Midtrans::verifySignature($notification)) {
            error_log('[Midtrans Webhook] Signature verification FAILED');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
            exit;
        }

        $orderId           = $notification['order_id'] ?? '';
        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus       = $notification['fraud_status'] ?? 'accept';

        // Cari transaksi berdasarkan order_id (transaction_code)
        $transaction = $this->transactionModel->findByCode($orderId);

        if (!$transaction) {
            error_log('[Midtrans Webhook] Transaction not found: ' . $orderId);
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
            exit;
        }

        $trxId = (int) $transaction['id'];

        // Proses berdasarkan status
        if (($transactionStatus === 'capture' && $fraudStatus === 'accept') || $transactionStatus === 'settlement') {
            // Pembayaran berhasil → konfirmasi (potong stok)
            if ($transaction['payment_status'] !== 'paid') {
                $this->transactionModel->confirmPayment($trxId);
                error_log('[Midtrans Webhook] Payment confirmed for: ' . $orderId);
            }
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            // Pembayaran gagal / expired
            $this->transactionModel->cancelTransaction($trxId);
            error_log('[Midtrans Webhook] Payment cancelled for: ' . $orderId);
        }
        // Status 'pending' → biarkan, tidak perlu action

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    /**
     * GET /payment/status?order_id=TRX-xxx
     * Endpoint AJAX untuk frontend mengecek status pembayaran (polling).
     */
    public function checkStatus(): void
    {
        Auth::check();

        $orderId = $_GET['order_id'] ?? '';

        if (empty($orderId)) {
            Response::json(['status' => 'error', 'message' => 'order_id diperlukan'], 400);
        }

        // Cek ke database dulu
        $transaction = $this->transactionModel->findByCode($orderId);

        if (!$transaction) {
            Response::json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Jika sudah paid di database, langsung return
        if ($transaction['payment_status'] === 'paid') {
            Response::json([
                'status' => 'success',
                'data'   => [
                    'payment_status' => 'paid',
                    'transaction_id' => $transaction['id']
                ]
            ]);
        }

        // Jika masih pending, cek langsung ke Midtrans API
        $midtransStatus = Midtrans::checkStatus($orderId);
        $mtStatus = $midtransStatus['transaction_status'] ?? 'pending';

        if ($mtStatus === 'settlement' || $mtStatus === 'capture') {
            // Update di database
            $this->transactionModel->confirmPayment((int) $transaction['id']);

            Response::json([
                'status' => 'success',
                'data'   => [
                    'payment_status' => 'paid',
                    'transaction_id' => $transaction['id']
                ]
            ]);
        }

        Response::json([
            'status' => 'success',
            'data'   => [
                'payment_status' => $transaction['payment_status'],
                'transaction_id' => $transaction['id']
            ]
        ]);
    }

    /**
     * POST /payment/cancel
     * Membatalkan transaksi QRIS yang masih pending.
     */
    public function cancelPayment(): void
    {
        Auth::check();

        $payload = json_decode(file_get_contents('php://input'), true);
        $orderId = $payload['order_id'] ?? '';

        if (empty($orderId)) {
            Response::json(['status' => 'error', 'message' => 'order_id diperlukan'], 400);
        }

        $transaction = $this->transactionModel->findByCode($orderId);

        if (!$transaction) {
            Response::json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Hanya bisa cancel jika masih pending
        if ($transaction['payment_status'] !== 'pending') {
            Response::json(['status' => 'error', 'message' => 'Transaksi tidak bisa dibatalkan'], 400);
        }

        $this->transactionModel->cancelTransaction((int) $transaction['id']);

        Response::json([
            'status'  => 'success',
            'message' => 'Transaksi berhasil dibatalkan'
        ]);
    }
}
