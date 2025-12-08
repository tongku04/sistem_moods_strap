<?php
session_start();
include_once '../config/koneksi.php';

// Cek session dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kasir') {
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
            case 'get_stock_report':
                $stock_filter = $_POST['stock_filter'] ?? 'all';
                $category = $_POST['category'] ?? '';
                $page = $_POST['page'] ?? 1;
                $limit = $_POST['limit'] ?? 15;
                $offset = ($page - 1) * $limit;

                // Build query
                $query = "SELECT p.*, k.nama_kategori 
                         FROM produk p 
                         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                         WHERE p.status = 'active'";
                $params = [];
                $types = '';

                // Filter stok
                if ($stock_filter === 'low') {
                    $query .= " AND p.stok > 0 AND p.stok <= 10";
                } elseif ($stock_filter === 'out') {
                    $query .= " AND p.stok = 0";
                } elseif ($stock_filter === 'available') {
                    $query .= " AND p.stok > 10";
                }

                if (!empty($category) && $category !== 'all') {
                    $query .= " AND p.id_kategori = ?";
                    $params[] = $category;
                    $types .= 'i';
                }

                // Get total count
                $countQuery = "SELECT COUNT(*) as total FROM produk p WHERE p.status = 'active'";
                $countParams = [];
                $countTypes = '';

                if ($stock_filter === 'low') {
                    $countQuery .= " AND p.stok > 0 AND p.stok <= 10";
                } elseif ($stock_filter === 'out') {
                    $countQuery .= " AND p.stok = 0";
                } elseif ($stock_filter === 'available') {
                    $countQuery .= " AND p.stok > 10";
                }

                if (!empty($category) && $category !== 'all') {
                    $countQuery .= " AND p.id_kategori = ?";
                    $countParams[] = $category;
                    $countTypes .= 'i';
                }

                $stmt = $koneksi->prepare($countQuery);
                if (!empty($countParams)) {
                    $stmt->bind_param($countTypes, ...$countParams);
                }
                $stmt->execute();
                $totalResult = $stmt->get_result();
                $totalRow = $totalResult->fetch_assoc();
                $totalProducts = $totalRow['total'];
                $stmt->close();

                // Get products with pagination
                $query .= " ORDER BY 
                          CASE 
                            WHEN p.stok = 0 THEN 1
                            WHEN p.stok <= 10 THEN 2
                            ELSE 3
                          END,
                          p.stok ASC,
                          p.nama_produk ASC 
                         LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $types .= 'ii';

                $stmt = $koneksi->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = [
                        'id_produk' => $row['id_produk'],
                        'nama_produk' => $row['nama_produk'],
                        'harga' => $row['harga'],
                        'stok' => $row['stok'],
                        'nama_kategori' => $row['nama_kategori'] ?? 'Tidak ada kategori',
                        'foto' => $row['foto'],
                        'deskripsi' => $row['deskripsi']
                    ];
                }

                $response['success'] = true;
                $response['products'] = $products;
                $response['total'] = $totalProducts;
                $response['pages'] = ceil($totalProducts / $limit);
                break;

            case 'get_categories':
                $query = "SELECT * FROM kategori ORDER BY nama_kategori";
                $result = $koneksi->query($query);
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
                $response['success'] = true;
                $response['categories'] = $categories;
                break;

            case 'get_stock_stats':
                // Total produk
                $totalQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active'";
                $totalResult = $koneksi->query($totalQuery);
                $total = $totalResult->fetch_assoc()['total'];

                // Stok tersedia (> 10)
                $availableQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active' AND stok > 10";
                $availableResult = $koneksi->query($availableQuery);
                $available = $availableResult->fetch_assoc()['total'];

                // Stok menipis (1-10)
                $lowQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active' AND stok > 0 AND stok <= 10";
                $lowResult = $koneksi->query($lowQuery);
                $low = $lowResult->fetch_assoc()['total'];

                // Stok habis
                $outQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active' AND stok = 0";
                $outResult = $koneksi->query($outQuery);
                $out = $outResult->fetch_assoc()['total'];

                // Total nilai stok
                $valueQuery = "SELECT COALESCE(SUM(stok * harga), 0) as total_value FROM produk WHERE status = 'active'";
                $valueResult = $koneksi->query($valueQuery);
                $total_value = $valueResult->fetch_assoc()['total_value'];

                $response['success'] = true;
                $response['stats'] = [
                    'total' => $total,
                    'available' => $available,
                    'low' => $low,
                    'out' => $out,
                    'total_value' => $total_value
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
    <title>Laporan Stok - Sistem Penjualan Aksesoris</title>
    
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
        
        .stock-bar {
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        
        .stock-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .stock-low .stock-fill {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }
        
        .stock-out .stock-fill {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }
        
        .stock-available .stock-fill {
            background: linear-gradient(90deg, #10b981, #34d399);
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
                    <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Laporan Stok Produk</h1>
                    <p class="text-gray-400">Monitor dan kelola tingkat persediaan stok produk</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <button onclick="exportStockReport()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 flex items-center space-x-2">
                        <i class="fas fa-file-excel"></i>
                        <span>Export Excel</span>
                    </button>
                </div>
            </div>

            <!-- Stock Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8" id="stockStatsContainer">
                <!-- Stats will be loaded here -->
            </div>

            <!-- Stock Alerts -->
            <div id="stockAlertsContainer" class="mb-8">
                <!-- Alerts will be loaded here -->
            </div>

            <!-- Filters -->
            <div class="blackscrim-card p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status Stok</label>
                        <select id="stockFilter" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="all">Semua Stok</option>
                            <option value="low">Stok Menipis (≤ 10)</option>
                            <option value="out">Stok Habis</option>
                            <option value="available">Stok Tersedia (> 10)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategori</label>
                        <select id="categoryFilter" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="all">Semua Kategori</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button onclick="loadStockReport()" class="w-full bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
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

            <!-- Stock Report Table -->
            <div class="blackscrim-card p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-dark">
                        <thead>
                            <tr>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Produk</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Kategori</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Harga</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Stok</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Status</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Nilai Stok</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">
                                    <div class="flex justify-center">
                                        <div class="spinner"></div>
                                    </div>
                                    <p class="mt-2">Memuat data stok...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-center mt-6 space-y-4 sm:space-y-0">
                    <div class="text-gray-400 text-sm">
                        Menampilkan <span id="showingFrom">0</span> - <span id="showingTo">0</span> dari <span id="totalShowing">0</span> produk
                    </div>
                    <div class="flex space-x-2" id="pagination">
                        <!-- Pagination will be loaded here -->
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
        let currentPage = 1;
        let currentLimit = 15;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadStockStats();
            loadStockReport();
        });

        // Load categories for dropdown
        async function loadCategories() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_categories');
                
                const response = await fetch('produk_stok.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const categoryFilter = document.getElementById('categoryFilter');
                    
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id_kategori;
                        option.textContent = category.nama_kategori;
                        categoryFilter.appendChild(option);
                    });
                }
            } catch (error) {
                showToast('Error loading categories: ' + error.message, 'error');
            }
        }

        // Load stock statistics
        async function loadStockStats() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_stock_stats');
                
                const response = await fetch('produk_stok.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayStockStats(data.stats);
                    displayStockAlerts(data.stats);
                }
            } catch (error) {
                console.error('Error loading stock stats:', error);
            }
        }

        // Display stock statistics
        function displayStockStats(stats) {
            const container = document.getElementById('stockStatsContainer');
            
            container.innerHTML = `
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-boxes text-blue-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Produk</h3>
                    <p class="text-2xl font-bold text-white">${stats.total}</p>
                    <div class="mt-2 text-xs text-gray-400">Semua produk aktif</div>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-green-500/20 rounded-xl">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Tersedia</h3>
                    <p class="text-2xl font-bold text-white">${stats.available}</p>
                    <div class="mt-2 text-xs text-gray-400">Stok > 10</div>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-yellow-500/20 rounded-xl">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Menipis</h3>
                    <p class="text-2xl font-bold text-white">${stats.low}</p>
                    <div class="mt-2 text-xs text-gray-400">Stok 1-10</div>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-red-500/20 rounded-xl">
                            <i class="fas fa-times-circle text-red-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Habis</h3>
                    <p class="text-2xl font-bold text-white">${stats.out}</p>
                    <div class="mt-2 text-xs text-gray-400">Stok = 0</div>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-purple-500/20 rounded-xl">
                            <i class="fas fa-money-bill-wave text-purple-400 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Nilai Stok</h3>
                    <p class="text-2xl font-bold text-white">Rp ${stats.total_value.toLocaleString('id-ID')}</p>
                    <div class="mt-2 text-xs text-gray-400">Total nilai persediaan</div>
                </div>
            `;
        }

        // Display stock alerts
        function displayStockAlerts(stats) {
            const container = document.getElementById('stockAlertsContainer');
            let alerts = [];

            if (stats.out > 0) {
                alerts.push(`
                    <div class="blackscrim-card p-6 mb-4 border-l-4 border-red-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="p-3 bg-red-500/20 rounded-lg mr-4">
                                    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white mb-1">Stok Habis!</h3>
                                    <p class="text-gray-300 text-sm">
                                        ${stats.out} produk telah kehabisan stok dan tidak dapat dijual.
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-sm font-medium border border-red-500/30">
                                ${stats.out} Produk
                            </span>
                        </div>
                    </div>
                `);
            }

            if (stats.low > 0) {
                alerts.push(`
                    <div class="blackscrim-card p-6 mb-4 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="p-3 bg-yellow-500/20 rounded-lg mr-4">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white mb-1">Stok Menipis!</h3>
                                    <p class="text-gray-300 text-sm">
                                        ${stats.low} produk memiliki stok menipis (≤ 10). Segera lakukan restock.
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-sm font-medium border border-yellow-500/30">
                                ${stats.low} Produk
                            </span>
                        </div>
                    </div>
                `);
            }

            if (alerts.length === 0) {
                alerts.push(`
                    <div class="blackscrim-card p-6 mb-4 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-500/20 rounded-lg mr-4">
                                <i class="fas fa-check-circle text-green-400 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white mb-1">Stok Optimal</h3>
                                <p class="text-gray-300 text-sm">
                                    Semua produk memiliki stok yang cukup. Tidak ada produk dengan stok menipis atau habis.
                                </p>
                            </div>
                        </div>
                    </div>
                `);
            }

            container.innerHTML = alerts.join('');
        }

        // Load stock report
        async function loadStockReport(page = 1) {
            try {
                currentPage = page;
                
                const formData = new FormData();
                formData.append('action', 'get_stock_report');
                formData.append('stock_filter', document.getElementById('stockFilter').value);
                formData.append('category', document.getElementById('categoryFilter').value);
                formData.append('page', page);
                formData.append('limit', currentLimit);
                
                const response = await fetch('produk_stok.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayStockReport(data.products);
                    updatePagination(data.total, data.pages, page);
                } else {
                    showToast('Error loading stock report: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading stock report: ' + error.message, 'error');
            }
        }

        // Display stock report in table
        function displayStockReport(products) {
            const tbody = document.getElementById('stockTableBody');
            tbody.innerHTML = '';
            
            if (products.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-2"></i>
                            <p>Tidak ada produk ditemukan</p>
                            <p class="text-sm text-gray-500 mt-1">Coba ubah filter pencarian Anda</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            products.forEach(product => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-800/50';
                
                // Determine stock status
                let statusClass = '';
                let statusText = '';
                let statusColor = '';
                let stockPercentage = 0;
                
                if (product.stok === 0) {
                    statusClass = 'stock-out';
                    statusText = 'Habis';
                    statusColor = 'text-red-400';
                    stockPercentage = 0;
                } else if (product.stok <= 10) {
                    statusClass = 'stock-low';
                    statusText = 'Menipis';
                    statusColor = 'text-yellow-400';
                    stockPercentage = (product.stok / 10) * 100;
                } else {
                    statusClass = 'stock-available';
                    statusText = 'Tersedia';
                    statusColor = 'text-green-400';
                    stockPercentage = 100;
                }
                
                const photo = product.foto ? 
                    `<img src="../uploads/produk/${product.foto}" alt="${product.nama_produk}" class="w-12 h-12 object-cover rounded-lg">` :
                    `<div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>`;
                
                const stockValue = product.stok * product.harga;
                
                row.innerHTML = `
                    <td class="py-4 px-6">
                        <div class="flex items-center space-x-3">
                            ${photo}
                            <div>
                                <div class="font-medium text-white">${product.nama_produk}</div>
                                <div class="text-sm text-gray-400 truncate max-w-xs">${product.deskripsi || 'Tidak ada deskripsi'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-gray-300">${product.nama_kategori}</td>
                    <td class="py-4 px-6 text-white">Rp ${product.harga.toLocaleString('id-ID')}</td>
                    <td class="py-4 px-6">
                        <div class="flex items-center space-x-3">
                            <span class="font-semibold ${statusColor} min-w-12">${product.stok}</span>
                            <div class="stock-bar flex-1 max-w-24">
                                <div class="stock-fill ${statusClass}" style="width: ${stockPercentage}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6">
                        <span class="px-3 py-1 ${statusColor} bg-opacity-20 rounded-full text-xs font-medium border ${statusColor.replace('text', 'border')} border-opacity-30">
                            ${statusText}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-green-400 font-semibold">
                        Rp ${stockValue.toLocaleString('id-ID')}
                    </td>
                `;
                
                tbody.appendChild(row);
            });
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
                    <button onclick="loadStockReport(${currentPage - 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                `;
            }
            
            // Show limited page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    paginationHTML += `
                        <button class="px-3 py-2 bg-primary-500 border border-primary-500 rounded-lg text-white">
                            ${i}
                        </button>
                    `;
                } else {
                    paginationHTML += `
                        <button onclick="loadStockReport(${i})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                            ${i}
                        </button>
                    `;
                }
            }
            
            if (currentPage < totalPages) {
                paginationHTML += `
                    <button onclick="loadStockReport(${currentPage + 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }
            
            pagination.innerHTML = paginationHTML;
        }

        // Reset filter
        function resetFilter() {
            document.getElementById('stockFilter').value = 'all';
            document.getElementById('categoryFilter').value = 'all';
            loadStockReport(1);
        }

        // Export stock report
        function exportStockReport() {
            const stockFilter = document.getElementById('stockFilter').value;
            const category = document.getElementById('categoryFilter').value;
            
            window.open(`../kasir/export_stock_report.php?stock_filter=${stockFilter}&category=${category}`, '_blank');
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

        // Auto refresh when filters change
        document.getElementById('stockFilter').addEventListener('change', function() {
            loadStockReport(1);
            loadStockStats();
        });

        document.getElementById('categoryFilter').addEventListener('change', function() {
            loadStockReport(1);
        });
    </script>
</body>
</html>