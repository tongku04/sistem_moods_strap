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

// Cek apakah ada parameter id pesanan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: pesanan.php');
    exit;
}

$id_penjualan = $_GET['id'];

// Ambil detail pesanan - PERBAIKAN QUERY DI SINI
$query_pesanan = "SELECT p.*, pl.nama_lengkap, pl.email
                  FROM penjualan p
                  JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                  WHERE p.id_penjualan = '$id_penjualan' 
                  AND p.id_pelanggan = '{$data_pelanggan['id_pelanggan']}'";
$result_pesanan = mysqli_query($koneksi, $query_pesanan);
$pesanan = mysqli_fetch_assoc($result_pesanan);

if (!$pesanan) {
    die("Pesanan tidak ditemukan atau tidak ada akses");
}

// Cek status pesanan
if ($pesanan['status_pesanan'] !== 'pending') {
    header('Location: detail_pesanan.php?id=' . $id_penjualan);
    exit;
}

// Ambil detail produk pesanan
$query_detail = "SELECT dp.*, pr.nama_produk, pr.foto, pr.harga
                 FROM detail_penjualan dp
                 JOIN produk pr ON dp.id_produk = pr.id_produk
                 WHERE dp.id_penjualan = '$id_penjualan'";
$result_detail = mysqli_query($koneksi, $query_detail);

// Format data
$tanggal_pesanan = date('d M Y H:i', strtotime($pesanan['tanggal']));
$total_harga = number_format($pesanan['total'], 0, ',', '.');
$no_pesanan = str_pad($pesanan['id_penjualan'], 6, '0', STR_PAD_LEFT);

