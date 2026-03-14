<?php
/**
 * Lokasi: app/views/kasir/receipt.php
 * Deskripsi: Struk Thermal (58mm/80mm) untuk cetak resi pembayaran.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= htmlspecialchars($transaction['transaction_code']) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .receipt-container {
            width: 300px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 15px; }
        .border-dashed { border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        
        .row-flex {
            display: flex;
            justify-content: space-between;
        }

        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        
        .header p {
            margin: 0;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th, table td {
            font-size: 11px;
            text-align: left;
            vertical-align: top;
            padding: 2px 0;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-family: sans-serif;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-print { background: #2563eb; color: #fff; }
        .btn-new { background: #e2e8f0; color: #1e293b; }

        @media print {
            body { background: none; padding: 0; margin: 0; }
            .receipt-container { box-shadow: none; width: 100%; max-width: 80mm; padding: 0mm; }
            .actions { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header text-center border-dashed">
        <h2><?= APP_NAME ?></h2>
        <p><?= htmlspecialchars($transaction['warehouse_name']) ?></p>
        <p>Tgl: <?= date('d/m/Y H:i', strtotime($transaction['transaction_date'])) ?></p>
        <p>Kasir: <?= htmlspecialchars($transaction['cashier_name']) ?></p>
        <p>No: <?= htmlspecialchars($transaction['transaction_code']) ?></p>
    </div>

    <table class="border-dashed">
        <tbody>
            <?php 
            $subtotalBelanja = 0;
            foreach ($items as $item): 
                $subtotalBelanja += $item['subtotal'];
            ?>
            <tr>
                <td colspan="3" class="text-bold"><?= htmlspecialchars($item['product_name']) ?></td>
            </tr>
            <tr>
                <td style="width: 25%;"><?= $item['quantity'] ?>x</td>
                <td style="width: 35%;"><?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                <td style="width: 40%;" class="text-right"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row-flex mb-1">
        <span>Subtotal</span>
        <span><?= number_format($subtotalBelanja, 0, ',', '.') ?></span>
    </div>
    
    <?php if ($transaction['discount_amount'] > 0): ?>
    <div class="row-flex mb-1">
        <span>Diskon</span>
        <span>- <?= number_format($transaction['discount_amount'], 0, ',', '.') ?></span>
    </div>
    <?php endif; ?>

    <div class="row-flex text-bold border-dashed" style="font-size: 14px;">
        <span>Total</span>
        <span><?= number_format($transaction['total_amount'], 0, ',', '.') ?></span>
    </div>

    <div class="row-flex mb-1">
        <span>Tunai</span>
        <span><?= number_format($transaction['amount_paid'], 0, ',', '.') ?></span>
    </div>
    <div class="row-flex">
        <span>Kembali</span>
        <span><?= number_format($transaction['change_amount'], 0, ',', '.') ?></span>
    </div>

    <div class="text-center mt-3" style="margin-top: 15px; font-size: 11px;">
        <p>Terima Kasih!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>
</div>

<div class="actions">
    <button class="btn btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
    <a href="<?= APP_URL ?>/kasir" class="btn btn-new">⬅️ Transaksi Baru</a>
</div>

</body>
</html>
