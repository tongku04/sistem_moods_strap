<?php
session_start();
include_once '../config/koneksi.php';

// Cek koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login dan memiliki role admin/owner
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'owner')) {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$pesan_error = '';

// Default filter
$filter_tanggal_awal = date('Y-m-01'); // Awal bulan ini
$filter_tanggal_akhir = date('Y-m-d'); // Hari ini
$filter_kategori = '';
$filter_status = '';

// Ambil data kategori untuk filter
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = mysqli_query($koneksi, $query_kategori);
$kategori_list = [];
if ($result_kategori) {
    while ($row = mysqli_fetch_assoc($result_kategori)) {
        $kategori_list[] = $row;
    }
}

// Proses filter
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $filter_tanggal_awal = mysqli_real_escape_string($koneksi, $_POST['tanggal_awal']);
    $filter_tanggal_akhir = mysqli_real_escape_string($koneksi, $_POST['tanggal_akhir']);
    $filter_kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $filter_status = mysqli_real_escape_string($koneksi, $_POST['status']);
}

// Build query untuk laporan
$where_conditions = [];
$where_conditions[] = "DATE(p.tanggal) BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";

if (!empty($filter_kategori)) {
    $where_conditions[] = "pr.id_kategori = '$filter_kategori'";
}

if (!empty($filter_status)) {
    $where_conditions[] = "p.status_pembayaran = '$filter_status'";
}

$where_clause = implode(' AND ', $where_conditions);

// Query untuk data penjualan
$query_penjualan = "SELECT 
                    p.id_penjualan,
                    p.tanggal,
                    p.total,
                    p.bayar,
                    p.kembalian,
                    p.status_pembayaran,
                    u.username as kasir,
                    pr.nama_produk,
                    pr.harga,
                    k.nama_kategori,
                    dp.jumlah,
                    dp.subtotal
                FROM penjualan p
                JOIN user u ON p.id_user = u.id_user
                JOIN detail_penjualan dp ON p.id_penjualan = dp.id_penjualan
                JOIN produk pr ON dp.id_produk = pr.id_produk
                LEFT JOIN kategori k ON pr.id_kategori = k.id_kategori
                WHERE $where_clause
                ORDER BY p.tanggal DESC, p.id_penjualan DESC";

$result_penjualan = mysqli_query($koneksi, $query_penjualan);

$laporan_data = [];
$total_keseluruhan = 0;
$total_transaksi = 0;
$total_produk_terjual = 0;

if ($result_penjualan) {
    while ($row = mysqli_fetch_assoc($result_penjualan)) {
        $laporan_data[] = $row;
        $total_keseluruhan += $row['subtotal'];
        $total_produk_terjual += $row['jumlah'];
    }
    $total_transaksi = count(array_unique(array_column($laporan_data, 'id_penjualan')));
}

// Query untuk statistik
$query_stats = "SELECT 
                COUNT(DISTINCT p.id_penjualan) as total_transaksi,
                SUM(p.total) as total_pendapatan,
                AVG(p.total) as rata_rata_transaksi,
                COUNT(DISTINCT p.id_user) as total_kasir
            FROM penjualan p
            WHERE DATE(p.tanggal) BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";

