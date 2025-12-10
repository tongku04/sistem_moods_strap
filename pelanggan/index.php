<?php
session_start();
// Include koneksi database dengan path yang benar
include '../config/koneksi.php';

// Cek koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal");
}

// Query untuk mengambil data produk
$query_produk = "SELECT p.*, k.nama_kategori 
                 FROM produk p 
                 LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                 WHERE p.status = 'active' 
                 ORDER BY p.id_produk DESC 
                 LIMIT 8";
$result_produk = mysqli_query($koneksi, $query_produk);

// Query untuk statistik
$total_produk_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk WHERE status = 'active'");
$total_penjualan_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penjualan");
$total_kategori_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kategori");
$produk_terjual_query = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah), 0) as total FROM detail_penjualan");

// Ambil nilai statistik
$total_produk = $total_produk_query ? mysqli_fetch_assoc($total_produk_query)['total'] : 0;
$total_penjualan = $total_penjualan_query ? mysqli_fetch_assoc($total_penjualan_query)['total'] : 0;
$total_kategori = $total_kategori_query ? mysqli_fetch_assoc($total_kategori_query)['total'] : 0;
$produk_terjual = $produk_terjual_query ? mysqli_fetch_assoc($produk_terjual_query)['total'] : 0;

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
    <title>Moods Strap - Gantungan Aksesoris HP Terbaru</title>
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
        
        .slider {
            transition: transform 0.5s ease-in-out;
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
        
        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Pulse animation */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pulse {
            animation: pulse 4s ease-in-out infinite;
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
    </style>
</head>
<body class="bg-white">
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
                <a href="index.php" class="text-pink-500 font-semibold nav-link transition">Beranda</a>
                <a href="produk.php" class="text-gray-700 font-medium hover:text-pink-500 nav-link transition">Produk</a>
                <a href="keranjang.php" class="text-gray-700 font-medium hover:text-pink-500 nav-link transition">Keranjang</a>
                <a href="pesanan.php" class="text-gray-700 font-medium hover:text-pink-500 nav-link transition">Pesanan</a>
            </nav>
            
            <div class="flex items-center space-x-4">
                <button class="p-2 text-gray-700 hover:text-pink-500 transition relative group">
                    <i class="fas fa-search"></i>
                </button>
                
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
                            <a href="../index.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
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
                <a href="index.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition mb-2">
                    <i class="fas fa-home mr-3"></i>Beranda
                </a>
                <a href="produk.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
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

    <!-- Hero Slider -->
    <section class="relative overflow-hidden">
        <div class="slider-container flex transition-transform duration-500 ease-in-out" id="slider">
            <!-- Slide 1 -->
            <div class="w-full flex-shrink-0 relative">
                <div class="gradient-bg py-20 md:py-28 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full floating"></div>
                        <div class="absolute top-1/4 right-20 w-16 h-16 bg-white rounded-full floating" style="animation-delay: 2s;"></div>
                        <div class="absolute bottom-20 left-1/4 w-12 h-12 bg-white rounded-full floating" style="animation-delay: 4s;"></div>
                    </div>
                    <div class="container mx-auto px-4 flex flex-col md:flex-row items-center relative z-10">
                        <div class="md:w-1/2 mb-8 md:mb-0">
                            <h2 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                                Gantungan HP <span class="text-yellow-300">Eksklusif</span>
                            </h2>
                            <p class="text-white/90 text-lg mb-8 max-w-md">Temukan koleksi gantungan aksesoris HP terbaru dengan desain unik dan berkualitas tinggi yang membuat gaya Anda semakin stylish.</p>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <button onclick="window.location.href='produk.php';" class="px-8 py-4 bg-white text-pink-500 font-bold rounded-xl hover:bg-gray-100 transition transform hover:scale-105 shadow-lg">
                                    <i class="fas fa-shopping-bag mr-2"></i>Belanja Sekarang
                                </button>
                                <button onclick="window.open('https://youtu.be/7f3m_jFX7xI?si=hbax-60z5LMAPt-B');" class="px-8 py-4 border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition transform hover:scale-105">
                                    <i class="fas fa-play-circle mr-2"></i>Lihat Demo
                                </button>
                            </div>
                        </div>
                        <div class="md:w-1/2 flex justify-center">
                            <div class="relative">
                                <img src="https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png" alt="Gantungan HP" class="w-full max-w-lg floating">
                                <div class="absolute -bottom-4 -left-4 w-24 h-24 gradient-bg rounded-2xl rotate-12 opacity-80"></div>
                                <div class="absolute -top-4 -right-4 w-20 h-20 bg-yellow-400 rounded-2xl -rotate-12 opacity-80"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="w-full flex-shrink-0 relative">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 py-20 md:py-28 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-20 right-10 w-24 h-24 bg-white rounded-full floating" style="animation-delay: 1s;"></div>
                        <div class="absolute bottom-10 left-20 w-18 h-18 bg-white rounded-full floating" style="animation-delay: 3s;"></div>
                    </div>
                    <div class="container mx-auto px-4 flex flex-col md:flex-row items-center relative z-10">
                        <div class="md:w-1/2 mb-8 md:mb-0">
                            <h2 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                                Koleksi <span class="text-green-300">Terbaru</span>
                            </h2>
                            <p class="text-white/90 text-lg mb-8 max-w-md">Dapatkan gantungan HP dengan desain kekinian dan bahan premium untuk melengkapi gaya Anda. Limited edition hanya untuk Anda!</p>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <button onclick="window.location.href='produk.php';" class="px-8 py-4 bg-white text-purple-500 font-bold rounded-xl hover:bg-gray-100 transition transform hover:scale-105 shadow-lg">
                                    <i class="fas fa-star mr-2"></i>Lihat Koleksi
                                </button>
                                <?php if ($user_logged_in && $user_role === 'pelanggan'): ?>
                                    <button onclick="window.location.href='wishlist.php';" class="px-8 py-4 border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition transform hover:scale-105">
                                        <i class="fas fa-heart mr-2"></i>Wishlist
                                    </button>
                                <?php else: ?>
                                    <button onclick="showLoginAlert()" class="px-8 py-4 border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition transform hover:scale-105">
                                        <i class="fas fa-heart mr-2"></i>Wishlist
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="md:w-1/2 flex justify-center">
                            <div class="relative">
                                <img src="https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981834_1280.png" alt="Gantungan HP" class="w-full max-w-lg floating" style="animation-delay: 1s;">
                                <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-green-400 rounded-2xl rotate-12 opacity-80"></div>
                                <div class="absolute -top-4 -left-4 w-20 h-20 bg-white rounded-2xl -rotate-12 opacity-80"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="w-full flex-shrink-0 relative">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 py-20 md:py-28 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-1/3 left-10 w-20 h-20 bg-white rounded-full floating" style="animation-delay: 2s;"></div>
                        <div class="absolute bottom-1/4 right-10 w-16 h-16 bg-white rounded-full floating" style="animation-delay: 4s;"></div>
                    </div>
                    <div class="container mx-auto px-4 flex flex-col md:flex-row items-center relative z-10">
                        <div class="md:w-1/2 mb-8 md:mb-0">
                            <h2 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                                Diskon <span class="text-orange-300">Spesial</span>
                            </h2>
                            <p class="text-white/90 text-lg mb-8 max-w-md">Dapatkan penawaran spesial untuk pembelian pertama dengan diskon hingga 30%. Buruan sebelum kehabisan!</p>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <button onclick="window.location.href='produk.php';" class="px-8 py-4 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition transform hover:scale-105 shadow-lg pulse">
                                    <i class="fas fa-tag mr-2"></i>Dapatkan 30% OFF
                                </button>
                                <button onclick="window.location.href='produk.php';" class="px-8 py-4 border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition transform hover:scale-105">
                                    <i class="fas fa-gift mr-2"></i>Lihat Promo
                                </button>
                            </div>
                        </div>
                        <div class="md:w-1/2 flex justify-center">
                            <div class="relative">
                                <img src="https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981835_1280.png" alt="Gantungan HP" class="w-full max-w-lg floating" style="animation-delay: 2s;">
                                <div class="absolute -bottom-4 -left-4 w-24 h-24 bg-orange-400 rounded-2xl rotate-12 opacity-80"></div>
                                <div class="absolute -top-4 -right-4 w-20 h-20 bg-white rounded-2xl -rotate-12 opacity-80"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Slider Controls -->
        <button id="prev" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/20 backdrop-blur-md p-4 rounded-full shadow-lg hover:bg-white/30 transition glass">
            <i class="fas fa-chevron-left text-white text-xl"></i>
        </button>
        <button id="next" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/20 backdrop-blur-md p-4 rounded-full shadow-lg hover:bg-white/30 transition glass">
            <i class="fas fa-chevron-right text-white text-xl"></i>
        </button>
        
        <!-- Slider Indicators -->
        <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex space-x-3">
            <button class="slider-indicator w-4 h-4 rounded-full bg-white/50 border-2 border-white hover:bg-white transition" data-slide="0"></button>
            <button class="slider-indicator w-4 h-4 rounded-full bg-white/50 border-2 border-white hover:bg-white transition" data-slide="1"></button>
            <button class="slider-indicator w-4 h-4 rounded-full bg-white/50 border-2 border-white hover:bg-white transition" data-slide="2"></button>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Kenapa Memilih <span class="gradient-text">Moods Strap?</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Kami memberikan yang terbaik untuk pelanggan dengan kualitas premium dan pelayanan terbaik.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Kualitas Premium</h3>
                    <p class="text-gray-600">Bahan berkualitas tinggi dengan jaminan kualitas terbaik untuk produk kami.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-shipping-fast text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Gratis Ongkir</h3>
                    <p class="text-gray-600">Gratis pengiriman ke seluruh Medan untuk pembelian di atas Rp 100.000.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Support 12/5</h3>
                    <p class="text-gray-600">Tim customer service siap membantu Anda kapan saja selama 12 jam.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Produk <span class="gradient-text">Unggulan</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Temukan gantungan aksesoris HP terpopuler dengan desain eksklusif dan kualitas terbaik.</p>
            </div>
            
            <div id="products-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php
                if ($result_produk && mysqli_num_rows($result_produk) > 0) {
                    while ($produk = mysqli_fetch_assoc($result_produk)) {
                        $harga = number_format($produk['harga'], 0, ',', '.');
                        $harga_diskon = number_format($produk['harga'] * 1.2, 0, ',', '.');
                        $stok = $produk['stok'] > 0 ? "Tersedia" : "Habis";
                        $stok_class = $produk['stok'] > 0 ? "bg-green-500" : "bg-red-500";
                        $stok_badge = $produk['stok'] > 0 ? "bg-green-500" : "bg-red-500";
                        
                        // PERBAIKAN PATH GAMBAR: Gunakan path relatif yang benar
                        // Asumsi: file ini berada di folder pelanggan, dan folder uploads berada di root
                        $gambar_produk = $produk['foto'] ? '../uploads/produk/' . $produk['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                        
                        // Alternatif jika folder uploads berada di admin
                        // $gambar_produk = $produk['foto'] ? '../admin/uploads/produk/' . $produk['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                        
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
                                <div class="absolute top-4 left-4">
                                    <span class="bg-yellow-400 text-gray-800 text-xs font-bold px-3 py-1 rounded-full">
                                        <i class="fas fa-star mr-1"></i>4.8
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2">' . htmlspecialchars($produk['nama_produk']) . '</h3>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">' . htmlspecialchars(substr($produk['deskripsi'] ?: 'Deskripsi tidak tersedia', 0, 80)) . '...</p>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-2xl pink-text">Rp ' . $harga . '</span>
                                        <p class="text-gray-500 text-sm line-through">Rp ' . $harga_diskon . '</p>
                                        ' . ($produk['stok'] > 0 ? '<p class="text-green-500 text-sm font-semibold">Stok: ' . $produk['stok'] . '</p>' : '<p class="text-red-500 text-sm font-semibold">Stok Habis</p>') . '
                                    </div>
                                    ' . ($user_logged_in && $user_role === 'pelanggan' ? '
                                    <button onclick="addToCart(' . $produk['id_produk'] . ')" 
                                            class="w-12 h-12 gradient-bg text-white rounded-xl hover:shadow-lg transition transform hover:scale-110 flex items-center justify-center ' . ($produk['stok'] == 0 ? 'opacity-50 cursor-not-allowed' : '') . '" 
                                            ' . ($produk['stok'] == 0 ? 'disabled' : '') . '>
                                        ' . ($produk['stok'] == 0 ? '<i class="fas fa-times"></i>' : '<i class="fas fa-shopping-cart"></i>') . '
                                    </button>
                                    ' : '
                                    <button onclick="showLoginAlert()" 
                                            class="w-12 h-12 gradient-bg text-white rounded-xl hover:shadow-lg transition transform hover:scale-110 flex items-center justify-center">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                    ') . '
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '
                    <div class="col-span-4 text-center py-8">
                        <div class="bg-gray-50 rounded-2xl p-12">
                            <i class="fas fa-box-open text-gray-400 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-xl">Tidak ada produk yang tersedia.</p>
                            <p class="text-gray-400 mt-2">Silakan kembali lagi nanti.</p>
                        </div>
                    </div>';
                }
                ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="produk.php" class="inline-flex items-center px-8 py-4 gradient-bg text-white font-bold rounded-2xl hover:shadow-xl transition transform hover:scale-105">
                    <i class="fas fa-eye mr-3"></i>Lihat Semua Produk
                </a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-gradient-to-r from-purple-600 to-pink-500 text-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Statistik <span class="text-yellow-300">Toko</span></h2>
                <p class="text-white/80 text-lg max-w-2xl mx-auto">Lihat perkembangan toko Moods Strap dalam angka yang terus bertumbuh.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center glass rounded-2xl p-8 hover:scale-105 transition">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-box text-3xl"></i>
                    </div>
                    <h3 class="text-4xl font-bold mb-2" id="counter1">0</h3>
                    <p class="text-white/80">Total Produk</p>
                </div>
                
                <div class="text-center glass rounded-2xl p-8 hover:scale-105 transition">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-cart text-3xl"></i>
                    </div>
                    <h3 class="text-4xl font-bold mb-2" id="counter2">0</h3>
                    <p class="text-white/80">Total Penjualan</p>
                </div>
                
                <div class="text-center glass rounded-2xl p-8 hover:scale-105 transition">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tags text-3xl"></i>
                    </div>
                    <h3 class="text-4xl font-bold mb-2" id="counter3">0</h3>
                    <p class="text-white/80">Kategori</p>
                </div>
                
                <div class="text-center glass rounded-2xl p-8 hover:scale-105 transition">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-3xl"></i>
                    </div>
                    <h3 class="text-4xl font-bold mb-2" id="counter4">0</h3>
                    <p class="text-white/80">Produk Terjual</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Promo Section -->
    <section class="py-16 bg-gradient-to-r from-pink-50 to-purple-50 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-72 h-72 bg-pink-200 rounded-full -translate-x-1/2 -translate-y-1/2 opacity-50"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-200 rounded-full translate-x-1/2 translate-y-1/2 opacity-50"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between">
                <div class="lg:w-1/2 mb-8 lg:mb-0 text-center lg:text-left">
                    <h2 class="text-4xl font-bold text-gray-800 mb-4">Dapatkan <span class="gradient-text">Diskon 30%</span> untuk Pembelian Pertama</h2>
                    <p class="text-gray-600 text-lg mb-6 max-w-xl">Bergabunglah dengan komunitas Moods Strap dan nikmati penawaran spesial untuk pembelian pertama Anda. Jangan lewatkan kesempatan ini!</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <?php if ($user_logged_in && $user_role === 'pelanggan'): ?>
                            <button onclick="window.location.href='produk.php';" class="px-8 py-4 gradient-bg text-white font-bold rounded-2xl hover:shadow-xl transition transform hover:scale-105 flex items-center justify-center">
                                <i class="fas fa-gift mr-3"></i>Belanja Sekarang
                            </button>
                        <?php else: ?>
                            <button onclick="window.location.href='../auth/register.php';" class="px-8 py-4 gradient-bg text-white font-bold rounded-2xl hover:shadow-xl transition transform hover:scale-105 flex items-center justify-center">
                                <i class="fas fa-gift mr-3"></i>Daftar Sekarang
                            </button>
                        <?php endif; ?>
                        <button onclick="window.location.href='produk.php';" class="px-8 py-4 border-2 border-pink-500 text-pink-500 font-bold rounded-2xl hover:bg-pink-50 transition transform hover:scale-105">
                            Pelajari Selengkapnya
                        </button>
                    </div>
                </div>
                <div class="lg:w-1/2 flex justify-center">
                    <div class="relative">
                        <div class="w-80 h-80 gradient-bg rounded-full flex items-center justify-center shadow-2xl pulse">
                            <div class="text-center text-white">
                                <div class="text-6xl font-bold">30%</div>
                                <div class="text-xl font-semibold">OFF</div>
                                <div class="text-sm mt-2 opacity-90">Pembelian Pertama</div>
                            </div>
                        </div>
                        <div class="absolute -top-4 -right-4 w-16 h-16 bg-yellow-400 rounded-2xl rotate-12 flex items-center justify-center shadow-lg">
                            <i class="fas fa-bolt text-white text-xl"></i>
                        </div>
                        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-green-400 rounded-2xl -rotate-12 flex items-center justify-center shadow-lg">
                            <i class="fas fa-check text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Apa Kata <span class="gradient-text">Pelanggan</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Lihat pengalaman nyata pelanggan kami dengan produk Moods Strap.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gradient-to-br from-pink-50 to-purple-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl">A</div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 text-lg">Syeila</h4>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"Gantungan HP dari Moods Strap sangat lucu dan berkualitas. Pengiriman cepat dan packagingnya rapi sekali! Recommended banget!"</p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl">R</div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 text-lg">Rara</h4>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"Desainnya unik dan bahan yang digunakan premium. Teman-teman saya sampai minta link belinya! Pelayanan juga ramah banget."</p>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-green-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl">D</div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 text-lg">Egi</h4>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"Sudah beli beberapa kali dan selalu puas. Pelayanannya ramah dan produknya sesuai dengan gambar. Bakal repeat order lagi!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-16 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-4xl font-bold mb-4">Tetap <span class="gradient-text">Terhubung</span></h2>
                <p class="text-gray-400 text-lg mb-8 max-w-2xl mx-auto">Berlangganan newsletter kami untuk mendapatkan informasi tentang produk baru, promo eksklusif, dan penawaran spesial.</p>
                <div class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                    <input type="email" placeholder="Email Anda" class="flex-grow px-6 py-4 bg-gray-800 border border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-pink-500 text-white">
                    <button class="px-8 py-4 gradient-bg text-white font-bold rounded-2xl hover:shadow-xl transition transform hover:scale-105 whitespace-nowrap">
                        <i class="fas fa-paper-plane mr-2"></i>Berlangganan
                    </button>
                </div>
                <p class="text-gray-500 text-sm mt-4">Dengan berlangganan, Anda menyetujui kebijakan privasi kami.</p>
            </div>
        </div>
    </section>

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
                        <li class="flex items-center">
                            <i class="fas fa-clock pink-text mr-4 w-5"></i>
                            <span class="text-gray-400">10:00 - 17:00 WIB</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">© 2025 Moods Strap. All rights reserved. Made with <i class="fas fa-heart text-pink-500 mx-1"></i> for you</p>
            </div>
        </div>
    </footer>

    <script>
        // Slider functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('slider');
            const prevButton = document.getElementById('prev');
            const nextButton = document.getElementById('next');
            const indicators = document.querySelectorAll('.slider-indicator');
            
            let currentSlide = 0;
            const totalSlides = 3;
            let autoSlideInterval;
            
            function goToSlide(index) {
                currentSlide = index;
                slider.style.transform = `translateX(-${currentSlide * 100}%)`;
                
                // Update indicators
                indicators.forEach((indicator, i) => {
                    if (i === currentSlide) {
                        indicator.classList.add('bg-white');
                        indicator.classList.remove('bg-white/50');
                    } else {
                        indicator.classList.remove('bg-white');
                        indicator.classList.add('bg-white/50');
                    }
                });
            }
            
            function nextSlide() {
                currentSlide = (currentSlide + 1) % totalSlides;
                goToSlide(currentSlide);
            }
            
            function prevSlide() {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                goToSlide(currentSlide);
            }
            
            function startAutoSlide() {
                autoSlideInterval = setInterval(nextSlide, 5000);
            }
            
            function stopAutoSlide() {
                clearInterval(autoSlideInterval);
            }
            
            // Event listeners
            nextButton.addEventListener('click', () => {
                stopAutoSlide();
                nextSlide();
                startAutoSlide();
            });
            
            prevButton.addEventListener('click', () => {
                stopAutoSlide();
                prevSlide();
                startAutoSlide();
            });
            
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    stopAutoSlide();
                    goToSlide(index);
                    startAutoSlide();
                });
            });
            
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
            
            // Counter animation for statistics
            function animateCounter(element, target, duration = 2000) {
                let start = 0;
                const increment = target / (duration / 16);
                const timer = setInterval(() => {
                    start += increment;
                    if (start >= target) {
                        element.textContent = target;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(start);
                    }
                }, 16);
            }
            
            // Initialize counters when section is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(document.getElementById('counter1'), <?php echo $total_produk; ?>);
                        animateCounter(document.getElementById('counter2'), <?php echo $total_penjualan; ?>);
                        animateCounter(document.getElementById('counter3'), <?php echo $total_kategori; ?>);
                        animateCounter(document.getElementById('counter4'), <?php echo $produk_terjual; ?>);
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            const statsSection = document.querySelector('.bg-gradient-to-r');
            if (statsSection) {
                observer.observe(statsSection);
            }
            
            // Initialize
            goToSlide(0);
            startAutoSlide();
        });

        // Function to show login alert
        function showLoginAlert() {
            alert('Silakan login terlebih dahulu untuk menggunakan fitur ini.');
            window.location.href = '../auth/login.php';
        }

        // Function to add product to cart
        function addToCart(productId) {
            fetch('ajax/add_to_cart.php', {
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