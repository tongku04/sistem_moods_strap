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
            case 'get_penjualan':
                $start_date = $_POST['start_date'] ?? '';
                $end_date = $_POST['end_date'] ?? '';
                $page = $_POST['page'] ?? 1;
                $limit = $_POST['limit'] ?? 10;
                $offset = ($page - 1) * $limit;

                // Build query
                $query = "SELECT p.*, u.username 
                         FROM penjualan p 
                         LEFT JOIN user u ON p.id_user = u.id_user 
                         WHERE 1=1";
                $params = [];
                $types = '';

                if (!empty($start_date) && !empty($end_date)) {
                    $query .= " AND DATE(p.tanggal) BETWEEN ? AND ?";
                    $params[] = $start_date;
                    $params[] = $end_date;
                    $types .= 'ss';
                }

                // Get total count
                $countQuery = "SELECT COUNT(*) as total FROM penjualan p WHERE 1=1";
                $countParams = [];
                $countTypes = '';

                if (!empty($start_date) && !empty($end_date)) {
                    $countQuery .= " AND DATE(p.tanggal) BETWEEN ? AND ?";
                    $countParams[] = $start_date;
                    $countParams[] = $end_date;
                    $countTypes .= 'ss';
                }

                $stmt = $koneksi->prepare($countQuery);
                if (!empty($countParams)) {
                    $stmt->bind_param($countTypes, ...$countParams);
                }
                $stmt->execute();
                $totalResult = $stmt->get_result();
                $totalRow = $totalResult->fetch_assoc();
                $totalPenjualan = $totalRow['total'];
                $stmt->close();

                // Get penjualan with pagination
                $query .= " ORDER BY p.tanggal DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $types .= 'ii';

                $stmt = $koneksi->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                $penjualan = [];
                while ($row = $result->fetch_assoc()) {
                    // Get detail penjualan
                    $detailQuery = "SELECT dp.*, pr.nama_produk 
                                   FROM detail_penjualan dp 
                                   JOIN produk pr ON dp.id_produk = pr.id_produk 
                                   WHERE dp.id_penjualan = ?";
                    $detailStmt = $koneksi->prepare($detailQuery);
                    $detailStmt->bind_param("i", $row['id_penjualan']);
                    $detailStmt->execute();
                    $detailResult = $detailStmt->get_result();
                    
                    $detail_items = [];
                    while ($detail = $detailResult->fetch_assoc()) {
                        $detail_items[] = $detail;
                    }
                    $detailStmt->close();

                    $penjualan[] = [
                        'id_penjualan' => $row['id_penjualan'],
                        'tanggal' => $row['tanggal'],
                        'total' => $row['total'],
                        'username' => $row['username'] ?? 'Tidak diketahui',
                        'detail_items' => $detail_items
                    ];
                }

                $response['success'] = true;
                $response['penjualan'] = $penjualan;
                $response['total'] = $totalPenjualan;
                $response['pages'] = ceil($totalPenjualan / $limit);
                break;

            case 'get_penjualan_stats':
                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                $end_date = $_POST['end_date'] ?? date('Y-m-d');

                // Total penjualan
                $totalQuery = "SELECT COALESCE(SUM(total), 0) as total_penjualan 
                              FROM penjualan 
                              WHERE DATE(tanggal) BETWEEN ? AND ?";
                $stmt = $koneksi->prepare($totalQuery);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $totalResult = $stmt->get_result();
                $totalData = $totalResult->fetch_assoc();
                $stmt->close();

                // Total transaksi
                $transaksiQuery = "SELECT COUNT(*) as total_transaksi 
                                  FROM penjualan 
                                  WHERE DATE(tanggal) BETWEEN ? AND ?";
                $stmt = $koneksi->prepare($transaksiQuery);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $transaksiResult = $stmt->get_result();
                $transaksiData = $transaksiResult->fetch_assoc();
                $stmt->close();

                // Produk terlaris
                $produkQuery = "SELECT pr.nama_produk, SUM(dp.jumlah) as total_terjual 
                               FROM detail_penjualan dp 
                               JOIN produk pr ON dp.id_produk = pr.id_produk 
                               JOIN penjualan p ON dp.id_penjualan = p.id_penjualan 
                               WHERE DATE(p.tanggal) BETWEEN ? AND ? 
                               GROUP BY dp.id_produk 
                               ORDER BY total_terjual DESC 
                               LIMIT 5";
                $stmt = $koneksi->prepare($produkQuery);
                $stmt->bind_param("ss", $start_date, $end_date);
                $stmt->execute();
                $produkResult = $stmt->get_result();
                
                $produk_terlaris = [];
                while ($produk = $produkResult->fetch_assoc()) {
                    $produk_terlaris[] = $produk;
                }
                $stmt->close();

                // Penjualan harian (7 hari terakhir)
                $harianQuery = "SELECT DATE(tanggal) as tanggal, SUM(total) as total_harian 
                               FROM penjualan 
                               WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                               GROUP BY DATE(tanggal) 
                               ORDER BY tanggal DESC";
                $harianResult = $koneksi->query($harianQuery);
                
                $penjualan_harian = [];
                while ($harian = $harianResult->fetch_assoc()) {
                    $penjualan_harian[] = $harian;
                }

                $response['success'] = true;
                $response['stats'] = [
                    'total_penjualan' => $totalData['total_penjualan'],
                    'total_transaksi' => $transaksiData['total_transaksi'],
                    'produk_terlaris' => $produk_terlaris,
                    'penjualan_harian' => $penjualan_harian,
                    'periode' => "$start_date s/d $end_date"
                ];
                break;

            case 'get_penjualan_detail':
                $id_penjualan = (int)$_POST['id_penjualan'];
                
                $query = "SELECT p.*, u.username 
                         FROM penjualan p 
                         LEFT JOIN user u ON p.id_user = u.id_user 
                         WHERE p.id_penjualan = ?";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("i", $id_penjualan);
                $stmt->execute();
                $result = $stmt->get_result();
                $penjualan = $result->fetch_assoc();
                $stmt->close();

                if ($penjualan) {
                    // Get detail items
                    $detailQuery = "SELECT dp.*, pr.nama_produk, pr.harga 
                                   FROM detail_penjualan dp 
                                   JOIN produk pr ON dp.id_produk = pr.id_produk 
                                   WHERE dp.id_penjualan = ?";
                    $detailStmt = $koneksi->prepare($detailQuery);
                    $detailStmt->bind_param("i", $id_penjualan);
                    $detailStmt->execute();
                    $detailResult = $detailStmt->get_result();
                    
                    $detail_items = [];
                    while ($detail = $detailResult->fetch_assoc()) {
                        $detail_items[] = $detail;
                    }
                    $detailStmt->close();

                    $penjualan['detail_items'] = $detail_items;
                    
                    $response['success'] = true;
                    $response['penjualan'] = $penjualan;
                } else {
                    throw new Exception('Data penjualan tidak ditemukan');
                }
                break;

            case 'delete_penjualan':
                $id_penjualan = (int)$_POST['id_penjualan'];

                // Start transaction
                $koneksi->begin_transaction();

                try {
                    // Get penjualan data for log
                    $penjualanQuery = "SELECT total FROM penjualan WHERE id_penjualan = ?";
                    $penjualanStmt = $koneksi->prepare($penjualanQuery);
                    $penjualanStmt->bind_param("i", $id_penjualan);
                    $penjualanStmt->execute();
                    $penjualanResult = $penjualanStmt->get_result();
                    $penjualanData = $penjualanResult->fetch_assoc();
                    $penjualanStmt->close();

                    // Delete detail penjualan first
                    $deleteDetailQuery = "DELETE FROM detail_penjualan WHERE id_penjualan = ?";
                    $deleteDetailStmt = $koneksi->prepare($deleteDetailQuery);
                    $deleteDetailStmt->bind_param("i", $id_penjualan);
                    $deleteDetailStmt->execute();
                    $deleteDetailStmt->close();

                    // Delete penjualan
                    $deleteQuery = "DELETE FROM penjualan WHERE id_penjualan = ?";
                    $deleteStmt = $koneksi->prepare($deleteQuery);
                    $deleteStmt->bind_param("i", $id_penjualan);
                    
                    if ($deleteStmt->execute()) {
                        $koneksi->commit();
                        log_aktivitas('Hapus Penjualan', "Menghapus transaksi penjualan ID: $id_penjualan (Total: Rp " . number_format($penjualanData['total'], 0, ',', '.') . ")");
                        $response['success'] = true;
                        $response['message'] = 'Data penjualan berhasil dihapus';
                    } else {
                        throw new Exception('Gagal menghapus data penjualan');
                    }
                    $deleteStmt->close();
                } catch (Exception $e) {
                    $koneksi->rollback();
                    throw $e;
                }
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
    <title>Laporan Penjualan - Sistem Penjualan Aksesoris</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
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
                    <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Laporan Penjualan</h1>
                    <p class="text-gray-400">Kelola dan pantau data penjualan toko</p>
                </div>
                
                <button onclick="exportToExcel()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export Excel</span>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="statsContainer">
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-primary-500/20 rounded-xl">
                            <i class="fas fa-chart-line text-primary-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-primary-400 bg-primary-500/20 px-3 py-1 rounded-full border border-primary-500/30">
                            Total
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Penjualan</h3>
                    <p id="totalPenjualan" class="text-2xl font-bold text-white">Rp 0</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-green-500/20 rounded-xl">
                            <i class="fas fa-receipt text-green-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-green-400 bg-green-500/20 px-3 py-1 rounded-full border border-green-500/30">
                            Transaksi
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Transaksi</h3>
                    <p id="totalTransaksi" class="text-2xl font-bold text-white">0</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-yellow-500/20 rounded-xl">
                            <i class="fas fa-star text-yellow-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-yellow-400 bg-yellow-500/20 px-3 py-1 rounded-full border border-yellow-500/30">
                            Terlaris
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Produk Terlaris</h3>
                    <p id="produkTerlaris" class="text-2xl font-bold text-white">-</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-calendar text-blue-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-blue-400 bg-blue-500/20 px-3 py-1 rounded-full border border-blue-500/30">
                            Periode
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Periode Laporan</h3>
                    <p id="periodeLaporan" class="text-lg font-bold text-white">-</p>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="blackscrim-card p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    
                    <div class="flex items-end">
                        <button onclick="loadPenjualan()" class="w-full bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                    
                    <div class="flex items-end">
                        <button onclick="resetFilter()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-refresh mr-2"></i>Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Penjualan Table -->
            <div class="blackscrim-card p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-dark">
                        <thead>
                            <tr>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">ID Transaksi</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Tanggal</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Kasir</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Total</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Detail</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="penjualanTableBody">
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">
                                    <div class="flex justify-center">
                                        <div class="spinner"></div>
                                    </div>
                                    <p class="mt-2">Memuat data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-center mt-6 space-y-4 sm:space-y-0">
                    <div class="text-gray-400 text-sm">
                        Menampilkan <span id="showingFrom">0</span> - <span id="showingTo">0</span> dari <span id="totalShowing">0</span> transaksi
                    </div>
                    <div class="flex space-x-2" id="pagination">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="blackscrim-card p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-white">Detail Transaksi</h3>
                <button onclick="hideDetailModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="detailContent">
                <!-- Detail content will be loaded here -->
            </div>
            
            <div class="flex justify-end mt-6">
                <button onclick="hideDetailModal()" class="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

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
        let currentPage = 1;
        let currentLimit = 10;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates (current month)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
            
            loadPenjualan();
            loadStats();
        });

        // Load penjualan data
        async function loadPenjualan(page = 1) {
            try {
                currentPage = page;
                
                const formData = new FormData();
                formData.append('action', 'get_penjualan');
                formData.append('start_date', document.getElementById('startDate').value);
                formData.append('end_date', document.getElementById('endDate').value);
                formData.append('page', page);
                formData.append('limit', currentLimit);
                
                const response = await fetch('penjualan.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayPenjualan(data.penjualan);
                    updatePagination(data.total, data.pages, page);
                } else {
                    showToast('Error loading penjualan: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading penjualan: ' + error.message, 'error');
            }
        }

        // Load statistics
        async function loadStats() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_penjualan_stats');
                formData.append('start_date', document.getElementById('startDate').value);
                formData.append('end_date', document.getElementById('endDate').value);
                
                const response = await fetch('penjualan.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    updateStats(data.stats);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Display penjualan in table
        function displayPenjualan(penjualan) {
            const tbody = document.getElementById('penjualanTableBody');
            tbody.innerHTML = '';
            
            if (penjualan.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-400">
                            <i class="fas fa-receipt text-4xl mb-2"></i>
                            <p>Tidak ada transaksi ditemukan</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            penjualan.forEach(transaksi => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-800/50';
                
                const tanggal = new Date(transaksi.tanggal).toLocaleString('id-ID');
                
                row.innerHTML = `
                    <td class="py-4 px-6">
                        <div class="font-mono text-primary-400 font-semibold">#${transaksi.id_penjualan.toString().padStart(6, '0')}</div>
                    </td>
                    <td class="py-4 px-6 text-gray-300">${tanggal}</td>
                    <td class="py-4 px-6 text-gray-300">${transaksi.username}</td>
                    <td class="py-4 px-6 text-white font-semibold">Rp ${transaksi.total.toLocaleString('id-ID')}</td>
                    <td class="py-4 px-6">
                        <button onclick="showDetail(${transaksi.id_penjualan})" class="text-blue-400 hover:text-blue-300 transition-colors">
                            <i class="fas fa-eye mr-1"></i> Lihat Detail
                        </button>
                    </td>
                    <td class="py-4 px-6">
                        <button onclick="deletePenjualan(${transaksi.id_penjualan})" class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Update statistics display
        function updateStats(stats) {
            document.getElementById('totalPenjualan').textContent = 'Rp ' + stats.total_penjualan.toLocaleString('id-ID');
            document.getElementById('totalTransaksi').textContent = stats.total_transaksi;
            document.getElementById('periodeLaporan').textContent = stats.periode;
            
            // Produk terlaris
            const produkTerlarisElement = document.getElementById('produkTerlaris');
            if (stats.produk_terlaris.length > 0) {
                produkTerlarisElement.textContent = stats.produk_terlaris[0].nama_produk;
                produkTerlarisElement.title = stats.produk_terlaris[0].nama_produk + ' (' + stats.produk_terlaris[0].total_terjual + ' terjual)';
            } else {
                produkTerlarisElement.textContent = '-';
            }
        }

        // Update pagination
        function updatePagination(total, totalPages, currentPage) {
            const pagination = document.getElementById('pagination');
            const showingFrom = document.getElementById('showingFrom');
            const showingTo = document.getElementById('showingTo');
            const totalShowing = document.getElementById('totalShowing');
            
            // Update showing info
            const from = ((currentPage - 1) * currentLimit) + 1;
            const to = Math.min(currentPage * currentLimit, total);
            showingFrom.textContent = from;
            showingTo.textContent = to;
            totalShowing.textContent = total;
            
            // Build pagination buttons
            let paginationHTML = '';
            
            if (currentPage > 1) {
                paginationHTML += `
                    <button onclick="loadPenjualan(${currentPage - 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                `;
            }
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHTML += `
                        <button class="px-3 py-2 bg-primary-500 border border-primary-500 rounded-lg text-white">
                            ${i}
                        </button>
                    `;
                } else {
                    paginationHTML += `
                        <button onclick="loadPenjualan(${i})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                            ${i}
                        </button>
                    `;
                }
            }
            
            if (currentPage < totalPages) {
                paginationHTML += `
                    <button onclick="loadPenjualan(${currentPage + 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }
            
            pagination.innerHTML = paginationHTML;
        }

        // Show detail modal
        async function showDetail(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_penjualan_detail');
                formData.append('id_penjualan', id);
                
                const response = await fetch('penjualan.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const transaksi = data.penjualan;
                    const tanggal = new Date(transaksi.tanggal).toLocaleString('id-ID');
                    
                    let detailHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-4">Informasi Transaksi</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">ID Transaksi:</span>
                                        <span class="text-white font-mono">#${transaksi.id_penjualan.toString().padStart(6, '0')}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Tanggal:</span>
                                        <span class="text-white">${tanggal}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Kasir:</span>
                                        <span class="text-white">${transaksi.username}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Total:</span>
                                        <span class="text-white font-semibold">Rp ${transaksi.total.toLocaleString('id-ID')}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-lg font-semibold text-white mb-4">Detail Produk</h4>
                                <div class="space-y-3">
                    `;
                    
                    transaksi.detail_items.forEach(item => {
                        const subtotal = item.jumlah * item.harga;
                        detailHTML += `
                            <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
                                <div>
                                    <div class="text-white font-medium">${item.nama_produk}</div>
                                    <div class="text-gray-400 text-sm">${item.jumlah} x Rp ${item.harga.toLocaleString('id-ID')}</div>
                                </div>
                                <div class="text-white font-semibold">Rp ${subtotal.toLocaleString('id-ID')}</div>
                            </div>
                        `;
                    });
                    
                    detailHTML += `
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('detailContent').innerHTML = detailHTML;
                    document.getElementById('detailModal').classList.remove('hidden');
                } else {
                    showToast('Error loading detail: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading detail: ' + error.message, 'error');
            }
        }

        // Hide detail modal
        function hideDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        // Delete penjualan
        async function deletePenjualan(id) {
            if (confirm('Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_penjualan');
                    formData.append('id_penjualan', id);
                    
                    const response = await fetch('penjualan.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('Transaksi berhasil dihapus', 'success');
                        loadPenjualan(currentPage);
                        loadStats();
                    } else {
                        showToast('Error deleting transaction: ' + data.message, 'error');
                    }
                } catch (error) {
                    showToast('Error deleting transaction: ' + error.message, 'error');
                }
            }
        }

        // Reset filter
        function resetFilter() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
            
            loadPenjualan(1);
            loadStats();
        }

        // Export to Excel
        function exportToExcel() {
            // Simple implementation - in real scenario, you might want to use a library
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            window.open(`export_penjualan.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
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
    </script>
</body>
</html>