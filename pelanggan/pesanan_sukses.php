<?php
session_start();
include '../config/koneksi.php';

// Cek apakah user sudah login sebagai pelanggan
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pelanggan') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil ID pesanan dari URL
$id_penjualan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data pesanan
$query_penjualan = "SELECT p.*, pl.nama_lengkap, pl.email, pl.telepon 
                    FROM penjualan p 
                    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
                    WHERE p.id_penjualan = '$id_penjualan'";
$result_penjualan = mysqli_query($koneksi, $query_penjualan);
$pesanan = mysqli_fetch_assoc($result_penjualan);

if (!$pesanan) {
    die("Pesanan tidak ditemukan");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Moods Strap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full text-center">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <!-- Success Icon -->
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-green-500 text-3xl"></i>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Pesanan Berhasil!</h1>
                <p class="text-gray-600 mb-6">Terima kasih telah berbelanja di Moods Strap. Pesanan Anda sedang diproses.</p>
                
                <!-- Order Details -->
                <div class="bg-gray-50 rounded-xl p-6 mb-6 text-left">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">No. Pesanan</span>
                        <span class="font-semibold">#<?php echo str_pad($pesanan['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Total Pembayaran</span>
                        <span class="font-bold text-lg pink-text">Rp <?php echo number_format($pesanan['total'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Status</span>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                            Menunggu Pembayaran
                        </span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="space-y-3">
                    <a href="pesanan.php" 
                       class="w-full gradient-bg text-white font-bold py-3 px-6 rounded-xl hover:shadow-lg transition block">
                        Lihat Pesanan Saya
                    </a>
                    <a href="produk.php" 
                       class="w-full bg-gray-100 text-gray-700 font-semibold py-3 px-6 rounded-xl hover:bg-gray-200 transition block">
                        Lanjut Belanja
                    </a>
                </div>
            </div>
            
            <!-- Info Pembayaran -->
            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">
                    Silakan lakukan pembayaran dalam waktu 24 jam untuk menghindari pembatalan pesanan.
                </p>
            </div>
        </div>
    </div>
</body>
</html>