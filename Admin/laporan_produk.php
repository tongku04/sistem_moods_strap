<?php
session_start();
include_once '../config/koneksi.php';

// Cek koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Set default filter
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Query data produk dengan filter
$where_conditions = ["p.status = 'active'"];
$params = [];
$param_types = '';

if ($filter_bulan) {
    $where_conditions[] = "DATE_FORMAT(pj.tanggal, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $param_types .= 's';
}

if ($filter_kategori) {
    $where_conditions[] = "p.id_kategori = ?";
    $params[] = $filter_kategori;
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$query_produk = "SELECT 
    p.id_produk,
    p.nama_produk,
    p.harga,
    p.stok,
    k.nama_kategori,
    p.foto,
    COALESCE(SUM(dp.jumlah), 0) as total_terjual,
    COALESCE(SUM(dp.subtotal), 0) as total_pendapatan,
    COALESCE(COUNT(DISTINCT dp.id_penjualan), 0) as total_transaksi
FROM produk p
LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
LEFT JOIN detail_penjualan dp ON p.id_produk = dp.id_produk
LEFT JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
WHERE $where_clause
GROUP BY p.id_produk
ORDER BY total_terjual DESC, p.nama_produk";

$stmt = mysqli_prepare($koneksi, $query_produk);
if ($params) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result_produk = mysqli_stmt_get_result($stmt);

$produk = [];
$total_keseluruhan = [
    'total_produk' => 0,
    'total_stok' => 0,
    'total_terjual' => 0,
    'total_pendapatan' => 0,
    'total_transaksi' => 0
];

if ($result_produk) {
    while ($row = mysqli_fetch_assoc($result_produk)) {
        $produk[] = $row;
        $total_keseluruhan['total_produk']++;
        $total_keseluruhan['total_stok'] += $row['stok'];
        $total_keseluruhan['total_terjual'] += $row['total_terjual'];
        $total_keseluruhan['total_pendapatan'] += $row['total_pendapatan'];
        $total_keseluruhan['total_transaksi'] += $row['total_transaksi'];
    }
}

// Ambil data kategori untuk filter
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = mysqli_query($koneksi, $query_kategori);
$kategori = [];
if ($result_kategori) {
    while ($row = mysqli_fetch_assoc($result_kategori)) {
        $kategori[] = $row;
    }
}

// Generate data untuk chart (maksimal 10 produk untuk chart)
$chart_labels = [];
$chart_terjual = [];
$chart_pendapatan = [];

$top_produk = array_slice($produk, 0, 10);
foreach ($top_produk as $p) {
    $chart_labels[] = substr($p['nama_produk'], 0, 15) . (strlen($p['nama_produk']) > 15 ? '...' : '');
    $chart_terjual[] = $p['total_terjual'];
    $chart_pendapatan[] = $p['total_pendapatan'];
}
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
        
        /* Chart container */
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Laporan Produk</h1>
                <p class="text-gray-400">Analisis performa dan penjualan produk</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Export Button -->
                <button onclick="exportToExcel()" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </button>
                
                <!-- Print Button -->
                <button onclick="window.print()" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="blackscrim-card rounded-xl p-6 mb-6">
            <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                <i class="fas fa-filter text-primary-400 mr-3"></i>
                Filter Laporan
            </h2>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="bulan" class="form-label block text-sm font-medium mb-2">Bulan</label>
                    <input type="month" id="bulan" name="bulan" value="<?php echo $filter_bulan; ?>"
                        class="form-input w-full px-4 py-2 rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div>
                    <label for="kategori" class="form-label block text-sm font-medium mb-2">Kategori</label>
                    <select id="kategori" name="kategori"
                        class="form-input w-full px-4 py-2 rounded-lg focus:ring-2 focus:ring-primary-500">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori as $kat): ?>
                            <option value="<?php echo $kat['id_kategori']; ?>" 
                                <?php echo ($filter_kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg text-sm">
                        <i class="fas fa-search mr-2"></i>Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistik Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-blue-500/30">
                    <i class="fas fa-box text-blue-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white"><?php echo $total_keseluruhan['total_produk']; ?></h3>
                <p class="text-gray-400 text-sm">Total Produk</p>
            </div>
            
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-green-500/30">
                    <i class="fas fa-boxes text-green-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white"><?php echo $total_keseluruhan['total_stok']; ?></h3>
                <p class="text-gray-400 text-sm">Stok Tersedia</p>
            </div>
            
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-yellow-500/30">
                    <i class="fas fa-shopping-cart text-yellow-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white"><?php echo $total_keseluruhan['total_terjual']; ?></h3>
                <p class="text-gray-400 text-sm">Total Terjual</p>
            </div>
            
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-purple-500/30">
                    <i class="fas fa-money-bill-wave text-purple-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Rp <?php echo number_format($total_keseluruhan['total_pendapatan'], 0, ',', '.'); ?></h3>
                <p class="text-gray-400 text-sm">Total Pendapatan</p>
            </div>
            
            <div class="blackscrim-card rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-red-500/30">
                    <i class="fas fa-receipt text-red-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white"><?php echo $total_keseluruhan['total_transaksi']; ?></h3>
                <p class="text-gray-400 text-sm">Total Transaksi</p>
            </div>
        </div>

        <!-- Charts Section -->
        <?php if (count($top_produk) > 0): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Chart Produk Terlaris -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-primary-400 mr-3"></i>
                    Top 10 Produk Terlaris
                </h2>
                <div class="chart-container">
                    <canvas id="produkTerlarisChart"></canvas>
                </div>
            </div>
            
            <!-- Chart Pendapatan Produk -->
            <div class="blackscrim-card rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                    <i class="fas fa-chart-line text-primary-400 mr-3"></i>
                    Pendapatan per Produk
                </h2>
                <div class="chart-container">
                    <canvas id="pendapatanProdukChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabel Laporan -->
        <div class="blackscrim-card rounded-xl p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-table text-primary-400 mr-3"></i>
                    Detail Laporan Produk
                </h2>
                
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400">
                        Menampilkan <?php echo count($produk); ?> produk
                    </span>
                </div>
            </div>

            <?php if (count($produk) > 0): ?>
                <div class="overflow-x-auto dark-table rounded-lg">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">No</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Produk</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Kategori</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Harga</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Stok</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Terjual</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Pendapatan</th>
                                <th class="text-left py-4 px-6 text-gray-300 font-semibold">Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produk as $index => $prod): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-gray-900/30' : ''; ?>">
                                    <td class="py-4 px-6 text-gray-300">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <?php if ($prod['foto']): ?>
                                                <img src="../uploads/produk/<?php echo $prod['foto']; ?>" 
                                                     alt="<?php echo htmlspecialchars($prod['nama_produk']); ?>"
                                                     class="w-10 h-10 rounded-lg object-cover mr-3">
                                            <?php else: ?>
                                                <div class="w-10 h-10 bg-gray-600 rounded-lg flex items-center justify-center mr-3">
                                                    <i class="fas fa-box text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="font-medium text-white"><?php echo htmlspecialchars($prod['nama_produk']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300">
                                        <?php echo $prod['nama_kategori'] ? htmlspecialchars($prod['nama_kategori']) : '-'; ?>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300">
                                        Rp <?php echo number_format($prod['harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                            <?php echo $prod['stok'] > 10 ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 
                                                   ($prod['stok'] > 0 ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30' : 
                                                   'bg-red-500/20 text-red-400 border border-red-500/30'); ?>">
                                            <i class="fas fa-boxes mr-1 text-xs"></i>
                                            <?php echo $prod['stok']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300">
                                        <?php echo number_format($prod['total_terjual'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-6 font-semibold text-green-400">
                                        Rp <?php echo number_format($prod['total_pendapatan'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300">
                                        <?php echo number_format($prod['total_transaksi'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-700/30 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-600/30">
                        <i class="fas fa-chart-bar text-gray-500 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-400 mb-2">Tidak ada data</h3>
                    <p class="text-gray-500">Tidak ada data produk untuk periode yang dipilih</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if (count($top_produk) > 0): ?>
    <script>
        // Chart configuration for dark theme
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        
        // Produk Terlaris Chart
        const produkTerlarisCtx = document.getElementById('produkTerlarisChart').getContext('2d');
        const produkTerlarisChart = new Chart(produkTerlarisCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Jumlah Terjual',
                    data: <?php echo json_encode($chart_terjual); ?>,
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
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            maxRotation: 45,
                            minRotation: 45
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
                        padding: 12
                    }
                }
            }
        });
        
        // Pendapatan Produk Chart
        const pendapatanProdukCtx = document.getElementById('pendapatanProdukChart').getContext('2d');
        const pendapatanProdukChart = new Chart(pendapatanProdukCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($chart_pendapatan); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
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
                            color: '#94a3b8',
                            maxRotation: 45,
                            minRotation: 45
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
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Pendapatan: Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Auto refresh charts on window resize
        window.addEventListener('resize', function() {
            produkTerlarisChart.resize();
            pendapatanProdukChart.resize();
        });
    </script>
    <?php endif; ?>

    <script>
        // Export to Excel function
        function exportToExcel() {
            // Simple table export (you can implement more sophisticated Excel export here)
            const table = document.querySelector('.dark-table');
            const html = table.outerHTML;
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'laporan-produk-<?php echo $filter_bulan; ?>.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>