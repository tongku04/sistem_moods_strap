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

// Query untuk mendapatkan data produk
$queryProduk = mysqli_query($koneksi, 
    "SELECT p.*, k.nama_kategori 
     FROM produk p 
     LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
     ORDER BY p.id_produk DESC");

$produk = [];
if ($queryProduk) {
    while ($row = mysqli_fetch_assoc($queryProduk)) {
        $produk[] = $row;
    }
}

// Query untuk statistik produk
$queryTotalProduk = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM produk");
$totalProduk = mysqli_fetch_assoc($queryTotalProduk)['total'] ?? 0;

$queryProdukAktif = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM produk WHERE status='active'");
$produkAktif = mysqli_fetch_assoc($queryProdukAktif)['total'] ?? 0;

$queryProdukNonAktif = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM produk WHERE status='inactive'");
$produkNonAktif = mysqli_fetch_assoc($queryProdukNonAktif)['total'] ?? 0;

$queryTotalStok = mysqli_query($koneksi, "SELECT COALESCE(SUM(stok), 0) AS total FROM produk WHERE status='active'");
$totalStokData = mysqli_fetch_assoc($queryTotalStok);
$totalStok = $totalStokData['total'] ?? 0;

// Query untuk produk terlaris
$queryProdukTerlaris = mysqli_query($koneksi, 
    "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual, p.harga, p.stok
     FROM detail_penjualan dp 
     JOIN produk p ON dp.id_produk = p.id_produk 
     GROUP BY dp.id_produk 
     ORDER BY total_terjual DESC 
     LIMIT 10");

$produkTerlaris = [];
if ($queryProdukTerlaris) {
    while ($row = mysqli_fetch_assoc($queryProdukTerlaris)) {
        $produkTerlaris[] = $row;
    }
}

// Query untuk stok menipis (kurang dari 10)
$queryStokMenipis = mysqli_query($koneksi, 
    "SELECT nama_produk, stok, harga 
     FROM produk 
     WHERE stok < 10 AND status='active' 
     ORDER BY stok ASC");

$stokMenipis = [];
if ($queryStokMenipis) {
    while ($row = mysqli_fetch_assoc($queryStokMenipis)) {
        $stokMenipis[] = $row;
    }
}

// Query untuk distribusi produk per kategori - DIPERBAIKI
$queryKategoriDistribusi = mysqli_query($koneksi,
    "SELECT k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
     FROM kategori k
     LEFT JOIN produk p ON k.id_kategori = p.id_kategori
     GROUP BY k.id_kategori, k.nama_kategori
     ORDER BY jumlah_produk DESC");

$kategoriDistribusi = [];
$kategoriLabels = [];
$kategoriData = [];
$warnaKategori = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#84cc16', '#f97316'];

if ($queryKategoriDistribusi && mysqli_num_rows($queryKategoriDistribusi) > 0) {
    $index = 0;
    while ($row = mysqli_fetch_assoc($queryKategoriDistribusi)) {
        $kategoriDistribusi[] = $row;
        $kategoriLabels[] = $row['nama_kategori'] ?: 'Tidak Berkategori';
        $kategoriData[] = (int)$row['jumlah_produk'];
        $index++;
    }
} else {
    // Data default jika tidak ada kategori
    $kategoriLabels = ['Belum Ada Kategori'];
    $kategoriData = [0];
}

// Data untuk chart status produk - DIPERBAIKI
$statusLabels = ['Aktif', 'Non-Aktif'];
$statusData = [(int)$produkAktif, (int)$produkNonAktif];
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Produk - Sistem Penjualan Aksesoris</title>
    
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
        
        /* Chart container responsive */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Status badges */
        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .stok-low {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .stok-out {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .blackscrim-card {
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Laporan Produk</h1>
                <p class="text-gray-400">Manajemen dan Analisis Data Produk</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Date Display -->
                <div class="glass px-4 py-2 rounded-lg text-sm text-gray-300">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>
                
                <!-- Print Button -->
                <button onclick="window.print()" class="glass px-4 py-2 rounded-lg text-sm text-blue-300 bg-blue-900/20 border border-blue-800/30 hover:bg-blue-800/30 transition-colors no-print">
                    <i class="fas fa-print mr-2"></i>
                    <span>Cetak Laporan</span>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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

            <!-- Produk Aktif -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 border border-green-500/30">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Produk Aktif</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($produkAktif, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Produk Non-Aktif -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mr-4 border border-red-500/30">
                        <i class="fas fa-times-circle text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Produk Non-Aktif</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($produkNonAktif, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Stok -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4 border border-blue-500/30">
                        <i class="fas fa-cubes text-blue-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Stok</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalStok, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Distribusi Produk per Kategori -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Distribusi Produk per Kategori</h2>
                <div class="chart-container">
                    <canvas id="kategoriChart"></canvas>
                </div>
                <?php if (empty($kategoriDistribusi)): ?>
                    <div class="text-center py-4 text-gray-400">
                        <i class="fas fa-folder-open text-2xl mb-2"></i>
                        <p>Belum ada data kategori</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Status Produk -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Status Produk</h2>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Produk Terlaris & Stok Menipis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Produk Terlaris -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">10 Produk Terlaris</h2>
                <div class="space-y-4">
                    <?php if (!empty($produkTerlaris)): ?>
                        <?php foreach ($produkTerlaris as $index => $produk): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-primary-500/20 rounded-full flex items-center justify-center mr-3 border border-primary-500/30">
                                        <span class="text-primary-400 font-bold text-sm"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($produk['nama_produk']); ?></p>
                                        <p class="text-gray-400 text-sm">Stok: <?php echo number_format($produk['stok'], 0, ',', '.'); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-green-400 font-semibold"><?php echo number_format($produk['total_terjual'], 0, ',', '.'); ?> terjual</p>
                                    <p class="text-gray-400 text-sm">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-chart-line text-3xl mb-3"></i>
                            <p>Belum ada data penjualan produk</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Stok Menipis -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Stok Menipis <span class="text-yellow-400">(< 10)</span></h2>
                <div class="space-y-4">
                    <?php if (!empty($stokMenipis)): ?>
                        <?php foreach ($stokMenipis as $produk): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg border-l-4 border-yellow-500">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-yellow-500/20 rounded-full flex items-center justify-center mr-3 border border-yellow-500/30">
                                        <i class="fas fa-exclamation-triangle text-yellow-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($produk['nama_produk']); ?></p>
                                        <p class="text-gray-400 text-sm">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-yellow-400 font-semibold"><?php echo number_format($produk['stok'], 0, ',', '.'); ?> stok</p>
                                    <p class="text-gray-400 text-sm">Segera restok!</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-check-circle text-3xl mb-3"></i>
                            <p>Semua stok produk mencukupi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Data Produk Lengkap -->
        <div class="blackscrim-card rounded-xl p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h2 class="text-xl font-semibold text-white">Data Semua Produk</h2>
                <div class="flex gap-2 mt-4 md:mt-0 no-print">
                    <button onclick="exportToExcel()" class="glass px-4 py-2 rounded-lg text-sm text-green-300 bg-green-900/20 border border-green-800/30 hover:bg-green-800/30 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>
                        <span>Export Excel</span>
                    </button>
                    <button onclick="exportToPDF()" class="glass px-4 py-2 rounded-lg text-sm text-red-300 bg-red-900/20 border border-red-800/30 hover:bg-red-800/30 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>
                        <span>Export PDF</span>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-700/50">
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">#</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Produk</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Kategori</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Harga</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Stok</th>
                            <th class="text-left py-3 px-4 text-gray-400 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produk)): ?>
                            <?php foreach ($produk as $index => $item): ?>
                                <tr class="border-b border-gray-700/30 hover:bg-white/5 transition duration-200">
                                    <td class="py-4 px-4 text-gray-300"><?php echo $index + 1; ?></td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-white"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                        <?php if ($item['deskripsi']): ?>
                                            <div class="text-sm text-gray-400 mt-1"><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 50)); ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-gray-300"><?php echo htmlspecialchars($item['nama_kategori'] ?? 'Tidak Berkategori'); ?></td>
                                    <td class="py-4 px-4 text-gray-300">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-4">
                                        <?php if ($item['stok'] == 0): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium stok-out">Habis</span>
                                        <?php elseif ($item['stok'] < 10): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium stok-low"><?php echo number_format($item['stok'], 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-300"><?php echo number_format($item['stok'], 0, ',', '.'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($item['status'] == 'active'): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium status-active">Aktif</span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium status-inactive">Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-8 px-6 text-center text-gray-400">
                                    <i class="fas fa-box text-3xl mb-3"></i>
                                    <p>Belum ada data produk</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="blackscrim-card rounded-xl p-6 no-print">
            <h2 class="text-xl font-semibold text-white mb-6">Aksi Cepat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Dashboard -->
                <a href="dashboard.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-blue-500/30">
                    <div class="p-3 bg-blue-500/20 rounded-lg mr-4 group-hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-chart-bar text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Dashboard</h3>
                        <p class="text-sm text-gray-400">Overview sistem</p>
                    </div>
                </a>
                
                <!-- Kelola Produk -->
                <a href="../admin/produk.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-green-500/30">
                    <div class="p-3 bg-green-500/20 rounded-lg mr-4 group-hover:bg-green-500/30 transition-colors">
                        <i class="fas fa-box text-green-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Kelola Produk</h3>
                        <p class="text-sm text-gray-400">Data produk</p>
                    </div>
                </a>
                
                <!-- Laporan Penjualan -->
                <a href="../admin/laporan.php" class="group flex items-center p-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700 hover:border-purple-500/30">
                    <div class="p-3 bg-purple-500/20 rounded-lg mr-4 group-hover:bg-purple-500/30 transition-colors">
                        <i class="fas fa-chart-pie text-purple-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Laporan Penjualan</h3>
                        <p class="text-sm text-gray-400">Analisis penjualan</p>
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
            
            // Kategori Distribution Chart
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
            
            // Status Produk Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($statusLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($statusData); ?>,
                        backgroundColor: ['#22c55e', '#ef4444'],
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
        });

        // Export functions (placeholder)
        function exportToExcel() {
            alert('Fitur export Excel akan segera tersedia!');
        }

        function exportToPDF() {
            alert('Fitur export PDF akan segera tersedia!');
        }
    </script>
</body>
</html>