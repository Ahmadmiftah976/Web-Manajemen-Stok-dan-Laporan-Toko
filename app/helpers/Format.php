<?php
/**
 * app/helpers/Format.php
 * Kumpulan fungsi helper untuk formatting tampilan.
 */

class Format
{
    /**
     * Format angka ke format Rupiah.
     * Contoh: Format::rupiah(150000) → "Rp 150.000"
     */
    public static function rupiah(float|int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Format tanggal ke format Indonesia.
     * Contoh: Format::date('2026-03-06') → "06 Maret 2026"
     */
    public static function date(string $date, string $format = 'd F Y'): string
    {
        $months = [
            1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
            4  => 'April',   5  => 'Mei',       6  => 'Juni',
            7  => 'Juli',    8  => 'Agustus',   9  => 'September',
            10 => 'Oktober', 11 => 'November',  12 => 'Desember',
        ];

        $ts = strtotime($date);
        $result = date($format, $ts);

        // Ganti nama bulan Inggris ke Indonesia
        foreach ($months as $num => $indo) {
            $eng = date('F', mktime(0, 0, 0, $num, 1));
            $result = str_replace($eng, $indo, $result);
        }

        return $result;
    }

    /**
     * Format datetime ke format Indonesia lengkap.
     * Contoh: Format::datetime('2026-03-06 14:30:00') → "06 Maret 2026, 14:30"
     */
    public static function datetime(string $datetime): string
    {
        return self::date($datetime, 'd F Y') . ', ' . date('H:i', strtotime($datetime));
    }

    /**
     * Potong teks panjang dengan ellipsis.
     * Contoh: Format::truncate('Baju Koko Putih Motif Batik', 15) → "Baju Koko Putih..."
     */
    public static function truncate(string $text, int $length = 50): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }


    /**
     * Generate kode transaksi unik.
     * Contoh output: "TRX-20260306-0001"
     */
    public static function transactionCode(int $sequence): string
    {
        return 'TRX-' . date('Ymd') . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Format badge status pembayaran untuk HTML.
     */
    public static function paymentBadge(string $status): string
    {
        $badges = [
            'paid'    => '<span class="badge bg-success">Lunas</span>',
            'pending' => '<span class="badge bg-warning text-dark">Menunggu</span>',
            'failed'  => '<span class="badge bg-danger">Gagal</span>',
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }

    /**
     * Format badge role pengguna.
     */
    public static function roleBadge(string $role): string
    {
        $badges = [
            'pemilik' => '<span class="badge bg-primary">Pemilik</span>',
            'kasir'   => '<span class="badge bg-info text-dark">Kasir</span>',
        ];
        return $badges[$role] ?? '<span class="badge bg-secondary">' . htmlspecialchars($role) . '</span>';
    }
}