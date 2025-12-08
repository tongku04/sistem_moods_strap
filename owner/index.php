<?php
session_start();
include_once '../config/koneksi.php';

// Cek koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login dan memiliki role owner
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit;
}

// Query total produk aktif
$queryProduk = mysqli_query($koneksi, "SELECT COUNT(*) AS total_produk FROM produk WHERE status='active'");
$totalProduk = mysqli_fetch_assoc($queryProduk)['total_produk'] ?? 0;

// Query total kategori
$queryKategori = mysqli_query($koneksi, "SELECT COUNT(*) AS total_kategori FROM kategori");
$totalKategori = mysqli_fetch_assoc($queryKategori)['total_kategori'] ?? 0;

// Query total penjualan hari ini
$queryPenjualanHariIni = mysqli_query($koneksi, "SELECT COUNT(*) AS total_penjualan_hari_ini FROM penjualan WHERE DATE(tanggal) = CURDATE()");
$totalPenjualanHariIni = mysqli_fetch_assoc($queryPenjualanHariIni)['total_penjualan_hari_ini'] ?? 0;

// Query total pendapatan hari ini
$queryPendapatanHariIni = mysqli_query($koneksi, "SELECT COALESCE(SUM(total), 0) AS total_pendapatan_hari_ini FROM penjualan WHERE DATE(tanggal) = CURDATE()");
$pendapatanHariIni = mysqli_fetch_assoc($queryPendapatanHariIni)['total_pendapatan_hari_ini'] ?? 0;

