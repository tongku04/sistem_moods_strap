<?php
session_start();
include '../config/koneksi.php';

// Cek koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal");
}

// Cek apakah user sudah login sebagai pelanggan
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pelanggan') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user']['id_user'];
$username = $_SESSION['user']['username'];

// Ambil data pelanggan
$query_pelanggan = "SELECT * FROM pelanggan WHERE id_user = '$user_id'";
$result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
$data_pelanggan = mysqli_fetch_assoc($result_pelanggan);

if (!$data_pelanggan) {
    die("Data pelanggan tidak ditemukan");
}

// Cek apakah ada parameter id pesanan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: pesanan.php');
    exit;
}

$id_penjualan = $_GET['id'];

// Ambil detail pesanan
$query_pesanan = "SELECT p.*, pl.nama_lengkap, pl.email, pl.telepon
                  FROM penjualan p
                  JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                  WHERE p.id_penjualan = '$id_penjualan' 
                  AND p.id_pelanggan = '{$data_pelanggan['id_pelanggan']}'";
$result_pesanan = mysqli_query($koneksi, $query_pesanan);
$pesanan = mysqli_fetch_assoc($result_pesanan);

if (!$pesanan) {
    die("Pesanan tidak ditemukan atau tidak ada akses");
}

// Format data
$tanggal_pesanan = date('d M Y H:i', strtotime($pesanan['tanggal']));
$total_harga = number_format($pesanan['total'], 0, ',', '.');
$no_pesanan = str_pad($pesanan['id_penjualan'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - Moods Strap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
        }
        
        .success-check {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            animation: scaleCheck 0.5s ease-in-out;
        }
        
        @keyframes scaleCheck {
            0% { transform: scale(0); }
            70% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Success Icon -->
                <div class="success-check flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-white text-4xl"></i>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Pembayaran Berhasil!</h1>
                <p class="text-gray-600 mb-8">Terima kasih telah melakukan pembayaran. Pesanan Anda akan segera diproses.</p>
                
                <!-- Order Details -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <div class="grid grid-cols-2 gap-4 text-left">
                        <div>
                            <p class="text-gray-600 text-sm">Nomor Pesanan</p>
                            <p class="font-bold text-lg">#<?php echo $no_pesanan; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Tanggal</p>
                            <p class="font-medium"><?php echo $tanggal_pesanan; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Total Pembayaran</p>
                            <p class="font-bold text-xl text-pink-600">Rp <?php echo $total_harga; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Status</p>
                            <p class="font-medium text-green-600">Pembayaran Diterima</p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="pesanan.php" class="flex-1 px-6 py-3 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition font-medium">
                        <i class="fas fa-shopping-bag mr-2"></i>Lihat Pesanan
                    </a>
                    <a href="index.php" class="flex-1 px-6 py-3 gradient-bg text-white rounded-xl hover:shadow-lg transition font-medium">
                        <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                    </a>
                </div>
                
                <!-- Information -->
                <div class="mt-8 p-4 bg-blue-50 rounded-lg text-left">
                    <h3 class="font-semibold text-blue-800 mb-2">Apa selanjutnya?</h3>
                    <ul class="text-blue-700 text-sm space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>Pesanan Anda akan diproses dalam 1-2 hari kerja</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>Anda akan menerima notifikasi ketika pesanan dikirim</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                            <span>Hubungi kami jika ada pertanyaan</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
mysqli_close($koneksi);
?>