// Proses pembayaran jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_pembayaran = mysqli_real_escape_string($koneksi, $_POST['metode_pembayaran']);
    $bukti_pembayaran = null;
    
    // Upload bukti pembayaran jika ada
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === 0) {
        $file_name = $_FILES['bukti_pembayaran']['name'];
        $file_tmp = $_FILES['bukti_pembayaran']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = '../admin/uploads/bukti_pembayaran/' . $new_file_name;
            
            // Buat folder jika belum ada
            if (!file_exists('../admin/uploads/bukti_pembayaran/')) {
                mkdir('../admin/uploads/bukti_pembayaran/', 0777, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $bukti_pembayaran = $new_file_name;
            } else {
                $error_message = "Gagal mengunggah file. Pastikan folder uploads memiliki izin yang tepat.";
            }
        } else {
            $error_message = "Format file tidak didukung. Hanya JPG, PNG, GIF, dan PDF yang diperbolehkan.";
        }
    } else {
        // Cek error pada upload file
        if (isset($_FILES['bukti_pembayaran'])) {
            switch ($_FILES['bukti_pembayaran']['error']) {
                case 1:
                case 2:
                    $error_message = "File terlalu besar. Maksimal 2MB.";
                    break;
                case 3:
                    $error_message = "File hanya terunggah sebagian.";
                    break;
                case 4:
                    $error_message = "Silakan pilih file bukti pembayaran.";
                    break;
                case 6:
                case 7:
                case 8:
                    $error_message = "Terjadi kesalahan server. Silakan coba lagi.";
                    break;
            }
        }
    }
    
    // Jika tidak ada error, lanjutkan proses pembayaran
    if (!isset($error_message)) {
        // Update data pembayaran
        if ($bukti_pembayaran) {
            $update_query = "UPDATE penjualan SET 
                            metode_pembayaran = '$metode_pembayaran',
                            bukti_pembayaran = '$bukti_pembayaran',
                            status_pesanan = 'diproses'
                            WHERE id_penjualan = '$id_penjualan'";
        } else {
            $update_query = "UPDATE penjualan SET 
                            metode_pembayaran = '$metode_pembayaran',
                            status_pesanan = 'diproses'
                            WHERE id_penjualan = '$id_penjualan'";
        }
        
        if (mysqli_query($koneksi, $update_query)) {
            // Redirect ke halaman konfirmasi
            header('Location: konfirmasi_pembayaran.php?id=' . $id_penjualan);
            exit;
        } else {
            $error_message = "Gagal menyimpan pembayaran: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Moods Strap</title>
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
        
        .payment-method {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .payment-method:hover {
            transform: translateY(-2px);
        }
        
        .payment-method.selected {
            border-color: #ff69b4;
            background-color: rgba(255, 105, 180, 0.05);
        }
        
        .file-upload {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        
        .file-upload:hover {
            border-color: #ff69b4;
            background-color: rgba(255, 105, 180, 0.05);
        }
        
        .file-upload.dragover {
            border-color: #ff69b4;
            background-color: rgba(255, 105, 180, 0.1);
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
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <a href="pesanan.php" class="ml-1 text-sm font-medium text-gray-700 hover:text-pink-500 md:ml-2">Pesanan</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <span class="ml-1 text-sm font-medium text-pink-500 md:ml-2">Pembayaran</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Pembayaran Pesanan</h1>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Order Details -->
                <div class="space-y-6">
                    <!-- Order Summary -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Ringkasan Pesanan</h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Nomor Pesanan</span>
                                <span class="font-semibold">#<?php echo $no_pesanan; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Tanggal Pesanan</span>
                                <span><?php echo $tanggal_pesanan; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Status</span>
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Menunggu Pembayaran</span>
                            </div>
                            <?php if (!empty($pesanan['nama_lengkap'])): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Nama Pelanggan</span>
                                <span><?php echo htmlspecialchars($pesanan['nama_lengkap']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr class="my-6 border-gray-200">
                        
                        <!-- Order Items -->
                        <h3 class="font-bold text-gray-800 mb-4">Detail Produk</h3>
                        <div class="space-y-4">
                            <?php 
                            // Reset pointer result detail
                            mysqli_data_seek($result_detail, 0);
                            while ($detail = mysqli_fetch_assoc($result_detail)): 
                            ?>
                                <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-16 h-16 bg-gradient-to-br from-pink-50 to-purple-50 rounded-lg flex items-center justify-center">
                                        <img src="<?php echo $detail['foto'] ? '../admin/uploads/produk/' . $detail['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($detail['nama_produk']); ?>" 
                                             class="w-12 h-12 object-contain"
                                             onerror="this.src='https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($detail['nama_produk']); ?></h4>
                                        <p class="text-gray-600 text-sm">Rp <?php echo number_format($detail['harga'], 0, ',', '.'); ?> x <?php echo $detail['jumlah']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-800">Rp <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <hr class="my-6 border-gray-200">
                        
                        <!-- Total -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Subtotal</span>
                                <span>Rp <?php echo $total_harga; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Ongkos Kirim</span>
                                <span>Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-bold">
                                <span class="text-gray-800">Total Pembayaran</span>
                                <span class="pink-text text-xl">Rp <?php echo $total_harga; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Instruksi Pembayaran</h2>
                        
                        <div class="space-y-4">
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <h4 class="font-semibold text-blue-800 mb-2">Transfer Bank</h4>
                                <p class="text-blue-700 text-sm mb-3">Silakan transfer ke rekening berikut:</p>
                                <div class="space-y-2">
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-blue-600">Bank Mandiri</span>
                                        <span class="font-mono font-bold text-blue-800">987 654 3210</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-blue-600">Bank BRI</span>
                                        <span class="font-mono font-bold text-blue-800">3539 0104 1085 536</span>
                                    </div>
                                </div>
                                <p class="text-blue-700 text-sm mt-3"><strong>Atas Nama:</strong> Moods Strap</p>
                            </div>
                            
                            <div class="p-4 bg-green-50 rounded-lg">
                                <h4 class="font-semibold text-green-800 mb-2">E-Wallet</h4>
                                <p class="text-green-700 text-sm mb-3">Pembayaran via e-wallet:</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-green-600">DANA</span>
                                        <span class="font-mono font-bold text-green-800">0812 3456 7890</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-green-600">OVO</span>
                                        <span class="font-mono font-bold text-green-800">0812 3456 7890</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-green-600">ShopeePay</span>
                                        <span class="font-mono font-bold text-green-800">0812 3456 7890</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                <p><strong>Catatan:</strong> Pesanan akan diproses setelah pembayaran dikonfirmasi.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Payment Form -->
                <div class="space-y-6">
                    <!-- Payment Form -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Informasi Pembayaran</h2>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <!-- Payment Method Selection -->
                            <div class="mb-8">
                                <h3 class="font-semibold text-gray-700 mb-4">Pilih Metode Pembayaran</h3>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="payment-method border-2 border-gray-200 rounded-xl p-4 text-center" onclick="selectPaymentMethod('bank_transfer')">
                                        <div class="w-12 h-12 gradient-bg rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-university text-white text-lg"></i>
                                        </div>
                                        <span class="font-medium">Transfer Bank</span>
                                        <div class="mt-2">
                                            <i class="fas fa-check-circle text-green-500 hidden" id="check-bank"></i>
                                        </div>
                                        <input type="radio" name="metode_pembayaran" value="bank_transfer" class="hidden" id="radio-bank">
                                    </div>
                                    
                                    <div class="payment-method border-2 border-gray-200 rounded-xl p-4 text-center" onclick="selectPaymentMethod('e_wallet')">
                                        <div class="w-12 h-12 gradient-bg rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-wallet text-white text-lg"></i>
                                        </div>
                                        <span class="font-medium">E-Wallet</span>
                                        <div class="mt-2">
                                            <i class="fas fa-check-circle text-green-500 hidden" id="check-ewallet"></i>
                                        </div>
                                        <input type="radio" name="metode_pembayaran" value="e_wallet" class="hidden" id="radio-ewallet">
                                    </div>
                                </div>
                                
                                <div class="mt-4 text-sm text-red-500 hidden" id="payment-error">
                                    Silakan pilih metode pembayaran
                                </div>
                            </div>



                            <!-- Order Total -->
                            <div class="bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl p-6 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 font-medium">Total Pembayaran:</span>
                                    <span class="text-2xl font-bold pink-text">Rp <?php echo $total_harga; ?></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-4">
                                <a href="pesanan.php" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium text-center">
                                    Kembali
                                </a>
                                <button type="submit" 
                                        class="flex-1 px-6 py-3 gradient-bg text-white rounded-xl hover:shadow-lg transition font-medium flex items-center justify-center"
                                        id="submit-btn">
                                    <i class="fas fa-check-circle mr-2"></i>Konfirmasi Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Information Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-info-circle text-blue-500 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-2">Informasi Penting</h4>
                                <ul class="space-y-2 text-blue-700 text-sm">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Pesanan akan diproses maksimal 1x24 jam setelah pembayaran dikonfirmasi</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Pastikan bukti pembayaran jelas dan terbaca</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                        <span>Hubungi customer service jika ada kendala dalam pembayaran</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
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
            
            // File upload functionality
            const fileInput = document.getElementById('bukti_pembayaran');
            const fileUploadArea = document.getElementById('file-upload-area');
            const filePreview = document.getElementById('file-preview');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const fileError = document.getElementById('file-error');
            const paymentError = document.getElementById('payment-error');
            const submitBtn = document.getElementById('submit-btn');
            
            // Handle drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                fileUploadArea.classList.add('dragover');
            }
            
            function unhighlight() {
                fileUploadArea.classList.remove('dragover');
            }
            
            fileUploadArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }
            
            fileInput.addEventListener('change', function(e) {
                handleFiles(this.files);
            });
            
            function handleFiles(files) {
                if (files.length > 0) {
                    const file = files[0];
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                    
                    if (!validTypes.includes(file.type)) {
                        fileError.textContent = 'Format file tidak didukung. Harap upload file JPG, PNG, GIF, atau PDF.';
                        fileError.classList.remove('hidden');
                        return;
                    }
                    
                    if (file.size > 2 * 1024 * 1024) { // 2MB
                        fileError.textContent = 'File terlalu besar. Maksimal 2MB.';
                        fileError.classList.remove('hidden');
                        return;
                    }
                    
                    // Hide error
                    fileError.classList.add('hidden');
                    
                    // Show preview
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    filePreview.classList.remove('hidden');
                }
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            window.removeFile = function() {
                fileInput.value = '';
                filePreview.classList.add('hidden');
            };
        });

        // Payment method selection
        function selectPaymentMethod(method) {
            // Remove selection from all
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelectorAll('.payment-method i.fa-check-circle').forEach(el => {
                el.classList.add('hidden');
            });
            document.querySelectorAll('input[type="radio"]').forEach(el => {
                el.checked = false;
            });
            
            // Add selection to clicked method
            if (method === 'bank_transfer') {
                document.querySelector('.payment-method:nth-child(1)').classList.add('selected');
                document.getElementById('check-bank').classList.remove('hidden');
                document.getElementById('radio-bank').checked = true;
                document.getElementById('selected-method').value = 'bank_transfer';
                document.getElementById('payment-error').classList.add('hidden');
            } else {
                document.querySelector('.payment-method:nth-child(2)').classList.add('selected');
                document.getElementById('check-ewallet').classList.remove('hidden');
                document.getElementById('radio-ewallet').checked = true;
                document.getElementById('selected-method').value = 'e_wallet';
                document.getElementById('payment-error').classList.add('hidden');
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="metode_pembayaran"]:checked');
            const fileInput = document.getElementById('bukti_pembayaran');
            const paymentError = document.getElementById('payment-error');
            const fileError = document.getElementById('file-error');
            let isValid = true;
            
            // Validate payment method
            if (!paymentMethod) {
                paymentError.classList.remove('hidden');
                isValid = false;
            } else {
                paymentError.classList.add('hidden');
            }
            
            // Validate file upload
            if (fileInput.files.length === 0) {
                fileError.textContent = 'Silakan upload bukti pembayaran terlebih dahulu.';
                fileError.classList.remove('hidden');
                isValid = false;
            } else {
                fileError.classList.add('hidden');
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
            submitButton.disabled = true;
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