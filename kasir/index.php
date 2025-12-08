<?php
session_start();
include_once '../config/koneksi.php';

// Cek koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login dan memiliki role kasir
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kasir') {
    header("Location: ../auth/login.php");
    exit;
}

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Query statistik untuk kasir
$id_user = $_SESSION['user']['id_user'];

// Total transaksi hari ini oleh kasir ini
$queryTransaksiHariIni = mysqli_query($koneksi, 
    "SELECT COUNT(*) as total_transaksi_hari_ini 
     FROM penjualan 
     WHERE DATE(tanggal) = CURDATE() AND id_user = '$id_user'");
$totalTransaksiHariIni = mysqli_fetch_assoc($queryTransaksiHariIni)['total_transaksi_hari_ini'] ?? 0;

// Total pendapatan hari ini oleh kasir ini
$queryPendapatanHariIni = mysqli_query($koneksi, 
    "SELECT COALESCE(SUM(total), 0) as total_pendapatan_hari_ini 
     FROM penjualan 
     WHERE DATE(tanggal) = CURDATE() AND id_user = '$id_user'");
$pendapatanHariIni = mysqli_fetch_assoc($queryPendapatanHariIni)['total_pendapatan_hari_ini'] ?? 0;

// Total produk terjual hari ini oleh kasir ini
$queryProdukTerjualHariIni = mysqli_query($koneksi, 
    "SELECT COALESCE(SUM(dp.jumlah), 0) as total_produk_terjual_hari_ini
     FROM detail_penjualan dp
     JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
     WHERE DATE(p.tanggal) = CURDATE() AND p.id_user = '$id_user'");
$produkTerjualHariIni = mysqli_fetch_assoc($queryProdukTerjualHariIni)['total_produk_terjual_hari_ini'] ?? 0;

// Produk dengan stok menipis (<= 10)
$queryStokMenipis = mysqli_query($koneksi, 
    "SELECT COUNT(*) as total_stok_menipis 
     FROM produk 
     WHERE stok > 0 AND stok <= 10 AND status = 'active'");
$totalStokMenipis = mysqli_fetch_assoc($queryStokMenipis)['total_stok_menipis'] ?? 0;

// Produk dengan stok habis
$queryStokHabis = mysqli_query($koneksi, 
    "SELECT COUNT(*) as total_stok_habis 
     FROM produk 
     WHERE stok = 0 AND status = 'active'");
$totalStokHabis = mysqli_fetch_assoc($queryStokHabis)['total_stok_habis'] ?? 0;

// Data penjualan 7 hari terakhir untuk kasir ini
$labelsHarian = [];
$dataHarian = [];
$dataPendapatanHarian = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('d M', strtotime("-$i days"));
    $labelsHarian[] = $date;
    
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $queryPenjualanHarian = mysqli_query($koneksi, 
        "SELECT COUNT(*) as total_penjualan, COALESCE(SUM(total), 0) as total_pendapatan 
         FROM penjualan 
         WHERE DATE(tanggal) = '$tanggal' AND id_user = '$id_user'");
    
    if ($queryPenjualanHarian) {
        $data = mysqli_fetch_assoc($queryPenjualanHarian);
        $dataHarian[] = $data['total_penjualan'] ?? 0;
        $dataPendapatanHarian[] = $data['total_pendapatan'] ?? 0;
    } else {
        $dataHarian[] = 0;
        $dataPendapatanHarian[] = 0;
    }
}

// Produk terlaris bulan ini untuk kasir ini
$queryProdukTerlaris = mysqli_query($koneksi, 
    "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual, p.harga
     FROM detail_penjualan dp 
     JOIN produk p ON dp.id_produk = p.id_produk 
     JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
     WHERE MONTH(pj.tanggal) = MONTH(CURDATE()) 
     AND YEAR(pj.tanggal) = YEAR(CURDATE())
     AND pj.id_user = '$id_user'
     GROUP BY dp.id_produk 
     ORDER BY total_terjual DESC 
     LIMIT 5");

$produkTerlaris = [];
if ($queryProdukTerlaris) {
    while ($produk = mysqli_fetch_assoc($queryProdukTerlaris)) {
        $produkTerlaris[] = $produk;
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - Sistem Penjualan Aksesoris</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        // Konfigurasi Tailwind
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #22c55e 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Blackscrim card effect */
        .blackscrim-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
            transition: all 0.3s ease;
        }
        
        .blackscrim-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Table styles */
        .dark-table {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .dark-table thead {
            background: rgba(30, 41, 59, 0.9);
        }
        
        .dark-table th {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .dark-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .dark-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="text-gray-100 min-h-screen">
    
    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 p-6 transition-all duration-300">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Dashboard Kasir</h1>
                <p class="text-gray-400">Selamat datang, <?php echo $_SESSION['user']['username']; ?>! 👋</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Date Display -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-gray-300">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <span id="currentDate"></span>
                </div>
                
                <!-- Revenue Info -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-green-300 bg-green-900/20 border border-green-800/30">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    <span>Pendapatan Hari Ini: Rp <?php echo number_format($pendapatanHariIni, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Transaksi Hari Ini -->
            <div class="blackscrim-card p-6 animate-slide-up">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-500/20 rounded-xl">
                        <i class="fas fa-shopping-cart text-blue-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-blue-400 bg-blue-500/20 px-3 py-1 rounded-full border border-blue-500/30">
                        Hari Ini
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Transaksi Hari Ini</h3>
                <p class="text-2xl font-bold text-white">
                    <?php echo number_format($totalTransaksiHariIni, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Total transaksi yang Anda proses</p>
                </div>
            </div>
            
            <!-- Pendapatan Hari Ini -->
            <div class="blackscrim-card p-6 animate-slide-up">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-green-500/20 rounded-xl">
                        <i class="fas fa-money-bill-wave text-green-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-green-400 bg-green-500/20 px-3 py-1 rounded-full border border-green-500/30">
                        Pendapatan
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Pendapatan Hari Ini</h3>
                <p class="text-2xl font-bold text-white">
                    Rp <?php echo number_format($pendapatanHariIni, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Total pendapatan dari transaksi Anda</p>
                </div>
            </div>
            
            <!-- Produk Terjual -->
            <div class="blackscrim-card p-6 animate-slide-up">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-purple-500/20 rounded-xl">
                        <i class="fas fa-box text-purple-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-purple-400 bg-purple-500/20 px-3 py-1 rounded-full border border-purple-500/30">
                        Terjual
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Produk Terjual</h3>
                <p class="text-2xl font-bold text-white">
                    <?php echo number_format($produkTerjualHariIni, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Total produk yang terjual</p>
                </div>
            </div>
            
            <!-- Stok Menipis -->
            <div class="blackscrim-card p-6 animate-slide-up">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-yellow-500/20 rounded-xl">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-yellow-400 bg-yellow-500/20 px-3 py-1 rounded-full border border-yellow-500/30">
                        Perhatian
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Menipis</h3>
                <p class="text-2xl font-bold text-white">
                    <?php echo number_format($totalStokMenipis, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Produk perlu restock</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="blackscrim-card rounded-xl p-6 mb-8">
            <h2 class="text-xl font-semibold text-white mb-6">Aksi Cepat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Transaksi Baru -->
                <a href="transaksi_baru.php" class="group flex flex-col items-center p-5 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-600/20 hover:from-blue-500/30 hover:to-blue-600/30 transition-all duration-300 border border-blue-500/30 hover:scale-105">
                    <div class="p-3 bg-blue-500/30 rounded-lg mb-3 group-hover:bg-blue-500/40 transition-colors">
                        <i class="fas fa-cash-register text-blue-400 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-white text-center mb-1">Transaksi Baru</h3>
                    <p class="text-gray-400 text-sm text-center">Buat transaksi penjualan baru</p>
                </a>
                
                <!-- Riwayat Transaksi -->
                <a href="penjualan.php" class="group flex flex-col items-center p-5 rounded-xl bg-gradient-to-br from-green-500/20 to-green-600/20 hover:from-green-500/30 hover:to-green-600/30 transition-all duration-300 border border-green-500/30 hover:scale-105">
                    <div class="p-3 bg-green-500/30 rounded-lg mb-3 group-hover:bg-green-500/40 transition-colors">
                        <i class="fas fa-history text-green-400 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-white text-center mb-1">Riwayat Transaksi</h3>
                    <p class="text-gray-400 text-sm text-center">Lihat riwayat transaksi</p>
                </a>
                
                <!-- Daftar Produk -->
                <a href="produk.php" class="group flex flex-col items-center p-5 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-600/20 hover:from-purple-500/30 hover:to-purple-600/30 transition-all duration-300 border border-purple-500/30 hover:scale-105">
                    <div class="p-3 bg-purple-500/30 rounded-lg mb-3 group-hover:bg-purple-500/40 transition-colors">
                        <i class="fas fa-boxes text-purple-400 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-white text-center mb-1">Daftar Produk</h3>
                    <p class="text-gray-400 text-sm text-center">Lihat katalog produk</p>
                </a>
                
                <!-- Stok Produk -->
                <a href="laporan_stok.php" class="group flex flex-col items-center p-5 rounded-xl bg-gradient-to-br from-yellow-500/20 to-yellow-600/20 hover:from-yellow-500/30 hover:to-yellow-600/30 transition-all duration-300 border border-yellow-500/30 hover:scale-105">
                    <div class="p-3 bg-yellow-500/30 rounded-lg mb-3 group-hover:bg-yellow-500/40 transition-colors">
                        <i class="fas fa-chart-bar text-yellow-400 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-white text-center mb-1">Laporan Stok</h3>
                    <p class="text-gray-400 text-sm text-center">Monitor stok produk</p>
                </a>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Penjualan 7 Hari Terakhir -->
            <div class="blackscrim-card rounded-xl p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-xl font-semibold text-white">Penjualan 7 Hari Terakhir</h2>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">Data Anda</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="penjualanHarianChart"></canvas>
                </div>
            </div>
            
            <!-- Produk Terlaris -->
            <div class="blackscrim-card rounded-xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-white">5 Produk Terlaris</h2>
                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-500/20 text-purple-400 border border-purple-500/30">Bulan Ini</span>
                </div>
                
                <?php if (!empty($produkTerlaris)): ?>
                    <div class="space-y-4">
                        <?php foreach ($produkTerlaris as $index => $produk): ?>
                            <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 border border-purple-500/30">
                                        <span class="text-purple-400 font-bold text-sm">#<?php echo $index + 1; ?></span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-white text-sm"><?php echo htmlspecialchars($produk['nama_produk']); ?></h4>
                                        <p class="text-gray-400 text-xs">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-green-400 font-semibold text-sm"><?php echo number_format($produk['total_terjual'], 0, ',', '.'); ?> terjual</p>
                                    <p class="text-gray-400 text-xs">Rp <?php echo number_format($produk['total_terjual'] * $produk['harga'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-chart-bar text-gray-500 text-4xl mb-3"></i>
                        <p class="text-gray-400">Belum ada data penjualan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Stok Menipis -->
        <?php if ($totalStokMenipis > 0 || $totalStokHabis > 0): ?>
        <div class="blackscrim-card rounded-xl p-6 mb-8 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-500/20 rounded-lg mr-4">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1">Perhatian Stok Produk</h3>
                        <p class="text-gray-300 text-sm">
                            <?php if ($totalStokHabis > 0): ?>
                                <span class="text-red-400"><?php echo $totalStokHabis; ?> produk stok habis</span>
                                <?php if ($totalStokMenipis > 0) echo ' dan '; ?>
                            <?php endif; ?>
                            <?php if ($totalStokMenipis > 0): ?>
                                <span class="text-yellow-400"><?php echo $totalStokMenipis; ?> produk stok menipis</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <a href="laporan_stok.php" class="bg-yellow-500/20 text-yellow-400 px-4 py-2 rounded-lg hover:bg-yellow-500/30 transition duration-200 border border-yellow-500/30 text-sm">
                    Lihat Detail
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="blackscrim-card rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-6">Aktivitas Terkini</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-500/20 rounded-lg mr-4">
                            <i class="fas fa-shopping-cart text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">Transaksi Baru</h4>
                            <p class="text-gray-400 text-xs">Buat transaksi penjualan baru</p>
                        </div>
                    </div>
                    <a href="transaksi_baru.php" class="text-blue-400 hover:text-blue-300 text-sm">
                        Mulai <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-500/20 rounded-lg mr-4">
                            <i class="fas fa-history text-green-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">Riwayat Transaksi</h4>
                            <p class="text-gray-400 text-xs">Lihat riwayat transaksi hari ini</p>
                        </div>
                    </div>
                    <a href="penjualan.php" class="text-green-400 hover:text-green-300 text-sm">
                        Lihat <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-500/20 rounded-lg mr-4">
                            <i class="fas fa-box text-purple-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-sm">Cek Stok Produk</h4>
                            <p class="text-gray-400 text-xs">Periksa ketersediaan stok</p>
                        </div>
                    </div>
                    <a href="produk.php" class="text-purple-400 hover:text-purple-300 text-sm">
                        Cek <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Set current date
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('id-ID', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });

        // Chart configuration for dark theme
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        
        // Penjualan Harian Chart
        const penjualanHarianCtx = document.getElementById('penjualanHarianChart').getContext('2d');
        const penjualanHarianChart = new Chart(penjualanHarianCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labelsHarian); ?>,
                datasets: [
                    {
                        label: 'Jumlah Transaksi',
                        data: <?php echo json_encode($dataHarian); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Pendapatan (Rp)',
                        data: <?php echo json_encode($dataPendapatanHarian); ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.3)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 2,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#f1f5f9',
                        bodyColor: '#f1f5f9',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        padding: 12
                    }
                }
            }
        });

        // Auto refresh charts on window resize
        window.addEventListener('resize', function() {
            penjualanHarianChart.resize();
        });
    </script>
</body>
</html>