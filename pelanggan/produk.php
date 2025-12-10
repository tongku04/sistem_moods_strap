<?php
session_start();
// Include koneksi database dengan path yang benar
include '../config/koneksi.php';

// Cek koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal");
}

// Ambil parameter filter dan pencarian
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Query untuk mengambil data kategori
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = mysqli_query($koneksi, $query_kategori);

// Build query untuk produk dengan filter
$query_produk = "SELECT p.*, k.nama_kategori 
                 FROM produk p 
                 LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                 WHERE p.status = 'active'";

// Tambahkan filter kategori jika dipilih
if (!empty($kategori_filter) && $kategori_filter != 'all') {
    $query_produk .= " AND p.id_kategori = '$kategori_filter'";
}

// Tambahkan pencarian jika ada
if (!empty($search_query)) {
    $query_produk .= " AND (p.nama_produk LIKE '%$search_query%' OR p.deskripsi LIKE '%$search_query%')";
}

// Tambahkan sorting
switch ($sort_by) {
    case 'harga_terendah':
        $query_produk .= " ORDER BY p.harga ASC";
        break;
    case 'harga_tertinggi':
        $query_produk .= " ORDER BY p.harga DESC";
        break;
    case 'nama':
        $query_produk .= " ORDER BY p.nama_produk ASC";
        break;
    case 'stok':
        $query_produk .= " ORDER BY p.stok DESC";
        break;
    default: // terbaru
        $query_produk .= " ORDER BY p.id_produk DESC";
        break;
}

$result_produk = mysqli_query($koneksi, $query_produk);

// Hitung total produk
$total_produk = mysqli_num_rows($result_produk);

// Cek apakah user sudah login
$user_logged_in = isset($_SESSION['user']);
$user_role = $user_logged_in ? $_SESSION['user']['role'] : '';
$username = $user_logged_in ? $_SESSION['user']['username'] : '';