$result_stats = mysqli_query($koneksi, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Query untuk produk terlaris dalam periode
$query_produk_terlaris = "SELECT 
                        pr.nama_produk,
                        k.nama_kategori,
                        SUM(dp.jumlah) as total_terjual,
                        SUM(dp.subtotal) as total_pendapatan
                    FROM detail_penjualan dp
                    JOIN produk pr ON dp.id_produk = pr.id_produk
                    LEFT JOIN kategori k ON pr.id_kategori = k.id_kategori
                    JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
                    WHERE DATE(p.tanggal) BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'
                    GROUP BY dp.id_produk
                    ORDER BY total_terjual DESC
                    LIMIT 10";

$result_produk_terlaris = mysqli_query($koneksi, $query_produk_terlaris);
$produk_terlaris = [];
if ($result_produk_terlaris) {
    while ($row = mysqli_fetch_assoc($result_produk_terlaris)) {
        $produk_terlaris[] = $row;
    }
}

// Query untuk penjualan per kategori
$query_kategori_stats = "SELECT 
                        k.nama_kategori,
                        COUNT(dp.id_detail) as total_penjualan,
                        SUM(dp.subtotal) as total_pendapatan
                    FROM kategori k
                    LEFT JOIN produk pr ON k.id_kategori = pr.id_kategori
                    LEFT JOIN detail_penjualan dp ON pr.id_produk = dp.id_produk
                    LEFT JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
                    WHERE (p.tanggal IS NULL OR DATE(p.tanggal) BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir')
                    GROUP BY k.id_kategori
                    ORDER BY total_pendapatan DESC";

$result_kategori_stats = mysqli_query($koneksi, $query_kategori_stats);
$kategori_stats = [];
if ($result_kategori_stats) {
    while ($row = mysqli_fetch_assoc($result_kategori_stats)) {
        $kategori_stats[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Sistem Penjualan Aksesoris</title>
    
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
        
        /* Table styles */
        .table-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .laporan-print, .laporan-print * {
                visibility: visible;
            }
            .laporan-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }
            .no-print {
                display: none !important;
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Laporan Penjualan</h1>
                <p class="text-gray-400">Analisis dan statistik penjualan</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Export Button -->
                <button onclick="exportToExcel()" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg text-white font-semibold transition duration-200 text-sm flex items-center">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </button>
                
                <!-- Print Button -->
                <button onclick="cetakLaporan()" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg text-white font-semibold transition duration-200 text-sm flex items-center">
                    <i class="fas fa-print mr-2"></i>Cetak Laporan
                </button>
                
                <!-- Back Button -->
                <a href="index.php" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="blackscrim-card rounded-xl p-6 mb-6 no-print">
            <h2 class="text-xl font-semibold text-white mb-4">Filter Laporan</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="form-label block text-sm font-medium mb-2">Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" value="<?php echo $filter_tanggal_awal; ?>" 
                           class="form-input w-full px-3 py-2 rounded-lg text-sm">
                </div>
                <div>
                    <label class="form-label block text-sm font-medium mb-2">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="<?php echo $filter_tanggal_akhir; ?>" 
                           class="form-input w-full px-3 py-2 rounded-lg text-sm">
                </div>
                <div>
                    <label class="form-label block text-sm font-medium mb-2">Kategori</label>
                    <select name="kategori" class="form-input w-full px-3 py-2 rounded-lg text-sm">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?php echo $kategori['id_kategori']; ?>" <?php echo $filter_kategori == $kategori['id_kategori'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label block text-sm font-medium mb-2">Status</label>
                    <select name="status" class="form-input w-full px-3 py-2 rounded-lg text-sm">
                        <option value="">Semua Status</option>
                        <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Lunas</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex justify-end space-x-3 pt-4">
                    <button type="submit" name="filter" 
                            class="bg-primary-500 hover:bg-primary-600 px-6 py-2 rounded-lg text-white font-semibold transition duration-200 text-sm">
                        <i class="fas fa-filter mr-2"></i>Terapkan Filter
                    </button>
                    <a href="laporan_penjualan.php" 
                       class="glass hover:bg-white/10 px-6 py-2 rounded-lg text-gray-300 transition duration-200 text-sm">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4 border border-blue-500/30">
                        <i class="fas fa-shopping-cart text-blue-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Transaksi</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($stats['total_transaksi'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 border border-green-500/30">
                        <i class="fas fa-money-bill-wave text-green-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Pendapatan</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 border border-purple-500/30">
                        <i class="fas fa-chart-line text-purple-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Rata-rata Transaksi</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($stats['rata_rata_transaksi'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-4 border border-yellow-500/30">
                        <i class="fas fa-users text-yellow-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Kasir</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($stats['total_kasir'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Produk Terlaris -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-4">10 Produk Terlaris</h2>
                <div class="space-y-3">
                    <?php if (!empty($produk_terlaris)): ?>
                        <?php foreach ($produk_terlaris as $index => $produk): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-800/30 border border-gray-700/50">
                                <div class="flex items-center">
                                    <span class="w-6 h-6 bg-primary-500/20 text-primary-400 text-xs rounded-full flex items-center justify-center mr-3 border border-primary-500/30">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <div>
                                        <p class="text-white text-sm font-medium"><?php echo htmlspecialchars($produk['nama_produk']); ?></p>
                                        <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($produk['nama_kategori']); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-green-400 text-sm font-semibold"><?php echo number_format($produk['total_terjual'], 0, ',', '.'); ?> pcs</p>
                                    <p class="text-gray-400 text-xs">Rp <?php echo number_format($produk['total_pendapatan'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-box-open text-3xl mb-3"></i>
                            <p>Belum ada data produk terlaris</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Penjualan per Kategori -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Penjualan per Kategori</h2>
                <div class="space-y-3">
                    <?php if (!empty($kategori_stats)): ?>
                        <?php foreach ($kategori_stats as $kategori): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-800/30 border border-gray-700/50">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-3 border border-blue-500/30">
                                        <i class="fas fa-tag text-blue-400 text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-white text-sm font-medium"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></p>
                                        <p class="text-gray-400 text-xs"><?php echo number_format($kategori['total_penjualan'], 0, ',', '.'); ?> penjualan</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-green-400 text-sm font-semibold">Rp <?php echo number_format($kategori['total_pendapatan'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-tags text-3xl mb-3"></i>
                            <p>Belum ada data penjualan per kategori</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detail Laporan -->
        <div class="blackscrim-card rounded-xl p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-white">Detail Transaksi</h2>
                    <p class="text-gray-400 text-sm"><?php echo count($laporan_data); ?> item ditemukan</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="text-gray-400 text-sm">Periode:</span>
                    <span class="bg-primary-500/20 text-primary-400 text-xs px-3 py-1 rounded-full border border-primary-500/30">
                        <?php echo date('d M Y', strtotime($filter_tanggal_awal)); ?> - <?php echo date('d M Y', strtotime($filter_tanggal_akhir)); ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($laporan_data)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700/50">
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">Tanggal</th>
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">No. Transaksi</th>
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">Produk</th>
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">Kategori</th>
                                <th class="text-right py-3 px-4 text-gray-400 font-semibold text-sm">Qty</th>
                                <th class="text-right py-3 px-4 text-gray-400 font-semibold text-sm">Harga</th>
                                <th class="text-right py-3 px-4 text-gray-400 font-semibold text-sm">Subtotal</th>
                                <th class="text-center py-3 px-4 text-gray-400 font-semibold text-sm">Status</th>
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">Kasir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_transaksi = null;
                            $transaksi_subtotal = 0;
                            ?>
                            
                            <?php foreach ($laporan_data as $index => $data): ?>
                                <?php if ($current_transaksi !== $data['id_penjualan']): ?>
                                    <?php if ($current_transaksi !== null): ?>
                                        <!-- Total per transaksi -->
                                        <tr class="bg-gray-800/50">
                                            <td colspan="6" class="py-3 px-4 text-right text-gray-300 font-semibold text-sm">
                                                Total Transaksi #<?php echo str_pad($current_transaksi, 6, '0', STR_PAD_LEFT); ?>:
                                            </td>
                                            <td class="py-3 px-4 text-right text-green-400 font-semibold text-sm">
                                                Rp <?php echo number_format($transaksi_subtotal, 0, ',', '.'); ?>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $current_transaksi = $data['id_penjualan'];
                                    $transaksi_subtotal = 0;
                                    ?>
                                <?php endif; ?>
                                
                                <tr class="table-row border-b border-gray-700/30 hover:bg-white/5 transition duration-200">
                                    <td class="py-4 px-4">
                                        <p class="text-white text-sm"><?php echo date('d/m/Y H:i', strtotime($data['tanggal'])); ?></p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-white font-semibold text-sm">#<?php echo str_pad($data['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-white text-sm"><?php echo htmlspecialchars($data['nama_produk']); ?></p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-gray-300 text-sm"><?php echo htmlspecialchars($data['nama_kategori']); ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <p class="text-gray-300 text-sm"><?php echo number_format($data['jumlah'], 0, ',', '.'); ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <p class="text-gray-300 text-sm">Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <p class="text-green-400 font-semibold text-sm">Rp <?php echo number_format($data['subtotal'], 0, ',', '.'); ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <?php if ($data['status_pembayaran'] == 'paid'): ?>
                                            <span class="bg-green-500/20 text-green-400 text-xs px-3 py-1 rounded-full border border-green-500/30">
                                                Lunas
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-yellow-500/20 text-yellow-400 text-xs px-3 py-1 rounded-full border border-yellow-500/30">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-gray-300 text-sm"><?php echo htmlspecialchars($data['kasir']); ?></p>
                                    </td>
                                </tr>
                                
                                <?php 
                                $transaksi_subtotal += $data['subtotal'];
                                $total_keseluruhan += $data['subtotal'];
                                ?>
                                
                                <?php if ($index === count($laporan_data) - 1): ?>
                                    <!-- Total untuk transaksi terakhir -->
                                    <tr class="bg-gray-800/50">
                                        <td colspan="6" class="py-3 px-4 text-right text-gray-300 font-semibold text-sm">
                                            Total Transaksi #<?php echo str_pad($current_transaksi, 6, '0', STR_PAD_LEFT); ?>:
                                        </td>
                                        <td class="py-3 px-4 text-right text-green-400 font-semibold text-sm">
                                            Rp <?php echo number_format($transaksi_subtotal, 0, ',', '.'); ?>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <!-- Total Keseluruhan -->
                            <tr class="bg-primary-500/20 border-t-2 border-primary-500/50">
                                <td colspan="6" class="py-4 px-4 text-right text-white font-bold text-lg">
                                    TOTAL KESELURUHAN:
                                </td>
                                <td class="py-4 px-4 text-right text-green-400 font-bold text-lg">
                                    Rp <?php echo number_format($total_keseluruhan, 0, ',', '.'); ?>
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-600/50">
                        <i class="fas fa-chart-bar text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Tidak ada data</h3>
                    <p class="text-gray-400 mb-6">Tidak ada transaksi pada periode yang dipilih.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Laporan untuk Print (tersembunyi) -->
    <div class="laporan-print" style="display: none;">
        <!-- Content akan diisi via JavaScript -->
    </div>

    <script>
        function exportToExcel() {
            // Simple Excel export menggunakan table
            const table = document.querySelector('table');
            const html = table.outerHTML;
            const url = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(html);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'laporan_penjualan_<?php echo date('Y-m-d'); ?>.xls';
            link.click();
        }

        function cetakLaporan() {
            const printContent = `
                <div style="padding: 20px; font-family: Arial, sans-serif;">
                    <h1 style="text-align: center; color: #333; margin-bottom: 10px;">LAPORAN PENJUALAN</h1>
                    <p style="text-align: center; color: #666; margin-bottom: 20px;">
                        Periode: <?php echo date('d M Y', strtotime($filter_tanggal_awal)); ?> - <?php echo date('d M Y', strtotime($filter_tanggal_akhir)); ?>
                    </p>
                    
                    <div style="margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Total Transaksi</td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo number_format($stats['total_transaksi'] ?? 0, 0, ',', '.'); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Total Pendapatan</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">Rp <?php echo number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.'); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    ${document.querySelector('table').outerHTML}
                    
                    <div style="margin-top: 30px; text-align: right; color: #666;">
                        <p>Dicetak pada: <?php echo date('d/m/Y H:i'); ?></p>
                        <p>Oleh: <?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
                    </div>
                </div>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Laporan Penjualan</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f5f5f5; font-weight: bold; }
                            .total { background-color: #e8f5e8; font-weight: bold; }
                            @media print {
                                body { margin: 0; }
                            }
                        </style>
                    </head>
                    <body>
                        ${printContent}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Auto set tanggal akhir jika tanggal awal diisi
        document.querySelector('input[name="tanggal_awal"]')?.addEventListener('change', function() {
            const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');
            if (tanggalAkhir && !tanggalAkhir.value) {
                tanggalAkhir.value = this.value;
            }
        });
    </script>
</body>
</html>