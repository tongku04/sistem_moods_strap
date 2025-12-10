<?php
session_start();
include '../config/koneksi.php';

// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal");
}

// Cek apakah user sudah login sebagai pelanggan
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pelanggan') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user']['id_user'];
$username = $_SESSION['user']['username'];

// Ambil data pelanggan berdasarkan id_user
$query_pelanggan = "SELECT * FROM pelanggan WHERE id_user = ?";
$stmt_pelanggan = mysqli_prepare($koneksi, $query_pelanggan);
mysqli_stmt_bind_param($stmt_pelanggan, "i", $user_id);
mysqli_stmt_execute($stmt_pelanggan);
$result_pelanggan = mysqli_stmt_get_result($stmt_pelanggan);
$data_pelanggan = mysqli_fetch_assoc($result_pelanggan);

if (!$data_pelanggan) {
    die("Data pelanggan tidak ditemukan");
}

$id_pelanggan = $data_pelanggan['id_pelanggan'];

// ====================== PROSES BATAL PESANAN ======================
if (isset($_POST['action']) && $_POST['action'] == 'batalkan_pesanan' && isset($_POST['order_id'])) {
    $order_id = mysqli_real_escape_string($koneksi, $_POST['order_id']);
    
    // Cek apakah pesanan milik pelanggan ini dan statusnya masih pending
    $check_query = "SELECT * FROM penjualan 
                    WHERE id_penjualan = ? 
                    AND id_pelanggan = ? 
                    AND status_pesanan = 'pending'";
    $stmt_check = mysqli_prepare($koneksi, $check_query);
    mysqli_stmt_bind_param($stmt_check, "ii", $order_id, $id_pelanggan);
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Pesanan tidak ditemukan atau tidak dapat dibatalkan'
        ]);
        exit;
    }
    
    // Update status pesanan menjadi dibatalkan
    $update_query = "UPDATE penjualan 
                     SET status_pesanan = 'dibatalkan' 
                     WHERE id_penjualan = ? 
                     AND id_pelanggan = ?";
    
    $stmt_update = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($stmt_update, "ii", $order_id, $id_pelanggan);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Kembalikan stok produk
        $detail_query = "SELECT dp.id_produk, dp.jumlah 
                         FROM detail_penjualan dp 
                         WHERE dp.id_penjualan = ?";
        $stmt_detail = mysqli_prepare($koneksi, $detail_query);
        mysqli_stmt_bind_param($stmt_detail, "i", $order_id);
        mysqli_stmt_execute($stmt_detail);
        $detail_result = mysqli_stmt_get_result($stmt_detail);
        
        while ($detail = mysqli_fetch_assoc($detail_result)) {
            $id_produk = $detail['id_produk'];
            $jumlah = $detail['jumlah'];
            
            // Update stok produk
            $update_stok = "UPDATE produk 
                           SET stok = stok + ? 
                           WHERE id_produk = ?";
            $stmt_stok = mysqli_prepare($koneksi, $update_stok);
            mysqli_stmt_bind_param($stmt_stok, "ii", $jumlah, $id_produk);
            mysqli_stmt_execute($stmt_stok);
        }
        
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membatalkan pesanan: ' . mysqli_error($koneksi)]);
    }
    exit;
}

