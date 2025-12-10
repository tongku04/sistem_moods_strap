<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Moods Strap - Gantungan Aksesoris HP Terbaru</title>
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
        
        /* Contact card hover effect */
        .contact-card {
            transition: all 0.3s ease;
        }
        
        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 105, 180, 0.2);
        }
        
        /* Form input focus effect */
        .form-input:focus {
            border-color: #ff69b4;
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        
        /* Map container */
        .map-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        /* Social media hover effects */
        .social-instagram:hover {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }
        
        .social-tiktok:hover {
            background: #000000;
        }
        
        .social-whatsapp:hover {
            background: #25D366;
        }
        
        .social-facebook:hover {
            background: #1877F2;
        }
        
        /* Loading animation for form */
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
                <a href="tentang.php" class="nav-link text-gray-700 font-medium hover:text-pink-500 transition">Tentang Kami</a>
                <a href="kontak.php" class="nav-link text-pink-500 font-semibold transition">Kontak</a>
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
                <a href="auth/Register.php" class="p-2 text-gray-700 hover:text-pink-500 transition group">
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
                <a href="tentang.php" class="block py-3 px-4 text-gray-700 hover:text-pink-500 hover:bg-pink-50 rounded-lg transition mb-2">
                    <i class="fas fa-info-circle mr-3"></i>Tentang Kami
                </a>
                <a href="kontak.php" class="block py-3 px-4 text-pink-500 bg-pink-50 rounded-lg font-semibold transition">
                    <i class="fas fa-envelope mr-3"></i>Kontak
                </a>
            </nav>
            
            <!-- Social Media Links in Mobile Menu -->
            <div class="p-4 border-t border-gray-100">
                <h4 class="font-bold text-gray-800 mb-4">Ikuti Kami</h4>
                <div class="flex space-x-4">
                    <a href="https://instagram.com/moodsstrap" target="_blank" class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center text-white hover:bg-pink-600 transition transform hover:scale-110">
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
                <h1 class="text-5xl md:text-6xl font-bold mb-6">Hubungi <span class="text-yellow-300">Kami</span></h1>
                <p class="text-xl text-white/90 mb-8 leading-relaxed">
                    Kami siap membantu Anda! Jangan ragu untuk menghubungi kami melalui berbagai channel yang tersedia. 
                    Tim customer service kami siap melayani dengan senang hati.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#contact-form" class="px-8 py-4 bg-white text-pink-500 font-bold rounded-2xl hover:bg-gray-100 transition transform hover:scale-105">
                        <i class="fas fa-envelope mr-2"></i>Kirim Pesan
                    </a>
                    <a href="https://wa.me/6282162961621" target="_blank" class="px-8 py-4 border-2 border-white text-white font-bold rounded-2xl hover:bg-white/10 transition transform hover:scale-105">
                        <i class="fab fa-whatsapp mr-2"></i>Chat WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Informasi <span class="gradient-text">Kontak</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Beberapa cara untuk menghubungi kami. Pilih yang paling nyaman untuk Anda!</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                <!-- Phone -->
                <div class="contact-card bg-white rounded-3xl p-8 shadow-lg text-center">
                    <div class="w-20 h-20 gradient-bg rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-phone text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Telepon</h3>
                    <p class="text-gray-600 mb-4">Hubungi kami langsung untuk konsultasi cepat</p>
                    <a href="tel:+6281234567890" class="text-pink-500 font-semibold hover:text-pink-600 transition">
                        +62 821 6269 1621
                    </a>
                    <p class="text-gray-500 text-sm mt-2">Senin - Jumaat, 10:00 - 17:00 WIB</p>
                </div>
                
                <!-- WhatsApp -->
                <div class="contact-card bg-white rounded-3xl p-8 shadow-lg text-center">
                    <div class="w-20 h-20 bg-green-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fab fa-whatsapp text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">WhatsApp</h3>
                    <p class="text-gray-600 mb-4">Chat langsung dengan tim customer service</p>
                    <a href="https://wa.me/6282162961621" target="_blank" class="inline-flex items-center px-6 py-3 bg-green-500 text-white font-semibold rounded-2xl hover:bg-green-600 transition transform hover:scale-105">
                        <i class="fab fa-whatsapp mr-2"></i>Chat Sekarang
                    </a>
                </div>
                
                <!-- Email -->
                <div class="contact-card bg-white rounded-3xl p-8 shadow-lg text-center">
                    <div class="w-20 h-20 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-envelope text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Email</h3>
                    <p class="text-gray-600 mb-4">Kirim pertanyaan detail melalui email</p>
                    <a href="mailto:info@moodsstrap.com" class="text-pink-500 font-semibold hover:text-pink-600 transition">
                        infomoodsstrap@gmail.com
                    </a>
                    <p class="text-gray-500 text-sm mt-2">Response dalam 12 jam</p>
                </div>
                
                <!-- Location -->
                <div class="contact-card bg-white rounded-3xl p-8 shadow-lg text-center">
                    <div class="w-20 h-20 bg-purple-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-map-marker-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Lokasi</h3>
                    <p class="text-gray-600 mb-4">Kunjungi kantor kami langsung</p>
                    <p class="text-gray-700 font-medium">Medan, Indonesia</p>
                    <p class="text-gray-500 text-sm mt-2">Politeknik Negeri Medan</p>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Ikuti Kami di Media Sosial</h3>
                <div class="flex justify-center space-x-6">
                    <a href="https://www.instagram.com/moods_strap?igsh=aXExOGozazVycmk2" target="_blank" class="social-instagram w-16 h-16 bg-pink-500 rounded-2xl flex items-center justify-center text-white hover:shadow-xl transition transform hover:scale-110">
                        <i class="fab fa-instagram text-2xl"></i>
                    
                    </a>
                    <a href="https://wa.me/6282162961621" target="_blank" class="social-whatsapp w-16 h-16 bg-green-500 rounded-2xl flex items-center justify-center text-white hover:shadow-xl transition transform hover:scale-110">
                        <i class="fab fa-whatsapp text-2xl"></i>
                    
                    </a>
                </div>
                <p class="text-gray-600 mt-4">@moodsstrap di semua platform</p>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div id="contact-form">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Kirim <span class="gradient-text">Pesan</span></h2>
                    <p class="text-gray-600 mb-8">Isi form di bawah ini dan kami akan membalas pesan Anda secepatnya.</p>
                    
                    <form id="contactForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-gray-700 font-semibold mb-2">Nama Lengkap *</label>
                                <input type="text" id="name" name="name" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-2xl form-input focus:outline-none focus:ring-2 focus:ring-pink-500"
                                       placeholder="Masukkan nama lengkap">
                            </div>
                            <div>
                                <label for="email" class="block text-gray-700 font-semibold mb-2">Email *</label>
                                <input type="email" id="email" name="email" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-2xl form-input focus:outline-none focus:ring-2 focus:ring-pink-500"
                                       placeholder="nama@email.com">
                            </div>
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-gray-700 font-semibold mb-2">Nomor Telepon</label>
                            <input type="tel" id="phone" name="phone" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-2xl form-input focus:outline-none focus:ring-2 focus:ring-pink-500"
                                   placeholder="+62 812 3456 7890">
                        </div>
                        
                        <div>
                            <label for="subject" class="block text-gray-700 font-semibold mb-2">Subjek *</label>
                            <select id="subject" name="subject" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-2xl form-input focus:outline-none focus:ring-2 focus:ring-pink-500">
                                <option value="">Pilih subjek pesan</option>
                                <option value="pertanyaan-produk">Pertanyaan tentang Produk</option>
                                <option value="pemesanan">Pemesanan & Pembelian</option>
                                <option value="pengiriman">Info Pengiriman</option>
                                <option value="keluhan">Keluhan & Saran</option>
                                <option value="kerjasama">Kerjasama & Partnership</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="message" class="block text-gray-700 font-semibold mb-2">Pesan *</label>
                            <textarea id="message" name="message" rows="6" required 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-2xl form-input focus:outline-none focus:ring-2 focus:ring-pink-500 resize-none"
                                      placeholder="Tulis pesan Anda di sini..."></textarea>
                        </div>
                        
                        <button type="submit" id="submit-btn" 
                                class="w-full px-8 py-4 gradient-bg text-white font-bold rounded-2xl hover:shadow-xl transition transform hover:scale-105 flex items-center justify-center">
                            <i class="fas fa-paper-plane mr-3"></i>
                            <span id="submit-text">Kirim Pesan</span>
                            <div id="submit-loading" class="loading ml-2 hidden"></div>
                        </button>
                        
                        <div id="form-message" class="hidden p-4 rounded-2xl text-center"></div>
                    </form>
                </div>
                
                <!-- Map & Additional Info -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Lokasi <span class="gradient-text">Kami</span></h2>
                    
                    <!-- Map -->
                    <div class="map-container mb-8">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.1062805036127!2d98.65297457349007!3d3.5629988504723498!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x303131d6e3d2b367%3A0xc5edba7e577329d2!2sPoliteknik%20Negeri%20Medan!5e0!3m2!1sid!2sid!4v1763570544859!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>"
                        </iframe>
                    </div>
                    
                    <!-- Business Hours -->
                    <div class="bg-white rounded-3xl p-6 shadow-lg mb-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-clock text-pink-500 mr-3"></i>
                            Jam Operasional
                        </h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Senin - Jumat</span>
                                <span class="font-semibold text-gray-800">09:00 - 17:00 WIB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Sabtu - Minggu</span>
                                <span class="font-semibold text-gray-800">10:00 - 18:00 WIB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Hari Libur</span>
                                <span class="font-semibold text-gray-800">Tutup</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Quick Links -->
                    <div class="bg-white rounded-3xl p-6 shadow-lg">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-question-circle text-pink-500 mr-3"></i>
                            Pertanyaan Umum
                        </h3>
                        <div class="space-y-3">
                            <a href="#" class="block text-gray-600 hover:text-pink-500 transition flex items-center">
                                <i class="fas fa-chevron-right text-xs mr-3 text-pink-500"></i>
                                Berapa lama waktu pengiriman?
                            </a>
                            <a href="#" class="block text-gray-600 hover:text-pink-500 transition flex items-center">
                                <i class="fas fa-chevron-right text-xs mr-3 text-pink-500"></i>
                                Apakah tersedia pengembalian produk?
                            </a>
                            <a href="#" class="block text-gray-600 hover:text-pink-500 transition flex items-center">
                                <i class="fas fa-chevron-right text-xs mr-3 text-pink-500"></i>
                                Bagaimana cara melakukan pembelian?
                            </a>
                            <a href="#" class="block text-gray-600 hover:text-pink-500 transition flex items-center">
                                <i class="fas fa-chevron-right text-xs mr-3 text-pink-500"></i>
                                Apakah tersedia grosir?
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Pertanyaan <span class="gradient-text">Umum</span></h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Beberapa pertanyaan yang sering diajukan oleh pelanggan kami.</p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <div class="space-y-6">
                    <!-- FAQ 1 -->
                    <div class="bg-gray-50 rounded-3xl p-6 hover:shadow-lg transition">
                        <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-shipping-fast text-pink-500 mr-3"></i>
                            Berapa lama waktu pengiriman?
                        </h3>
                        <p class="text-gray-600">
                            Waktu pengiriman bervariasi tergantung lokasi Anda. Untuk area Medan: 1 hari kerja, 
                            Jabodetabek: 2-4 hari kerja, Pulau Jawa: 4-5 hari kerja, Luar Jawa: 6-8 hari kerja. Kami menggunakan jasa ekspedisi terpercaya 
                            untuk memastikan produk sampai dengan aman.
                        </p>
                    </div>
                    
                    <!-- FAQ 2 -->
                    <div class="bg-gray-50 rounded-3xl p-6 hover:shadow-lg transition">
                        <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-undo-alt text-pink-500 mr-3"></i>
                            Apakah tersedia pengembalian produk?
                        </h3>
                        <p class="text-gray-600">
                            Ya, kami menerima pengembalian produk dalam kondisi tertentu. Produk dapat dikembalikan dalam 
                            waktu 7 hari setelah diterima dengan syarat: kemasan lengkap, dan tidak ada 
                            kerusakan. Silakan hubungi customer service untuk proses lebih lanjut.
                        </p>
                    </div>
                    
                    <!-- FAQ 3 -->
                    <div class="bg-gray-50 rounded-3xl p-6 hover:shadow-lg transition">
                        <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-credit-card text-pink-500 mr-3"></i>
                            Metode pembayaran apa saja yang diterima?
                        </h3>
                        <p class="text-gray-600">
                            Kami menerima berbagai metode pembayaran: Transfer Bank (BCA, Mandiri, BNI, BRI), E-money (Gopay, 
                            OVO, Dana). Semua transaksi diproses 
                            dengan sistem keamanan terenkripsi.
                        </p>
                    </div>
                    
                    <!-- FAQ 4 -->
                    <div class="bg-gray-50 rounded-3xl p-6 hover:shadow-lg transition">
                        <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-boxes text-pink-500 mr-3"></i>
                            Apakah tersedia grosir?
                        </h3>
                        <p class="text-gray-600">
                            Tentu! Kami menyediakan harga khusus untuk pembelian grosir dengan minimum order 24 pcs. 
                            Untuk informasi lebih detail mengenai harga grosir dan syaratnya, silakan hubungi tim sales 
                            kami melalui WhatsApp atau email.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-pink-500 to-purple-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-4">Masih Ada <span class="text-yellow-300">Pertanyaan?</span></h2>
            <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                Jangan ragu untuk menghubungi kami. Tim customer service kami siap membantu 5 hari seminggu!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://wa.me/6282162961621" target="_blank" class="px-8 py-4 bg-white text-pink-500 font-bold rounded-2xl hover:bg-gray-100 transition transform hover:scale-105">
                    <i class="fab fa-whatsapp mr-2"></i>Chat WhatsApp
                </a>
                <a href="tel:+6281234567890" class="px-8 py-4 border-2 border-white text-white font-bold rounded-2xl hover:bg-white/10 transition transform hover:scale-105">
                    <i class="fas fa-phone mr-2"></i>Telepon Sekarang
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
            
            // Contact form handling
            const contactForm = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submit-btn');
            const submitText = document.getElementById('submit-text');
            const submitLoading = document.getElementById('submit-loading');
            const formMessage = document.getElementById('form-message');
            
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                submitText.textContent = 'Mengirim...';
                submitLoading.classList.remove('hidden');
                submitBtn.disabled = true;
                
                // Simulate form submission
                setTimeout(() => {
                    // Show success message
                    formMessage.textContent = 'Pesan Anda berhasil dikirim! Kami akan membalas dalam 24 jam.';
                    formMessage.className = 'bg-green-100 text-green-700 p-4 rounded-2xl text-center';
                    formMessage.classList.remove('hidden');
                    
                    // Reset form
                    contactForm.reset();
                    
                    // Reset button state
                    submitText.textContent = 'Kirim Pesan';
                    submitLoading.classList.add('hidden');
                    submitBtn.disabled = false;
                    
                    // Hide message after 5 seconds
                    setTimeout(() => {
                        formMessage.classList.add('hidden');
                    }, 5000);
                }, 2000);
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