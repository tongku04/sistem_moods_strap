<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi - Moods Strap - Gantungan Aksesoris HP</title>
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
        
        /* Filter sidebar */
        .filter-sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .filter-sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 40;
                overflow-y: auto;
            }
            
            .filter-sidebar.active {
                transform: translateX(0);
            }
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
        
        /* Checkbox custom style */
        .custom-checkbox:checked {
            background-color: #ff69b4;
            border-color: #ff69b4;
        }
        
        /* Animation for filter */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
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
                <a href="index.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Beranda</a>
                <a href="produk.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Produk</a>
                <a href="koleksi.php" class="nav-link text-pink-500 font-semibold transition">Koleksi</a>
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
                <a href="/Berak/auth/login.php" class="p-2 text-gray-700 hover:text-pink-500 transition group">
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
                <a href="index.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-home mr-3"></i>Beranda
                </a>
                <a href="produk.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-box mr-3"></i>Produk
                </a>
                <a href="koleksi.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition mb-2">
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

    <!-- Page Header -->
    <section class="bg-gradient-to-r from-pink-500 to-purple-600 py-16 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full"></div>
            <div class="absolute top-1/4 right-20 w-16 h-16 bg-white rounded-full"></div>
            <div class="absolute bottom-20 left-1/4 w-12 h-12 bg-white rounded-full"></div>
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-4">Katalog <span class="text-yellow-300">Koleksi</span></h1>
                <p class="text-white/90 text-lg max-w-2xl mx-auto">Temukan berbagai macam gantungan aksesoris HP dengan desain unik dan kualitas terbaik untuk melengkapi gaya HP Anda.</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Filter Sidebar -->
                <div class="filter-sidebar lg:w-1/4 bg-white rounded-2xl shadow-lg p-6 h-fit sticky top-24">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Filter Koleksi</h3>
                        <button id="close-filter" class="lg:hidden text-gray-500 hover:text-pink-500">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    
                    <!-- Search -->
                    <div class="mb-6">
                        <div class="relative">
                            <input type="text" id="search-input" placeholder="Cari koleksi..." class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <i class="fas fa-search absolute right-4 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Kategori Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                            <span>Kategori</span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <?php
                            // Include koneksi database
                            include 'config/koneksi.php';
                            
                            // Query untuk mengambil kategori
                            $kategori_query = "SELECT * FROM kategori ORDER BY nama_kategori";
                            $kategori_result = mysqli_query($koneksi, $kategori_query);
                            
                            if ($kategori_result && mysqli_num_rows($kategori_result) > 0) {
                                while ($kategori = mysqli_fetch_assoc($kategori_result)) {
                                    echo '<label class="flex items-center cursor-pointer group">
                                            <input type="checkbox" name="kategori[]" value="' . $kategori['id_kategori'] . '" class="kategori-filter custom-checkbox rounded border-gray-300 text-pink-500 focus:ring-pink-500 mr-3">
                                            <span class="text-gray-700 group-hover:text-pink-500 transition">' . $kategori['nama_kategori'] . '</span>
                                          </label>';
                                }
                            } else {
                                echo '<p class="text-gray-500 text-sm">Tidak ada kategori</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Harga Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-4">Rentang Harga</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Min: Rp <span id="min-price-value">0</span></span>
                                <span class="text-sm text-gray-600">Max: Rp <span id="max-price-value">500.000</span></span>
                            </div>
                            <div class="space-y-4">
                                <input type="range" id="min-price" min="0" max="500000" value="0" step="10000" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                <input type="range" id="max-price" min="0" max="500000" value="500000" step="10000" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stok Filter -->
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-4">Ketersediaan</h4>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer group">
                                <input type="checkbox" name="stok" value="tersedia" class="stok-filter custom-checkbox rounded border-gray-300 text-pink-500 focus:ring-pink-500 mr-3">
                                <span class="text-gray-700 group-hover:text-pink-500 transition flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Tersedia
                                </span>
                            </label>
                            <label class="flex items-center cursor-pointer group">
                                <input type="checkbox" name="stok" value="habis" class="stok-filter custom-checkbox rounded border-gray-300 text-pink-500 focus:ring-pink-500 mr-3">
                                <span class="text-gray-700 group-hover:text-pink-500 transition flex items-center">
                                    <i class="fas fa-times-circle text-red-500 mr-2"></i>Habis
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button id="apply-filter" class="flex-1 px-4 py-3 gradient-bg text-white font-semibold rounded-2xl hover:shadow-lg transition transform hover:scale-105">
                            Terapkan Filter
                        </button>
                        <button id="reset-filter" class="px-4 py-3 border border-gray-300 text-gray-700 font-semibold rounded-2xl hover:bg-gray-50 transition">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Product Grid -->
                <div class="lg:w-3/4">
                    <!-- Toolbar -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                        <div class="flex items-center">
                            <button id="filter-toggle" class="lg:hidden flex items-center text-gray-700 bg-white px-4 py-3 rounded-2xl border border-gray-300 hover:border-pink-500 transition">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>
                            <p class="ml-4 text-gray-600" id="product-count">
                                Menampilkan semua koleksi
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <label class="text-gray-700 mr-2 font-medium">Urutkan:</label>
                                <select id="sort-by" class="border border-gray-300 rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                    <option value="terbaru">Terbaru</option>
                                    <option value="terlama">Terlama</option>
                                    <option value="harga_terendah">Harga Terendah</option>
                                    <option value="harga_tertinggi">Harga Tertinggi</option>
                                    <option value="nama_az">Nama A-Z</option>
                                    <option value="nama_za">Nama Z-A</option>
                                </select>
                            </div>
                            
                            <div class="hidden md:flex items-center space-x-2 bg-gray-100 p-1 rounded-2xl">
                                <button id="grid-view" class="p-2 text-gray-700 rounded-xl hover:bg-white transition">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button id="list-view" class="p-2 text-gray-700 rounded-xl hover:bg-white transition">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products -->
                    <div id="products-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        // Query untuk mengambil semua produk aktif
                        $produk_query = "SELECT p.*, k.nama_kategori 
                                        FROM produk p 
                                        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                                        WHERE p.status = 'active' 
                                        ORDER BY p.id_produk DESC";
                        $produk_result = mysqli_query($koneksi, $produk_query);
                        
                        // Cek apakah query berhasil
                        if (!$produk_result) {
                            echo '<div class="col-span-3 text-center py-8">
                                    <p class="text-red-500">Error: ' . mysqli_error($koneksi) . '</p>
                                  </div>';
                        } else {
                            // Cek apakah ada data
                            if (mysqli_num_rows($produk_result) > 0) {
                                // Tampilkan produk
                                while ($produk = mysqli_fetch_assoc($produk_result)) {
                                    $harga = number_format($produk['harga'], 0, ',', '.');
                                    $stok = $produk['stok'] > 0 ? "Tersedia" : "Habis";
                                    $stok_class = $produk['stok'] > 0 ? "text-green-500" : "text-red-500";
                                    $stok_badge = $produk['stok'] > 0 ? "bg-green-500" : "bg-red-500";
                                    
                                    // PERBAIKAN PATH GAMBAR
                                    $foto = $produk['foto'] ? 'uploads/produk/' . $produk['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                                    
                                    // Cek apakah file gambar ada di server
                                    $foto_path = $produk['foto'] ? $_SERVER['DOCUMENT_ROOT'] . '/Berak/uploads/produk/' . $produk['foto'] : '';
                                    if ($produk['foto'] && !file_exists($foto_path)) {
                                        $foto = 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png';
                                    }
                                    
                                    $kategori = $produk['nama_kategori'] ?: 'Uncategorized';
                                    $rating = rand(40, 50) / 10; // Random rating 4.0 - 5.0
                                    
                                    echo '
                                    <div class="product-card bg-white rounded-2xl shadow-lg overflow-hidden transition duration-300 border border-gray-100 fade-in" 
                                         data-id="' . $produk['id_produk'] . '" 
                                         data-kategori="' . $produk['id_kategori'] . '" 
                                         data-harga="' . $produk['harga'] . '" 
                                         data-stok="' . $produk['stok'] . '"
                                         data-rating="' . $rating . '"
                                         data-nama="' . strtolower($produk['nama_produk']) . '">
                                        <div class="relative overflow-hidden">
                                            <div class="p-6 gradient-bg flex items-center justify-center h-56">
                                                <img src="' . $foto . '" alt="' . $produk['nama_produk'] . '" 
                                                     class="w-full h-full object-contain transform hover:scale-110 transition duration-500"
                                                     onerror="this.src=\'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png\'">
                                            </div>
                                            <div class="absolute top-4 right-4">
                                                <span class="' . $stok_badge . ' text-white text-xs font-bold px-3 py-1 rounded-full">' . $stok . '</span>
                                            </div>
                                            <div class="absolute top-4 left-4">
                                                <span class="bg-yellow-400 text-gray-800 text-xs font-bold px-3 py-1 rounded-full">
                                                    <i class="fas fa-star mr-1"></i>' . $rating . '
                                                </span>
                                            </div>
                                            <div class="absolute bottom-4 left-4">
                                                <span class="bg-white/90 text-gray-700 text-xs font-medium px-3 py-1 rounded-full">
                                                    ' . $kategori . '
                                                </span>
                                            </div>
                                        </div>
                                        <div class="p-6">
                                            <h3 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2">' . $produk['nama_produk'] . '</h3>
                                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">' . substr($produk['deskripsi'], 0, 80) . '...</p>
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <span class="font-bold text-2xl pink-text">Rp ' . $harga . '</span>
                                                    <p class="text-gray-500 text-sm line-through">Rp ' . number_format($produk['harga'] * 1.2, 0, ',', '.') . '</p>
                                                </div>
                                                <button class="w-12 h-12 gradient-bg text-white rounded-xl hover:shadow-lg transition transform hover:scale-110 flex items-center justify-center ' . ($produk['stok'] == 0 ? 'opacity-50 cursor-not-allowed' : '') . '" ' . ($produk['stok'] == 0 ? 'disabled' : '') . '>
                                                    ' . ($produk['stok'] == 0 ? '<i class="fas fa-times"></i>' : '<i class="fas fa-shopping-cart"></i>') . '
                                                </button>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            } else {
                                echo '<div class="col-span-3 text-center py-8">
                                        <div class="bg-gray-50 rounded-2xl p-12">
                                            <i class="fas fa-box-open text-gray-400 text-6xl mb-4"></i>
                                            <p class="text-gray-500 text-xl mb-4">Tidak ada koleksi yang tersedia.</p>
                                            <a href="index.php" class="inline-flex items-center px-6 py-3 gradient-bg text-white font-semibold rounded-2xl hover:shadow-lg transition">
                                                <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                                            </a>
                                        </div>
                                      </div>';
                            }
                        }
                        
                        // Tutup koneksi
                        mysqli_close($koneksi);
                        ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex justify-center mt-12">
                        <nav class="flex items-center space-x-2">
                            <button class="px-4 py-2 border border-gray-300 rounded-2xl text-gray-500 hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="px-4 py-2 gradient-bg text-white rounded-2xl font-semibold">1</button>
                            <button class="px-4 py-2 border border-gray-300 rounded-2xl text-gray-700 hover:bg-gray-50 transition">2</button>
                            <button class="px-4 py-2 border border-gray-300 rounded-2xl text-gray-700 hover:bg-gray-50 transition">3</button>
                            <button class="px-4 py-2 border border-gray-300 rounded-2xl text-gray-500 hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-16 bg-gradient-to-r from-pink-50 to-purple-50">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold text-gray-800 mb-4">Dapatkan <span class="gradient-text">Promo Spesial</span></h2>
            <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">Berlangganan newsletter kami untuk mendapatkan informasi tentang koleksi baru dan penawaran eksklusif.</p>
            <div class="max-w-md mx-auto flex">
                <input type="email" placeholder="Email Anda" class="flex-grow px-6 py-4 rounded-l-2xl border border-r-0 focus:outline-none focus:ring-2 focus:ring-pink-500">
                <button class="px-8 py-4 gradient-bg text-white font-bold rounded-r-2xl hover:shadow-lg transition transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i>Berlangganan
                </button>
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
                        <a href="https://www.instagram.com/moods_strap?igsh=MTB1cnRreWNmejJ6OA==" target="_blank" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition group">
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
                        <li><a href="koleksi.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Koleksi</a></li>
                        <li><a href="tentang.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Tentang Kami</a></li>
                        <li><a href="kontak.php" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Kontak</a></li>
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
                            <span class="text-gray-400">+62 821 6296 1621</span>
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

                </div>
            </div>
        </div>
    </footer>

    <script>
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
            
            // Filter functionality
            const filterToggle = document.getElementById('filter-toggle');
            const filterSidebar = document.querySelector('.filter-sidebar');
            const closeFilter = document.getElementById('close-filter');
            
            filterToggle.addEventListener('click', () => {
                filterSidebar.classList.add('active');
            });
            
            closeFilter.addEventListener('click', () => {
                filterSidebar.classList.remove('active');
            });
            
            // Price range functionality
            const minPrice = document.getElementById('min-price');
            const maxPrice = document.getElementById('max-price');
            const minPriceValue = document.getElementById('min-price-value');
            const maxPriceValue = document.getElementById('max-price-value');
            
            function formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID').format(angka);
            }
            
            minPrice.addEventListener('input', function() {
                minPriceValue.textContent = formatRupiah(this.value);
                if (parseInt(minPrice.value) > parseInt(maxPrice.value)) {
                    maxPrice.value = minPrice.value;
                    maxPriceValue.textContent = formatRupiah(maxPrice.value);
                }
            });
            
            maxPrice.addEventListener('input', function() {
                maxPriceValue.textContent = formatRupiah(this.value);
                if (parseInt(maxPrice.value) < parseInt(minPrice.value)) {
                    minPrice.value = maxPrice.value;
                    minPriceValue.textContent = formatRupiah(minPrice.value);
                }
            });
            
            // Filter products
            const productCards = document.querySelectorAll('.product-card');
            const kategoriFilters = document.querySelectorAll('.kategori-filter');
            const stokFilters = document.querySelectorAll('.stok-filter');
            const searchInput = document.getElementById('search-input');
            const sortBy = document.getElementById('sort-by');
            const applyFilter = document.getElementById('apply-filter');
            const resetFilter = document.getElementById('reset-filter');
            const productCount = document.getElementById('product-count');
            
            function filterProducts() {
                const selectedKategori = Array.from(kategoriFilters)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);
                
                const selectedStok = Array.from(stokFilters)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);
                
                const searchTerm = searchInput.value.toLowerCase();
                const minPriceVal = parseInt(minPrice.value);
                const maxPriceVal = parseInt(maxPrice.value);
                
                let visibleCount = 0;
                
                productCards.forEach(card => {
                    let show = true;
                    
                    // Filter by kategori
                    if (selectedKategori.length > 0) {
                        const cardKategori = card.getAttribute('data-kategori');
                        if (!selectedKategori.includes(cardKategori)) {
                            show = false;
                        }
                    }
                    
                    // Filter by harga
                    const cardHarga = parseInt(card.getAttribute('data-harga'));
                    if (cardHarga < minPriceVal || cardHarga > maxPriceVal) {
                        show = false;
                    }
                    
                    // Filter by stok
                    if (selectedStok.length > 0) {
                        const cardStok = parseInt(card.getAttribute('data-stok'));
                        const isTersedia = cardStok > 0;
                        
                        if (selectedStok.includes('tersedia') && !isTersedia) {
                            show = false;
                        }
                        
                        if (selectedStok.includes('habis') && isTersedia) {
                            show = false;
                        }
                    }
                    
                    // Filter by search
                    if (searchTerm) {
                        const cardNama = card.getAttribute('data-nama');
                        if (!cardNama.includes(searchTerm)) {
                            show = false;
                        }
                    }
                    
                    if (show) {
                        card.style.display = 'block';
                        visibleCount++;
                        card.classList.add('fade-in');
                    } else {
                        card.style.display = 'none';
                        card.classList.remove('fade-in');
                    }
                });
                
                // Update product count
                productCount.textContent = `Menampilkan ${visibleCount} dari ${productCards.length} koleksi`;
            }
            
            // Add event listeners to filters
            applyFilter.addEventListener('click', filterProducts);
            searchInput.addEventListener('input', filterProducts);
            
            // Sort functionality
            sortBy.addEventListener('change', function() {
                const container = document.getElementById('products-container');
                const cards = Array.from(productCards).filter(card => card.style.display !== 'none');
                
                cards.sort((a, b) => {
                    const sortValue = this.value;
                    
                    switch(sortValue) {
                        case 'terbaru':
                            return parseInt(b.getAttribute('data-id')) - parseInt(a.getAttribute('data-id'));
                        case 'terlama':
                            return parseInt(a.getAttribute('data-id')) - parseInt(b.getAttribute('data-id'));
                        case 'harga_terendah':
                            return parseInt(a.getAttribute('data-harga')) - parseInt(b.getAttribute('data-harga'));
                        case 'harga_tertinggi':
                            return parseInt(b.getAttribute('data-harga')) - parseInt(a.getAttribute('data-harga'));
                        case 'nama_az':
                            return a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent);
                        case 'nama_za':
                            return b.querySelector('h3').textContent.localeCompare(a.querySelector('h3').textContent);
                        default:
                            return 0;
                    }
                });
                
                // Clear container and append sorted cards
                container.innerHTML = '';
                cards.forEach(card => {
                    container.appendChild(card);
                });
            });
            
            // Reset filter
            resetFilter.addEventListener('click', function() {
                kategoriFilters.forEach(checkbox => checkbox.checked = false);
                stokFilters.forEach(checkbox => checkbox.checked = false);
                searchInput.value = '';
                minPrice.value = 0;
                maxPrice.value = 500000;
                minPriceValue.textContent = '0';
                maxPriceValue.textContent = '500.000';
                sortBy.value = 'terbaru';
                
                productCards.forEach(card => {
                    card.style.display = 'block';
                    card.classList.add('fade-in');
                });
                
                productCount.textContent = `Menampilkan semua ${productCards.length} koleksi`;
            });
            
            // View toggle functionality
            const gridView = document.getElementById('grid-view');
            const listView = document.getElementById('list-view');
            const productsContainer = document.getElementById('products-container');
            
            gridView.addEventListener('click', function() {
                productsContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';
                gridView.classList.add('bg-white', 'text-pink-500');
                listView.classList.remove('bg-white', 'text-pink-500');
            });
            
            listView.addEventListener('click', function() {
                productsContainer.className = 'grid grid-cols-1 gap-6';
                listView.classList.add('bg-white', 'text-pink-500');
                gridView.classList.remove('bg-white', 'text-pink-500');
                
                // Adjust card layout for list view
                productCards.forEach(card => {
                    card.classList.add('flex', 'items-center');
                    const imgContainer = card.querySelector('.relative');
                    const contentContainer = card.querySelector('.p-6');
                    
                    if (imgContainer && contentContainer) {
                        imgContainer.className = 'relative w-1/3 flex-shrink-0';
                        imgContainer.querySelector('div').className = 'p-4 gradient-bg flex items-center justify-center h-48';
                        contentContainer.className = 'p-6 flex-grow';
                    }
                });
            });
            
            // Initialize price display
            minPriceValue.textContent = formatRupiah(minPrice.value);
            maxPriceValue.textContent = formatRupiah(maxPrice.value);
        });
    </script>
</body>
</html>