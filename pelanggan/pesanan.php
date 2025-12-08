<?php
session_start();
include '../config/koneksi.php';

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

// Ambil data pelanggan
$query_pelanggan = "SELECT * FROM pelanggan WHERE id_user = '$user_id'";
$result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
$data_pelanggan = mysqli_fetch_assoc($result_pelanggan);

if (!$data_pelanggan) {
    die("Data pelanggan tidak ditemukan");
}

$id_pelanggan = $data_pelanggan['id_pelanggan'];

// Ambil parameter filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk mengambil pesanan
$query_pesanan = "SELECT p.*, 
                         COUNT(dp.id_detail) as total_items,
                         SUM(dp.jumlah) as total_quantity
                  FROM penjualan p 
                  LEFT JOIN detail_penjualan dp ON p.id_penjualan = dp.id_penjualan
                  WHERE p.id_pelanggan = '$id_pelanggan'";

// Tambahkan filter status
if ($status_filter !== 'all') {
    $query_pesanan .= " AND p.status_pesanan = '$status_filter'";
}

// Tambahkan pencarian
if (!empty($search_query)) {
    $query_pesanan .= " AND p.id_penjualan LIKE '%$search_query%'";
}

$query_pesanan .= " GROUP BY p.id_penjualan ORDER BY p.tanggal DESC";

$result_pesanan = mysqli_query($koneksi, $query_pesanan);

// Hitung statistik pesanan
$query_stats = "SELECT 
    COUNT(*) as total_pesanan,
    SUM(CASE WHEN status_pesanan = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status_pesanan = 'diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status_pesanan = 'dikirim' THEN 1 ELSE 0 END) as dikirim,
    SUM(CASE WHEN status_pesanan = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status_pesanan = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
FROM penjualan 
WHERE id_pelanggan = '$id_pelanggan'";

$result_stats = mysqli_query($koneksi, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <a href="index.php" class="text-2xl font-bold pink-text flex items-center">
                    <div class="flex items-center justify-center mr-2">
                        <img src="../assets/images/logo.png" class="w-11 h-11 rounded-full object-cover" alt="Logo Moods Strap" onerror="this.src='https://via.placeholder.com/44x44/ff69b4/ffffff?text=MS'">
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
                        <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                            <i class="fas fa-user-circle mr-2"></i>Profil Saya
                        </a>
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
        <div id="mobile-menu" class="mobile-menu fixed inset-y-0 left-0 w-64 bg-white/95 backdrop-blur-md shadow-lg z-50 md:hidden">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold pink-text">Moods <span class="text-gray-800">Strap</span></h2>
                <button id="close-mobile-menu" class="p-2 text-gray-500 hover:text-pink-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="p-4">
                <a href="index.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-home mr-3"></i>Beranda
                </a>
                <a href="produk.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-box mr-3"></i>Produk
                </a>
                <a href="keranjang.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-shopping-cart mr-3"></i>Keranjang
                </a>
                <a href="pesanan.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition">
                    <i class="fas fa-shopping-bag mr-3"></i>Pesanan
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
                        // Ambil detail produk untuk pesanan ini
                        $id_penjualan = $pesanan['id_penjualan'];
                        $query_detail = "SELECT dp.*, p.nama_produk, p.foto 
                                        FROM detail_penjualan dp 
                                        JOIN produk p ON dp.id_produk = p.id_produk 
                                        WHERE dp.id_penjualan = '$id_penjualan'";
                        $result_detail = mysqli_query($koneksi, $query_detail);
                        
                        // Format data
                        $tanggal = date('d M Y H:i', strtotime($pesanan['tanggal']));
                        $total_harga = number_format($pesanan['total'], 0, ',', '.');
                        $no_pesanan = str_pad($pesanan['id_penjualan'], 6, '0', STR_PAD_LEFT);
                        
                        // Status badge
                        $status_class = "status-" . $pesanan['status_pesanan'];
                        $status_text = ucfirst($pesanan['status_pesanan']);
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
                                                <img src="<?php echo $detail['foto'] ? '../admin/uploads/produk/' . $detail['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($detail['nama_produk']); ?>" 
                                                     class="w-12 h-12 object-contain"
                                                     onerror="this.src='https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'">
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
                                        <?php if ($pesanan['status_pesanan'] === 'pending'): ?>
                                            <button onclick="batalkanPesanan(<?php echo $pesanan['id_penjualan']; ?>)" 
                                                    class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition font-medium">
                                                Batalkan Pesanan
                                            </button>
                                            <a href="pembayaran.php?id=<?php echo $pesanan['id_penjualan']; ?>" 
                                               class="px-4 py-2 gradient-bg text-white rounded-xl hover:shadow-lg transition font-medium">
                                                Bayar Sekarang
                                            </a>
                                        <?php elseif ($pesanan['status_pesanan'] === 'dikirim'): ?>
                                            <button onclick="konfirmasiPenerimaan(<?php echo $pesanan['id_penjualan']; ?>)" 
                                                    class="px-4 py-2 gradient-bg text-white rounded-xl hover:shadow-lg transition font-medium">
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
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                        <div class="max-w-md mx-auto">
                            <i class="fas fa-shopping-bag text-gray-400 text-6xl mb-4"></i>
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

    <!-- Footer -->
    <footer class="bg-gray-800 text-white pt-16 pb-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div>
                    <h3 class="text-xl font-bold mb-6 flex items-center">
                        <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-gem text-white text-sm"></i>
                        </div>
                        Moods <span class="text-gray-300">Strap</span>
                    </h3>
                    <p class="text-gray-400 mb-6">Toko online gantungan aksesoris HP dengan desain unik dan berkualitas tinggi untuk melengkapi gaya Anda.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition">
                            <i class="fab fa-whatsapp"></i>
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
                            <span class="text-gray-400">+62 812 3456 7890</span>
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

        // Function to cancel order
        function batalkanPesanan(orderId) {
            if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
                fetch('ajax/batalkan_pesanan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pesanan berhasil dibatalkan');
                        location.reload();
                    } else {
                        alert('Gagal membatalkan pesanan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat membatalkan pesanan');
                });
            }
        }

        // Function to confirm receipt
        function konfirmasiPenerimaan(orderId) {
            if (confirm('Apakah Anda yakin pesanan sudah diterima?')) {
                fetch('ajax/konfirmasi_penerimaan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pesanan berhasil dikonfirmasi');
                        location.reload();
                    } else {
                        alert('Gagal mengkonfirmasi pesanan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengkonfirmasi pesanan');
                });
            }
        }
    </script>
</body>
</html>

<?php
// Tutup koneksi database
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>