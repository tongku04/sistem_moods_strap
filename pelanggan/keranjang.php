<?php
session_start();
include '../config/koneksi.php';

// Cek apakah user sudah login sebagai pelanggan
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pelanggan') {
    header('Location: ../auth/login.php');
    exit;
}

$id_user = $_SESSION['user']['id_user'];

// Ambil data pelanggan
$query_pelanggan = "SELECT * FROM pelanggan WHERE id_user = '$id_user'";
$result_pelanggan = mysqli_query($koneksi, $query_pelanggan);
$pelanggan = mysqli_fetch_assoc($result_pelanggan);
$id_pelanggan = $pelanggan['id_pelanggan'];

// Handle actions: update, delete, clear
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update quantity
        foreach ($_POST['quantity'] as $id_keranjang => $quantity) {
            $quantity = intval($quantity);
            if ($quantity > 0) {
                $query_update = "UPDATE keranjang SET jumlah = '$quantity' WHERE id_keranjang = '$id_keranjang' AND id_pelanggan = '$id_pelanggan'";
                mysqli_query($koneksi, $query_update);
            } else {
                // Jika quantity 0, hapus dari keranjang
                $query_delete = "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_pelanggan = '$id_pelanggan'";
                mysqli_query($koneksi, $query_delete);
            }
        }
        $_SESSION['success'] = 'Keranjang berhasil diperbarui';
    } 
    elseif (isset($_POST['remove_item'])) {
        // Hapus item tertentu
        $id_keranjang = intval($_POST['id_keranjang']);
        $query_delete = "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_pelanggan = '$id_pelanggan'";
        mysqli_query($koneksi, $query_delete);
        $_SESSION['success'] = 'Item berhasil dihapus dari keranjang';
    }
    elseif (isset($_POST['clear_cart'])) {
        // Kosongkan keranjang
        $query_clear = "DELETE FROM keranjang WHERE id_pelanggan = '$id_pelanggan'";
        mysqli_query($koneksi, $query_clear);
        $_SESSION['success'] = 'Keranjang berhasil dikosongkan';
    }
    
    header('Location: keranjang.php');
    exit;
}

// Ambil data keranjang
$query_keranjang = "SELECT k.id_keranjang, k.jumlah, p.id_produk, p.nama_produk, p.harga, p.foto, p.stok 
                    FROM keranjang k 
                    JOIN produk p ON k.id_produk = p.id_produk 
                    WHERE k.id_pelanggan = '$id_pelanggan' 
                    ORDER BY k.created_at DESC";
$result_keranjang = mysqli_query($koneksi, $query_keranjang);

// Hitung total
$total_items = 0;
$total_price = 0;
$cart_items = [];

while ($item = mysqli_fetch_assoc($result_keranjang)) {
    $subtotal = $item['harga'] * $item['jumlah'];
    $total_items += $item['jumlah'];
    $total_price += $subtotal;
    $cart_items[] = $item;
}

