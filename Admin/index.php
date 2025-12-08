<?php
session_start();
include_once '../config/koneksi.php';

// 检查数据库连接
if (!$koneksi) {
    die("连接失败: " . mysqli_connect_error());
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: auth/login.php");
    exit;
}

// 启用错误报告用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Query total produk aktif
$queryProduk = mysqli_query($koneksi, "SELECT COUNT(*) AS total_produk FROM produk WHERE status='active'");
if (!$queryProduk) {
    die("查询产品数据失败: " . mysqli_error($koneksi));
}
$totalProduk = mysqli_fetch_assoc($queryProduk)['total_produk'] ?? 0;

// Query total kategori
$queryKategori = mysqli_query($koneksi, "SELECT COUNT(*) AS total_kategori FROM kategori");
if (!$queryKategori) {
    die("查询类别数据失败: " . mysqli_error($koneksi));
}
$totalKategori = mysqli_fetch_assoc($queryKategori)['total_kategori'] ?? 0;

// Query total penjualan hari ini
$queryPenjualanHariIni = mysqli_query($koneksi, "SELECT COUNT(*) AS total_penjualan_hari_ini FROM penjualan WHERE DATE(tanggal) = CURDATE()");
if (!$queryPenjualanHariIni) {
    die("查询今日销售数据失败: " . mysqli_error($koneksi));
}
$totalPenjualanHariIni = mysqli_fetch_assoc($queryPenjualanHariIni)['total_penjualan_hari_ini'] ?? 0;

// Query total pendapatan hari ini
$queryPendapatanHariIni = mysqli_query($koneksi, "SELECT COALESCE(SUM(total), 0) AS total_pendapatan_hari_ini FROM penjualan WHERE DATE(tanggal) = CURDATE()");
if (!$queryPendapatanHariIni) {
    die("查询今日收入数据失败: " . mysqli_error($koneksi));
}
$pendapatanHariIni = mysqli_fetch_assoc($queryPendapatanHariIni)['total_pendapatan_hari_ini'] ?? 0;

// Query total pendapatan bulan ini
$queryPendapatanBulanIni = mysqli_query($koneksi, "SELECT COALESCE(SUM(total), 0) AS total_pendapatan_bulan_ini FROM penjualan WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
if (!$queryPendapatanBulanIni) {
    die("查询本月收入数据失败: " . mysqli_error($koneksi));
}
$pendapatanBulanIni = mysqli_fetch_assoc($queryPendapatanBulanIni)['total_pendapatan_bulan_ini'] ?? 0;

// Query total user
$queryUser = mysqli_query($koneksi, "SELECT COUNT(*) AS total_user FROM user");
if (!$queryUser) {
    die("查询用户数据失败: " . mysqli_error($koneksi));
}
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

// 调试信息
echo "<!-- 调试信息: 
总产品: $totalProduk
总类别: $totalKategori
今日销售: $totalPenjualanHariIni
今日收入: $pendapatanHariIni
本月收入: $pendapatanBulanIni
总用户: $totalUser
-->";
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Penjualan Aksesoris</title>
    
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
    
    <!-- Alpine.js untuk reaktivitas -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
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
        
        /* Card hover effects */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        /* Chart container responsive */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
        
        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            color: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        /* Neon glow effect */
        .neon-glow {
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
        }
        
        /* Gradient borders */
        .gradient-border {
            position: relative;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.9) 100%);
            padding: 1px;
            border-radius: 16px;
        }
        
        .gradient-border::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, #22c55e, #3b82f6, #8b5cf6);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
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

