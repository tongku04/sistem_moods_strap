<?php
session_start();
include_once '../config/koneksi.php';

// Cek session dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Fungsi untuk log aktivitas
function log_aktivitas($aktivitas, $deskripsi) {
    global $koneksi;
    $id_user = $_SESSION['user']['id_user'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $query = "INSERT INTO log_aktivitas (id_user, aktivitas, deskripsi, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("issss", $id_user, $aktivitas, $deskripsi, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    try {
        switch ($action) {
            case 'get_laporan_penjualan':
                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                $type = $_POST['type'] ?? 'harian'; // harian, bulanan, tahunan
                
                if ($type === 'harian') {
                    $query = "SELECT 
                                DATE(tanggal) as periode,
                                COUNT(*) as total_transaksi,
                                SUM(total) as total_penjualan,
                                AVG(total) as rata_rata
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ? 
                              GROUP BY DATE(tanggal) 
                              ORDER BY periode DESC";
                } elseif ($type === 'bulanan') {
                    $query = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as periode,
                                COUNT(*) as total_transaksi,
                                SUM(total) as total_penjualan,
                                AVG(total) as rata_rata
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ? 
                              GROUP BY DATE_FORMAT(tanggal, '%Y-%m') 
                              ORDER BY periode DESC";
                } else { // tahunan
                    $query = "SELECT 
                                YEAR(tanggal) as periode,
                                COUNT(*) as total_transaksi,
                                SUM(total) as total_penjualan,
                                AVG(total) as rata_rata
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ? 
                              GROUP BY YEAR(tanggal) 
                              ORDER BY periode DESC";
                }
                
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $laporan = [];
                while ($row = $result->fetch_assoc()) {
                    $laporan[] = [
                        'periode' => $row['periode'],
                        'total_transaksi' => $row['total_transaksi'],
                        'total_penjualan' => $row['total_penjualan'],
                        'rata_rata' => $row['rata_rata']
                    ];
                }
                $stmt->close();
                
                $response['success'] = true;
                $response['laporan'] = $laporan;
                break;

            case 'get_statistik_penjualan':
                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                
                // Total statistics
                $statsQuery = "SELECT 
                                COUNT(*) as total_transaksi,
                                COALESCE(SUM(total), 0) as total_penjualan,
                                AVG(total) as rata_rata,
                                MIN(total) as transaksi_terkecil,
                                MAX(total) as transaksi_terbesar
                               FROM penjualan 
                               WHERE DATE(tanggal) BETWEEN ? AND ?";
                $stmt = $koneksi->prepare($statsQuery);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $statsResult = $stmt->get_result();
                $stats = $statsResult->fetch_assoc();
                $stmt->close();
                
                // Top products
                $produkQuery = "SELECT 
                                pr.nama_produk,
                                SUM(dp.jumlah) as total_terjual,
                                SUM(dp.subtotal) as total_pendapatan,
                                pr.harga
                               FROM detail_penjualan dp 
                               JOIN produk pr ON dp.id_produk = pr.id_produk 
                               JOIN penjualan p ON dp.id_penjualan = p.id_penjualan 
                               WHERE DATE(p.tanggal) BETWEEN ? AND ? 
                               GROUP BY dp.id_produk 
                               ORDER BY total_terjual DESC 
                               LIMIT 10";
                $stmt = $koneksi->prepare($produkQuery);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $produkResult = $stmt->get_result();
                
                $produk_terlaris = [];
                while ($produk = $produkResult->fetch_assoc()) {
                    $produk_terlaris[] = $produk;
                }
                $stmt->close();
                
                // Top kasir
                $kasirQuery = "SELECT 
                                u.username,
                                COUNT(p.id_penjualan) as total_transaksi,
                                SUM(p.total) as total_penjualan
                               FROM penjualan p 
                               JOIN user u ON p.id_user = u.id_user 
                               WHERE DATE(p.tanggal) BETWEEN ? AND ? 
                               GROUP BY p.id_user 
                               ORDER BY total_penjualan DESC 
                               LIMIT 5";
                $stmt = $koneksi->prepare($kasirQuery);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $kasirResult = $stmt->get_result();
                
                $top_kasir = [];
                while ($kasir = $kasirResult->fetch_assoc()) {
                    $top_kasir[] = $kasir;
                }
                $stmt->close();
                
                // Daily trend (7 days)
                $trendQuery = "SELECT 
                                DATE(tanggal) as tanggal,
                                COUNT(*) as total_transaksi,
                                SUM(total) as total_penjualan
                               FROM penjualan 
                               WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                               GROUP BY DATE(tanggal) 
                               ORDER BY tanggal";
                $trendResult = $koneksi->query($trendQuery);
                
                $trend_harian = [];
                while ($trend = $trendResult->fetch_assoc()) {
                    $trend_harian[] = $trend;
                }
                
                $response['success'] = true;
                $response['statistik'] = [
                    'total' => $stats,
                    'produk_terlaris' => $produk_terlaris,
                    'top_kasir' => $top_kasir,
                    'trend_harian' => $trend_harian,
                    'periode' => "$start_date s/d $end_date"
                ];
                break;

            case 'get_grafik_penjualan':
                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                $grafik_type = $_POST['grafik_type'] ?? 'harian';
                
                if ($grafik_type === 'harian') {
                    $query = "SELECT 
                                DATE(tanggal) as label,
                                SUM(total) as total_penjualan,
                                COUNT(*) as total_transaksi
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ? 
                              GROUP BY DATE(tanggal) 
                              ORDER BY label";
                } elseif ($grafik_type === 'bulanan') {
                    $query = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as label,
                                SUM(total) as total_penjualan,
                                COUNT(*) as total_transaksi
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ? 
                              GROUP BY DATE_FORMAT(tanggal, '%Y-%m') 
                              ORDER BY label";
                } else { // mingguan
                    $query = "SELECT 
                                YEARWEEK(tanggal) as label,
                                CONCAT('Minggu ', WEEK(tanggal)) as minggu,
                                SUM(total) as total_penjualan,
                                COUNT(*) as total_transaksi
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ? 
                              GROUP BY YEARWEEK(tanggal) 
                              ORDER BY label";
                }
                
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $grafik_data = [];
                $labels = [];
                $penjualan_data = [];
                $transaksi_data = [];
                
                while ($row = $result->fetch_assoc()) {
                    $labels[] = $row['label'];
                    $penjualan_data[] = $row['total_penjualan'];
                    $transaksi_data[] = $row['total_transaksi'];
                }
                $stmt->close();
                
                $response['success'] = true;
                $response['grafik'] = [
                    'labels' => $labels,
                    'penjualan' => $penjualan_data,
                    'transaksi' => $transaksi_data
                ];
                break;

            default:
                throw new Exception('Aksi tidak valid');
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Analitik - Sistem Penjualan Aksesoris</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }
        
        .blackscrim-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            color: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
        }
        
        .table-dark {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table-dark thead {
            background: rgba(30, 41, 59, 0.9);
        }
        
        .table-dark th {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table-dark td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .table-dark tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #22c55e 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top: 3px solid #22c55e;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body class="text-gray-100 min-h-screen">
    <!-- Include Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:pl-64 pt-16">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Laporan & Analitik</h1>
                    <p class="text-gray-400">Analisis data penjualan dan performa toko</p>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="exportLaporan()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 flex items-center space-x-2">
                        <i class="fas fa-file-excel"></i>
                        <span>Export Excel</span>
                    </button>
                    <button onclick="cetakLaporan()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 flex items-center space-x-2">
                        <i class="fas fa-print"></i>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="blackscrim-card p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal Mulai</label>
                        <input type="date" id="startDate" 
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal Akhir</label>
                        <input type="date" id="endDate" 
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipe Laporan</label>
                        <select id="reportType" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="harian">Harian</option>
                            <option value="bulanan">Bulanan</option>
                            <option value="tahunan">Tahunan</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipe Grafik</label>
                        <select id="chartType" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="harian">Harian</option>
                            <option value="mingguan">Mingguan</option>
                            <option value="bulanan">Bulanan</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button onclick="loadLaporan()" class="flex-1 bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <button onclick="resetFilter()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-refresh mr-2"></i>Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8" id="statsContainer">
                <!-- Stats will be loaded here -->
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Sales Chart -->
                <div class="blackscrim-card p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Grafik Penjualan</h3>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Transactions Chart -->
                <div class="blackscrim-card p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Grafik Transaksi</h3>
                    <div class="chart-container">
                        <canvas id="transactionsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Report Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Laporan Penjualan -->
                <div class="blackscrim-card p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Laporan Penjualan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full table-dark">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Periode</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Transaksi</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Total</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody id="reportTableBody">
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400">
                                        <div class="flex justify-center">
                                            <div class="spinner"></div>
                                        </div>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Produk Terlaris -->
                <div class="blackscrim-card p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Produk Terlaris</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full table-dark">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Produk</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Terjual</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody id="topProductsBody">
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-gray-400">
                                        <div class="flex justify-center">
                                            <div class="spinner"></div>
                                        </div>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Kasir -->
                <div class="blackscrim-card p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Top Performa Kasir</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full table-dark">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Kasir</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Transaksi</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Total Penjualan</th>
                                </tr>
                            </thead>
                            <tbody id="topKasirBody">
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-gray-400">
                                        <div class="flex justify-center">
                                            <div class="spinner"></div>
                                        </div>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Trend 7 Hari -->
                <div class="blackscrim-card p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Trend 7 Hari Terakhir</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full table-dark">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Tanggal</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Transaksi</th>
                                    <th class="py-3 px-4 text-left text-gray-300 font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody id="trendTableBody">
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-gray-400">
                                        <div class="flex justify-center">
                                            <div class="spinner"></div>
                                        </div>
                                        <p class="mt-2">Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 transform translate-x-full transition-transform duration-300 z-50">
        <div class="bg-gray-800 rounded-lg shadow-xl p-4 flex items-center space-x-3 min-w-[300px] border border-gray-700">
            <div id="toastIcon"></div>
            <div class="flex-1">
                <p id="toastMessage" class="text-sm font-medium text-white"></p>
            </div>
        </div>
    </div>

    <script>
        let salesChart, transactionsChart;
        let currentStartDate, currentEndDate, currentReportType;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates (current month)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
            
            loadLaporan();
            initializeCharts();
        });

        // Initialize charts
        function initializeCharts() {
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
            
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Total Penjualan',
                        data: [],
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#f8fafc'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        y: {
                            ticks: {
                                color: '#94a3b8',
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
            
            transactionsChart = new Chart(transactionsCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Transaksi',
                        data: [],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: '#3b82f6',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#f8fafc'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        y: {
                            ticks: {
                                color: '#94a3b8'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        }

        // Load all reports
        async function loadLaporan() {
            currentStartDate = document.getElementById('startDate').value;
            currentEndDate = document.getElementById('endDate').value;
            currentReportType = document.getElementById('reportType').value;
            const chartType = document.getElementById('chartType').value;
            
            await Promise.all([
                loadLaporanData(),
                loadStatistik(),
                loadGrafik(chartType)
            ]);
        }

        // Load report data
        async function loadLaporanData() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_laporan_penjualan');
                formData.append('start_date', currentStartDate);
                formData.append('end_date', currentEndDate);
                formData.append('type', currentReportType);
                
                const response = await fetch('laporan_penjualan.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayLaporanData(data.laporan);
                } else {
                    showToast('Error loading report: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading report: ' + error.message, 'error');
            }
        }

        // Load statistics
        async function loadStatistik() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_statistik_penjualan');
                formData.append('start_date', currentStartDate);
                formData.append('end_date', currentEndDate);
                
                const response = await fetch('laporan_penjualan.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayStatistik(data.statistik);
                } else {
                    showToast('Error loading statistics: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading statistics: ' + error.message, 'error');
            }
        }

        // Load chart data
        async function loadGrafik(chartType) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_grafik_penjualan');
                formData.append('start_date', currentStartDate);
                formData.append('end_date', currentEndDate);
                formData.append('grafik_type', chartType);
                
                const response = await fetch('laporan_penjualan.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    updateCharts(data.grafik);
                } else {
                    showToast('Error loading chart: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading chart: ' + error.message, 'error');
            }
        }

        // Display report data
        function displayLaporanData(laporan) {
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = '';
            
            if (laporan.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-400">
                            <i class="fas fa-chart-line text-4xl mb-2"></i>
                            <p>Tidak ada data laporan</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            laporan.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-800/50';
                
                let periodeDisplay = item.periode;
                if (currentReportType === 'harian') {
                    periodeDisplay = new Date(item.periode).toLocaleDateString('id-ID');
                } else if (currentReportType === 'bulanan') {
                    const [year, month] = item.periode.split('-');
                    periodeDisplay = new Date(year, month - 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
                }
                
                row.innerHTML = `
                    <td class="py-3 px-4 text-gray-300">${periodeDisplay}</td>
                    <td class="py-3 px-4 text-white">${item.total_transaksi}</td>
                    <td class="py-3 px-4 text-green-400">Rp ${item.total_penjualan.toLocaleString('id-ID')}</td>
                    <td class="py-3 px-4 text-blue-400">Rp ${Math.round(item.rata_rata).toLocaleString('id-ID')}</td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Display statistics
        function displayStatistik(statistik) {
            // Update stats cards
            const statsContainer = document.getElementById('statsContainer');
            statsContainer.innerHTML = `
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-primary-500/20 rounded-xl">
                            <i class="fas fa-receipt text-primary-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Transaksi</h3>
                    <p class="text-2xl font-bold text-white">${statistik.total.total_transaksi}</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-green-500/20 rounded-xl">
                            <i class="fas fa-chart-line text-green-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Penjualan</h3>
                    <p class="text-2xl font-bold text-white">Rp ${statistik.total.total_penjualan.toLocaleString('id-ID')}</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-calculator text-blue-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Rata-rata Transaksi</h3>
                    <p class="text-2xl font-bold text-white">Rp ${Math.round(statistik.total.rata_rata).toLocaleString('id-ID')}</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-yellow-500/20 rounded-xl">
                            <i class="fas fa-arrow-up text-yellow-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Transaksi Terbesar</h3>
                    <p class="text-2xl font-bold text-white">Rp ${statistik.total.transaksi_terbesar.toLocaleString('id-ID')}</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-red-500/20 rounded-xl">
                            <i class="fas fa-arrow-down text-red-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Transaksi Terkecil</h3>
                    <p class="text-2xl font-bold text-white">Rp ${statistik.total.transaksi_terkecil.toLocaleString('id-ID')}</p>
                </div>
            `;
            
            // Update top products
            const topProductsBody = document.getElementById('topProductsBody');
            topProductsBody.innerHTML = '';
            
            if (statistik.produk_terlaris.length === 0) {
                topProductsBody.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-400">Tidak ada data produk</td></tr>';
            } else {
                statistik.produk_terlaris.forEach(produk => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-800/50';
                    row.innerHTML = `
                        <td class="py-3 px-4 text-gray-300">${produk.nama_produk}</td>
                        <td class="py-3 px-4 text-white">${produk.total_terjual} pcs</td>
                        <td class="py-3 px-4 text-green-400">Rp ${produk.total_pendapatan.toLocaleString('id-ID')}</td>
                    `;
                    topProductsBody.appendChild(row);
                });
            }
            
            // Update top kasir
            const topKasirBody = document.getElementById('topKasirBody');
            topKasirBody.innerHTML = '';
            
            if (statistik.top_kasir.length === 0) {
                topKasirBody.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-400">Tidak ada data kasir</td></tr>';
            } else {
                statistik.top_kasir.forEach(kasir => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-800/50';
                    row.innerHTML = `
                        <td class="py-3 px-4 text-gray-300">${kasir.username}</td>
                        <td class="py-3 px-4 text-white">${kasir.total_transaksi}</td>
                        <td class="py-3 px-4 text-green-400">Rp ${kasir.total_penjualan.toLocaleString('id-ID')}</td>
                    `;
                    topKasirBody.appendChild(row);
                });
            }
            
            // Update trend table
            const trendTableBody = document.getElementById('trendTableBody');
            trendTableBody.innerHTML = '';
            
            if (statistik.trend_harian.length === 0) {
                trendTableBody.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-400">Tidak ada data trend</td></tr>';
            } else {
                statistik.trend_harian.forEach(trend => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-800/50';
                    row.innerHTML = `
                        <td class="py-3 px-4 text-gray-300">${new Date(trend.tanggal).toLocaleDateString('id-ID')}</td>
                        <td class="py-3 px-4 text-white">${trend.total_transaksi}</td>
                        <td class="py-3 px-4 text-green-400">Rp ${trend.total_penjualan.toLocaleString('id-ID')}</td>
                    `;
                    trendTableBody.appendChild(row);
                });
            }
        }

        // Update charts
        function updateCharts(grafik) {
            salesChart.data.labels = grafik.labels;
            salesChart.data.datasets[0].data = grafik.penjualan;
            salesChart.update();
            
            transactionsChart.data.labels = grafik.labels;
            transactionsChart.data.datasets[0].data = grafik.transaksi;
            transactionsChart.update();
        }

        // Reset filter
        function resetFilter() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
            document.getElementById('reportType').value = 'harian';
            document.getElementById('chartType').value = 'harian';
            
            loadLaporan();
        }

        // Export to Excel
        function exportLaporan() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            window.open(`../admin/export_laporan.php?start_date=${startDate}&end_date=${endDate}&type=${currentReportType}`, '_blank');
        }

        // Print report
        function cetakLaporan() {
            window.print();
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            
            toastMessage.textContent = message;
            
            let iconClass = 'fas fa-info-circle text-blue-400';
            if (type === 'success') {
                iconClass = 'fas fa-check-circle text-green-400';
            } else if (type === 'error') {
                iconClass = 'fas fa-exclamation-circle text-red-400';
            } else if (type === 'warning') {
                iconClass = 'fas fa-exclamation-triangle text-yellow-400';
            }
            
            toastIcon.innerHTML = `<i class="${iconClass} text-xl"></i>`;
            toast.classList.remove('translate-x-full');
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
            }, 3000);
        }

        // Chart type change
        document.getElementById('chartType').addEventListener('change', function() {
            loadGrafik(this.value);
        });
    </script>
</body>
</html>