// Jika user sudah login sebagai pelanggan, ambil data pelanggan
$data_pelanggan = null;
if ($user_logged_in && $user_role === 'pelanggan') {
    $id_user = $_SESSION['user']['id_user'];
    $query_pelanggan = "SELECT * FROM pelanggan WHERE id_user = '$id_user'";
    $result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
    if ($result_pelanggan) {
        $data_pelanggan = mysqli_fetch_assoc($result_pelanggan);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Moods Strap</title>
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
        
        .pink-border {
            border-color: #ff69b4;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .product-card {
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 105, 180, 0.2);
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
        
        /* Mobile menu styles */
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .mobile-menu.active {
            transform: translateX(0);
        }
        
        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #ff69b4;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #ff1493;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
            padding: 20px;
        }

        .filter-section {
            transition: all 0.3s ease;
        }

        .sticky-filter {
            position: sticky;
            top: 80px;
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
                        <img src="../assets/images/logo.png" class="w-11 h-11 rounded-full object-cover" alt="Logo Moods Strap" onerror="this.src='https://via.placeholder.com/44x44/ff69b4/ffffff?text=MS'">
                    </div>
                    Moods <span class="text-gray-800">Strap</span>
                </a>
            </div>
            
            <nav class="hidden md:flex space-x-8">
                <a href="index.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Beranda</a>
                <a href="produk.php" class="text-pink-500 font-semibold transition">Produk</a>
                <a href="keranjang.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Keranjang</a>
                <a href="pesanan.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Pesanan</a>
            </nav>
            
            <div class="flex items-center space-x-4">
                <!-- Keranjang -->
                <?php if ($user_logged_in && $user_role === 'pelanggan' && $data_pelanggan): ?>
                    <?php
                    // Hitung jumlah item di keranjang
                    $query_keranjang = "SELECT COUNT(*) as total FROM keranjang WHERE id_pelanggan = '{$data_pelanggan['id_pelanggan']}'";
                    $result_keranjang = mysqli_query($koneksi, $query_keranjang);
                    $total_keranjang = $result_keranjang ? mysqli_fetch_assoc($result_keranjang)['total'] : 0;
                    ?>
                    <a href="keranjang.php" class="p-2 text-gray-700 hover:text-pink-500 transition relative group">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($total_keranjang > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center">
                                <?php echo $total_keranjang; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <button class="p-2 text-gray-700 hover:text-pink-500 transition relative group" onclick="showLoginAlert()">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                <?php endif; ?>

                <!-- User Menu -->
                <?php if ($user_logged_in): ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 p-2 text-gray-700 hover:text-pink-500 transition">
                            <i class="fas fa-user"></i>
                            <span class="hidden md:inline"><?php echo htmlspecialchars($username); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <?php if ($user_role === 'pelanggan'): ?>

                                </a>
                                <a href="pesanan.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                                    <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                                </a>
                            <?php else: ?>
                                <a href="../<?php echo $user_role; ?>/index.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="p-2 text-gray-700 hover:text-pink-500 transition group">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>

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
                <a href="produk.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition mb-2">
                    <i class="fas fa-box mr-3"></i>Produk
                </a>
                <a href="keranjang.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-shopping-cart mr-3"></i>Keranjang
                </a>
                <a href="pesanan.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition">
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
                            <span class="ml-1 text-sm font-medium text-pink-500 md:ml-2">Semua Produk</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filter -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky-filter">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-filter mr-3 pink-text"></i>
                        Filter Produk
                    </h3>

                    <!-- Search Form -->
                    <form method="GET" action="produk.php" class="mb-6">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Cari produk..." 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <button type="submit" class="absolute right-3 top-3 text-gray-400 hover:text-pink-500">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Kategori Filter -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-800 mb-3">Kategori</h4>
                        <form method="GET" action="produk.php" id="kategoriForm">
                            <?php if (!empty($search_query)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                            <?php endif; ?>
                            <?php if (!empty($sort_by)): ?>
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                            <?php endif; ?>
                            <select name="kategori" onchange="document.getElementById('kategoriForm').submit()" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                <option value="all" <?php echo $kategori_filter == 'all' ? 'selected' : ''; ?>>Semua Kategori</option>
                                <?php
                                if ($result_kategori && mysqli_num_rows($result_kategori) > 0) {
                                    while ($kategori = mysqli_fetch_assoc($result_kategori)) {
                                        $selected = $kategori_filter == $kategori['id_kategori'] ? 'selected' : '';
                                        echo '<option value="' . $kategori['id_kategori'] . '" ' . $selected . '>' . htmlspecialchars($kategori['nama_kategori']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </form>
                    </div>

                    <!-- Sorting -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-800 mb-3">Urutkan</h4>
                        <form method="GET" action="produk.php" id="sortForm">
                            <?php if (!empty($search_query)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                            <?php endif; ?>
                            <?php if (!empty($kategori_filter) && $kategori_filter != 'all'): ?>
                                <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori_filter); ?>">
                            <?php endif; ?>
                            <select name="sort" onchange="document.getElementById('sortForm').submit()" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                <option value="terbaru" <?php echo $sort_by == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="harga_terendah" <?php echo $sort_by == 'harga_terendah' ? 'selected' : ''; ?>>Harga Terendah</option>
                                <option value="harga_tertinggi" <?php echo $sort_by == 'harga_tertinggi' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                                <option value="nama" <?php echo $sort_by == 'nama' ? 'selected' : ''; ?>>Nama A-Z</option>
                                <option value="stok" <?php echo $sort_by == 'stok' ? 'selected' : ''; ?>>Stok Terbanyak</option>
                            </select>
                        </form>
                    </div>

                    <!-- Reset Filters -->
                    <a href="produk.php" class="w-full bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-xl hover:bg-gray-200 transition flex items-center justify-center">
                        <i class="fas fa-refresh mr-2"></i>
                        Reset Filter
                    </a>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="lg:w-3/4">
                <!-- Header Results -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">Semua Produk</h1>
                            <p class="text-gray-600">
                                Menampilkan <span class="font-semibold pink-text"><?php echo $total_produk; ?></span> produk
                                <?php if (!empty($search_query)): ?>
                                    untuk "<span class="font-semibold"><?php echo htmlspecialchars($search_query); ?></span>"
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if (!empty($search_query) || (!empty($kategori_filter) && $kategori_filter != 'all')): ?>
                        <div class="mt-4 md:mt-0">
                            <a href="produk.php" class="text-pink-500 hover:text-pink-600 font-semibold flex items-center">
                                <i class="fas fa-times mr-2"></i>
                                Hapus Filter
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Products -->
                <div id="products-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    if ($result_produk && mysqli_num_rows($result_produk) > 0) {
                        while ($produk = mysqli_fetch_assoc($result_produk)) {
                            $harga = number_format($produk['harga'], 0, ',', '.');
                            $stok = $produk['stok'] > 0 ? "Tersedia" : "Habis";
                            $stok_class = $produk['stok'] > 0 ? "bg-green-500" : "bg-red-500";
                            $stok_badge = $produk['stok'] > 0 ? "bg-green-500" : "bg-red-500";
                            
                            // PERBAIKAN PATH GAMBAR: Sesuaikan dengan struktur folder Anda
                            // Pilihan 1: Jika folder uploads berada di root (satu tingkat di atas pelanggan)
                            $gambar_produk = $produk['foto'] ? '../uploads/produk/' . $produk['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                            
                            // Pilihan 2: Jika folder uploads berada di admin (dua tingkat di atas pelanggan)
                            // $gambar_produk = $produk['foto'] ? '../../admin/uploads/produk/' . $produk['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                            
                            // Pilihan 3: Path absolut jika perlu
                            // $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                            // $gambar_produk = $produk['foto'] ? $base_url . '/moods-strap/uploads/produk/' . $produk['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                            
                            echo '
                            <div class="product-card bg-white rounded-2xl shadow-lg overflow-hidden transition duration-300 border border-gray-100">
                                <div class="relative overflow-hidden">
                                    <div class="p-4 bg-gradient-to-br from-pink-50 to-purple-50 flex items-center justify-center h-48">
                                        <img src="' . $gambar_produk . '" alt="' . htmlspecialchars($produk['nama_produk']) . '" 
                                             class="product-image transform hover:scale-110 transition duration-500"
                                             onerror="this.onerror=null; this.src=\'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png\'">
                                    </div>
                                    <div class="absolute top-4 right-4">
                                        <span class="' . $stok_badge . ' text-white text-xs font-bold px-3 py-1 rounded-full">' . $stok . '</span>
                                    </div>
                                    ' . ($produk['nama_kategori'] ? '
                                    <div class="absolute top-4 left-4">
                                        <span class="bg-white/90 backdrop-blur-sm text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">' . htmlspecialchars($produk['nama_kategori']) . '</span>
                                    </div>
                                    ' : '') . '
                                </div>
                                <div class="p-6">
                                    <h3 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2">' . htmlspecialchars($produk['nama_produk']) . '</h3>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">' . htmlspecialchars(substr($produk['deskripsi'] ?: 'Deskripsi tidak tersedia', 0, 80)) . '...</p>
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-bold text-2xl pink-text">Rp ' . $harga . '</span>
                                            ' . ($produk['stok'] > 0 ? '<p class="text-green-500 text-sm font-semibold">Stok: ' . $produk['stok'] . '</p>' : '<p class="text-red-500 text-sm font-semibold">Stok Habis</p>') . '
                                        </div>
                                        ' . ($user_logged_in && $user_role === 'pelanggan' ? '
                                        <button onclick="addToCart(' . $produk['id_produk'] . ')" 
                                                class="w-12 h-12 gradient-bg text-white rounded-xl hover:shadow-lg transition transform hover:scale-110 flex items-center justify-center ' . ($produk['stok'] == 0 ? 'opacity-50 cursor-not-allowed' : '') . '" 
                                                ' . ($produk['stok'] == 0 ? 'disabled' : '') . ' title="' . ($produk['stok'] == 0 ? 'Stok Habis' : 'Tambah ke Keranjang') . '">
                                            ' . ($produk['stok'] == 0 ? '<i class="fas fa-times"></i>' : '<i class="fas fa-shopping-cart"></i>') . '
                                        </button>
                                        ' : '
                                        <button onclick="showLoginAlert()" 
                                                class="w-12 h-12 gradient-bg text-white rounded-xl hover:shadow-lg transition transform hover:scale-110 flex items-center justify-center"
                                                title="Login untuk Belanja">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                        ') . '
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '
                        <div class="col-span-3 text-center py-12">
                            <div class="bg-white rounded-2xl p-12 shadow-lg">
                                <i class="fas fa-search text-gray-400 text-6xl mb-4"></i>
                                <h3 class="text-2xl font-bold text-gray-700 mb-2">Produk Tidak Ditemukan</h3>
                                <p class="text-gray-500 mb-6">Maaf, tidak ada produk yang sesuai dengan kriteria pencarian Anda.</p>
                                <a href="produk.php" class="inline-flex items-center px-6 py-3 gradient-bg text-white font-semibold rounded-xl hover:shadow-lg transition">
                                    <i class="fas fa-refresh mr-2"></i>Lihat Semua Produk
                                </a>
                            </div>
                        </div>';
                    }
                    ?>
                </div>

                <!-- Load More Button (untuk pagination future) -->
                <?php if ($total_produk > 0): ?>
                <div class="text-center mt-12">
                    <button class="px-8 py-4 bg-white text-gray-700 font-semibold rounded-2xl hover:shadow-lg transition border border-gray-200 hover:border-pink-300 hover:text-pink-500">
                        <i class="fas fa-spinner mr-2"></i>Muat Lebih Banyak
                    </button>
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
            // Mobile menu functionality
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

        // Function to show login alert
        function showLoginAlert() {
            alert('Silakan login terlebih dahulu untuk menggunakan fitur ini.');
            window.location.href = '../auth/login.php';
        }

        // Function to add product to cart
        function addToCart(productId) {
            fetch('../ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Produk berhasil ditambahkan ke keranjang!');
                    // Reload page to update cart count
                    location.reload();
                } else {
                    alert('Gagal menambahkan produk ke keranjang: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan produk ke keranjang.');
            });
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