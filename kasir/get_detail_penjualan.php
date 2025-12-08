<?php
session_start();
include_once '../config/koneksi.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kasir') {
    die('Akses ditolak');
}

if (!isset($_GET['id'])) {
    die('ID transaksi tidak valid');
}

$id_penjualan = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil data transaksi
$query = "SELECT p.*, u.username 
          FROM penjualan p 
          JOIN user u ON p.id_user = u.id_user 
          WHERE p.id_penjualan = '$id_penjualan'";
$result = mysqli_query($koneksi, $query);
$transaksi = mysqli_fetch_assoc($result);

// Ambil detail items
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

<div class="space-y-4">
    <!-- Info Transaksi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="bg-gray-800/50 p-4 rounded-lg">
            <p class="text-gray-400 text-sm">No. Transaksi</p>
            <p class="text-white font-semibold">#<?php echo str_pad($transaksi['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        <div class="bg-gray-800/50 p-4 rounded-lg">
            <p class="text-gray-400 text-sm">Tanggal</p>
            <p class="text-white font-semibold"><?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal'])); ?></p>
        </div>
        <div class="bg-gray-800/50 p-4 rounded-lg">
            <p class="text-gray-400 text-sm">Kasir</p>
            <p class="text-white font-semibold"><?php echo htmlspecialchars($transaksi['username']); ?></p>
        </div>
        <div class="bg-gray-800/50 p-4 rounded-lg">
            <p class="text-gray-400 text-sm">Status</p>
            <p class="font-semibold <?php echo $transaksi['status_pembayaran'] == 'paid' ? 'text-green-400' : 'text-yellow-400'; ?>">
                <?php echo $transaksi['status_pembayaran'] == 'paid' ? 'LUNAS' : 'PENDING'; ?>
            </p>
        </div>
    </div>

    <!-- Detail Items -->
    <div class="bg-gray-800/50 rounded-lg p-4">
        <h4 class="text-white font-semibold mb-3">Detail Barang</h4>
        <div class="space-y-2">
            <?php foreach ($detail_items as $item): ?>
            <div class="flex justify-between items-center py-2 border-b border-gray-700/30">
                <div class="flex-1">
                    <p class="text-white font-medium"><?php echo htmlspecialchars($item['nama_produk']); ?></p>
                    <p class="text-gray-400 text-sm"><?php echo $item['jumlah']; ?> x Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></p>
                </div>
                <p class="text-white font-semibold">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Total -->
    <div class="bg-gray-800/50 rounded-lg p-4">
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-400">Total:</span>
                <span class="text-white font-semibold">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Bayar:</span>
                <span class="text-green-400 font-semibold">Rp <?php echo number_format($transaksi['bayar'], 0, ',', '.'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Kembali:</span>
                <span class="text-blue-400 font-semibold">Rp <?php echo number_format($transaksi['kembalian'], 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700/30">
        <a href="cetak_struk.php?id=<?php echo $transaksi['id_penjualan']; ?>" 
           target="_blank"
           class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg text-white font-semibold transition duration-200 text-sm flex items-center">
            <i class="fas fa-print mr-2"></i>Cetak Struk
        </a>
        <button onclick="tutupModalDetail()" 
                class="glass hover:bg-white/10 px-4 py-2 rounded-lg text-gray-300 transition duration-200 text-sm">
            Tutup
        </button>
    </div>
</div>