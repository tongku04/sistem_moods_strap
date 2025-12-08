<?php
session_start();
include '../config/koneksi.php';

header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pelanggan') {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

// Cek koneksi database
if (!$koneksi) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

// Validasi input
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID produk tidak valid.']);
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$user_id = $_SESSION['user']['id_user'];

// Validasi quantity
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity harus lebih dari 0.']);
    exit;
}

// Ambil data pelanggan berdasarkan user_id
$query_pelanggan = "SELECT id_pelanggan FROM pelanggan WHERE id_user = ?";
$stmt_pelanggan = mysqli_prepare($koneksi, $query_pelanggan);
mysqli_stmt_bind_param($stmt_pelanggan, "i", $user_id);
mysqli_stmt_execute($stmt_pelanggan);
$result_pelanggan = mysqli_stmt_get_result($stmt_pelanggan);

if (!$result_pelanggan || mysqli_num_rows($result_pelanggan) === 0) {
    echo json_encode(['success' => false, 'message' => 'Data pelanggan tidak ditemukan.']);
    exit;
}

$pelanggan = mysqli_fetch_assoc($result_pelanggan);
$id_pelanggan = $pelanggan['id_pelanggan'];

// Cek apakah produk exists dan stok tersedia
$query_produk = "SELECT * FROM produk WHERE id_produk = ? AND status = 'active'";
$stmt_produk = mysqli_prepare($koneksi, $query_produk);
mysqli_stmt_bind_param($stmt_produk, "i", $product_id);
mysqli_stmt_execute($stmt_produk);
$result_produk = mysqli_stmt_get_result($stmt_produk);

if (!$result_produk || mysqli_num_rows($result_produk) === 0) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan atau tidak aktif.']);
    exit;
}

$produk = mysqli_fetch_assoc($result_produk);

// Cek stok
if ($produk['stok'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Stok produk tidak mencukupi. Stok tersedia: ' . $produk['stok']]);
    exit;
}

// Cek apakah produk sudah ada di keranjang
$query_cek_keranjang = "SELECT * FROM keranjang WHERE id_pelanggan = ? AND id_produk = ?";
$stmt_cek_keranjang = mysqli_prepare($koneksi, $query_cek_keranjang);
mysqli_stmt_bind_param($stmt_cek_keranjang, "ii", $id_pelanggan, $product_id);
mysqli_stmt_execute($stmt_cek_keranjang);
$result_cek_keranjang = mysqli_stmt_get_result($stmt_cek_keranjang);

if (mysqli_num_rows($result_cek_keranjang) > 0) {
    // Update quantity jika produk sudah ada di keranjang
    $item_keranjang = mysqli_fetch_assoc($result_cek_keranjang);
    $new_quantity = $item_keranjang['jumlah'] + $quantity;
    
    // Cek stok lagi untuk update quantity
    if ($produk['stok'] < $new_quantity) {
        echo json_encode(['success' => false, 'message' => 'Stok produk tidak mencukupi untuk quantity yang diminta. Stok tersedia: ' . $produk['stok']]);
        exit;
    }
    
    $query_update = "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?";
    $stmt_update = mysqli_prepare($koneksi, $query_update);
    mysqli_stmt_bind_param($stmt_update, "ii", $new_quantity, $item_keranjang['id_keranjang']);
    
    if (mysqli_stmt_execute($stmt_update)) {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan ke keranjang.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update keranjang: ' . mysqli_error($koneksi)]);
    }
} else {
    // Insert new item ke keranjang
    $query_insert = "INSERT INTO keranjang (id_pelanggan, id_produk, jumlah, created_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = mysqli_prepare($koneksi, $query_insert);
    mysqli_stmt_bind_param($stmt_insert, "iii", $id_pelanggan, $product_id, $quantity);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan ke keranjang.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke keranjang: ' . mysqli_error($koneksi)]);
    }
}

// Tutup koneksi
mysqli_close($koneksi);
?>