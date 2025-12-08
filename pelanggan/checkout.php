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

// Ambil item keranjang
$query_keranjang = "SELECT k.*, p.nama_produk, p.harga, p.stok, p.foto 
                    FROM keranjang k 
                    JOIN produk p ON k.id_produk = p.id_produk 
                    WHERE k.id_pelanggan = '$id_pelanggan' 
                    AND p.status = 'active'";
$result_keranjang = mysqli_query($koneksi, $query_keranjang);

// Hitung total dan cek stok
$total_harga = 0;
$items = [];
$error_stok = false;

while ($item = mysqli_fetch_assoc($result_keranjang)) {
    $subtotal = $item['harga'] * $item['jumlah'];
    $total_harga += $subtotal;
    
    // Cek stok
    if ($item['stok'] < $item['jumlah']) {
        $error_stok = true;
    }
    
    $items[] = $item;
}

// Jika keranjang kosong, redirect ke keranjang
if (empty($items)) {
    header('Location: keranjang.php');
    exit;
}

// Proses checkout jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi alamat pengiriman
    $alamat_pengiriman = mysqli_real_escape_string($koneksi, $_POST['alamat_pengiriman']);
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    
    if (empty($alamat_pengiriman)) {
        $error = "Alamat pengiriman harus diisi";
    } elseif ($error_stok) {
        $error = "Beberapa produk stok tidak mencukupi. Silakan periksa keranjang Anda.";
    } else {
        // Mulai transaction
        mysqli_begin_transaction($koneksi);
        
        try {
            // 1. Insert ke tabel penjualan
            $query_penjualan = "INSERT INTO penjualan (
                total, bayar, kembalian, status_pembayaran, 
                id_user, id_pelanggan, jenis_penjualan, status_pesanan, 
                alamat_pengiriman
            ) VALUES (
                '$total_harga', '$total_harga', 0, 'pending',
                '$user_id', '$id_pelanggan', 'online', 'pending',
                '$alamat_pengiriman'
            )";
            
            if (!mysqli_query($koneksi, $query_penjualan)) {
                throw new Exception("Gagal membuat pesanan: " . mysqli_error($koneksi));
            }
            
            $id_penjualan = mysqli_insert_id($koneksi);
            
            // 2. Insert detail penjualan dan update stok
            foreach ($items as $item) {
                $id_produk = $item['id_produk'];
                $jumlah = $item['jumlah'];
                $harga = $item['harga'];
                $subtotal = $harga * $jumlah;
                
                // Insert detail penjualan
                $query_detail = "INSERT INTO detail_penjualan (
                    id_penjualan, id_produk, jumlah, subtotal
                ) VALUES (
                    '$id_penjualan', '$id_produk', '$jumlah', '$subtotal'
                )";
                
                if (!mysqli_query($koneksi, $query_detail)) {
                    throw new Exception("Gagal menambah detail pesanan: " . mysqli_error($koneksi));
                }
                
                // Update stok produk
                $query_update_stok = "UPDATE produk SET stok = stok - $jumlah WHERE id_produk = '$id_produk'";
                if (!mysqli_query($koneksi, $query_update_stok)) {
                    throw new Exception("Gagal update stok: " . mysqli_error($koneksi));
                }
            }
            
            // 3. Kosongkan keranjang
            $query_hapus_keranjang = "DELETE FROM keranjang WHERE id_pelanggan = '$id_pelanggan'";
            if (!mysqli_query($koneksi, $query_hapus_keranjang)) {
                throw new Exception("Gagal mengosongkan keranjang: " . mysqli_error($koneksi));
            }
            
            // 4. Log aktivitas
            $aktivitas = "Checkout pesanan";
            $deskripsi = "Pelanggan $username melakukan checkout dengan total Rp " . number_format($total_harga, 0, ',', '.');
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            $query_log = "INSERT INTO log_aktivitas (id_user, aktivitas, deskripsi, ip_address, user_agent) 
                         VALUES ('$user_id', '$aktivitas', '$deskripsi', '$ip_address', '$user_agent')";
            mysqli_query($koneksi, $query_log);
            
            // Commit transaction
            mysqli_commit($koneksi);
            
            // Redirect ke halaman sukses
            header('Location: pesanan_sukses.php?id=' . $id_penjualan);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction jika ada error
            mysqli_rollback($koneksi);
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Moods Strap</title>
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
                <a href="pesanan.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Pesanan</a>
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
                        <a href="pesanan.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
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
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <a href="keranjang.php" class="ml-1 text-sm font-medium text-gray-700 hover:text-pink-500 md:ml-2">Keranjang</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <span class="ml-1 text-sm font-medium text-pink-500 md:ml-2">Checkout</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Checkout Pesanan</h1>
            <p class="text-gray-600 mb-8">Lengkapi informasi pengiriman dan pembayaran</p>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_stok): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <span>Beberapa produk stok tidak mencukupi. Silakan periksa keranjang Anda.</span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Informasi Pengiriman & Pembayaran -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Informasi Pengiriman -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-truck mr-3 pink-text"></i>
                            Informasi Pengiriman
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" value="<?php echo htmlspecialchars($data_pelanggan['nama_lengkap']); ?>" 
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" value="<?php echo htmlspecialchars($data_pelanggan['email']); ?>" 
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent bg-gray-50" readonly>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telepon</label>
                                <input type="text" value="<?php echo htmlspecialchars($data_pelanggan['telepon'] ?? '-'); ?>" 
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent bg-gray-50" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Pengiriman <span class="text-red-500">*</span></label>
                                <textarea name="alamat_pengiriman" rows="4" 
                                          class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                          placeholder="Masukkan alamat lengkap pengiriman..." required><?php echo htmlspecialchars($data_pelanggan['alamat'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                                <textarea name="catatan" rows="3" 
                                          class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                          placeholder="Catatan untuk penjual..."><?php echo htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-credit-card mr-3 pink-text"></i>
                            Metode Pembayaran
                        </h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-pink-300 cursor-pointer">
                                <input type="radio" name="metode_pembayaran" value="transfer" class="text-pink-500 focus:ring-pink-500" checked>
                                <div class="ml-3">
                                    <span class="font-medium text-gray-800">Transfer Bank</span>
                                    <p class="text-sm text-gray-600">Transfer ke rekening BCA, BNI, BRI, atau Mandiri</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-pink-300 cursor-pointer">
                                <input type="radio" name="metode_pembayaran" value="qris" class="text-pink-500 focus:ring-pink-500">
                                <div class="ml-3">
                                    <span class="font-medium text-gray-800">QRIS</span>
                                    <p class="text-sm text-gray-600">Bayar dengan scan QR code</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-pink-300 cursor-pointer">
                                <input type="radio" name="metode_pembayaran" value="cod" class="text-pink-500 focus:ring-pink-500">
                                <div class="ml-3">
                                    <span class="font-medium text-gray-800">Cash on Delivery (COD)</span>
                                    <p class="text-sm text-gray-600">Bayar ketika pesanan diterima</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Pesanan -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-receipt mr-3 pink-text"></i>
                            Ringkasan Pesanan
                        </h2>
                        
                        <!-- Daftar Produk -->
                        <div class="space-y-4 mb-6 max-h-80 overflow-y-auto">
                            <?php foreach ($items as $item): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-16 h-16 bg-gradient-to-br from-pink-50 to-purple-50 rounded-lg flex items-center justify-center">
                                        <img src="<?php echo $item['foto'] ? '../admin/uploads/produk/' . $item['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" 
                                             class="w-12 h-12 object-contain"
                                             onerror="this.src='https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800 text-sm line-clamp-2"><?php echo htmlspecialchars($item['nama_produk']); ?></h4>
                                        <p class="text-gray-600 text-sm"><?php echo number_format($item['harga'], 0, ',', '.'); ?> x <?php echo $item['jumlah']; ?></p>
                                        <?php if ($item['stok'] < $item['jumlah']): ?>
                                            <p class="text-red-500 text-xs font-semibold">Stok tidak cukup</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-800">Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Total -->
                        <div class="border-t border-gray-200 pt-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="text-gray-800">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Ongkos Kirim</span>
                                <span class="text-green-600">Gratis</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-bold pt-3 border-t border-gray-200">
                                <span class="text-gray-800">Total</span>
                                <span class="pink-text text-xl">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                        
                        <!-- Tombol Checkout -->
                        <button type="submit" 
                                class="w-full gradient-bg text-white font-bold py-4 px-6 rounded-xl hover:shadow-lg transition transform hover:scale-105 mt-6 flex items-center justify-center"
                                <?php echo $error_stok ? 'disabled' : ''; ?>>
                            <i class="fas fa-lock mr-3"></i>
                            Buat Pesanan
                        </button>
                        
                        <p class="text-center text-gray-500 text-sm mt-3">
                            Dengan membuat pesanan, Anda menyetujui 
                            <a href="#" class="pink-text hover:underline">Syarat & Ketentuan</a>
                        </p>
                    </div>
                </div>
            </form>
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
    </script>
</body>
</html>

<?php
// Tutup koneksi database
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>