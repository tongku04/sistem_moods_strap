<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Moods Strap - Gantungan Aksesoris HP Terbaru</title>
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
        
        /* Timeline styles */
        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ff69b4;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 12px;
            width: 2px;
            height: calc(100% + 2rem);
            background: #e5e7eb;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        /* Team card hover effect */
        .team-card {
            transition: all 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 105, 180, 0.2);
        }
        
        /* Value card animation */
        .value-card {
            transition: all 0.3s ease;
        }
        
        .value-card:hover {
            transform: scale(1.05);
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
                        <img src="WhatsApp_Image_2025-11-13_at_08.21.58_d2b62406-removebg-preview.png" class="w-12 h-12 rounded-full object cover" alt="Logo">
                        <i class="fas fa-gem text-white text-sm"></i>
                    </div>
                    Moods <span class="text-gray-800">Strap</span>
                </a>
            </div>
            
            <nav class="hidden md:flex space-x-8">
                <a href="index.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Beranda</a>
                <a href="produk.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Produk</a>
                <a href="tentang.php" class="nav-link text-pink-500 font-semibold transition">Tentang Kami</a>
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
                <a href="koleksi.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-layer-group mr-3"></i>Koleksi
                </a>
                <a href="tentang.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition mb-2">
                    <i class="fas fa-info-circle mr-3"></i>Tentang Kami
                </a>
                <a href="kontak.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition">
                    <i class="fas fa-envelope mr-3"></i>Kontak
                </a>
            </nav>
            
            <!-- Social Media Links in Mobile Menu -->
            <div class="p-4 border-t border-gray-100">
                <h4 class="font-bold text-gray-800 mb-4">Ikuti Kami</h4>
                <div class="flex space-x-4">
                    <a href="https://www.instagram.com/moods_strap?igsh=aXExOGozazVycmk2 " target="_blank" class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center text-white hover:bg-pink-600 transition transform hover:scale-110">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://tiktok.com/@moodsstrap" target="_blank" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-white hover:bg-black transition transform hover:scale-110">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://wa.me/6281234567890" target="_blank" class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white hover:bg-green-600 transition transform hover:scale-110">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="https://facebook.com/moodsstrap" target="_blank" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white hover:bg-blue-700 transition transform hover:scale-110">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-pink-500 to-purple-600 py-20 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full floating"></div>
            <div class="absolute top-1/4 right-20 w-16 h-16 bg-white rounded-full floating" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/4 w-12 h-12 bg-white rounded-full floating" style="animation-delay: 4s;"></div>
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">Tentang <span class="text-yellow-300">Moods Strap</span></h1>
                <p class="text-xl text-white/90 mb-8 leading-relaxed">
                    Menghadirkan kebahagiaan melalui gantungan aksesoris HP yang tidak hanya fungsional, 
                    tetapi juga menjadi ekspresi gaya dan kepribadian Anda.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#visi-misi" class="px-8 py-4 bg-white text-pink-500 font-bold rounded-2xl hover:bg-gray-100 transition transform hover:scale-105">
                        <i class="fas fa-bullseye mr-2"></i>Visi & Misi
                    </a>
                    <a href="#tim-kami" class="px-8 py-4 border-2 border-white text-white font-bold rounded-2xl hover:bg-white/10 transition transform hover:scale-105">
                        <i class="fas fa-users mr-2"></i>Tim Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Story -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-6">Cerita <span class="gradient-text">Kami</span></h2>
                    <p class="text-gray-600 text-lg mb-6 leading-relaxed">
                        Moods Strap lahir dari passion terhadap fashion dan teknologi. Kami percaya bahwa aksesoris 
                        kecil seperti gantungan HP dapat membuat perbedaan besar dalam mengekspresikan kepribadian 
                        dan gaya hidup seseorang.
                    </p>
                    <p class="text-gray-600 text-lg mb-6 leading-relaxed">
                        Didirikan pada tahun 2025, kami memulai perjalanan dengan misi sederhana: menghadirkan 
                        produk berkualitas tinggi dengan desain yang unik dan terjangkau. Kini, Moods Strap 
                        telah berkembang menjadi brand terpercaya dengan puluhan pelanggan setia di seluruh Medan.
                    </p>
                   
                    </div>
                </div>
                <div class="relative">
                    <div class="bg-gradient-to-br from-pink-100 to-purple-100 rounded-3xl p-8">
                        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" 
                             alt="Workspace Moods Strap" 
                             class="w-full h-96 object-cover rounded-2xl shadow-lg">
                    </div>
                    <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-yellow-400 rounded-2xl rotate-12 flex items-center justify-center shadow-lg">
                        <i class="fas fa-heart text-white text-2xl"></i>
                    </div>
                    <div class="absolute -top-6 -right-6 w-20 h-20 gradient-bg rounded-2xl -rotate-12 flex items-center justify-center shadow-lg">
                        <i class="fas fa-star text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision & Mission -->
    <section id="visi-misi" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Visi & <span class="gradient-text">Misi</span> Kami</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Menjadi usaha kreatif dan inovatif yang menghasilkan berbagai produk strap berkualitas tinggi—baik tali maupun manik handmade—dengan desain unik, trendi, serta sesuai permintaan pasar. Usaha ini berkomitmen untuk menyediakan produk yang menarik dan terjangkau, memanfaatkan media sosial sebagai sarana utama promosi dan penjualan, serta terus berkembang secara berkelanjutan dengan menjaga kualitas, kepercayaan pelanggan, dan inovasi produk.
.</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Vision -->
                <div class="bg-white rounded-3xl p-8 shadow-lg hover:shadow-xl transition">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-eye text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Visi Kami</h3>
                    <p class="text-gray-600 text-lg leading-relaxed mb-6">
                        Menjadi usaha kreatif dan inovatif dalam menghasilkan produk strap berkualitas, unik, dan trendi yang diminati oleh semua kalangan.
.
                    </p>
                    <div class="bg-pink-50 rounded-2xl p-4">
                        <p class="text-pink-700 font-semibold italic">
                            "Membawa kebahagiaan melalui aksesoris yang tidak hanya cantik, tetapi juga bermakna."
                        </p>
                    </div>
                </div>
                
                <!-- Mission -->
                <div class="bg-white rounded-3xl p-8 shadow-lg hover:shadow-xl transition">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-bullseye text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Misi Kami</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-600">Menyediakan berbagai jenis strap tali dan strap manik dengan kualitas terbaik serta desain yang menarik.
</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-600">Menghasilkan strap manik handmade dengan desain menarik sesuai tren dan permintaan pasar.
</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-600">Menyediakan produk dengan harga terjangkau namun tetap berkualitas tinggi.
Memanfaatkan media sosial dan platform online sebagai sarana promosi dan penjualan utama.
</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-600">Mengembangkan usaha secara berkelanjutan dengan menjaga kualitas, kepercayaan pelanggan, dan inovasi produk.</span>
                        </li>
                        
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Nilai <span class="gradient-text">Kami</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Prinsip-prinsip yang menjadi fondasi dalam setiap keputusan dan tindakan kami.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="value-card bg-gradient-to-br from-pink-50 to-purple-50 rounded-3xl p-8 text-center hover:shadow-xl transition">
                    <div class="w-20 h-20 gradient-bg rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-lightbulb text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Kreativitas</h3>
                    <p class="text-gray-600">
                        Kami selalu berusaha menghadirkan desain yang unik dan fresh, 
                        menginspirasi melalui inovasi tanpa batas.
                    </p>
                </div>
                
                <div class="value-card bg-gradient-to-br from-purple-50 to-blue-50 rounded-3xl p-8 text-center hover:shadow-xl transition">
                    <div class="w-20 h-20 gradient-bg rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Kualitas</h3>
                    <p class="text-gray-600">
                        Setiap produk melalui proses quality control ketat untuk 
                        memastikan kepuasan dan kepercayaan pelanggan.
                    </p>
                </div>
                
                <div class="value-card bg-gradient-to-br from-blue-50 to-green-50 rounded-3xl p-8 text-center hover:shadow-xl transition">
                    <div class="w-20 h-20 gradient-bg rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-heart text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Passion</h3>
                    <p class="text-gray-600">
                        Kami melakukan segalanya dengan cinta dan dedikasi, 
                        karena kami percaya passion adalah kunci kesuksesan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Perjalanan <span class="gradient-text">Kami</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Cerita perkembangan Moods Strap dari awal hingga sekarang.</p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <div class="space-y-12">
                    <!-- 2025 -->
                    <div class="timeline-item">
                        <div class="bg-white rounded-3xl p-8 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold mr-4">
                                    2025
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800">Awal Perjalanan</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                Moods Strap resmi diluncurkan dengan koleksi pertama yang terdiri dari 10 desain unik. 
                                Kami memulai dengan tim kecil dan passion besar untuk menghadirkan aksesoris berkualitas.
                            </p>
                        </div>
                    </div>
                    
                    <!-- 2025 -->
                    <div class="timeline-item">
                        <div class="bg-white rounded-3xl p-8 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold mr-4">
                                    2025
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800">Ekspansi & Pertumbuhan</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                Mencapai 10 pelanggan pertama dan memperluas koleksi menjadi 14+ desain. 
                                Kami mulai berkolaborasi dengan mahasiswa untuk menghadirkan desain yang lebih beragam.
                            </p>
                        </div>
                    </div>
                    
                    <!-- 2025 -->
                    <div class="timeline-item">
                        <div class="bg-white rounded-3xl p-8 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold mr-4">
                                    2025
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800">Inovasi & Pengakuan</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                Meluncurkan line produk premium dengan material berkualitas tinggi. 
                            </p>
                        </div>
                    </div>
                    
                    <!-- 2025 -->
                    <div class="timeline-item">
                        <div class="bg-white rounded-3xl p-8 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold mr-4">
                                    2025
                                </div>
                                <h3 class="text-2xl font-bold text-gray-800">Masa Depan Cerah</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                Terus berinovasi dengan teknologi terbaru dan memperluas jangkauan ke seluruh Sumatra Utara. 
                                Berkomitmen untuk tetap menghadirkan produk terbaik dengan nilai-nilai yang kami pegang teguh.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section id="tim-kami" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Tim <span class="gradient-text">Kami</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Orang-orang kreatif dan berdedikasi di balik kesuksesan Moods Strap.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Team Member 1 -->
                <div class="team-card bg-white rounded-3xl p-6 shadow-lg text-center">
                    <div class="w-full h-64 flex items-center justify-center overflow-hidden rounded-xl">
                        <img src="foto_tongku-removebg-preview.png" alt="Tongku Guru Siregar" class="w-full h-full object-cover -[80%_50%]">
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Tongku Guru Siregar</h3>
                    <p class="text-pink-500 font-semibold mb-4">Back-end Developer</p>
                    <p class="text-gray-600 text-sm mb-4">
                        Back-End Developer Moods Strap bertanggung jawab membangun sistem yang stabil, aman, dan efisien sebagai fondasi utama platform. Kami mengelola server, database, dan logika aplikasi untuk memastikan layanan berjalan optimal tanpa hambatan.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <a href="https://www.instagram.com/anakorglewat?igsh=MWR5Yjk4NmYybHZzbw==" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-pink-500 hover:text-white transition">
                            <i class="fab fa-instagram text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="https://wa.me/628315055245"
                        class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-green-500 hover:text-white transition"
                        target="_blank">
                        <i class="fab fa-whatsapp text-xs"></i>
                    </a>
                        </a>
                    </div>
                </div>
                
                <!-- Team Member 2 -->
                <div class="team-card bg-white rounded-3xl p-6 shadow-lg text-center">
                    <div class="w-full h-64 flex items-center justify-center overflow-hidden rounded-xl">
                        <img src="foto_ayu-removebg-preview.png" alt="Ayu Musvita Dewi" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Ayu Musvita Dewi</h3>
                    <p class="text-pink-500 font-semibold mb-4">Marketing Strategist</p>
                    <p class="text-gray-600 text-sm mb-4">
                        Ahli strategi pemasaran yang membawa Moods Strap dikenal luas oleh mahasiswa.


                    </p>
                    <div class="flex justify-center space-x-3">
                        <a href="https://www.instagram.com/ayumsvtaa?igsh=MTJra2o5a3Vld2t6" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-pink-500 hover:text-white transition">
                            <i class="fab fa-instagram text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="https://wa.me/6282267789566"
                        class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-green-500 hover:text-white transition"
                        target="_blank">
                        <i class="fab fa-whatsapp text-xs"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Team Member 3 -->
                <div class="team-card bg-white rounded-3xl p-6 shadow-lg text-center">
                    <div class="w-full h-64 flex items-center justify-center overflow-hidden rounded-xl">
                        <img src="foto_rissa-removebg-preview.png" alt="Sekarissa Ramdhani Suriadi" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Sekarissa Ramadhani Suriadi</h3>
                    <p class="text-pink-500 font-semibold mb-4">Graphic Designer</p>
                    <p class="text-gray-600 text-sm mb-4">
                         Creative mind dengan kemampuan mengubah ide menjadi desain yang memukau.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <a href="https://www.instagram.com/hyskr_?igsh=NXZvbXpxNXA4cGh0" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-pink-500 hover:text-white transition">
                            <i class="fab fa-instagram text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="https://wa.me/6282162961621"
                        class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-green-500 hover:text-white transition"
                        target="_blank">
                        <i class="fab fa-whatsapp text-xs"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Team Member 4 -->
                <div class="team-card bg-white rounded-3xl p-6 shadow-lg text-center">
                    <div class="w-full h-64 flex items-center justify-center overflow-hidden rounded-xl">
                        <img src="foto_pedo-removebg-preview.png" alt="Johannes Alfredo Sitorus " class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Johannes Alfredo Sitorus</h3>
                    <p class="text-pink-500 font-semibold mb-4">Front-end Developer</p>
                    <p class="text-gray-600 text-sm mb-4">
                        Front-End Developer Moods Strap bertugas mengimplementasikan desain menjadi antarmuka yang interaktif, responsif, dan ramah pengguna.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <a href="https://www.instagram.com/alfredo_sitorus86?igsh=dWluN2FnNno1dWxk" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-pink-500 hover:text-white transition">
                            <i class="fab fa-instagram text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="#" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-500 hover:text-white transition">
                        <a href="https://wa.me/6282215254298"
                        class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-green-500 hover:text-white transition"
                        target="_blank">
                        <i class="fab fa-whatsapp text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-pink-500 to-purple-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-4">Tertarik dengan <span class="text-yellow-300">Kisah Kami?</span></h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                Mari bergabung dengan komunitas Moods Strap dan jadilah bagian dari perjalanan kami yang penuh warna!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="produk.php" class="px-8 py-4 bg-white text-pink-500 font-bold rounded-2xl hover:bg-gray-100 transition transform hover:scale-105">
                    <i class="fas fa-shopping-bag mr-2"></i>Lihat Produk
                </a>
                <a href="kontak.php" class="px-8 py-4 border-2 border-white text-white font-bold rounded-2xl hover:bg-white/10 transition transform hover:scale-105">
                    <i class="fas fa-envelope mr-2"></i>Hubungi Kami
                </a>
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
                            Moods <span class="text-gray-300">Strap</span>
                    </h3>
                    <p class="text-gray-400 mb-6">Toko online gantungan aksesoris HP dengan desain unik dan berkualitas tinggi untuk melengkapi gaya Hp Anda.</p>
                    <div class="flex space-x-4">
                        <a href="https://www.instagram.com/moods_strap?igsh=aXExOGozazVycmk2" target="_blank" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:gradient-bg transition group">
                            <i class="fab fa-instagram group-hover:text-white"></i>

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
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Kebijakan Privasi</a></li>
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
                            <span class="text-gray-400">+62 821 6296 2632</span>
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
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>