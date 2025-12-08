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
            case 'get_products':
                $search = $_POST['search'] ?? '';
                $category = $_POST['category'] ?? '';
                $stock_filter = $_POST['stock_filter'] ?? '';
                $page = $_POST['page'] ?? 1;
                $limit = $_POST['limit'] ?? 12;
                $offset = ($page - 1) * $limit;

                // Build query
                $query = "SELECT p.*, k.nama_kategori 
                         FROM produk p 
                         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                         WHERE p.status = 'active'";
                $params = [];
                $types = '';

                if (!empty($search)) {
                    $query .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $types .= 'ss';
                }

                if (!empty($category) && $category !== 'all') {
                    $query .= " AND p.id_kategori = ?";
                    $params[] = $category;
                    $types .= 'i';
                }

                // Filter stok
                if (!empty($stock_filter)) {
                    if ($stock_filter === 'available') {
                        $query .= " AND p.stok > 0";
                    } elseif ($stock_filter === 'low') {
                        $query .= " AND p.stok > 0 AND p.stok <= 10";
                    } elseif ($stock_filter === 'out') {
                        $query .= " AND p.stok = 0";
                    }
                }

                // Get total count
                $countQuery = "SELECT COUNT(*) as total FROM produk p WHERE p.status = 'active'";
                $countParams = [];
                $countTypes = '';

                if (!empty($search)) {
                    $countQuery .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
                    $countParams[] = $searchTerm;
                    $countParams[] = $searchTerm;
                    $countTypes .= 'ss';
                }
                if (!empty($category) && $category !== 'all') {
                    $countQuery .= " AND p.id_kategori = ?";
                    $countParams[] = $category;
                    $countTypes .= 'i';
                }
                if (!empty($stock_filter)) {
                    if ($stock_filter === 'available') {
                        $countQuery .= " AND p.stok > 0";
                    } elseif ($stock_filter === 'low') {
                        $countQuery .= " AND p.stok > 0 AND p.stok <= 10";
                    } elseif ($stock_filter === 'out') {
                        $countQuery .= " AND p.stok = 0";
                    }
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
                $query .= " ORDER BY p.stok ASC, p.nama_produk ASC LIMIT ? OFFSET ?";
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

            case 'get_product_detail':
                $id_produk = (int)$_POST['id_produk'];
                $query = "SELECT p.*, k.nama_kategori 
                         FROM produk p 
                         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                         WHERE p.id_produk = ?";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("i", $id_produk);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $stmt->close();

                if ($product) {
                    $response['success'] = true;
                    $response['product'] = $product;
                } else {
                    throw new Exception('Produk tidak ditemukan');
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
    <title>Katalog Produk - Sistem Penjualan Aksesoris</title>
    
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
        
        .product-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .stock-low {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-color: rgba(245, 158, 11, 0.3);
        }
        
        .stock-out {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-color: rgba(239, 68, 68, 0.3);
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
                    <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Katalog Produk</h1>
                    <p class="text-gray-400">Kelola dan lihat katalog produk aksesoris</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <!-- Quick Stats -->
                    <div class="hidden md:flex items-center space-x-4 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-gray-400">Tersedia</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <span class="text-gray-400">Menipis</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-gray-400">Habis</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <?php
                // Query statistik produk
                $totalProductsQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active'";
                $availableProductsQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active' AND stok > 0";
                $lowStockProductsQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active' AND stok > 0 AND stok <= 10";
                $outOfStockProductsQuery = "SELECT COUNT(*) as total FROM produk WHERE status = 'active' AND stok = 0";
                
                $totalProducts = $koneksi->query($totalProductsQuery)->fetch_assoc()['total'];
                $availableProducts = $koneksi->query($availableProductsQuery)->fetch_assoc()['total'];
                $lowStockProducts = $koneksi->query($lowStockProductsQuery)->fetch_assoc()['total'];
                $outOfStockProducts = $koneksi->query($outOfStockProductsQuery)->fetch_assoc()['total'];
                ?>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-boxes text-blue-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-blue-400 bg-blue-500/20 px-3 py-1 rounded-full border border-blue-500/30">
                            Total
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Produk</h3>
                    <p class="text-2xl font-bold text-white"><?php echo $totalProducts; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-green-500/20 rounded-xl">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-green-400 bg-green-500/20 px-3 py-1 rounded-full border border-green-500/30">
                            Tersedia
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Tersedia</h3>
                    <p class="text-2xl font-bold text-white"><?php echo $availableProducts; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-yellow-500/20 rounded-xl">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-yellow-400 bg-yellow-500/20 px-3 py-1 rounded-full border border-yellow-500/30">
                            Menipis
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Menipis</h3>
                    <p class="text-2xl font-bold text-white"><?php echo $lowStockProducts; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-red-500/20 rounded-xl">
                            <i class="fas fa-times-circle text-red-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-red-400 bg-red-500/20 px-3 py-1 rounded-full border border-red-500/30">
                            Habis
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok Habis</h3>
                    <p class="text-2xl font-bold text-white"><?php echo $outOfStockProducts; ?></p>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="blackscrim-card p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Cari Produk</label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Nama produk atau deskripsi..." 
                                   class="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategori</label>
                        <select id="categoryFilter" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="all">Semua Kategori</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status Stok</label>
                        <select id="stockFilter" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="all">Semua Stok</option>
                            <option value="available">Tersedia</option>
                            <option value="low">Menipis (≤ 10)</option>
                            <option value="out">Habis</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button onclick="loadProducts()" class="w-full bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="blackscrim-card p-6">
                <div id="productsContainer">
                    <div class="flex justify-center items-center py-12">
                        <div class="spinner"></div>
                        <p class="ml-3 text-gray-400">Memuat produk...</p>
                    </div>
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

    <!-- Product Detail Modal -->
    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="blackscrim-card p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-white">Detail Produk</h3>
                <button onclick="hideModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="productDetailContent">
                <!-- Product detail will be loaded here -->
            </div>
            
            <div class="flex justify-end mt-6">
                <button onclick="hideModal()" class="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors">
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
        let currentLimit = 12;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadProducts();
        });

        // Load categories for dropdown
        async function loadCategories() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_categories');
                
                const response = await fetch('produk.php', {
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

        // Load products
        async function loadProducts(page = 1) {
            try {
                currentPage = page;
                
                const formData = new FormData();
                formData.append('action', 'get_products');
                formData.append('search', document.getElementById('searchInput').value);
                formData.append('category', document.getElementById('categoryFilter').value);
                formData.append('stock_filter', document.getElementById('stockFilter').value);
                formData.append('page', page);
                formData.append('limit', currentLimit);
                
                const response = await fetch('produk.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayProducts(data.products);
                    updatePagination(data.total, data.pages, page);
                } else {
                    showToast('Error loading products: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading products: ' + error.message, 'error');
            }
        }

        // Display products in grid
        function displayProducts(products) {
            const container = document.getElementById('productsContainer');
            
            if (products.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-box-open text-4xl text-gray-500 mb-3"></i>
                        <p class="text-gray-400 text-lg">Tidak ada produk ditemukan</p>
                        <p class="text-gray-500 text-sm mt-1">Coba ubah filter pencarian Anda</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">';
            
            products.forEach(product => {
                const photo = product.foto ? 
                    `<img src="../uploads/produk/${product.foto}" alt="${product.nama_produk}" class="w-full h-48 object-cover rounded-lg">` :
                    `<div class="w-full h-48 bg-gray-700 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-gray-400 text-3xl"></i>
                    </div>`;
                
                // Determine stock status and styling
                let stockClass = '';
                let stockText = '';
                let stockColor = '';
                
                if (product.stok === 0) {
                    stockClass = 'stock-out';
                    stockText = 'Stok Habis';
                    stockColor = 'text-red-400';
                } else if (product.stok <= 10) {
                    stockClass = 'stock-low';
                    stockText = 'Stok Menipis';
                    stockColor = 'text-yellow-400';
                } else {
                    stockText = 'Stok Tersedia';
                    stockColor = 'text-green-400';
                }
                
                html += `
                    <div class="blackscrim-card product-card p-4 rounded-xl ${stockClass}">
                        <div class="relative">
                            ${photo}
                            <div class="absolute top-2 right-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${stockColor} bg-gray-900/80 border border-gray-700">
                                    ${stockText}
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h3 class="font-semibold text-white text-lg mb-1 truncate">${product.nama_produk}</h3>
                            <p class="text-gray-400 text-sm mb-2 line-clamp-2">${product.deskripsi || 'Tidak ada deskripsi'}</p>
                            
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-primary-400 font-bold text-lg">Rp ${product.harga.toLocaleString('id-ID')}</span>
                                <span class="text-gray-300 text-sm">Stok: ${product.stok}</span>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm text-gray-400 mb-3">
                                <span>${product.nama_kategori}</span>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="showProductDetail(${product.id_produk})" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
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
                    <button onclick="loadProducts(${currentPage - 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
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
                        <button onclick="loadProducts(${i})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                            ${i}
                        </button>
                    `;
                }
            }
            
            if (currentPage < totalPages) {
                paginationHTML += `
                    <button onclick="loadProducts(${currentPage + 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }
            
            pagination.innerHTML = paginationHTML;
        }

        // Show product detail
        async function showProductDetail(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_product_detail');
                formData.append('id_produk', id);
                
                const response = await fetch('produk.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const product = data.product;
                    
                    // Determine stock status
                    let stockStatus = '';
                    let stockColor = '';
                    if (product.stok === 0) {
                        stockStatus = 'Stok Habis';
                        stockColor = 'text-red-400';
                    } else if (product.stok <= 10) {
                        stockStatus = 'Stok Menipis';
                        stockColor = 'text-yellow-400';
                    } else {
                        stockStatus = 'Stok Tersedia';
                        stockColor = 'text-green-400';
                    }
                    
                    const photo = product.foto ? 
                        `<img src="../uploads/produk/${product.foto}" alt="${product.nama_produk}" class="w-full h-64 object-cover rounded-lg mb-4">` :
                        `<div class="w-full h-64 bg-gray-700 rounded-lg flex items-center justify-center mb-4">
                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                        </div>`;
                    
                    const detailHTML = `
                        ${photo}
                        <div class="space-y-3">
                            <div>
                                <h4 class="text-lg font-semibold text-white">${product.nama_produk}</h4>
                                <p class="text-gray-400 text-sm">${product.nama_kategori || 'Tidak ada kategori'}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-400 text-sm">Harga</p>
                                    <p class="text-primary-400 font-bold text-lg">Rp ${product.harga.toLocaleString('id-ID')}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-sm">Stok</p>
                                    <p class="${stockColor} font-semibold">${product.stok} (${stockStatus})</p>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-gray-400 text-sm mb-2">Deskripsi</p>
                                <p class="text-gray-300">${product.deskripsi || 'Tidak ada deskripsi'}</p>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('productDetailContent').innerHTML = detailHTML;
                    document.getElementById('productModal').classList.remove('hidden');
                } else {
                    showToast('Error loading product details: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading product details: ' + error.message, 'error');
            }
        }

        // Hide modal
        function hideModal() {
            document.getElementById('productModal').classList.add('hidden');
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

        // Search on enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadProducts(1);
            }
        });

        // Auto search with debounce
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadProducts(1);
            }, 500);
        });
    </script>
</body>
</html>