// ====================== PROSES KONFIRMASI PENERIMAAN ======================
if (isset($_POST['action']) && $_POST['action'] == 'konfirmasi_penerimaan' && isset($_POST['order_id'])) {
    $order_id = mysqli_real_escape_string($koneksi, $_POST['order_id']);
    
    // Cek apakah pesanan milik pelanggan ini dan statusnya dikirim
    $check_query = "SELECT * FROM penjualan 
                    WHERE id_penjualan = ? 
                    AND id_pelanggan = ? 
                    AND status_pesanan = 'dikirim'";
    $stmt_check = mysqli_prepare($koneksi, $check_query);
    mysqli_stmt_bind_param($stmt_check, "ii", $order_id, $id_pelanggan);
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Pesanan tidak ditemukan atau tidak dapat dikonfirmasi'
        ]);
        exit;
    }
    
    // Update status pesanan menjadi selesai
    $update_query = "UPDATE penjualan 
                     SET status_pesanan = 'selesai' 
                     WHERE id_penjualan = ? 
                     AND id_pelanggan = ?";
    
    $stmt_update = mysqli_prepare($koneksi, $update_query);
    mysqli_stmt_bind_param($stmt_update, "ii", $order_id, $id_pelanggan);
    
    if (mysqli_stmt_execute($stmt_update)) {
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dikonfirmasi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengkonfirmasi pesanan: ' . mysqli_error($koneksi)]);
    }
    exit;
}

// ====================== PROSES KONFIRMASI PEMBAYARAN WHATSAPP ======================
if (isset($_POST['action']) && $_POST['action'] == 'konfirmasi_pembayaran_wa' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    // Cek apakah pesanan milik pelanggan ini dan statusnya pending
    $check_query = "SELECT p.*, pl.nama_lengkap, pl.email, pl.telepon 
                    FROM penjualan p
                    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                    WHERE p.id_penjualan = ? 
                    AND p.id_pelanggan = ? 
                    AND p.status_pesanan = 'pending' 
                    AND p.status_pembayaran = 'pending'";
    
    $stmt_check = mysqli_prepare($koneksi, $check_query);
    if (!$stmt_check) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error preparing statement: ' . mysqli_error($koneksi)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt_check, "ii", $order_id, $id_pelanggan);
    
    if (!mysqli_stmt_execute($stmt_check)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error executing query: ' . mysqli_error($koneksi)
        ]);
        exit;
    }
    
    $check_result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Pesanan tidak ditemukan atau tidak dapat melakukan pembayaran'
        ]);
        exit;
    }
    
    $pesanan = mysqli_fetch_assoc($check_result);
    $no_pesanan = str_pad($pesanan['id_penjualan'], 6, '0', STR_PAD_LEFT);
    $total_harga = number_format($pesanan['total'], 0, ',', '.');
    $nama_pelanggan = $pesanan['nama_lengkap'] ? $pesanan['nama_lengkap'] : $username;
    $no_telepon = $pesanan['telepon'] ? $pesanan['telepon'] : '-';
    
    // Buat pesan WhatsApp
    $whatsapp_number = "082215254298"; // Ganti dengan nomor WhatsApp admin
    
    // Format pesan
    $message = "Halo Admin Moods Strap,%0A%0A";
    $message .= "Saya ingin mengirimkan bukti pembayaran untuk pesanan:%0A";
    $message .= "📦 *No. Pesanan:* #$no_pesanan%0A";
    $message .= "👤 *Nama Pelanggan:* $nama_pelanggan%0A";
    $message .= "📱 *No. Telepon:* $no_telepon%0A";
    $message .= "✉️ *Email:* " . ($pesanan['email'] ? $pesanan['email'] : '-') . "%0A";
    $message .= "💰 *Total Pembayaran:* Rp $total_harga%0A";
    $message .= "📅 *Tanggal Pesan:* " . date('d M Y H:i', strtotime($pesanan['tanggal'])) . "%0A%0A";
    $message .= "Bukti pembayaran akan saya kirim di chat ini.%0A%0A";
    $message .= "Mohon konfirmasi setelah menerima pembayaran. Terima kasih!%0A%0A";
    $message .= "_Pesan ini dikirim otomatis dari sistem Moods Strap_";
    
    $whatsapp_url = "https://wa.me/$whatsapp_number?text=" . $message;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Arahkan ke WhatsApp untuk mengirim bukti pembayaran',
        'whatsapp_url' => $whatsapp_url
    ]);
    exit;
}

