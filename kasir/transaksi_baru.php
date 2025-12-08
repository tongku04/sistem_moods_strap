<?php
session_start();
include_once '../config/koneksi.php';

// Cek koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login dan memiliki role kasir
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kasir') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$pesan_error = '';
$id_penjualan = null;
$transaksi_data = null;
$show_struk_button = false;
$struk_id = null;

// Ambil data produk untuk dropdown
$query_produk = "SELECT * FROM produk WHERE status = 'active' AND stok > 0 ORDER BY nama_produk";
$result_produk = mysqli_query($koneksi, $query_produk);
$produk = [];
if ($result_produk) {
    while ($row = mysqli_fetch_assoc($result_produk)) {
        $produk[] = $row;
    }
}

// Proses transaksi baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buat_transaksi'])) {
    $id_user = $_SESSION['user']['id_user'];
    $items = $_POST['items'];
    
    // Validasi items
    $valid = true;
    $total_transaksi = 0;
    $detail_items = [];
    
    foreach ($items as $item) {
        if (empty($item['id_produk']) || empty($item['jumlah']) || $item['jumlah'] < 1) {
            $valid = false;
            $pesan_error = "Data produk tidak valid!";
            break;
        }
        
        // Cek stok produk
        $id_produk = mysqli_real_escape_string($koneksi, $item['id_produk']);
        $jumlah = mysqli_real_escape_string($koneksi, $item['jumlah']);
        
        $query_cek_stok = "SELECT stok, harga, nama_produk FROM produk WHERE id_produk = '$id_produk'";
        $result_cek = mysqli_query($koneksi, $query_cek_stok);
        $produk_data = mysqli_fetch_assoc($result_cek);
        
        if ($produk_data['stok'] < $jumlah) {
            $valid = false;
            $pesan_error = "Stok " . $produk_data['nama_produk'] . " tidak mencukupi! Stok tersedia: " . $produk_data['stok'];
            break;
        }
        
        $subtotal = $produk_data['harga'] * $jumlah;
        $total_transaksi += $subtotal;
        
        $detail_items[] = [
            'id_produk' => $id_produk,
            'jumlah' => $jumlah,
            'harga' => $produk_data['harga'],
            'subtotal' => $subtotal,
            'nama_produk' => $produk_data['nama_produk']
        ];
    }
    
    if ($valid && count($detail_items) > 0) {
        // Mulai transaction
        mysqli_begin_transaction($koneksi);
        
        try {
            // Insert ke tabel penjualan dengan status pending
            $query_penjualan = "INSERT INTO penjualan (id_user, total, bayar, kembalian, status_pembayaran) 
                              VALUES ('$id_user', '$total_transaksi', 0, 0, 'pending')";
            if (mysqli_query($koneksi, $query_penjualan)) {
                $id_penjualan = mysqli_insert_id($koneksi);
                
                // Insert detail penjualan dan update stok
                foreach ($detail_items as $item) {
                    $query_detail = "INSERT INTO detail_penjualan (id_penjualan, id_produk, jumlah, subtotal) 
                                   VALUES ('$id_penjualan', '{$item['id_produk']}', '{$item['jumlah']}', '{$item['subtotal']}')";
                    
                    if (!mysqli_query($koneksi, $query_detail)) {
                        throw new Exception("Gagal menyimpan detail transaksi");
                    }
                    
                    // Update stok produk
                    $query_update_stok = "UPDATE produk SET stok = stok - {$item['jumlah']} WHERE id_produk = '{$item['id_produk']}'";
                    if (!mysqli_query($koneksi, $query_update_stok)) {
                        throw new Exception("Gagal update stok produk");
                    }
                }
                
                // Commit transaction
                mysqli_commit($koneksi);
                
                // Simpan data transaksi untuk form pembayaran
                $transaksi_data = [
                    'id_penjualan' => $id_penjualan,
                    'total' => $total_transaksi
                ];
                
            } else {
                throw new Exception("Gagal menyimpan transaksi");
            }
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $pesan_error = $e->getMessage();
        }
    } else {
        if (empty($pesan_error)) {
            $pesan_error = "Tidak ada item yang valid untuk transaksi!";
        }
    }
}

