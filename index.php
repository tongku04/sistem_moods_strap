<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moods Strap - Gantungan Aksesoris HP Terbaru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS styles tetap sama seperti sebelumnya */
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
        
        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                <a href="index.php" class="nav-link text-pink-500 font-semibold transition">Beranda</a>
                <a href="produk.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Produk</a>
                <a href="koleksi.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Koleksi</a>
                <a href="tentang.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Tentang Kami</a>
                <a href="kontak.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Kontak</a>
            </nav>
            
            <div class="flex items-center space-x-4">
                <button class="p-2 text-gray-700 hover:text-pink-500 transition relative group">
                    <i class="fas fa-search"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-pink-500 rounded-full opacity-0 group-hover:opacity-100 transition"></span>
                </button>
                <button class="p-2 text-gray-700 hover:text-pink-500 transition relative group">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                </button>
                <a href="auth/login.php" class="p-2 text-gray-700 hover:text-pink-500 transition group">
                    <i class="fas fa-user"></i>
                </a>
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
                <a href="koleksi.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-layer-group mr-3"></i>Koleksi
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
                                <!-- TOMBOL LIhat Koleksi YANG BISA DITEKAN -->
                                <button onclick="window.location.href='koleksi.php';" class="px-8 py-4 bg-white text-purple-500 font-bold rounded-xl hover:bg-gray-100 transition transform hover:scale-105 shadow-lg">
                                    <i class="fas fa-star mr-2"></i>Lihat Koleksi
                                </button>
                                
                                <!-- TOMBOL Wishlist YANG BISA DITEKAN -->
                                <button onclick="window.location.href='produk.php';" class="px-8 py-4 border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition transform hover:scale-105">
                                    <i class="fas fa-heart mr-2"></i>Wishlist
                                </button>
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
                                <!-- TOMBOL Dapatkan diskon 30% YANG BISA DITEKAN -->
                                <button onclick="window.location.href='produk.php';" class="px-8 py-4 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition transform hover:scale-105 shadow-lg pulse">
                                    <i class="fas fa-tag mr-2"></i>Dapatkan 30% OFF
                                </button>
                                <!-- TOMBOL Lihat Promo YANG BISA DITEKAN -->   
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
                    <p class="text-gray-600">Gratis pengiriman ke seluruh medan untuk pembelian di atas Rp 100.000.</p>
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
                <?php
                // Include koneksi database
                include 'config/koneksi.php';
                
                // Query untuk mengambil produk pertama sebagai produk unggulan
                $query_unggulan = "SELECT * FROM produk WHERE status = 'active' ORDER BY id_produk DESC LIMIT 1";
                $result_unggulan = mysqli_query($koneksi, $query_unggulan);
                
                if ($result_unggulan && mysqli_num_rows($result_unggulan) > 0) {
                    $produk_unggulan = mysqli_fetch_assoc($result_unggulan);
                    $foto_unggulan = $produk_unggulan['foto'] ? 'uploads/produk/' . $produk_unggulan['foto'] : 'moodstrap pink hitam.jpg';
                    
                    echo '<img src="' . $foto_unggulan . '" alt="Produk Unggulan" class="mx-auto mt-8 w-64 h-auto rounded-2xl shadow-lg">';
                } else {
                    echo '<img src="moodstrap pink hitam.jpg" alt="Produk Unggulan" class="mx-auto mt-8 w-64 h-auto rounded-2xl shadow-lg">';
                }
                ?>
            </div>
            
            <div id="products-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php
                // Query untuk mengambil semua data produk
                $query = "SELECT * FROM produk WHERE status = 'active' ORDER BY id_produk DESC LIMIT 8";
                $result = mysqli_query($koneksi, $query);
                
                // Cek apakah query berhasil
                if (!$result) {
                    echo '<div class="col-span-4 text-center py-8">
                            <p class="text-red-500">Error: ' . mysqli_error($koneksi) . '</p>
                          </div>';
                } else {
                    // Cek apakah ada data
                    if (mysqli_num_rows($result) > 0) {
                        // Tampilkan produk
                        while ($row = mysqli_fetch_assoc($result)) {
                            $harga = number_format($row['harga'], 0, ',', '.');
                            $stok = $row['stok'] > 0 ? "Tersedia" : "Habis";
                            $stok_class = $row['stok'] > 0 ? "text-green-500" : "text-red-500";
                            $stok_badge = $row['stok'] > 0 ? "bg-green-500" : "bg-red-500";
                            
                            // Perbaikan path gambar
                            $foto = $row['foto'] ? 'uploads/produk/' . $row['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                            
                            echo '
                            <div class="product-card bg-white rounded-2xl shadow-lg overflow-hidden transition duration-300 border border-gray-100">
                                <div class="relative overflow-hidden">
                                    <div class="p-6 gradient-bg flex items-center justify-center h-56">
                                        <img src="' . $foto . '" alt="' . $row['nama_produk'] . '" class="w-full h-full object-contain transform hover:scale-110 transition duration-500">
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
                                    <h3 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2">' . $row['nama_produk'] . '</h3>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">' . substr($row['deskripsi'], 0, 80) . '...</p>
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-bold text-2xl pink-text">Rp ' . $harga . '</span>
                                            <p class="text-gray-500 text-sm line-through">Rp ' . number_format($row['harga'] * 1.2, 0, ',', '.') . '</p>
                                        </div>
                                        <button class="w-12 h-12 gradient-bg text-white rounded-xl hover:shadow-lg transition transform hover:scale-110 flex items-center justify-center ' . ($row['stok'] == 0 ? 'opacity-50 cursor-not-allowed' : '') . '" ' . ($row['stok'] == 0 ? 'disabled' : '') . '>
                                            ' . ($row['stok'] == 0 ? '<i class="fas fa-times"></i>' : '<i class="fas fa-shopping-cart"></i>') . '
                                        </button>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<div class="col-span-4 text-center py-8">
                                <div class="bg-gray-50 rounded-2xl p-12">
                                    <i class="fas fa-box-open text-gray-400 text-6xl mb-4"></i>
                                    <p class="text-gray-500 text-xl">Tidak ada produk yang tersedia.</p>
                                </div>
                              </div>';
                    }
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
                        <!-- Tombol Daftar Sekarang -->
                        <button onclick="window.location.href='SISTEM_MOODS_STRAP/auth/Register.php';" class="px-8 py-4 gradient-bg text-white font-bold rounded-2xl hover:shadow-xl transition transform hover:scale-105 flex items-center justify-center">
                            <i class="fas fa-gift mr-3"></i>Daftar Sekarang
                        </button>
                        
                        <!-- Tombol Pelajari Selengkapnya -->
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
                <!-- Testimonial 1 -->
                <div class="bg-gradient-to-br from-pink-50 to-purple-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl">A</div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 text-lg">Amelia Putri</h4>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"Gantungan HP dari Moods Strap sangat lucu dan berkualitas. Pengiriman cepat dan packagingnya rapi sekali! Recommended banget!"</p>
                </div>
                
                <!-- Testimonial 2 -->
                <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl">R</div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 text-lg">Rina Sari</h4>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic">"Desainnya unik dan bahan yang digunakan premium. Teman-teman saya sampai minta link belinya! Pelayanan juga ramah banget."</p>
                </div>
                
                <!-- Testimonial 3 -->
                <div class="bg-gradient-to-br from-blue-50 to-green-50 rounded-2xl p-8 shadow-lg hover:shadow-xl transition group">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl">D</div>
                        <div class="ml-4">
                            <h4 class="font-bold text-gray-800 text-lg">Dewi Lestari</h4>
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
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
                        <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-gem text-white text-sm"></i>
                        </div>
                        Moods <span class="text-gray-300">Strap</span>
                    </h3>
                    <p class="text-gray-400 mb-6">Toko online gantungan aksesoris HP dengan desain unik dan berkualitas tinggi untuk melengkapi gaya Anda.</p>
                    <div class="flex space-x-4">
                        <a href="https://www.instagram.com/moods_strap?igsh=aXExOGozazVycmk2" target="_blank" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/6282162961621?text=Halo%20Moods%20Strap,%20saya%20mau%20tanya%20tentang%20produk%20kalian" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition group">
                            <i class="fab fa-whatsapp group-hover:text-white"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Tautan Cepat</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Beranda</a></li>
                        <li><a href="produk.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Produk</a></li>
                        <li><a href="koleksi.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Koleksi</a></li>
                        <li><a href="tentang.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Tentang Kami</a></li>
                        <li><a href="kontak.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Kontak</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Bantuan</h4>
                    <ul class="space-y-3">
                        <li><a href="cara-belanja.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Cara Belanja</a></li>
                        <li><a href="pembayaran.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pembayaran</a></li>
                        <li><a href="pengiriman.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pengiriman</a></li>
                        <li><a href="faq.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>FAQ</a></li>
                        <li><a href="kebijakan-privasi.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Kebijakan Privasi</a></li>
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
                        <li class="flex items-center">
                            <i class="fas fa-clock pink-text mr-4 w-5"></i>
                            <span class="text-gray-400">10:00 - 17:00 WIB</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">© 2025 Moods Strap. All rights reserved. Made with <i class="fas fa-heart text-pink-500 mx-1"></i> for you</p>
                <div class="flex items-center space-x-6">
                    <!-- Payment methods can be added here -->
                </div>
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
                autoSlideInterval = setInterval(nextSlide, 6000);
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
            
            // Initialize
            goToSlide(0);
            startAutoSlide();
        });
    </script>
</body>
</html>