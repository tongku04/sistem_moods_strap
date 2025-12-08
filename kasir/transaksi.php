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

$id_user = $_SESSION['user']['id_user'];

// Set filter default
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Query data transaksi dengan filter
$where_conditions = ["p.id_user = '$id_user'"];
$params = [];

if ($filter_tanggal) {
    $where_conditions[] = "DATE(p.tanggal) = '$filter_tanggal'";
}

if ($filter_bulan && !$filter_tanggal) {
    $where_conditions[] = "DATE_FORMAT(p.tanggal, '%Y-%m') = '$filter_bulan'";
}

$where_clause = implode(' AND ', $where_conditions);

$query_transaksi = "SELECT 
    p.id_penjualan,
    p.tanggal,
    p.total,
    u.username as kasir,
    COUNT(dp.id_detail) as total_items,
    GROUP_CONCAT(CONCAT(pr.nama_produk, ' (', dp.jumlah, 'x)') SEPARATOR ', ') as items
FROM penjualan p
LEFT JOIN user u ON p.id_user = u.id_user
LEFT JOIN detail_penjualan dp ON p.id_penjualan = dp.id_penjualan
LEFT JOIN produk pr ON dp.id_produk = pr.id_produk
WHERE $where_clause
GROUP BY p.id_penjualan
ORDER BY p.tanggal DESC, p.id_penjualan DESC";

$result_transaksi = mysqli_query($koneksi, $query_transaksi);
$transaksi = [];
$total_pendapatan = 0;
$total_transaksi = 0;

if ($result_transaksi) {
    while ($row = mysqli_fetch_assoc($result_transaksi)) {
        $transaksi[] = $row;
        $total_pendapatan += $row['total'];
        $total_transaksi++;
    }
}

// Statistik hari ini
$query_hari_ini = "SELECT 
    COUNT(*) as total_transaksi_hari_ini,
    COALESCE(SUM(total), 0) as total_pendapatan_hari_ini
