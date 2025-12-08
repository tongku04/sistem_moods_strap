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

$pesan = '';
$pesan_error = '';

// Filter tanggal
$filter_tanggal = '';
$tanggal_awal = '';
$tanggal_akhir = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $tanggal_awal = mysqli_real_escape_string($koneksi, $_POST['tanggal_awal']);
    $tanggal_akhir = mysqli_real_escape_string($koneksi, $_POST['tanggal_akhir']);
    
    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
        $filter_tanggal = "WHERE DATE(p.tanggal) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    } elseif (!empty($tanggal_awal)) {
        $filter_tanggal = "WHERE DATE(p.tanggal) = '$tanggal_awal'";
    }
}

// Query untuk mengambil data penjualan
$query = "SELECT p.*, u.username 
          FROM penjualan p 
          JOIN user u ON p.id_user = u.id_user 
          $filter_tanggal
          ORDER BY p.tanggal DESC";
$result = mysqli_query($koneksi, $query);

$penjualan = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $penjualan[] = $row;
    }
}

// Hitung total pendapatan
$query_total = "SELECT 
                COUNT(*) as total_transaksi,
                SUM(total) as total_pendapatan,
                SUM(bayar) as total_bayar,
                SUM(kembalian) as total_kembalian
                FROM penjualan 
                $filter_tanggal";
