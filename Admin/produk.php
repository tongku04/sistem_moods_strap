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
            case 'get_products':
                $search = $_POST['search'] ?? '';
                $category = $_POST['category'] ?? '';
                $status = $_POST['status'] ?? '';
                $page = $_POST['page'] ?? 1;
                $limit = $_POST['limit'] ?? 10;
                $offset = ($page - 1) * $limit;

                // Build query
                $query = "SELECT p.*, k.nama_kategori 
                         FROM produk p 
                         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                         WHERE 1=1";
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

                if (!empty($status) && $status !== 'all') {
                    $query .= " AND p.status = ?";
                    $params[] = $status;
                    $types .= 's';
                }

                // Get total count
                $countQuery = "SELECT COUNT(*) as total FROM produk p WHERE 1=1";
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
                if (!empty($status) && $status !== 'all') {
                    $countQuery .= " AND p.status = ?";
                    $countParams[] = $status;
                    $countTypes .= 's';
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
                $query .= " ORDER BY p.id_produk DESC LIMIT ? OFFSET ?";
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
                        'status' => $row['status'],
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

            case 'add_product':
                $nama_produk = trim($_POST['nama_produk']);
                $harga = (int)$_POST['harga'];
                $stok = (int)$_POST['stok'];
                $id_kategori = $_POST['id_kategori'] ?: NULL;
                $deskripsi = trim($_POST['deskripsi']);
                $status = $_POST['status'];

                // Handle file upload
                $foto = NULL;
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['foto']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        $upload_dir = '../uploads/produk/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                        $foto = 'produk_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $foto;
                        
                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                            // File uploaded successfully
                        } else {
                            throw new Exception('Gagal mengupload foto');
                        }
                    } else {
                        throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau GIF');
                    }
                }

                $query = "INSERT INTO produk (nama_produk, harga, stok, id_kategori, deskripsi, foto, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("siiisss", $nama_produk, $harga, $stok, $id_kategori, $deskripsi, $foto, $status);
                
                if ($stmt->execute()) {
                    $product_id = $koneksi->insert_id;
                    log_aktivitas('Tambah Produk', "Menambah produk: $nama_produk (ID: $product_id)");
                    $response['success'] = true;
                    $response['message'] = 'Produk berhasil ditambahkan';
                    $response['product_id'] = $product_id;
                } else {
                    throw new Exception('Gagal menambahkan produk: ' . $stmt->error);
                }
                $stmt->close();
                break;

            case 'edit_product':
                $id_produk = (int)$_POST['id_produk'];
                $nama_produk = trim($_POST['nama_produk']);
                $harga = (int)$_POST['harga'];
                $stok = (int)$_POST['stok'];
                $id_kategori = $_POST['id_kategori'] ?: NULL;
                $deskripsi = trim($_POST['deskripsi']);
                $status = $_POST['status'];

                // Get current product data
                $currentQuery = "SELECT foto, nama_produk FROM produk WHERE id_produk = ?";
                $currentStmt = $koneksi->prepare($currentQuery);
                $currentStmt->bind_param("i", $id_produk);
                $currentStmt->execute();
                $currentResult = $currentStmt->get_result();
                $currentProduct = $currentResult->fetch_assoc();
                $currentStmt->close();

                // Handle file upload
                $foto = $currentProduct['foto'];
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['foto']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        $upload_dir = '../uploads/produk/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Delete old photo if exists
                        if ($foto && file_exists($upload_dir . $foto)) {
                            unlink($upload_dir . $foto);
                        }
                        
                        $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                        $foto = 'produk_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $foto;
                        
                        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                            throw new Exception('Gagal mengupload foto');
                        }
                    } else {
                        throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau GIF');
                    }
                }

                $query = "UPDATE produk SET nama_produk = ?, harga = ?, stok = ?, id_kategori = ?, 
                         deskripsi = ?, foto = ?, status = ? WHERE id_produk = ?";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("siiisssi", $nama_produk, $harga, $stok, $id_kategori, $deskripsi, $foto, $status, $id_produk);
                
                if ($stmt->execute()) {
                    log_aktivitas('Edit Produk', "Mengedit produk: $nama_produk (ID: $id_produk)");
                    $response['success'] = true;
                    $response['message'] = 'Produk berhasil diupdate';
                } else {
                    throw new Exception('Gagal mengupdate produk: ' . $stmt->error);
                }
                $stmt->close();
                break;

            case 'delete_product':
                $id_produk = (int)$_POST['id_produk'];

                // Get product data for log and photo deletion
                $productQuery = "SELECT nama_produk, foto FROM produk WHERE id_produk = ?";
                $productStmt = $koneksi->prepare($productQuery);
                $productStmt->bind_param("i", $id_produk);
                $productStmt->execute();
                $productResult = $productStmt->get_result();
                $product = $productResult->fetch_assoc();
                $productStmt->close();

                // Delete photo if exists
                if ($product['foto']) {
                    $photo_path = '../uploads/produk/' . $product['foto'];
                    if (file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                }

                $query = "DELETE FROM produk WHERE id_produk = ?";
                $stmt = $koneksi->prepare($query);
                $stmt->bind_param("i", $id_produk);
                
                if ($stmt->execute()) {
                    log_aktivitas('Hapus Produk', "Menghapus produk: {$product['nama_produk']} (ID: $id_produk)");
                    $response['success'] = true;
                    $response['message'] = 'Produk berhasil dihapus';
                } else {
                    throw new Exception('Gagal menghapus produk: ' . $stmt->error);
                }
                $stmt->close();
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
    <title>Manajemen Produk - Sistem Penjualan Aksesoris</title>
    
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
                    <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Manajemen Produk</h1>
                    <p class="text-gray-400">Kelola data produk aksesoris toko</p>
                </div>
                
                <button onclick="showAddModal()" class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:scale-105 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Produk</span>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-primary-500/20 rounded-xl">
                            <i class="fas fa-box text-primary-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-primary-400 bg-primary-500/20 px-3 py-1 rounded-full border border-primary-500/30">
                            Total
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Total Produk</h3>
                    <p id="totalProducts" class="text-2xl font-bold text-white">0</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-green-500/20 rounded-xl">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-green-400 bg-green-500/20 px-3 py-1 rounded-full border border-green-500/30">
                            Aktif
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Produk Aktif</h3>
                    <p id="activeProducts" class="text-2xl font-bold text-white">0</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-yellow-500/20 rounded-xl">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-yellow-400 bg-yellow-500/20 px-3 py-1 rounded-full border border-yellow-500/30">
                            Stok Rendah
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Stok < 10</h3>
                    <p id="lowStockProducts" class="text-2xl font-bold text-white">0</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 bg-red-500/20 rounded-xl">
                            <i class="fas fa-times-circle text-red-400 text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-red-400 bg-red-500/20 px-3 py-1 rounded-full border border-red-500/30">
                            Nonaktif
                        </span>
                    </div>
                    <h3 class="text-gray-400 text-sm font-medium mb-1">Produk Nonaktif</h3>
                    <p id="inactiveProducts" class="text-2xl font-bold text-white">0</p>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="blackscrim-card p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Cari Produk</label>
                        <input type="text" id="searchInput" placeholder="Nama produk atau deskripsi..." 
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategori</label>
                        <select id="categoryFilter" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="all">Semua Kategori</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                        <select id="statusFilter" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="all">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button onclick="loadProducts()" class="w-full bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="blackscrim-card p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-dark">
                        <thead>
                            <tr>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Produk</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Harga</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Stok</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Kategori</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Status</th>
                                <th class="py-4 px-6 text-left text-gray-300 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
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
                        Menampilkan <span id="showingFrom">0</span> - <span id="showingTo">0</span> dari <span id="totalShowing">0</span> produk
                    </div>
                    <div class="flex space-x-2" id="pagination">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="blackscrim-card p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-semibold text-white">Tambah Produk Baru</h3>
                <button onclick="hideModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="editProductId" name="id_produk">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nama Produk *</label>
                        <input type="text" id="nama_produk" name="nama_produk" required
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Harga *</label>
                        <input type="number" id="harga" name="harga" min="0" required
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Stok *</label>
                        <input type="number" id="stok" name="stok" min="0" required
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategori</label>
                        <select id="id_kategori" name="id_kategori"
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                             class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Foto Produk</label>
                        <input type="file" id="foto" name="foto" accept="image/*"
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-500 file:text-white hover:file:bg-primary-600">
                        <div id="currentPhoto" class="mt-2 hidden">
                            <p class="text-sm text-gray-400">Foto saat ini:</p>
                            <img id="currentPhotoImg" class="mt-1 w-20 h-20 object-cover rounded-lg">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status *</label>
                        <select id="status" name="status" required
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="hideModal()" class="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
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
                    const categorySelect = document.getElementById('id_kategori');
                    const categoryFilter = document.getElementById('categoryFilter');
                    
                    // Clear existing options except the first one
                    categorySelect.innerHTML = '<option value="">Pilih Kategori</option>';
                    categoryFilter.innerHTML = '<option value="all">Semua Kategori</option>';
                    
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id_kategori;
                        option.textContent = category.nama_kategori;
                        categorySelect.appendChild(option.cloneNode(true));
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
                formData.append('status', document.getElementById('statusFilter').value);
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
                    updateStats(data.products);
                } else {
                    showToast('Error loading products: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error loading products: ' + error.message, 'error');
            }
        }

        // Display products in table
        function displayProducts(products) {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';
            
            if (products.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-2"></i>
                            <p>Tidak ada produk ditemukan</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            products.forEach(product => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-800/50';
                
                const photo = product.foto ? 
                    `<img src="../uploads/produk/${product.foto}" alt="${product.nama_produk}" class="w-12 h-12 object-cover rounded-lg">` :
                    `<div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>`;
                
                const statusBadge = product.status === 'active' ? 
                    '<span class="px-2 py-1 bg-green-500/20 text-green-400 rounded-full text-xs border border-green-500/30">Aktif</span>' :
                    '<span class="px-2 py-1 bg-red-500/20 text-red-400 rounded-full text-xs border border-red-500/30">Nonaktif</span>';
                
                const stockClass = product.stok < 10 ? 'text-red-400' : 'text-green-400';
                const stockIcon = product.stok < 10 ? 'fa-exclamation-triangle' : 'fa-check';
                
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
                    <td class="py-4 px-6 text-white">Rp ${product.harga.toLocaleString('id-ID')}</td>
                    <td class="py-4 px-6">
                        <div class="flex items-center space-x-2 ${stockClass}">
                            <i class="fas ${stockIcon}"></i>
                            <span>${product.stok}</span>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-gray-300">${product.nama_kategori}</td>
                    <td class="py-4 px-6">${statusBadge}</td>
                    <td class="py-4 px-6">
                        <div class="flex space-x-2">
                            <button onclick="editProduct(${product.id_produk})" class="p-2 text-blue-400 hover:bg-blue-500/20 rounded-lg transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteProduct(${product.id_produk}, '${product.nama_produk}')" class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
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
            
            // Update stats
            document.getElementById('totalProducts').textContent = total;
            
            // Build pagination buttons
            let paginationHTML = '';
            
            if (currentPage > 1) {
                paginationHTML += `
                    <button onclick="loadProducts(${currentPage - 1})" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-700">
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

        // Update statistics
        function updateStats(products) {
            const activeProducts = products.filter(p => p.status === 'active').length;
            const inactiveProducts = products.filter(p => p.status === 'inactive').length;
            const lowStockProducts = products.filter(p => p.stok < 10).length;
            
            document.getElementById('activeProducts').textContent = activeProducts;
            document.getElementById('inactiveProducts').textContent = inactiveProducts;
            document.getElementById('lowStockProducts').textContent = lowStockProducts;
        }

        // Show add modal
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
            document.getElementById('productForm').reset();
            document.getElementById('editProductId').value = '';
            document.getElementById('currentPhoto').classList.add('hidden');
            document.getElementById('productModal').classList.remove('hidden');
        }

        // Show edit modal
        async function editProduct(id) {
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
                    document.getElementById('modalTitle').textContent = 'Edit Produk';
                    document.getElementById('editProductId').value = product.id_produk;
                    document.getElementById('nama_produk').value = product.nama_produk;
                    document.getElementById('harga').value = product.harga;
                    document.getElementById('stok').value = product.stok;
                    document.getElementById('id_kategori').value = product.id_kategori || '';
                    document.getElementById('deskripsi').value = product.deskripsi || '';
                    document.getElementById('status').value = product.status;
                    
                    // Show current photo if exists
                    const currentPhotoDiv = document.getElementById('currentPhoto');
                    const currentPhotoImg = document.getElementById('currentPhotoImg');
                    if (product.foto) {
                        currentPhotoImg.src = `../uploads/produk/${product.foto}`;
                        currentPhotoDiv.classList.remove('hidden');
                    } else {
                        currentPhotoDiv.classList.add('hidden');
                    }
                    
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

        // Delete product
        async function deleteProduct(id, name) {
            if (confirm(`Apakah Anda yakin ingin menghapus produk "${name}"?`)) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete_product');
                    formData.append('id_produk', id);
                    
                    const response = await fetch('produk.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('Produk berhasil dihapus', 'success');
                        loadProducts(currentPage);
                    } else {
                        showToast('Error deleting product: ' + data.message, 'error');
                    }
                } catch (error) {
                    showToast('Error deleting product: ' + error.message, 'error');
                }
            }
        }

        // Handle form submission
        document.getElementById('productForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isEdit = document.getElementById('editProductId').value !== '';
            formData.append('action', isEdit ? 'edit_product' : 'add_product');
            
            try {
                const response = await fetch('produk.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    hideModal();
                    loadProducts(currentPage);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        });

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
    </script>
</body>
</html>