FROM penjualan 
WHERE DATE(tanggal) = CURDATE() AND id_user = '$id_user'";
$result_hari_ini = mysqli_query($koneksi, $query_hari_ini);
$stat_hari_ini = mysqli_fetch_assoc($result_hari_ini);
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Kasir</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
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
    <main class="ml-64 p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Riwayat Transaksi</h1>
                <p class="text-gray-400">Riwayat transaksi yang Anda proses</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Quick Stats -->
                <div class="glass px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-shopping-cart text-primary-400 mr-2"></i>
                    <span>Total: <?php echo $total_transaksi; ?> Transaksi</span>
                </div>
                
                <!-- New Transaction Button -->
                <a href="transaksi_baru.php" class="bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg text-sm">
                    <i class="fas fa-plus mr-2"></i>Transaksi Baru
                </a>
            </div>
        </div>

        <!-- Statistik Hari Ini -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-blue-500/30">
                    <i class="fas fa-shopping-cart text-blue-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white"><?php echo $stat_hari_ini['total_transaksi_hari_ini']; ?></h3>
                <p class="text-gray-400 text-sm">Transaksi Hari Ini</p>
            </div>
            
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-green-500/30">
                    <i class="fas fa-money-bill-wave text-green-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Rp <?php echo number_format($stat_hari_ini['total_pendapatan_hari_ini'], 0, ',', '.'); ?></h3>
                <p class="text-gray-400 text-sm">Pendapatan Hari Ini</p>
            </div>
            
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-purple-500/30">
                    <i class="fas fa-chart-line text-purple-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                <p class="text-gray-400 text-sm">Total Filter</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="blackscrim-card rounded-xl p-6 mb-6">
            <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                <i class="fas fa-filter text-primary-400 mr-3"></i>
                Filter Transaksi
            </h2>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="tanggal" class="form-label block text-sm font-medium mb-2">Tanggal Spesifik</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?php echo $filter_tanggal; ?>"
                        class="form-input w-full px-4 py-2 rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label for="bulan" class="form-label block text-sm font-medium mb-2">Bulan</label>
                    <input type="month" id="bulan" name="bulan" value="<?php echo $filter_bulan; ?>"
                        class="form-input w-full px-4 py-2 rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="flex items-end space-x-3">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg text-sm">
                        <i class="fas fa-search mr-2"></i>Terapkan Filter
                    </button>
                    <a href="transaksi.php" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition duration-200 border border-gray-700 text-sm">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabel Transaksi -->
        <div class="blackscrim-card rounded-xl p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-history text-primary-400 mr-3"></i>
                    Daftar Transaksi
                </h2>
                
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400">
                        Menampilkan <?php echo $total_transaksi; ?> transaksi
                    </span>
                </div>
            </div>

            <?php if (count($transaksi) > 0): ?>
                <div class="overflow-x-auto dark-table rounded-lg">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">No</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">ID Transaksi</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Tanggal & Waktu</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Items</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Total Items</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Total Bayar</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transaksi as $index => $trx): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-gray-900/30' : ''; ?>">
                                    <td class="py-4 px-6 text-gray-300">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-mono text-blue-400 bg-blue-500/20 px-2 py-1 rounded text-sm border border-blue-500/30">
                                            #<?php echo str_pad($trx['id_penjualan'], 6, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="text-white font-medium">
                                            <?php echo date('d M Y', strtotime($trx['tanggal'])); ?>
                                        </div>
                                        <div class="text-gray-400 text-sm">
                                            <?php echo date('H:i', strtotime($trx['tanggal'])); ?> WIB
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="max-w-xs">
                                            <p class="text-gray-300 text-sm line-clamp-2" title="<?php echo htmlspecialchars($trx['items']); ?>">
                                                <?php 
                                                $items = explode(', ', $trx['items']);
                                                if (count($items) > 2) {
                                                    echo htmlspecialchars(implode(', ', array_slice($items, 0, 2))) . '...';
                                                } else {
                                                    echo htmlspecialchars($trx['items']);
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                            <i class="fas fa-box mr-1 text-xs"></i>
                                            <?php echo $trx['total_items']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-semibold text-green-400 text-lg">
                                            Rp <?php echo number_format($trx['total'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex space-x-2">
                                            <!-- Detail Button -->
                                            <button onclick="showDetail(<?php echo $trx['id_penjualan']; ?>)" 
                                                    class="px-3 py-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition duration-200 border border-blue-500/30 text-sm">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Print Button -->
                                            <button onclick="printStruk(<?php echo $trx['id_penjualan']; ?>)" 
                                                    class="px-3 py-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition duration-200 border border-green-500/30 text-sm">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-700/30 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-600/30">
                        <i class="fas fa-shopping-cart text-gray-500 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-400 mb-2">Belum ada transaksi</h3>
                    <p class="text-gray-500">Mulai dengan membuat transaksi pertama Anda</p>
                    <a href="transaksi_baru.php" class="inline-block mt-4 bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-3 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Buat Transaksi Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Detail Transaksi -->
    <div id="detailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="blackscrim-card rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-white">Detail Transaksi</h3>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <div id="modalContent">
                <!-- Content akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Show detail modal
        function showDetail(id_penjualan) {
            // Dalam implementasi nyata, Anda akan fetch data dari server
            // Di sini kita buat contoh sederhana
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                        <span class="text-gray-300">ID Transaksi:</span>
                        <span class="font-mono text-blue-400">#${String(id_penjualan).padStart(6, '0')}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                            <span class="text-gray-300 text-sm">Tanggal</span>
                            <p class="text-white font-medium" id="modalTanggal">Loading...</p>
                        </div>
                        <div class="p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                            <span class="text-gray-300 text-sm">Total</span>
                            <p class="text-green-400 font-semibold text-lg" id="modalTotal">Loading...</p>
                        </div>
                    </div>
                    
                    <div class="p-4 rounded-lg bg-gray-800/30 border border-gray-700/50">
                        <h4 class="font-semibold text-white mb-3">Items:</h4>
                        <div id="modalItems" class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300">Loading items...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button onclick="closeDetailModal()" 
                                class="px-4 py-2 glass text-gray-300 rounded-lg hover:bg-white/10 transition duration-200 border border-gray-700">
                            Tutup
                        </button>
                        <button onclick="printStruk(${id_penjualan})"
                                class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition duration-200 shadow-lg">
                            <i class="fas fa-print mr-2"></i>Print Struk
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('detailModal').classList.remove('hidden');
            
            // Fetch real data (dalam implementasi nyata)
            setTimeout(() => {
                document.getElementById('modalTanggal').textContent = new Date().toLocaleDateString('id-ID');
                document.getElementById('modalTotal').textContent = 'Rp 0';
                document.getElementById('modalItems').innerHTML = '<p class="text-gray-400">Data detail akan diimplementasi dengan AJAX</p>';
            }, 500);
        }

        // Close detail modal
        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        // Print struk function
        function printStruk(id_penjualan) {
            // Dalam implementasi nyata, ini akan membuka halaman print struk
            alert(`Fitur print struk untuk transaksi #${String(id_penjualan).padStart(6, '0')} akan diimplementasi`);
        }

        // Close modal when clicking outside
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target.id === 'detailModal') {
                closeDetailModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailModal();
            }
        });

        // Auto focus on date filter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('tanggal') && !urlParams.has('bulan')) {
                document.getElementById('tanggal').focus();
            }
        });
    </script>
</body>
</html>