<body class="text-gray-100 min-h-screen" x-data="{ 
    darkMode: true,
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        document.documentElement.classList.toggle('dark');
    },
    activeTab: 'penjualan',
    setActiveTab(tab) {
        this.activeTab = tab;
    },
    loading: true
}" x-init="setTimeout(() => { loading = false }, 1000)">
    
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition.opacity class="fixed inset-0 bg-gray-950 z-50 flex items-center justify-center">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 border-4 border-primary-600 border-t-transparent rounded-full animate-spin neon-glow"></div>
            <p class="mt-4 text-gray-400">Memuat data...</p>
        </div>
    </div>

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 p-4 md:p-6 lg:p-8 transition-all duration-300">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Dashboard Admin</h1>
                <p class="text-gray-400">Sistem Penjualan Aksesoris</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Date Display -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-gray-300">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <span x-text="new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></span>
                </div>
                
                <!-- Revenue Info -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-green-300 bg-green-900/20 border border-green-800/30">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    <span>Pendapatan Hari Ini: Rp <?php echo number_format($pendapatanHariIni, 0, ',', '.'); ?></span>
                </div>
                
                <!-- Dark Mode Toggle -->
                <button @click="toggleDarkMode()" class="glass p-3 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 hover:scale-105">
                    <i class="fas fa-sun text-yellow-400"></i>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Produk Card -->
            <div class="stat-card card-hover gradient-border">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-primary-500/20 rounded-xl">
                        <i class="fas fa-box text-primary-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-primary-400 bg-primary-500/20 px-3 py-1 rounded-full border border-primary-500/30">
                        <i class="fas fa-check text-xs mr-1"></i>Aktif
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Total Produk</h3>
                <p class="text-2xl font-bold text-white">
                    <?php echo number_format($totalProduk, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Produk aktif di sistem</p>
                </div>
            </div>
            
            <!-- Total Kategori Card -->
            <div class="stat-card card-hover gradient-border">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-500/20 rounded-xl">
                        <i class="fas fa-tags text-blue-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-blue-400 bg-blue-500/20 px-3 py-1 rounded-full border border-blue-500/30">
                        <i class="fas fa-list text-xs mr-1"></i>Kategori
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Total Kategori</h3>
                <p class="text-2xl font-bold text-white">
                    <?php echo number_format($totalKategori, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Kategori produk</p>
                </div>
            </div>
            
            <!-- Penjualan Hari Ini Card -->
            <div class="stat-card card-hover gradient-border">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-green-500/20 rounded-xl">
                        <i class="fas fa-shopping-cart text-green-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-green-400 bg-green-500/20 px-3 py-1 rounded-full border border-green-500/30">
                        <i class="fas fa-calendar-day text-xs mr-1"></i>Hari Ini
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Penjualan Hari Ini</h3>
                <p class="text-2xl font-bold text-white">
                    <?php echo number_format($totalPenjualanHariIni, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Total transaksi</p>
                </div>
            </div>
            
            <!-- Pendapatan Bulan Ini Card -->
            <div class="stat-card card-hover gradient-border">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-purple-500/20 rounded-xl">
                        <i class="fas fa-money-bill-wave text-purple-400 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-purple-400 bg-purple-500/20 px-3 py-1 rounded-full border border-purple-500/30">
                        <i class="fas fa-chart-line text-xs mr-1"></i>Bulan Ini
                    </span>
                </div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Pendapatan Bulan Ini</h3>
                <p class="text-2xl font-bold text-white">
                    Rp <?php echo number_format($pendapatanBulanIni, 0, ',', '.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-400">Total pendapatan</p>
                </div>
            </div>
        </div>

        <!-- Revenue Overview -->
        <div class="blackscrim-card p-8 mb-8 neon-glow">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-white mb-2">Pendapatan Hari Ini</h2>
                    <p class="text-4xl font-bold text-green-400">Rp <?php echo number_format($pendapatanHariIni, 0, ',', '.'); ?></p>
                </div>
                <div class="w-20 h-20 rounded-full bg-green-500/20 flex items-center justify-center border border-green-500/30">
                    <i class="fas fa-chart-line text-green-400 text-3xl"></i>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-700/50">
                <div class="flex items-center text-green-400">
                    <i class="fas fa-shopping-cart mr-3 text-lg"></i>
                    <span class="text-lg"><?php echo number_format($totalPenjualanHariIni); ?> transaksi hari ini</span>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Penjualan 7 Hari Terakhir Chart -->
            <div class="blackscrim-card rounded-xl p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-xl font-semibold text-white">Penjualan 7 Hari Terakhir</h2>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">Data Real-time</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="penjualanHarianChart"></canvas>
                </div>
            </div>
            
            <!-- Penjualan per Kategori Chart -->
            <div class="blackscrim-card rounded-xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-white">Penjualan per Kategori</h2>
                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-500/20 text-purple-400 border border-purple-500/30">Analisis</span>
                </div>
                <div class="chart-container">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Produk Terlaris -->
        <div class="blackscrim-card rounded-xl p-6 mb-8">
            <h2 class="text-xl font-semibold text-white mb-6">5 Produk Terlaris</h2>
            <div class="overflow-x-auto dark-table rounded-lg">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left py-4 px-6 text-gray-300 font-semibold">Produk</th>
                            <th class="text-left py-4 px-6 text-gray-300 font-semibold">Terjual</th>
                            <th class="text-left py-4 px-6 text-gray-300 font-semibold">Harga</th>
                            <th class="text-left py-4 px-6 text-gray-300 font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produkTerlaris)): ?>
                            <?php foreach ($produkTerlaris as $index => $produk): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-gray-900/30' : ''; ?>">
                                    <td class="py-4 px-6">
                                        <div class="font-medium text-white"><?php echo htmlspecialchars($produk['nama_produk']); ?></div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300"><?php echo number_format($produk['total_terjual'], 0, ',', '.'); ?> pcs</td>
                                    <td class="py-4 px-6 text-gray-300">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-6 font-semibold text-green-400">Rp <?php echo number_format($produk['total_terjual'] * $produk['harga'], 0, ',', '.'); ?></td>
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
        <div class="blackscrim-card rounded-xl p-6 mb-8">
            <h2 class="text-xl font-semibold text-white mb-6">Aksi Cepat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Kelola Produk -->
                <a href="produk.php" class="group flex items-center p-5 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-primary-500/30 hover:scale-105">
                    <div class="p-3 bg-primary-500/20 rounded-lg mr-4 group-hover:bg-primary-500/30 transition-colors border border-primary-500/30">
                        <i class="fas fa-box text-primary-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Kelola Produk</h3>
                        <p class="text-sm text-gray-400">Data produk aksesoris</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-primary-400 transition-colors transform group-hover:translate-x-1"></i>
                </a>
                
                <!-- Data Penjualan -->
                <a href="penjualan.php" class="group flex items-center p-5 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-blue-500/30 hover:scale-105">
                    <div class="p-3 bg-blue-500/20 rounded-lg mr-4 group-hover:bg-blue-500/30 transition-colors border border-blue-500/30">
                        <i class="fas fa-shopping-cart text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Data Penjualan</h3>
                        <p class="text-sm text-gray-400">Riwayat transaksi</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-400 transition-colors transform group-hover:translate-x-1"></i>
                </a>
                
                <!-- Kelola Kategori -->
                <a href="kategori.php" class="group flex items-center p-5 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-purple-500/30 hover:scale-105">
                    <div class="p-3 bg-purple-500/20 rounded-lg mr-4 group-hover:bg-purple-500/30 transition-colors border border-purple-500/30">
                        <i class="fas fa-tags text-purple-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Kelola Kategori</h3>
                        <p class="text-sm text-gray-400">Kategori produk</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-purple-400 transition-colors transform group-hover:translate-x-1"></i>
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
                        hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                color: '#94a3b8'
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
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#f1f5f9',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Penjualan: ' + context.raw + ' transaksi';
                                }
                            }
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
                        borderColor: 'rgba(15, 23, 42, 0.8)',
                        borderWidth: 3,
                        hoverOffset: 15,
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                color: '#94a3b8',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#f1f5f9',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} penjualan (${percentage}%)`;
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