// Query total pendapatan bulan ini
$queryPendapatanBulanIni = mysqli_query($koneksi, "SELECT COALESCE(SUM(total), 0) AS total_pendapatan_bulan_ini FROM penjualan WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
$pendapatanBulanIni = mysqli_fetch_assoc($queryPendapatanBulanIni)['total_pendapatan_bulan_ini'] ?? 0;

// Query total pendapatan tahun ini
$queryPendapatanTahunIni = mysqli_query($koneksi, "SELECT COALESCE(SUM(total), 0) AS total_pendapatan_tahun_ini FROM penjualan WHERE YEAR(tanggal) = YEAR(CURDATE())");
$pendapatanTahunIni = mysqli_fetch_assoc($queryPendapatanTahunIni)['total_pendapatan_tahun_ini'] ?? 0;

// Query total user
$queryUser = mysqli_query($koneksi, "SELECT COUNT(*) AS total_user FROM user");
$totalUser = mysqli_fetch_assoc($queryUser)['total_user'] ?? 0;

// Query produk terlaris
$queryProdukTerlaris = mysqli_query($koneksi, 
    "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual, p.harga
     FROM detail_penjualan dp 
     JOIN produk p ON dp.id_produk = p.id_produk 
     GROUP BY dp.id_produk 
     ORDER BY total_terjual DESC 
     LIMIT 5");

$produkTerlaris = [];
if ($queryProdukTerlaris) {
    while ($produk = mysqli_fetch_assoc($queryProdukTerlaris)) {
        $produkTerlaris[] = $produk;
    }
}

// Data penjualan 7 hari terakhir
$labelsHarian = [];
$dataHarian = [];
$dataPendapatanHarian = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('d M', strtotime("-$i days"));
    $labelsHarian[] = $date;
    
    // Query penjualan per hari
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $queryPenjualanHarian = mysqli_query($koneksi, 
        "SELECT COUNT(*) AS total_penjualan, COALESCE(SUM(total), 0) AS total_pendapatan 
         FROM penjualan 
         WHERE DATE(tanggal) = '$tanggal'");
    
    if ($queryPenjualanHarian) {
        $data = mysqli_fetch_assoc($queryPenjualanHarian);
        $dataHarian[] = $data['total_penjualan'] ?? 0;
        $dataPendapatanHarian[] = $data['total_pendapatan'] ?? 0;
    } else {
        $dataHarian[] = 0;
        $dataPendapatanHarian[] = 0;
    }
}

// Data penjualan per kategori
$queryKategoriPenjualan = mysqli_query($koneksi,
    "SELECT k.nama_kategori, COUNT(dp.id_detail) AS total_penjualan
     FROM kategori k
     LEFT JOIN produk p ON k.id_kategori = p.id_kategori
     LEFT JOIN detail_penjualan dp ON p.id_produk = dp.id_produk
     GROUP BY k.id_kategori
     ORDER BY total_penjualan DESC");

$kategoriLabels = [];
$kategoriData = [];
$warnaKategori = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#84cc16', '#f97316'];

if ($queryKategoriPenjualan) {
    $index = 0;
    while ($kategori = mysqli_fetch_assoc($queryKategoriPenjualan)) {
        $kategoriLabels[] = $kategori['nama_kategori'];
        $kategoriData[] = $kategori['total_penjualan'];
        $index++;
    }
}

// Data penjualan per bulan (untuk chart tahunan)
$labelsBulanan = [];
$dataBulanan = [];
for ($i = 1; $i <= 12; $i++) {
    $labelsBulanan[] = date('M', mktime(0, 0, 0, $i, 1));
    
    $queryPenjualanBulanan = mysqli_query($koneksi,
        "SELECT COALESCE(SUM(total), 0) AS total_pendapatan 
         FROM penjualan 
         WHERE MONTH(tanggal) = $i AND YEAR(tanggal) = YEAR(CURDATE())");
    
    if ($queryPenjualanBulanan) {
        $data = mysqli_fetch_assoc($queryPenjualanBulanan);
        $dataBulanan[] = $data['total_pendapatan'] ?? 0;
    } else {
        $dataBulanan[] = 0;
    }
}

// Data performa kasir
$queryPerformaKasir = mysqli_query($koneksi,
    "SELECT u.username, 
            COUNT(p.id_penjualan) AS total_transaksi,
            COALESCE(SUM(p.total), 0) AS total_penjualan
     FROM user u
     LEFT JOIN penjualan p ON u.id_user = p.id_user 
     WHERE u.role = 'kasir'
     GROUP BY u.id_user
     ORDER BY total_penjualan DESC");

$performaKasir = [];
if ($queryPerformaKasir) {
    while ($kasir = mysqli_fetch_assoc($queryPerformaKasir)) {
        $performaKasir[] = $kasir;
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - Sistem Penjualan Aksesoris</title>
    
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
                        'pulse-slow': 'pulse 3s infinite',
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
        }
        
        /* Form styles */
        .form-input {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
            background: rgba(30, 41, 59, 0.9);
        }
        
        .form-input::placeholder {
            color: #64748b;
        }
        
        .form-label {
            color: #e2e8f0;
            font-weight: 500;
        }
        
        /* Chart container responsive */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body class="text-gray-100 min-h-screen">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Dashboard Owner</h1>
                <p class="text-gray-400">Overview Sistem Penjualan Aksesoris</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Date Display -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-gray-300">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>
                
                <!-- Revenue Info -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-green-300 bg-green-900/20 border border-green-800/30">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    <span>Pendapatan Hari Ini: Rp <?php echo number_format($pendapatanHariIni, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Pendapatan Tahun Ini -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 border border-green-500/30">
                        <i class="fas fa-money-bill-wave text-green-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Pendapatan Tahun Ini</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($pendapatanTahunIni, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Pendapatan Bulan Ini -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4 border border-blue-500/30">
                        <i class="fas fa-chart-line text-blue-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Pendapatan Bulan Ini</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($pendapatanBulanIni, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Produk -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 border border-purple-500/30">
                        <i class="fas fa-box text-purple-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Produk</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalProduk, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total User -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-4 border border-yellow-500/30">
                        <i class="fas fa-users text-yellow-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total User</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalUser, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Penjualan 7 Hari Terakhir -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Penjualan 7 Hari Terakhir</h2>
                <div class="chart-container">
                    <canvas id="penjualanHarianChart"></canvas>
                </div>
            </div>
            
            <!-- Penjualan per Kategori -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Penjualan per Kategori</h2>
                <div class="chart-container">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Additional Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Pendapatan Tahunan -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Pendapatan Tahun <?php echo date('Y'); ?></h2>
                <div class="chart-container">
                    <canvas id="pendapatanTahunanChart"></canvas>
                </div>
            </div>
            
            <!-- Performa Kasir -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Performa Kasir</h2>
                <div class="space-y-4">
                    <?php if (!empty($performaKasir)): ?>
                        <?php foreach ($performaKasir as $kasir): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-primary-500/20 rounded-full flex items-center justify-center mr-3 border border-primary-500/30">
                                        <i class="fas fa-user text-primary-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($kasir['username']); ?></p>
                                        <p class="text-gray-400 text-sm"><?php echo $kasir['total_transaksi']; ?> transaksi</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-green-400 font-semibold">Rp <?php echo number_format($kasir['total_penjualan'], 0, ',', '.'); ?></p>
                                    <p class="text-gray-400 text-sm">Total penjualan</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-users text-3xl mb-3"></i>
                            <p>Belum ada data performa kasir</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Produk Terlaris -->
        <div class="blackscrim-card rounded-xl p-6 mb-8">
            <h2 class="text-xl font-semibold text-white mb-6">5 Produk Terlaris</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-700/50">
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Produk</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Terjual</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Harga</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produkTerlaris)): ?>
                            <?php foreach ($produkTerlaris as $index => $produk): ?>
                                <tr class="border-b border-gray-700/30 hover:bg-white/5 transition duration-200">
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-white"><?php echo htmlspecialchars($produk['nama_produk']); ?></div>
                                    </td>
                                    <td class="py-4 px-4 text-gray-300"><?php echo number_format($produk['total_terjual'], 0, ',', '.'); ?> pcs</td>
                                    <td class="py-4 px-4 text-gray-300">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-4 font-semibold text-green-400">Rp <?php echo number_format($produk['total_terjual'] * $produk['harga'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-8 px-6 text-center text-gray-400">Belum ada data penjualan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="blackscrim-card rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-6">Aksi Cepat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Laporan Penjualan -->
                <a href="../admin/laporan.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-blue-500/30">
                    <div class="p-3 bg-blue-500/20 rounded-lg mr-4 group-hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-chart-bar text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Laporan Penjualan</h3>
                        <p class="text-sm text-gray-400">Analisis data</p>
                    </div>
                </a>
                
                <!-- Kelola User -->
                <a href="../admin/user.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-green-500/30">
                    <div class="p-3 bg-green-500/20 rounded-lg mr-4 group-hover:bg-green-500/30 transition-colors">
                        <i class="fas fa-users text-green-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Kelola User</h3>
                        <p class="text-sm text-gray-400">Data pengguna</p>
                    </div>
                </a>
                
                <!-- Data Produk -->
                <a href="../admin/produk.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-purple-500/30">
                    <div class="p-3 bg-purple-500/20 rounded-lg mr-4 group-hover:bg-purple-500/30 transition-colors">
                        <i class="fas fa-box text-purple-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Data Produk</h3>
                        <p class="text-sm text-gray-400">Kelola produk</p>
                    </div>
                </a>
                
                <!-- Riwayat Transaksi -->
                <a href="../admin/penjualan.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-yellow-500/30">
                    <div class="p-3 bg-yellow-500/20 rounded-lg mr-4 group-hover:bg-yellow-500/30 transition-colors">
                        <i class="fas fa-shopping-cart text-yellow-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Riwayat Transaksi</h3>
                        <p class="text-sm text-gray-400">Data penjualan</p>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart configuration for dark theme
            Chart.defaults.color = '#94a3b8';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            
            // Penjualan Harian Chart
            const penjualanHarianCtx = document.getElementById('penjualanHarianChart').getContext('2d');
            const penjualanHarianChart = new Chart(penjualanHarianCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labelsHarian); ?>,
                    datasets: [{
                        label: 'Jumlah Penjualan',
                        data: <?php echo json_encode($dataHarian); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Kategori Chart
            const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
            const kategoriChart = new Chart(kategoriCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($kategoriLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($kategoriData); ?>,
                        backgroundColor: <?php echo json_encode(array_slice($warnaKategori, 0, count($kategoriLabels))); ?>,
                        borderWidth: 2,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Pendapatan Tahunan Chart
            const pendapatanTahunanCtx = document.getElementById('pendapatanTahunanChart').getContext('2d');
            const pendapatanTahunanChart = new Chart(pendapatanTahunanCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labelsBulanan); ?>,
                    datasets: [{
                        label: 'Pendapatan',
                        data: <?php echo json_encode($dataBulanan); ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>