$result_total = mysqli_query($koneksi, $query_total);
$total_data = mysqli_fetch_assoc($result_total);
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penjualan - Kasir</title>
    
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
        .table-row:hover {
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Riwayat Penjualan</h1>
                <p class="text-gray-400">Daftar transaksi penjualan yang telah dilakukan</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- New Transaction Button -->
                <a href="transaksi_baru.php" class="bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 text-sm">
                    <i class="fas fa-plus mr-2"></i>Transaksi Baru
                </a>
                
                <!-- Quick Stats -->
                <div class="glass px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-chart-bar text-primary-400 mr-2"></i>
                    <span><?php echo $total_data['total_transaksi'] ?? 0; ?> Transaksi</span>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($pesan): ?>
            <div class="blackscrim-card p-4 mb-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-2 bg-green-500/20 rounded-lg mr-4">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Berhasil!</h3>
                        <p class="text-gray-300 text-sm"><?php echo $pesan; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="blackscrim-card p-4 mb-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-2 bg-red-500/20 rounded-lg mr-4">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Error!</h3>
                        <p class="text-gray-300 text-sm"><?php echo $pesan_error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Total Transaksi -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mr-4 border border-blue-500/30">
                        <i class="fas fa-shopping-cart text-blue-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Transaksi</p>
                        <p class="text-2xl font-bold text-white"><?php echo $total_data['total_transaksi'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Pendapatan -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 border border-green-500/30">
                        <i class="fas fa-money-bill-wave text-green-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Pendapatan</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($total_data['total_pendapatan'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Bayar -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-4 border border-yellow-500/30">
                        <i class="fas fa-credit-card text-yellow-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Bayar</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($total_data['total_bayar'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Kembalian -->
            <div class="blackscrim-card p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4 border border-purple-500/30">
                        <i class="fas fa-exchange-alt text-purple-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Total Kembalian</p>
                        <p class="text-2xl font-bold text-white">Rp <?php echo number_format($total_data['total_kembalian'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="blackscrim-card rounded-xl p-6 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-white mb-2">Filter Transaksi</h2>
                    <p class="text-gray-400 text-sm">Filter berdasarkan tanggal transaksi</p>
                </div>
                
                <form method="POST" class="flex flex-col md:flex-row gap-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div>
                            <label class="form-label block text-sm font-medium mb-2">Tanggal Awal</label>
                            <input type="date" name="tanggal_awal" value="<?php echo $tanggal_awal; ?>" 
                                   class="form-input px-3 py-2 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="form-label block text-sm font-medium mb-2">Tanggal Akhir</label>
                            <input type="date" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>" 
                                   class="form-input px-3 py-2 rounded-lg text-sm">
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" name="filter" 
                                class="bg-primary-500 hover:bg-primary-600 px-4 py-2 rounded-lg text-white font-semibold transition duration-200 text-sm h-10">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="penjualan.php" 
                           class="glass hover:bg-white/10 px-4 py-2 rounded-lg text-gray-300 transition duration-200 text-sm h-10 flex items-center">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="blackscrim-card rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-white">Daftar Transaksi</h2>
                    <p class="text-gray-400 text-sm"><?php echo count($penjualan); ?> transaksi ditemukan</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="text-gray-400 text-sm">Status:</span>
                    <span class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded border border-green-500/30">
                        <i class="fas fa-circle mr-1"></i>Lunas
                    </span>
                    <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2 py-1 rounded border border-yellow-500/30">
                        <i class="fas fa-circle mr-1"></i>Pending
                    </span>
                </div>
            </div>

            <?php if (count($penjualan) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700/50">
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">No. Transaksi</th>
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">Tanggal</th>
                                <th class="text-left py-3 px-4 text-gray-400 font-semibold text-sm">Kasir</th>
                                <th class="text-right py-3 px-4 text-gray-400 font-semibold text-sm">Total</th>
                                <th class="text-right py-3 px-4 text-gray-400 font-semibold text-sm">Bayar</th>
                                <th class="text-right py-3 px-4 text-gray-400 font-semibold text-sm">Kembali</th>
                                <th class="text-center py-3 px-4 text-gray-400 font-semibold text-sm">Status</th>
                                <th class="text-center py-3 px-4 text-gray-400 font-semibold text-sm">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penjualan as $index => $jual): ?>
                            <tr class="table-row border-b border-gray-700/30 hover:bg-white/5 transition duration-200">
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-primary-500/20 rounded-lg flex items-center justify-center mr-3 border border-primary-500/30">
                                            <i class="fas fa-receipt text-primary-400 text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-white font-semibold text-sm">#<?php echo str_pad($jual['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-white text-sm"><?php echo date('d/m/Y H:i', strtotime($jual['tanggal'])); ?></p>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-white text-sm"><?php echo htmlspecialchars($jual['username']); ?></p>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <p class="text-white font-semibold text-sm">Rp <?php echo number_format($jual['total'], 0, ',', '.'); ?></p>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <p class="text-green-400 text-sm">Rp <?php echo number_format($jual['bayar'], 0, ',', '.'); ?></p>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <p class="text-blue-400 text-sm">Rp <?php echo number_format($jual['kembalian'], 0, ',', '.'); ?></p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <?php if ($jual['status_pembayaran'] == 'paid'): ?>
                                        <span class="bg-green-500/20 text-green-400 text-xs px-3 py-1 rounded-full border border-green-500/30">
                                            <i class="fas fa-check-circle mr-1"></i>Lunas
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-yellow-500/20 text-yellow-400 text-xs px-3 py-1 rounded-full border border-yellow-500/30">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <a href="cetak_struk.php?id=<?php echo $jual['id_penjualan']; ?>" 
                                           target="_blank"
                                           class="bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-1 rounded-lg hover:bg-blue-500/30 transition duration-200 text-xs"
                                           title="Cetak Struk">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <button onclick="lihatDetail(<?php echo $jual['id_penjualan']; ?>)" 
                                           class="bg-green-500/20 text-green-400 border border-green-500/30 px-3 py-1 rounded-lg hover:bg-green-500/30 transition duration-200 text-xs"
                                           title="Detail Transaksi">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination atau info empty state -->
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-600/50">
                        <i class="fas fa-receipt text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Tidak ada transaksi</h3>
                    <p class="text-gray-400 mb-6">Belum ada transaksi penjualan yang tercatat.</p>
                    <a href="transaksi_baru.php" 
                       class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-3 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>Buat Transaksi Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="blackscrim-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <i class="fas fa-chart-line text-blue-400 mr-2"></i>
                    Laporan Harian
                </h3>
                <p class="text-gray-400 text-sm mb-4">Lihat laporan penjualan harian untuk analisis performa.</p>
                <button onclick="alert('Fitur laporan harian akan segera tersedia!')" class="text-primary-400 hover:text-primary-300 text-sm font-semibold flex items-center">
                    Lihat Laporan <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>

            <div class="blackscrim-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <i class="fas fa-box text-purple-400 mr-2"></i>
                    Kelola Produk
                </h3>
                <p class="text-gray-400 text-sm mb-4">Kelola stok produk dan tambah produk baru.</p>
                <a href="produk.php" class="text-primary-400 hover:text-primary-300 text-sm font-semibold flex items-center">
                    Kelola Produk <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <div class="blackscrim-card rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <i class="fas fa-download text-green-400 mr-2"></i>
                    Export Data
                </h3>
                <p class="text-gray-400 text-sm mb-4">Export data penjualan dalam format Excel.</p>
                <button onclick="exportData()" class="text-primary-400 hover:text-primary-300 text-sm font-semibold flex items-center">
                    Export Excel <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </main>

    <!-- Modal Detail -->
    <div id="modalDetail" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 hidden">
        <div class="blackscrim-card rounded-xl p-6 w-full max-w-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-white">Detail Transaksi</h3>
                <button type="button" onclick="tutupModalDetail()" 
                        class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="detailContent">
                <!-- Content akan diisi via AJAX -->
            </div>
        </div>
    </div>

    <script>
        // Auto set tanggal akhir jika tanggal awal diisi
        document.querySelector('input[name="tanggal_awal"]')?.addEventListener('change', function() {
            const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');
            if (tanggalAkhir && !tanggalAkhir.value) {
                tanggalAkhir.value = this.value;
            }
        });

        // Confirm sebelum reset filter
        document.querySelector('a[href="penjualan.php"]')?.addEventListener('click', function(e) {
            const tanggalAwal = document.querySelector('input[name="tanggal_awal"]');
            const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');
            
            if ((tanggalAwal && tanggalAwal.value) || (tanggalAkhir && tanggalAkhir.value)) {
                if (!confirm('Yakin ingin mereset filter?')) {
                    e.preventDefault();
                }
            }
        });

        // Fungsi lihat detail transaksi
        function lihatDetail(id_penjualan) {
            // Tampilkan loading
            document.getElementById('detailContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto"></div>
                    <p class="text-gray-400 mt-4">Memuat detail transaksi...</p>
                </div>
            `;
            
            // Tampilkan modal
            document.getElementById('modalDetail').classList.remove('hidden');
            
            // Load data via AJAX
            fetch(`get_detail_penjualan.php?id=${id_penjualan}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detailContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('detailContent').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-red-400 text-3xl mb-4"></i>
                            <p class="text-red-400">Gagal memuat detail transaksi</p>
                        </div>
                    `;
                });
        }

        function tutupModalDetail() {
            document.getElementById('modalDetail').classList.add('hidden');
        }

        function exportData() {
            const tanggalAwal = document.querySelector('input[name="tanggal_awal"]')?.value || '';
            const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]')?.value || '';
            
            let url = 'export_penjualan.php';
            if (tanggalAwal || tanggalAkhir) {
                url += `?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`;
            }
            
            window.open(url, '_blank');
        }

        // Close modal ketika klik di luar
        document.getElementById('modalDetail')?.addEventListener('click', function(e) {
            if (e.target === this) {
                tutupModalDetail();
            }
        });
    </script>
</body>
</html>