// Format currency
function format_currency($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Moods Strap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .pink-text {
            color: #ff69b4;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #f9fafb;
            border-color: #ff69b4;
        }
        
        .quantity-input {
            width: 50px;
            height: 35px;
            border: 1px solid #e5e7eb;
            text-align: center;
            outline: none;
        }
        
        .cart-item {
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .empty-cart {
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
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
                <a href="keranjang.php" class="text-pink-500 font-semibold transition">Keranjang</a>
                <a href="pesanan.php" class="text-gray-700 font-medium hover:text-pink-500 transition">Pesanan</a>
            </nav>
            
            <div class="flex items-center space-x-4">
                <a href="index.php" class="p-2 text-gray-700 hover:text-pink-500 transition">
                    <i class="fas fa-home"></i>
                </a>
                <a href="keranjang.php" class="p-2 text-pink-500 transition relative">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($total_items > 0): ?>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center">
                            <?php echo $total_items; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="relative group">
                    <button class="flex items-center space-x-2 p-2 text-gray-700 hover:text-pink-500 transition">
                        <i class="fas fa-user"></i>
                        <span class="hidden md:inline"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">

                        </a>
                        <a href="pesanan.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                            <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                        </a>
                        <a href="../auth/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-pink-50 hover:text-pink-500 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-pink-500">
                        <i class="fas fa-home mr-2"></i>
                        Beranda
                    </a>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-pink-500">Keranjang Belanja</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Keranjang Belanja</h1>
                <p class="text-gray-600 mt-2">Kelola produk yang ingin Anda beli</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-lg font-semibold text-gray-700">
                    <i class="fas fa-shopping-cart mr-2 pink-text"></i>
                    <?php echo $total_items; ?> Item
                </span>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart State -->
            <div class="empty-cart rounded-2xl p-12 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-24 h-24 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shopping-cart text-white text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Keranjang Belanja Kosong</h2>
                    <p class="text-gray-600 mb-8">Yuk, tambahkan produk favorit Anda ke keranjang belanja!</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="produk.php" class="px-8 py-3 gradient-bg text-white font-semibold rounded-xl hover:shadow-lg transition transform hover:scale-105">
                            <i class="fas fa-shopping-bag mr-2"></i>Jelajahi Produk
                        </a>
                        <a href="index.php" class="px-8 py-3 border-2 border-pink-500 text-pink-500 font-semibold rounded-xl hover:bg-pink-50 transition">
                            <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800">Item Belanja</h2>
                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan keranjang?')">
                                <button type="submit" name="clear_cart" class="text-red-500 hover:text-red-700 transition flex items-center">
                                    <i class="fas fa-trash mr-2"></i>
                                    Kosongkan Keranjang
                                </button>
                            </form>
                        </div>
                        
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item p-6 hover:bg-gray-50 transition">
                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0">
                                            <div class="w-20 h-20 bg-gradient-to-br from-pink-50 to-purple-50 rounded-xl flex items-center justify-center">
                                                <img src="<?php echo $item['foto'] ? '../admin/uploads/produk/' . $item['foto'] : 'https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" 
                                                     class="w-16 h-16 object-contain"
                                                     onerror="this.src='https://cdn.pixabay.com/photo/2022/01/30/19/46/phone-charms-6981833_1280.png'">
                                            </div>
                                        </div>
                                        
                                        <!-- Product Details -->
                                        <div class="flex-grow">
                                            <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($item['nama_produk']); ?></h3>
                                            <p class="text-2xl font-bold pink-text mb-3"><?php echo format_currency($item['harga']); ?></p>
                                            
                                            <!-- Quantity Controls -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm text-gray-600">Quantity:</span>
                                                    <div class="flex items-center border border-gray-200 rounded-lg">
                                                        <button type="button" class="quantity-btn decrease" data-id="<?php echo $item['id_keranjang']; ?>">
                                                            <i class="fas fa-minus text-xs"></i>
                                                        </button>
                                                        <input type="number" 
                                                               name="quantity[<?php echo $item['id_keranjang']; ?>]" 
                                                               value="<?php echo $item['jumlah']; ?>" 
                                                               min="1" 
                                                               max="<?php echo $item['stok']; ?>"
                                                               class="quantity-input">
                                                        <button type="button" class="quantity-btn increase" data-id="<?php echo $item['id_keranjang']; ?>">
                                                            <i class="fas fa-plus text-xs"></i>
                                                        </button>
                                                    </div>
                                                    <span class="text-sm text-gray-500">Stok: <?php echo $item['stok']; ?></span>
                                                </div>
                                                
                                                <!-- Subtotal & Remove -->
                                                <div class="flex items-center space-x-4">
                                                    <span class="text-lg font-semibold text-gray-800">
                                                        <?php echo format_currency($item['harga'] * $item['jumlah']); ?>
                                                    </span>
                                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus item ini?')">
                                                        <input type="hidden" name="id_keranjang" value="<?php echo $item['id_keranjang']; ?>">
                                                        <button type="submit" name="remove_item" class="text-red-500 hover:text-red-700 transition">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Update Cart Button -->
                        <div class="p-6 border-t border-gray-100">
                            <form method="POST">
                                <button type="submit" name="update_cart" class="w-full py-3 bg-blue-500 text-white font-semibold rounded-xl hover:bg-blue-600 transition transform hover:scale-105">
                                    <i class="fas fa-sync-alt mr-2"></i>Perbarui Keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-xl font-bold text-gray-800">Ringkasan Belanja</h2>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Item:</span>
                                <span class="font-semibold"><?php echo $total_items; ?> item</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold"><?php echo format_currency($total_price); ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Ongkos Kirim:</span>
                                <span class="font-semibold text-green-500">Gratis</span>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center text-lg">
                                    <span class="font-bold text-gray-800">Total:</span>
                                    <span class="font-bold text-2xl pink-text"><?php echo format_currency($total_price); ?></span>
                                </div>
                            </div>
                            
                            <!-- Promo Code -->
                            <div class="pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Promo</label>
                                <div class="flex space-x-2">
                                    <input type="text" placeholder="Masukkan kode promo" class="flex-grow px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                    <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition">
                                        Terapkan
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <div class="pt-6">
                                <a href="checkout.php" class="block w-full py-4 gradient-bg text-white font-bold rounded-xl hover:shadow-lg transition transform hover:scale-105 text-center">
                                    <i class="fas fa-credit-card mr-2"></i>Lanjut ke Pembayaran
                                </a>
                            </div>
                            
                            <!-- Continue Shopping -->
                            <div class="pt-4 text-center">
                                <a href="produk.php" class="text-pink-500 hover:text-pink-700 transition flex items-center justify-center">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Lanjutkan Belanja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
                    <h4 class="font-bold text-lg mb-6">Bantuan</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Cara Belanja</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pembayaran</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-pink-400 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Pengiriman</a></li>
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
            
            <div class="border-t border-gray-700 pt-8 text-center">
                <p class="text-gray-400 text-sm">© 2025 Moods Strap. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Quantity controls
        document.addEventListener('DOMContentLoaded', function() {
            // Increase quantity
            document.querySelectorAll('.increase').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const input = document.querySelector(`input[name="quantity[${id}]"]`);
                    const max = parseInt(input.getAttribute('max'));
                    if (input.value < max) {
                        input.value = parseInt(input.value) + 1;
                    }
                });
            });

            // Decrease quantity
            document.querySelectorAll('.decrease').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const input = document.querySelector(`input[name="quantity[${id}]"]`);
                    if (input.value > 1) {
                        input.value = parseInt(input.value) - 1;
                    }
                });
            });

            // Validate quantity input
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const min = parseInt(this.getAttribute('min'));
                    const max = parseInt(this.getAttribute('max'));
                    let value = parseInt(this.value);
                    
                    if (isNaN(value) || value < min) {
                        this.value = min;
                    } else if (value > max) {
                        this.value = max;
                        alert('Stok tidak mencukupi. Stok tersedia: ' + max);
                    }
                });
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