// ====================== TAMPILAN HALAMAN PESANAN ======================
// Ambil parameter filter
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : 'all';
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Query untuk mengambil pesanan dengan prepared statement
$query_pesanan = "SELECT p.*, 
                         COUNT(dp.id_detail) as total_items,
                         SUM(dp.jumlah) as total_quantity
                  FROM penjualan p 
                  LEFT JOIN detail_penjualan dp ON p.id_penjualan = dp.id_penjualan
                  WHERE p.id_pelanggan = ?";

$params = array($id_pelanggan);
$types = "i";

// Tambahkan filter status
if ($status_filter !== 'all') {
    $query_pesanan .= " AND p.status_pesanan = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Tambahkan pencarian
if (!empty($search_query)) {
    $query_pesanan .= " AND p.id_penjualan = ?";
    $params[] = intval($search_query);
    $types .= "i";
}

$query_pesanan .= " GROUP BY p.id_penjualan ORDER BY p.tanggal DESC";

$stmt_pesanan = mysqli_prepare($koneksi, $query_pesanan);
if ($stmt_pesanan) {
    mysqli_stmt_bind_param($stmt_pesanan, $types, ...$params);
    mysqli_stmt_execute($stmt_pesanan);
    $result_pesanan = mysqli_stmt_get_result($stmt_pesanan);
} else {
    die("Error preparing statement: " . mysqli_error($koneksi));
}

// Hitung statistik pesanan dengan prepared statement
$query_stats = "SELECT 
    COUNT(*) as total_pesanan,
    SUM(CASE WHEN status_pesanan = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status_pesanan = 'diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status_pesanan = 'dikirim' THEN 1 ELSE 0 END) as dikirim,
    SUM(CASE WHEN status_pesanan = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status_pesanan = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
FROM penjualan 
WHERE id_pelanggan = ?";

$stmt_stats = mysqli_prepare($koneksi, $query_stats);
mysqli_stmt_bind_param($stmt_stats, "i", $id_pelanggan);
mysqli_stmt_execute($stmt_stats);
$result_stats = mysqli_stmt_get_result($stmt_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Set default values if null
if (!$stats) {
    $stats = [
        'total_pesanan' => 0,
        'pending' => 0,
        'diproses' => 0,
        'dikirim' => 0,
        'selesai' => 0,
        'dibatalkan' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Moods Strap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .pink-bg {
            background-color: #ff69b4;
        }
        
        .pink-text {
            color: #ff69b4;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #ff69b4;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .mobile-menu.active {
            transform: translateX(0);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-diproses { background-color: #dbeafe; color: #1e40af; }
        .status-dikirim { background-color: #d1fae5; color: #065f46; }
        .status-selesai { background-color: #dcfce7; color: #166534; }
        .status-dibatalkan { background-color: #fee2e2; color: #991b1b; }
        
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20px;
            height: 20px;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .empty-state {
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        }
        
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        
        .payment-info {
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
   <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-2xl font-bold pink-text flex items-center">
                    <div class="flex items-center justify-center mr-2">
                        <img src="WhatsApp_Image_2025-11-13_at_08.21.58_d2b62406-removebg-preview.png" class="w-11 h-11 rounded-full object cover" alt="Logo">
                    </div>
                    Moods <span class="text-gray-800">Strap</span>
                </a>
            </div>
            
            <nav class="hidden md:flex space-x-8">
                <a href="index.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Beranda</a>
                <a href="produk.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Produk</a>
                <a href="keranjang.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Keranjang</a>
                <a href="pesanan.php" class="text-pink-500 font-semibold transition">Pesanan</a>
            </nav>
            
            <div class="flex items-center space-x-4">
                <!-- User Menu -->
                <div class="relative group">
                    <button class="flex items-center space-x-2 p-2 text-gray-700 hover:text-pink-500 transition">
                        <i class="fas fa-user"></i>
                        <span class="hidden md:inline"><?php echo htmlspecialchars($username); ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                        <a href="pesanan.php" class="block px-4 py-2 text-pink-500 bg-pink-50 transition">
                            <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                        </a>
                        <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>

                <button id="mobile-menu-button" class="md:hidden p-2 text-gray-700 hover:text-pink-500 transition">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
<!-- Mobile Menu -->
<div id="mobile-menu" class="mobile-menu fixed inset-y-0 left-0 w-64 bg-white shadow-lg z-50 md:hidden">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
        <h2 class="text-xl font-bold pink-text">Moods <span class="text-gray-800">Strap</span></h2>
        <button id="close-mobile-menu" class="p-2 text-gray-500 hover:text-pink-500">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="p-4 bg-white">
        <a href="index.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition mb-2">
            <i class="fas fa-home mr-3"></i>Beranda
        </a>
        <a href="produk.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
            <i class="fas fa-box mr-3"></i>Produk
        </a>
        <a href="tentang.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
            <i class="fas fa-info-circle mr-3"></i>Tentang Kami
        </a>
        <a href="kontak.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition">
            <i class="fas fa-envelope mr-3"></i>Kontak
        </a>
    </nav>
</div>
    </header>

    <!-- Breadcrumb -->
    <section class="bg-white border-b border-gray-100 py-4">
        <div class="container mx-auto px-4">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-pink-500">
                            <i class="fas fa-home mr-2"></i>
                            Beranda
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <span class="ml-1 text-sm font-medium text-pink-500 md:ml-2">Pesanan Saya</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Pesanan Saya</h1>
            <p class="text-gray-600 mb-8">Kelola dan lacak pesanan Anda di sini</p>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-4 text-center">
                    <div class="text-2xl font-bold pink-text mb-1"><?php echo $stats['total_pesanan']; ?></div>
                    <div class="text-sm text-gray-600">Total Pesanan</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600 mb-1"><?php echo $stats['pending']; ?></div>
                    <div class="text-sm text-gray-600">Pending</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1"><?php echo $stats['diproses']; ?></div>
                    <div class="text-sm text-gray-600">Diproses</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600 mb-1"><?php echo $stats['dikirim']; ?></div>
                    <div class="text-sm text-gray-600">Dikirim</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-4 text-center">
                    <div class="text-2xl font-bold text-emerald-600 mb-1"><?php echo $stats['selesai']; ?></div>
                    <div class="text-sm text-gray-600">Selesai</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600 mb-1"><?php echo $stats['dibatalkan']; ?></div>
                    <div class="text-sm text-gray-600">Dibatalkan</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Search Form -->
                    <form method="GET" class="flex-1">
                        <div class="relative max-w-md">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Cari nomor pesanan..." 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <button type="submit" class="absolute right-3 top-3 text-gray-400 hover:text-pink-500">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if ($status_filter !== 'all'): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Status Filter -->
                    <div class="flex flex-wrap gap-2">
                        <a href="pesanan.php?status=all<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="px-4 py-2 rounded-xl font-medium transition <?php echo $status_filter === 'all' ? 'gradient-bg text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Semua
                        </a>
                        <a href="pesanan.php?status=pending<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="px-4 py-2 rounded-xl font-medium transition <?php echo $status_filter === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Pending
                        </a>
                        <a href="pesanan.php?status=diproses<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="px-4 py-2 rounded-xl font-medium transition <?php echo $status_filter === 'diproses' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Diproses
                        </a>
                        <a href="pesanan.php?status=dikirim<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="px-4 py-2 rounded-xl font-medium transition <?php echo $status_filter === 'dikirim' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Dikirim
                        </a>
                        <a href="pesanan.php?status=selesai<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="px-4 py-2 rounded-xl font-medium transition <?php echo $status_filter === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Selesai
                        </a>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="space-y-6">
                <?php if ($result_pesanan && mysqli_num_rows($result_pesanan) > 0): ?>
                    <?php while ($pesanan = mysqli_fetch_assoc($result_pesanan)): ?>
                        <?php
                        // Ambil detail produk untuk pesanan ini dengan prepared statement
                        $id_penjualan = $pesanan['id_penjualan'];
                        $query_detail = "SELECT dp.*, p.nama_produk, p.foto 
                                        FROM detail_penjualan dp 
                                        JOIN produk p ON dp.id_produk = p.id_produk 
                                        WHERE dp.id_penjualan = ?";
                        $stmt_detail = mysqli_prepare($koneksi, $query_detail);
                        mysqli_stmt_bind_param($stmt_detail, "i", $id_penjualan);
                        mysqli_stmt_execute($stmt_detail);
                        $result_detail = mysqli_stmt_get_result($stmt_detail);
                        
                        // Format data
                        $tanggal = date('d M Y H:i', strtotime($pesanan['tanggal']));
                        $total_harga = number_format($pesanan['total'], 0, ',', '.');
                        $no_pesanan = str_pad($pesanan['id_penjualan'], 6, '0', STR_PAD_LEFT);
                        
                        // Status badge
                        $status_class = "status-" . $pesanan['status_pesanan'];
                        $status_text = ucfirst($pesanan['status_pesanan']);
                        
                        // Tentukan tombol yang ditampilkan berdasarkan status
                        $show_cancel_button = ($pesanan['status_pesanan'] === 'pending');
                        $show_pay_button = ($pesanan['status_pesanan'] === 'pending' && $pesanan['status_pembayaran'] === 'pending');
                        $show_confirm_button = ($pesanan['status_pesanan'] === 'dikirim');
                        ?>
                        
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                            <!-- Order Header -->
                            <div class="border-b border-gray-100 p-6">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                        <div>
                                            <h3 class="font-semibold text-gray-800">Pesanan #<?php echo $no_pesanan; ?></h3>
                                            <p class="text-sm text-gray-600"><?php echo $tanggal; ?></p>
                                        </div>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <?php if ($pesanan['status_pembayaran'] === 'paid'): ?>
                                            <span class="status-badge bg-green-100 text-green-800">
                                                Lunas
                                            </span>
                                        <?php elseif ($pesanan['status_pembayaran'] === 'pending' && $pesanan['status_pesanan'] !== 'dibatalkan'): ?>
                                            <span class="status-badge bg-yellow-100 text-yellow-800">
                                                Belum Bayar
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold pink-text">Rp <?php echo $total_harga; ?></p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo $pesanan['total_items']; ?> item • 
                                            <?php echo $pesanan['total_quantity']; ?> pcs
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Items -->
                            <div class="p-6">
                                <div class="space-y-4">
                                    <?php while ($detail = mysqli_fetch_assoc($result_detail)): ?>
                                        <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                                            <div class="w-16 h-16 bg-gradient-to-br from-pink-50 to-purple-50 rounded-lg flex items-center justify-center">
                                            <?php
// Perbaiki path gambar
$gambar_path = '';
if (!empty($detail['foto'])) {
    // Cek apakah file ada di path admin/uploads/produk/
    if (file_exists('../admin/uploads/produk/' . $detail['foto'])) {
        $gambar_path = '../admin/uploads/produk/' . htmlspecialchars($detail['foto']);
    } 
    // Cek apakah file ada di path uploads/produk/ (relatif terhadap root)
    else if (file_exists('uploads/produk/' . $detail['foto'])) {
        $gambar_path = 'uploads/produk/' . htmlspecialchars($detail['foto']);
    }
    // Coba cari di lokasi lain
    else if (file_exists($detail['foto'])) {
        $gambar_path = $detail['foto'];
    } else {
        // Gunakan gambar default jika tidak ditemukan
        $gambar_path = 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
    }
} else {
    $gambar_path = 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
}
?>

<img src="<?php echo $gambar_path; ?>" 
     alt="<?php echo htmlspecialchars($detail['nama_produk']); ?>" 
     class="w-12 h-12 object-contain"
     onerror="this.onerror=null; this.src='https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'">
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($detail['nama_produk']); ?></h4>
                                                <p class="text-gray-600 text-sm">Rp <?php echo number_format($detail['subtotal'] / $detail['jumlah'], 0, ',', '.'); ?> x <?php echo $detail['jumlah']; ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold text-gray-800">Rp <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?></p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                
                                <!-- Order Actions -->
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6 pt-6 border-t border-gray-100">
                                    <div class="text-sm text-gray-600">
                                        <?php if (!empty($pesanan['alamat_pengiriman'])): ?>
                                            <p><strong>Alamat Pengiriman:</strong> <?php echo htmlspecialchars($pesanan['alamat_pengiriman']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex space-x-3">
                                        <?php if ($show_cancel_button): ?>
                                            <button onclick="batalkanPesanan(<?php echo $pesanan['id_penjualan']; ?>)" 
                                                    class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition font-medium"
                                                    id="btn-batal-<?php echo $pesanan['id_penjualan']; ?>">
                                                Batalkan Pesanan
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($show_pay_button): ?>
                                            <button onclick="showPaymentModal(<?php echo $pesanan['id_penjualan']; ?>, <?php echo $pesanan['total']; ?>, '<?php echo $no_pesanan; ?>')" 
                                                    class="px-4 py-2 gradient-bg text-white rounded-xl hover:shadow-lg transition font-medium"
                                                    id="btn-bayar-<?php echo $pesanan['id_penjualan']; ?>">
                                                Bayar Sekarang
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($show_confirm_button): ?>
                                            <button onclick="konfirmasiPenerimaan(<?php echo $pesanan['id_penjualan']; ?>)" 
                                                    class="px-4 py-2 gradient-bg text-white rounded-xl hover:shadow-lg transition font-medium"
                                                    id="btn-konfirmasi-<?php echo $pesanan['id_penjualan']; ?>">
                                                Konfirmasi Diterima
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="detail_pesanan.php?id=<?php echo $pesanan['id_penjualan']; ?>" 
                                           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state rounded-2xl shadow-lg p-12 text-center">
                        <div class="max-w-md mx-auto">
                            <div class="w-24 h-24 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-shopping-bag text-white text-4xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-700 mb-2">Belum Ada Pesanan</h3>
                            <p class="text-gray-500 mb-6">Anda belum memiliki pesanan. Yuk mulai berbelanja!</p>
                            <a href="produk.php" class="inline-flex items-center px-6 py-3 gradient-bg text-white font-semibold rounded-xl hover:shadow-lg transition">
                                <i class="fas fa-shopping-cart mr-2"></i>Mulai Belanja
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Payment Modal -->
    <div id="payment-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Pembayaran Pesanan</h3>
                    <button onclick="closePaymentModal()" class="p-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- Order Info -->
                    <div class="payment-info rounded-xl p-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">No. Pesanan:</span>
                            <span class="font-semibold" id="payment-order-number"></span>
                        </div>
                        <div class="flex justify-between text-lg">
                            <span class="text-gray-800 font-medium">Total Pembayaran:</span>
                            <span class="font-bold pink-text" id="payment-total"></span>
                        </div>
                    </div>
                    
                    <!-- Payment Instructions -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-800">Cara Pembayaran:</h4>
                        <div class="space-y-3">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-bold text-sm">1</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Transfer ke salah satu rekening berikut:</p>
                                    <div class="mt-2 space-y-2 bg-pink-50 p-3 rounded-lg">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Bank BCA:</span>
                                            <span class="font-mono font-bold">1234 5678 9012</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Bank Mandiri:</span>
                                            <span class="font-mono font-bold">3456 7890 1234</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Bank BRI:</span>
                                            <span class="font-mono font-bold">5678 9012 3456</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-2">a.n. <strong>Moods Strap</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-bold text-sm">2</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Setelah transfer, kirim bukti pembayaran melalui WhatsApp:</p>
                                    <button onclick="prosesPembayaranWA()" 
                                            class="mt-3 w-full px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition font-medium flex items-center justify-center"
                                            id="btn-whatsapp">
                                        <i class="fab fa-whatsapp mr-2"></i>
                                        <span id="btn-whatsapp-text">Kirim Bukti ke WhatsApp</span>
                                    </button>
                                    <p class="text-sm text-gray-600 mt-2">Anda akan diarahkan ke WhatsApp untuk mengirim bukti transfer</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-bold text-sm">3</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Tunggu konfirmasi admin:</p>
                                    <p class="text-sm text-gray-600 mt-1">Status pesanan akan berubah menjadi "Diproses" setelah pembayaran dikonfirmasi oleh admin (maksimal 1x24 jam).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-8 pt-6 border-t border-gray-200">
                    <button onclick="closePaymentModal()" 
                            class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium">
                        Kembali
                    </button>
                    <button onclick="copyPaymentInfo()" 
                            class="flex-1 px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition font-medium">
                        Salin Info Rekening
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div>
                    <h3 class="text-xl font-bold mb-6 flex items-center">
                        <img src="WhatsApp_Image_2025-11-13_at_08.21.58_d2b62406-removebg-preview.png" class="w-12 h-12 rounded-full object cover" alt="Logo">
                         <div class="flex items-center justify-center mr-3">
                         </div>

                        <div class="flex items-center">
                        
                        </div>
                        Moods <span class="text-gray-300">Strap</span>
                    </h3>
                    <p class="text-gray-400 mb-6">Toko online gantungan aksesoris HP dengan desain unik dan berkualitas tinggi untuk melengkapi gaya Hp Anda.</p>
                    <div class="flex space-x-4">
                        <a href="https://www.instagram.com/moods_strap?igsh=aXExOGozazVycmk2" target="_blank" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition group">
                            <i class="fab fa-instagram group-hover:text-white"></i>
                        </a>
                        </a>
                        <a href="https://wa.me/6282162961621" target="_blank" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition group">
                            <i class="fab fa-whatsapp group-hover:text-white"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Tautan Cepat</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Beranda</a></li>
                        <li><a href="produk.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Produk</a></li>
                        <li><a href="keranjang.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Keranjang</a></li>
                        <li><a href="pesanan.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pesanan</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Bantuan</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Cara Belanja</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pembayaran</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pengiriman</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>FAQ</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Kontak</h4>
                    <ul class="space-y-4">
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt pink-text mr-4 w-5"></i>
                            <span class="text-gray-400">Medan, Indonesia</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone pink-text mr-4 w-5"></i>
                            <span class="text-gray-400">+62 821-6296-1621</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope pink-text mr-4 w-5"></i>
                            <span class="text-gray-400">info@moodsstrap.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">© 2025 Moods Strap. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const closeMobileMenu = document.getElementById('close-mobile-menu');
            
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.add('active');
            });
            
            closeMobileMenu.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    mobileMenu.classList.remove('active');
                }
            });
        });

        // Payment modal variables
        let currentOrderId = null;

        // Function to show payment modal
        function showPaymentModal(orderId, totalAmount, orderNumber) {
            currentOrderId = orderId;
            
            // Format total amount
            const formattedTotal = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(totalAmount);
            
            // Update modal content
            document.getElementById('payment-order-number').textContent = '#' + orderNumber;
            document.getElementById('payment-total').textContent = formattedTotal;
            
            // Show modal
            const modal = document.getElementById('payment-modal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Function to close payment modal
        function closePaymentModal() {
            const modal = document.getElementById('payment-modal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            currentOrderId = null;
        }

        // Function to process WhatsApp payment
        function prosesPembayaranWA() {
            if (!currentOrderId) {
                alert('ID pesanan tidak valid');
                return;
            }
            
            // Show loading
            const button = document.getElementById('btn-whatsapp');
            const buttonText = document.getElementById('btn-whatsapp-text');
            const originalText = buttonText.textContent;
            const originalDisabled = button.disabled;
            buttonText.textContent = 'Memproses...';
            button.disabled = true;
            button.classList.add('opacity-70');
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'konfirmasi_pembayaran_wa');
            formData.append('order_id', currentOrderId);
            
            // Send request
            fetch('pesanan.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Open WhatsApp in new tab
                    window.open(data.whatsapp_url, '_blank');
                    
                    // Show success message
                    alert('✅ Anda akan diarahkan ke WhatsApp. Kirim bukti transfer Anda di sana. Setelah admin mengkonfirmasi, status pesanan akan berubah.');
                    closePaymentModal();
                } else {
                    alert('❌ Gagal memproses pembayaran: ' + (data.message || 'Tidak ada pesan error'));
                    buttonText.textContent = originalText;
                    button.disabled = originalDisabled;
                    button.classList.remove('opacity-70');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('⚠️ Terjadi kesalahan jaringan atau server. Silakan coba lagi nanti.');
                buttonText.textContent = originalText;
                button.disabled = originalDisabled;
                button.classList.remove('opacity-70');
            });
        }

        // Function to copy payment info
        function copyPaymentInfo() {
            const paymentInfo = `Info Rekening Moods Strap:
BCA: 1234 5678 9012
Mandiri: 3456 7890 1234
BRI: 5678 9012 3456
a.n. Moods Strap

Setelah transfer, kirim bukti ke WhatsApp: +62 812 3456 7890`;
            
            navigator.clipboard.writeText(paymentInfo)
                .then(() => {
                    alert('✅ Info rekening berhasil disalin ke clipboard!');
                })
                .catch(err => {
                    console.error('Gagal menyalin: ', err);
                    alert('❌ Gagal menyalin info rekening');
                });
        }

        // Function to cancel order
        function batalkanPesanan(orderId) {
            if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?\nPesanan yang dibatalkan tidak dapat dikembalikan.')) {
                // Show loading
                const button = document.getElementById('btn-batal-' + orderId);
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                button.disabled = true;
                button.classList.add('btn-loading');
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'batalkan_pesanan');
                formData.append('order_id', orderId);
                
                // Send request
                fetch('pesanan.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('✅ Pesanan berhasil dibatalkan');
                        location.reload();
                    } else {
                        alert('❌ Gagal membatalkan pesanan: ' + (data.message || 'Tidak ada pesan error'));
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.classList.remove('btn-loading');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('⚠️ Terjadi kesalahan saat membatalkan pesanan');
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.classList.remove('btn-loading');
                });
            }
        }

        // Function to confirm receipt
        function konfirmasiPenerimaan(orderId) {
            if (confirm('Apakah Anda yakin pesanan sudah diterima?\nSetelah dikonfirmasi, pesanan akan berstatus selesai.')) {
                // Show loading
                const button = document.getElementById('btn-konfirmasi-' + orderId);
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                button.disabled = true;
                button.classList.add('btn-loading');
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'konfirmasi_penerimaan');
                formData.append('order_id', orderId);
                
                // Send request
                fetch('pesanan.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('✅ Pesanan berhasil dikonfirmasi');
                        location.reload();
                    } else {
                        alert('❌ Gagal mengkonfirmasi pesanan: ' + (data.message || 'Tidak ada pesan error'));
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.classList.remove('btn-loading');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('⚠️ Terjadi kesalahan saat mengkonfirmasi pesanan');
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.classList.remove('btn-loading');
                });
            }
        }

        // Close modal when clicking outside
        document.getElementById('payment-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi database
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>