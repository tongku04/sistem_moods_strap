<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "penjualan_aksesoris";

// Membuat koneksi dengan error handling yang lebih baik
try {
    $koneksi = new mysqli($host, $username, $password, $database);
    
    // Cek koneksi
    if ($koneksi->connect_error) {
        throw new Exception("Koneksi database gagal: " . $koneksi->connect_error);
    }
    
    // Set charset
    if (!$koneksi->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $koneksi->error);
    }
    
    // Set timezone jika perlu
    $koneksi->query("SET time_zone = '+07:00'");
    
} catch (Exception $e) {
    // Log error untuk production
    error_log("Database Error: " . $e->getMessage());
    
    // Tampilkan pesan user-friendly
    die("<div style='text-align: center; padding: 20px; font-family: Arial;'>
            <h2>⚠️ Sistem Sedang Maintenance</h2>
            <p>Mohon maaf, sistem sedang mengalami gangguan teknis.</p>
            <p>Silakan coba lagi beberapa saat lagi.</p>
        </div>");
}

// Fungsi helper untuk query yang aman
function query($sql, $params = []) {
    global $koneksi;
    
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $koneksi->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // semua parameter sebagai string
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    return $stmt->get_result();
}

// Fungsi untuk escape string
function escape($string) {
    global $koneksi;
    return $koneksi->real_escape_string($string);
}
