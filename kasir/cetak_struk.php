<?php
session_start();
include_once '../config/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID transaksi tidak valid!");
}

$id_penjualan = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil data transaksi
$query = "SELECT p.*, u.username, DATE_FORMAT(p.tanggal, '%d/%m/%Y %H:%i') as tanggal_format 
          FROM penjualan p 
          JOIN user u ON p.id_user = u.id_user 
          WHERE p.id_penjualan = '$id_penjualan'";
$result = mysqli_query($koneksi, $query);
$transaksi = mysqli_fetch_assoc($result);

if (!$transaksi) {
    die("Transaksi tidak ditemukan!");
}

// Ambil detail transaksi
$query_detail = "SELECT dp.*, pr.nama_produk, pr.harga 
                 FROM detail_penjualan dp 
                 JOIN produk pr ON dp.id_produk = pr.id_produk 
                 WHERE dp.id_penjualan = '$id_penjualan'";
$result_detail = mysqli_query($koneksi, $query_detail);
$detail_items = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #<?php echo $id_penjualan; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
    <style>
        /* Style untuk preview struk */
        .struk-preview {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            background: white;
            color: black;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px dashed #e5e7eb;
        }
        
        /* Style untuk cetakan */
        @media print {
            body * {
                visibility: hidden;
            }
            .struk-print, .struk-print * {
                visibility: visible;
            }
            .struk-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 300px;
                padding: 10px;
                box-shadow: none;
                border: none;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .struk-content {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.3;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 10px;
            text-align: center;
            margin-bottom: 10px;
            color: #666;
        }
        
        .transaction-info {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #000;
        }
        
        .transaction-info div {
            margin-bottom: 2px;
        }
        
        .items-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }
        
        .items-table th {
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        
        .items-table .nama {
            width: 60%;
        }
        
        .items-table .harga, .items-table .subtotal {
            text-align: right;
            width: 20%;
        }
        
        .total-section {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 10px;
            color: #666;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-6 no-print">
            <h1 class="text-2xl font-bold text-gray-800">Preview Struk Transaksi</h1>
            <p class="text-gray-600">No. Transaksi: #<?php echo str_pad($transaksi['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></p>
            <div class="flex justify-center space-x-2 mt-2">
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Status: <?php echo $transaksi['status_pembayaran'] == 'paid' ? 'Lunas' : 'Pending'; ?></span>
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">Tanggal: <?php echo $transaksi['tanggal_format']; ?></span>
            </div>
        </div>

        <!-- Container untuk preview dan print -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <!-- Preview Struk -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                        <i class="fas fa-receipt mr-2 text-blue-500"></i>
                        Preview Struk
                    </h2>
                    
                    <!-- Struk Preview -->
                    <div class="struk-preview">
                        <div class="struk-content">
                            <!-- Header -->
                            <div class="header">
                                <div class="company-name">MOODS STRAP</div>
                                <div class="company-address">
                                    Jl. Contoh No. 123<br>
                                    Telp: 0812-3456-7890<br>
                                    www.tokoaksesoris.com
                                </div>
                            </div>
                            
                            <!-- Garis Pembatas -->
                            <div class="text-center" style="margin: 10px 0;">
                                ============================
                            </div>
                            
                            <!-- Info Transaksi -->
                            <div class="transaction-info">
                                <div><strong>No:</strong> #<?php echo str_pad($transaksi['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></div>
                                <div><strong>Tanggal:</strong> <?php echo $transaksi['tanggal_format']; ?></div>
                                <div><strong>Kasir:</strong> <?php echo htmlspecialchars($transaksi['username']); ?></div>
                            </div>
                            
                            <!-- Items -->
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th class="nama">Item</th>
                                        <th class="harga">Qty</th>
                                        <th class="subtotal">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detail_items as $item): ?>
                                    <tr>
                                        <td class="nama">
                                            <?php echo htmlspecialchars($item['nama_produk']); ?>
                                        </td>
                                        <td class="harga">
                                            <?php echo $item['jumlah']; ?> x <?php echo number_format($item['harga'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="subtotal"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <!-- Garis Pembatas -->
                            <div class="text-center" style="margin: 10px 0;">
                                ----------------------------
                            </div>
                            
                            <!-- Total -->
                            <div class="total-section">
                                <div class="total-row">
                                    <span>Total:</span>
                                    <span>Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Bayar:</span>
                                    <span>Rp <?php echo number_format($transaksi['bayar'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Kembali:</span>
                                    <span>Rp <?php echo number_format($transaksi['kembalian'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Garis Pembatas -->
                            <div class="text-center" style="margin: 10px 0;">
                                ============================
                            </div>
                            
                            <!-- Footer -->
                            <div class="footer">
                                <div><strong>Terima kasih atas kunjungan Anda</strong></div>
                                <div>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</div>
                                <div style="margin-top: 8px;">*** Struk ini sebagai bukti pembayaran ***</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Kontrol -->
            <div class="lg:w-80">
                <div class="bg-white rounded-lg shadow-lg p-6 no-print">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                        <i class="fas fa-cog mr-2 text-gray-600"></i>
                        Kontrol Struk
                    </h2>
                    
                    <!-- Informasi Transaksi -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-3">Informasi Transaksi</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">No. Transaksi:</span>
                                <span class="font-semibold">#<?php echo str_pad($transaksi['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tanggal:</span>
                                <span class="font-semibold"><?php echo $transaksi['tanggal_format']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kasir:</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($transaksi['username']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-semibold <?php echo $transaksi['status_pembayaran'] == 'paid' ? 'text-green-600' : 'text-yellow-600'; ?>">
                                    <?php echo $transaksi['status_pembayaran'] == 'paid' ? 'LUNAS' : 'PENDING'; ?>
                                </span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total:</span>
                                    <span class="text-green-600">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tombol Aksi -->
                    <div class="space-y-3">
                        <button onclick="cetakStruk()" 
                                class="w-full bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                            <i class="fas fa-print mr-2"></i> Cetak Struk
                        </button>
                        
                        <button onclick="kembaliKeTransaksi()" 
                                class="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Transaksi
                        </button>
                        
                        <button onclick="tutupWindow()" 
                                class="w-full bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i> Tutup Window
                        </button>
                    </div>

                    <!-- Tips Cetak -->
                    <div class="mt-6 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 text-sm mb-2 flex items-center">
                            <i class="fas fa-lightbulb mr-2"></i> Tips Cetak
                        </h4>
                        <ul class="text-yellow-700 text-xs space-y-1">
                            <li>• Pastikan printer thermal sudah menyala</li>
                            <li>• Gunakan kertas struk thermal 80mm</li>
                            <li>• Cek alignment kertas sebelum mencetak</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Struk untuk Print (tersembunyi) -->
        <div class="struk-print" style="display: none;">
            <div class="struk-content">
                <!-- Header -->
                <div class="header">
                    <div class="company-name">TOKO AKSESORIS</div>
                    <div class="company-address">
                        Jl. Contoh No. 123<br>
                        Telp: 0812-3456-7890<br>
                        www.tokoaksesoris.com
                    </div>
                </div>
                
                <!-- Garis Pembatas -->
                <div class="text-center" style="margin: 10px 0;">
                    ============================
                </div>
                
                <!-- Info Transaksi -->
                <div class="transaction-info">
                    <div><strong>No:</strong> #<?php echo str_pad($transaksi['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></div>
                    <div><strong>Tanggal:</strong> <?php echo $transaksi['tanggal_format']; ?></div>
                    <div><strong>Kasir:</strong> <?php echo htmlspecialchars($transaksi['username']); ?></div>
                </div>
                
                <!-- Items -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="nama">Item</th>
                            <th class="harga">Qty</th>
                            <th class="subtotal">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_items as $item): ?>
                        <tr>
                            <td class="nama">
                                <?php echo htmlspecialchars($item['nama_produk']); ?>
                            </td>
                            <td class="harga">
                                <?php echo $item['jumlah']; ?> x <?php echo number_format($item['harga'], 0, ',', '.'); ?>
                            </td>
                            <td class="subtotal"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Garis Pembatas -->
                <div class="text-center" style="margin: 10px 0;">
                    ----------------------------
                </div>
                
                <!-- Total -->
                <div class="total-section">
                    <div class="total-row">
                        <span>Total:</span>
                        <span>Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Bayar:</span>
                        <span>Rp <?php echo number_format($transaksi['bayar'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Kembali:</span>
                        <span>Rp <?php echo number_format($transaksi['kembalian'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <!-- Garis Pembatas -->
                <div class="text-center" style="margin: 10px 0;">
                    ============================
                </div>
                
                <!-- Footer -->
                <div class="footer">
                    <div><strong>Terima kasih atas kunjungan Anda</strong></div>
                    <div>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</div>
                    <div style="margin-top: 8px;">*** Struk ini sebagai bukti pembayaran ***</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cetakStruk() {
            // Tampilkan elemen print
            const printElement = document.querySelector('.struk-print');
            printElement.style.display = 'block';
            
            // Jalankan print
            window.print();
            
            // Sembunyikan kembali setelah print
            setTimeout(() => {
                printElement.style.display = 'none';
            }, 500);
        }
        
        function kembaliKeTransaksi() {
            window.location.href = 'transaksi.php';
        }
        
        function tutupWindow() {
            window.close();
        }
        
        // Auto focus pada tombol cetak
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Struk preview loaded - siap untuk dicetak');
        });
    </script>
</body>
</html>