// Proses pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_pembayaran'])) {
    $id_penjualan = mysqli_real_escape_string($koneksi, $_POST['id_penjualan']);
    $bayar = mysqli_real_escape_string($koneksi, $_POST['bayar']);
    $total = mysqli_real_escape_string($koneksi, $_POST['total']);
    
    if ($bayar >= $total) {
        $kembalian = $bayar - $total;
        
        $query_bayar = "UPDATE penjualan SET bayar = '$bayar', kembalian = '$kembalian', status_pembayaran = 'paid' 
                       WHERE id_penjualan = '$id_penjualan'";
        
        if (mysqli_query($koneksi, $query_bayar)) {
            $pesan = "Pembayaran berhasil! Kembalian: Rp " . number_format($kembalian, 0, ',', '.');
            
            // Tampilkan tombol untuk melihat struk
            $show_struk_button = true;
            $struk_id = $id_penjualan;
            
        } else {
            $pesan_error = "Gagal memproses pembayaran!";
        }
    } else {
        $pesan_error = "Jumlah pembayaran kurang!";
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Baru - Kasir</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        // Konfigurasi Tailwind
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #22c55e 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Blackscrim card effect */
        .blackscrim-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
        }
        
        /* Form styles */
        .form-input {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
            background: rgba(30, 41, 59, 0.9);
        }
        
        .form-input::placeholder {
            color: #64748b;
        }
        
        .form-label {
            color: #e2e8f0;
            font-weight: 500;
        }
    </style>
</head>

<body class="text-gray-100 min-h-screen">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Transaksi Baru</h1>
                <p class="text-gray-400">Buat transaksi penjualan baru</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Back Button -->
                <a href="transaksi.php" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                
                <!-- Quick Stats -->
                <div class="glass px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-box text-primary-400 mr-2"></i>
                    <span><?php echo count($produk); ?> Produk Tersedia</span>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($pesan): ?>
            <div class="blackscrim-card p-4 mb-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-2 bg-green-500/20 rounded-lg mr-4">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Berhasil!</h3>
                        <p class="text-gray-300 text-sm"><?php echo $pesan; ?></p>
                        
                        <!-- Tombol Lihat Struk -->
                        <?php if ($show_struk_button && $struk_id): ?>
                        <div class="mt-3 flex space-x-3">
                            <a href="cetak_struk.php?id=<?php echo $struk_id; ?>" 
                               target="_blank"
                               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition duration-200 flex items-center">
                                <i class="fas fa-receipt mr-2"></i> Lihat & Cetak Struk
                            </a>
                            <a href="transaksi.php" 
                               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition duration-200 flex items-center">
                                <i class="fas fa-list mr-2"></i> Ke Daftar Transaksi
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="blackscrim-card p-4 mb-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-2 bg-red-500/20 rounded-lg mr-4">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Error!</h3>
                        <p class="text-gray-300 text-sm"><?php echo $pesan_error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Pembayaran Modal -->
        <?php if (isset($transaksi_data)): ?>
        <div id="modalPembayaran" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
            <div class="blackscrim-card rounded-xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-white">Pembayaran</h3>
                    <button type="button" onclick="tutupModalPembayaran()" 
                            class="text-gray-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="id_penjualan" value="<?php echo $transaksi_data['id_penjualan']; ?>">
                    <input type="hidden" name="total" value="<?php echo $transaksi_data['total']; ?>">
                    
                    <div class="space-y-4">
                        <div class="bg-gray-800/50 p-4 rounded-lg">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-400">No. Transaksi:</span>
                                <span class="text-white font-mono">#<?php echo str_pad($transaksi_data['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold">
                                <span class="text-white">Total:</span>
                                <span class="text-primary-400">Rp <?php echo number_format($transaksi_data['total'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="form-label block text-sm font-medium mb-2">Jumlah Bayar</label>
                            <input type="number" name="bayar" id="inputBayar" 
                                   class="form-input w-full px-3 py-3 rounded-lg text-lg font-semibold" 
                                   min="<?php echo $transaksi_data['total']; ?>" 
                                   required autofocus>
                        </div>
                        
                        <div id="infoKembalian" class="bg-primary-500/20 p-4 rounded-lg border border-primary-500/30 hidden">
                            <div class="flex justify-between items-center">
                                <span class="text-white font-semibold">Kembalian:</span>
                                <span id="textKembalian" class="text-primary-400 text-xl font-bold"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 mt-6">
                        <button type="button" onclick="tutupModalPembayaran()" 
                                class="flex-1 glass px-4 py-3 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700">
                            Batal
                        </button>
                        <button type="submit" name="proses_pembayaran" 
                                class="flex-1 bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-3 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300">
                            <i class="fas fa-credit-card mr-2"></i>Bayar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Hitung kembalian secara real-time
            document.getElementById('inputBayar').addEventListener('input', function() {
                const total = <?php echo $transaksi_data['total']; ?>;
                const bayar = parseInt(this.value) || 0;
                const kembalian = bayar - total;
                
                const infoKembalian = document.getElementById('infoKembalian');
                const textKembalian = document.getElementById('textKembalian');
                
                if (bayar >= total) {
                    textKembalian.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
                    infoKembalian.classList.remove('hidden');
                } else {
                    infoKembalian.classList.add('hidden');
                }
            });

            function tutupModalPembayaran() {
                document.getElementById('modalPembayaran').remove();
                // Redirect ke halaman transaksi
                window.location.href = 'transaksi.php';
            }
        </script>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Transaksi -->
            <div class="lg:col-span-2">
                <div class="blackscrim-card rounded-xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-primary-500/20 rounded-lg flex items-center justify-center mr-4 border border-primary-500/30">
                            <i class="fas fa-cash-register text-primary-400 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white">Form Transaksi</h2>
                            <p class="text-gray-400 text-sm">Tambah produk ke dalam transaksi</p>
                        </div>
                    </div>

                    <form method="POST" id="formTransaksi">
                        <div id="items-container">
                            <!-- Item akan ditambahkan secara dinamis -->
                            <div class="item-row mb-4 p-4 border border-gray-700/50 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    <div class="md:col-span-6">
                                        <label class="form-label block text-sm font-medium mb-2">Produk</label>
                                        <select name="items[0][id_produk]" class="item-produk form-input w-full px-3 py-2 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" onchange="updateHarga(this)">
                                            <option value="">Pilih Produk</option>
                                            <?php foreach ($produk as $prod): ?>
                                                <option value="<?php echo $prod['id_produk']; ?>" data-harga="<?php echo $prod['harga']; ?>" data-stok="<?php echo $prod['stok']; ?>">
                                                    <?php echo htmlspecialchars($prod['nama_produk']); ?> - Rp <?php echo number_format($prod['harga'], 0, ',', '.'); ?> (Stok: <?php echo $prod['stok']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="form-label block text-sm font-medium mb-2">Jumlah</label>
                                        <input type="number" name="items[0][jumlah]" min="1" value="1" 
                                            class="item-jumlah form-input w-full px-3 py-2 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" 
                                            onchange="updateSubtotal(this)">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label block text-sm font-medium mb-2">Subtotal</label>
                                        <input type="text" class="item-subtotal form-input w-full px-3 py-2 rounded-lg bg-gray-800/50 text-sm" value="Rp 0" readonly>
                                    </div>
                                    <div class="md:col-span-1">
                                        <button type="button" onclick="tambahItem()" class="w-full bg-green-500/20 text-green-400 border border-green-500/30 px-3 py-2 rounded-lg hover:bg-green-500/30 transition duration-200 text-sm">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Transaksi -->
                        <div class="mt-6 pt-4 border-t border-gray-700/50">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-lg font-semibold text-white">Total Transaksi</span>
                                <span id="total-transaksi" class="text-2xl font-bold text-primary-400">Rp 0</span>
                            </div>
                        </div>

                        <!-- Tombol Action -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="reset" class="glass px-6 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                            <button type="submit" name="buat_transaksi" class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg text-sm">
                                <i class="fas fa-save mr-2"></i>Simpan Transaksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informasi Panel -->
            <div class="lg:col-span-1">
                <div class="blackscrim-card rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle text-primary-400 mr-2"></i>
                        Petunjuk Transaksi
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <i class="fas fa-shopping-cart text-blue-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Pilih Produk</p>
                                <p class="text-gray-400 text-xs">Pilih produk dari dropdown menu</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-green-500/10 border border-green-500/20">
                            <i class="fas fa-calculator text-green-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Atur Jumlah</p>
                                <p class="text-gray-400 text-xs">Masukkan jumlah yang ingin dibeli</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <i class="fas fa-plus-circle text-purple-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Tambah Item</p>
                                <p class="text-gray-400 text-xs">Klik + untuk menambah item lain</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-700/50">
                        <h4 class="text-white font-semibold mb-3 text-sm">Tips Cepat</h4>
                        <ul class="text-gray-400 text-xs space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Hanya produk dengan stok tersedia yang ditampilkan
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Stok akan otomatis terupdate setelah transaksi
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Transaksi tidak dapat dibatalkan setelah disimpan
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Produk Tersedia -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-boxes text-yellow-400 mr-2"></i>
                        Produk Tersedia
                    </h3>
                    <div class="space-y-3 max-h-60 overflow-y-auto">
                        <?php foreach ($produk as $prod): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-800/30 border border-gray-700/50">
                                <div class="flex-1">
                                    <p class="text-white text-sm font-medium truncate"><?php echo htmlspecialchars($prod['nama_produk']); ?></p>
                                    <p class="text-gray-400 text-xs">Rp <?php echo number_format($prod['harga'], 0, ',', '.'); ?></p>
                                </div>
                                <span class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded border border-green-500/30">
                                    Stok: <?php echo $prod['stok']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($produk)): ?>
                            <div class="text-center py-4 text-gray-400 text-sm">
                                <i class="fas fa-box-open text-2xl mb-2"></i>
                                <p>Tidak ada produk tersedia</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-bolt text-green-400 mr-2"></i>
                        Aksi Cepat
                    </h3>
                    <div class="space-y-3">
                        <a href="transaksi.php" class="flex items-center p-3 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700">
                            <div class="p-2 bg-blue-500/20 rounded-lg mr-3">
                                <i class="fas fa-history text-blue-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-white text-sm font-medium">Riwayat Transaksi</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 text-sm"></i>
                        </a>
                        
                        <a href="produk.php" class="flex items-center p-3 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700">
                            <div class="p-2 bg-purple-500/20 rounded-lg mr-3">
                                <i class="fas fa-box text-purple-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-white text-sm font-medium">Kelola Produk</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 text-sm"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let itemCount = 1;

        function tambahItem() {
            const container = document.getElementById('items-container');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-4 p-4 border border-gray-700/50 rounded-lg';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-6">
                        <label class="form-label block text-sm font-medium mb-2">Produk</label>
                        <select name="items[${itemCount}][id_produk]" class="item-produk form-input w-full px-3 py-2 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" onchange="updateHarga(this)">
                            <option value="">Pilih Produk</option>
                            <?php foreach ($produk as $prod): ?>
                                <option value="<?php echo $prod['id_produk']; ?>" data-harga="<?php echo $prod['harga']; ?>" data-stok="<?php echo $prod['stok']; ?>">
                                    <?php echo htmlspecialchars($prod['nama_produk']); ?> - Rp <?php echo number_format($prod['harga'], 0, ',', '.'); ?> (Stok: <?php echo $prod['stok']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block text-sm font-medium mb-2">Jumlah</label>
                        <input type="number" name="items[${itemCount}][jumlah]" min="1" value="1" 
                            class="item-jumlah form-input w-full px-3 py-2 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" 
                            onchange="updateSubtotal(this)">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block text-sm font-medium mb-2">Subtotal</label>
                        <input type="text" class="item-subtotal form-input w-full px-3 py-2 rounded-lg bg-gray-800/50 text-sm" value="Rp 0" readonly>
                    </div>
                    <div class="md:col-span-1">
                        <button type="button" onclick="hapusItem(this)" class="w-full bg-red-500/20 text-red-400 border border-red-500/30 px-3 py-2 rounded-lg hover:bg-red-500/30 transition duration-200 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            itemCount++;
        }

        function hapusItem(button) {
            const itemRow = button.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                itemRow.remove();
                hitungTotal();
            } else {
                alert('Minimal harus ada 1 item dalam transaksi!');
            }
        }

        function updateHarga(select) {
            const row = select.closest('.item-row');
            const jumlahInput = row.querySelector('.item-jumlah');
            updateSubtotal(jumlahInput);
        }

        function updateSubtotal(input) {
            const row = input.closest('.item-row');
            const select = row.querySelector('.item-produk');
            const subtotalInput = row.querySelector('.item-subtotal');
            
            const selectedOption = select.options[select.selectedIndex];
            const harga = selectedOption ? parseInt(selectedOption.getAttribute('data-harga')) : 0;
            const jumlah = parseInt(input.value) || 0;
            const stok = selectedOption ? parseInt(selectedOption.getAttribute('data-stok')) : 0;
            
            // Validasi stok
            if (jumlah > stok) {
                alert('Jumlah melebihi stok tersedia! Stok: ' + stok);
                input.value = stok;
                jumlah = stok;
            }
            
            const subtotal = harga * jumlah;
            subtotalInput.value = 'Rp ' + subtotal.toLocaleString('id-ID');
            
            hitungTotal();
        }

        function hitungTotal() {
            let total = 0;
            document.querySelectorAll('.item-subtotal').forEach(input => {
                const value = input.value.replace('Rp ', '').replace(/\./g, '');
                total += parseInt(value) || 0;
            });
            
            document.getElementById('total-transaksi').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Validasi form sebelum submit
        document.getElementById('formTransaksi').addEventListener('submit', function(e) {
            let valid = false;
            document.querySelectorAll('.item-produk').forEach(select => {
                if (select.value) {
                    valid = true;
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Pilih minimal 1 produk untuk transaksi!');
                return;
            }
            
            const total = parseInt(document.getElementById('total-transaksi').textContent.replace('Rp ', '').replace(/\./g, ''));
            if (total === 0) {
                e.preventDefault();
                alert('Total transaksi tidak boleh 0!');
                return;
            }
        });

        // Initialize first item
        document.addEventListener('DOMContentLoaded', function() {
            hitungTotal();
        });

        // Auto focus on first product select
        document.addEventListener('DOMContentLoaded', function() {
            const firstSelect = document.querySelector('.item-produk');
            if (firstSelect) {
                firstSelect.focus();
            }
        });
    </script>
</body>
</html>