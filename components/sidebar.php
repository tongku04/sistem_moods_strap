<?php
// Pastikan session selalu dimulai di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Perbaiki path koneksi database
$base_dir = __DIR__; // Perbaiki: __DIR__ bukan _DIR_
$config_file = dirname($base_dir) . '/koneksi.php'; // Perbaiki path ke koneksi.php

if (file_exists($config_file)) {
    include_once $config_file;
} else {
    // Fallback jika file tidak ditemukan
    error_log("File koneksi database tidak ditemukan: " . $config_file);
    // Tidak di-die() agar sidebar tetap bisa ditampilkan
}

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    // Redirect hanya jika ini bukan request AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        header("Location: ../auth/login.php");
        exit;
    }
}

$role = $_SESSION['user']['role'] ?? 'kasir';
$userId = $_SESSION['user']['id_user'] ?? 0; // Perbaiki: 'id_user' bukan 'id'
$username = $_SESSION['user']['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penjualan Aksesoris</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        // Tailwind Configuration
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
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        
        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .dark .glass {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Menu category animation */
        .menu-category-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .menu-category.expanded .menu-category-content {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }
        
        .menu-category .chevron-icon {
            transition: transform 0.3s ease;
        }
        
        .menu-category.expanded .chevron-icon {
            transform: rotate(180deg);
        }

        /* Dropdown animations */
        .dropdown-menu {
            animation: dropdownFadeIn 0.2s ease-out;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Blackscrim theme */
        .blackscrim-sidebar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="h-full bg-gray-900 font-inter">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen blackscrim-sidebar transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">
        <!-- User Profile Section -->
        <div class="p-6 border-b border-gray-700">
            <div class="flex flex-col items-center">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=22c55e&color=fff&size=128" 
                    alt="Profile" class="w-16 h-16 rounded-full mb-3 border-4 border-gray-800 shadow-lg">
                <h3 class="font-semibold text-white text-center"><?= htmlspecialchars($username) ?></h3>
                <p class="text-sm text-gray-400"><?= ucfirst($role) ?></p>
                
                <!-- Activity Badge -->
                <div class="flex space-x-2 mt-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-900/50 text-green-400 border border-green-800/30">
                        <i class="fas fa-store mr-1"></i>
                        Toko Aksesoris
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-4 overflow-y-auto custom-scrollbar">
            <?php 
            $current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
            
            if ($role === 'admin'): ?>
                <!-- Dashboard -->
                <div class="mb-2">
                    <a href="index.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $current_page === 'index.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                        <i class="fas fa-gauge-high w-5 mr-3"></i>
                        Dashboard
                    </a>
                </div>
                
                <!-- Produk Management -->
                <div class="menu-category mb-2" data-category="produk">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-box w-5 mr-3"></i>
                            Manajemen Produk
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="produk.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'produk.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-list w-4 mr-2"></i>
                                Daftar Produk
                            </a>
                            <a href="produk_tambah.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'produk_tambah.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-plus w-4 mr-2"></i>
                                Tambah Produk
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Kategori Management -->
                <div class="menu-category mb-2" data-category="kategori">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-tags w-5 mr-3"></i>
                            Manajemen Kategori
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="kategori.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'kategori.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-list w-4 mr-2"></i>
                                Daftar Kategori
                            </a>
                            <a href="kategori_tambah.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'kategori_tambah.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-plus w-4 mr-2"></i>
                                Tambah Kategori
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Transaksi -->
                <div class="menu-category mb-2" data-category="transaksi">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-shopping-cart w-5 mr-3"></i>
                            Transaksi
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="penjualan.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'penjualan.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-receipt w-4 mr-2"></i>
                                Data Penjualan
                            </a>
                            <a href="transaksi_baru.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'transaksi_baru.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-cash-register w-4 mr-2"></i>
                                Transaksi Baru
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- User Management -->
                <div class="menu-category mb-2" data-category="user">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-users-cog w-5 mr-3"></i>
                            Manajemen User
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="users.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'users.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-list w-4 mr-2"></i>
                                Daftar User
                            </a>
                            <a href="user_tambah.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'user_tambah.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-user-plus w-4 mr-2"></i>
                                Tambah User
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Laporan -->
                <div class="menu-category mb-2" data-category="laporan">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar w-5 mr-3"></i>
                            Laporan
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="laporan_penjualan.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'laporan_penjualan.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-chart-line w-4 mr-2"></i>
                                Laporan Penjualan
                            </a>
                            <a href="laporan_produk.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'laporan_produk.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-cube w-4 mr-2"></i>
                                Laporan Produk
                            </a>
                            <a href="laporan_stok.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'laporan_stok.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-boxes w-4 mr-2"></i>
                                Laporan Stok
                            </a>
                        </div>
                    </div>
                </div>

            <?php elseif ($role === 'kasir'): ?>
                <!-- Dashboard -->
                <div class="mb-2">
                    <a href="index.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $current_page === 'index.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                        <i class="fas fa-gauge-high w-5 mr-3"></i>
                        Dashboard
                    </a>
                </div>
                
                <!-- Transaksi -->
                <div class="mb-2">
                    <a href="transaksi.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $current_page === 'transaksi.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                        <i class="fas fa-cash-register w-5 mr-3"></i>
                        Transaksi Baru
                    </a>
                </div>
                
                <!-- Produk -->
                <div class="menu-category mb-2" data-category="produk">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-box w-5 mr-3"></i>
                            Produk
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="produk.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'produk.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-list w-4 mr-2"></i>
                                Daftar Produk
                            </a>
                            <a href="produk_stok.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'produk_stok.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-boxes w-4 mr-2"></i>
                                Cek Stok
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Riwayat Penjualan -->
                <div class="mb-2">
                    <a href="penjualan.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $current_page === 'penjualan.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                        <i class="fas fa-history w-5 mr-3"></i>
                        Riwayat Penjualan
                    </a>
                </div>

            <?php elseif ($role === 'owner'): ?>
                <!-- Dashboard -->
                <div class="mb-2">
                    <a href="index.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $current_page === 'index.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>">
                        <i class="fas fa-gauge-high w-5 mr-3"></i>
                        Dashboard
                    </a>
                </div>
                
                <!-- Laporan -->
                <div class="menu-category mb-2" data-category="laporan">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar w-5 mr-3"></i>
                            Laporan
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="laporan_penjualan.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'laporan_penjualan.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-chart-line w-4 mr-2"></i>
                                Laporan Penjualan
                            </a>
                            <a href="laporan_produk.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'laporan_produk.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-cube w-4 mr-2"></i>
                                Laporan Produk
                            </a>
                            <a href="laporan_stok.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'laporan_stok.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-boxes w-4 mr-2"></i>
                                Laporan Stok
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Keuangan -->
                <div class="menu-category mb-2" data-category="keuangan">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-money-bill-wave w-5 mr-3"></i>
                            Keuangan
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="keuangan_harian.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'keuangan_harian.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-calendar-day w-4 mr-2"></i>
                                Penjualan Harian
                            </a>
                            <a href="keuangan_bulanan.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'keuangan_bulanan.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-calendar-alt w-4 mr-2"></i>
                                Penjualan Bulanan
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Monitoring -->
                <div class="menu-category mb-2" data-category="monitoring">
                    <button class="menu-category-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white">
                        <div class="flex items-center">
                            <i class="fas fa-eye w-5 mr-3"></i>
                            Monitoring
                        </div>
                        <i class="fas fa-chevron-down text-xs chevron-icon"></i>
                    </button>
                    <div class="menu-category-content">
                        <div class="pl-10 pr-4 py-1 space-y-1">
                            <a href="monitoring_transaksi.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'monitoring_transaksi.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-shopping-cart w-4 mr-2"></i>
                                Transaksi
                            </a>
                            <a href="monitoring_produk.php" class="block px-4 py-2 text-sm rounded-lg <?= $current_page === 'monitoring_produk.php' ? 'bg-primary-500/20 text-primary-400 border border-primary-500/30' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                                <i class="fas fa-box w-4 mr-2"></i>
                                Produk Terlaris
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Quick Actions -->
        <div class="p-4 border-t border-gray-700">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Aksi Cepat</h3>
            <div class="grid grid-cols-2 gap-2">
                <?php if ($role === 'admin'): ?>
                    <a href="transaksi_baru.php" class="flex flex-col items-center justify-center p-2 text-xs rounded-lg bg-primary-500/20 text-primary-400 border border-primary-500/30 hover:bg-primary-500/30">
                        <i class="fas fa-cash-register mb-1"></i>
                        Transaksi Baru
                    </a>
                    <a href="produk_tambah.php" class="flex flex-col items-center justify-center p-2 text-xs rounded-lg bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30">
                        <i class="fas fa-plus mb-1"></i>
                        Tambah Produk
                    </a>
                <?php elseif ($role === 'kasir'): ?>
                    <a href="transaksi.php" class="flex flex-col items-center justify-center p-2 text-xs rounded-lg bg-purple-500/20 text-purple-400 border border-purple-500/30 hover:bg-purple-500/30">
                        <i class="fas fa-cash-register mb-1"></i>
                        Transaksi Baru
                    </a>
                    <a href="produk_stok.php" class="flex flex-col items-center justify-center p-2 text-xs rounded-lg bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/30">
                        <i class="fas fa-boxes mb-1"></i>
                        Cek Stok
                    </a>
                <?php elseif ($role === 'owner'): ?>
                    <a href="laporan_penjualan.php" class="flex flex-col items-center justify-center p-2 text-xs rounded-lg bg-blue-500/20 text-blue-400 border border-blue-500/30 hover:bg-blue-500/30">
                        <i class="fas fa-chart-line mb-1"></i>
                        Laporan Penjualan
                    </a>
                    <a href="keuangan_harian.php" class="flex flex-col items-center justify-center p-2 text-xs rounded-lg bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30">
                        <i class="fas fa-money-bill-wave mb-1"></i>
                        Penjualan Hari Ini
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logout Section -->
        <div class="p-4 border-t border-gray-700">
            <a href="../auth/logout.php" class="flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 border border-red-500/30 transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Keluar
            </a>
        </div>
    </aside>

    <!-- Mobile Sidebar Overlay -->
    <div x-data="{ sidebarOpen: false }">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = true" class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-gray-900/80 backdrop-blur-sm border border-gray-700 text-white">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Mobile Sidebar -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-0"
             class="lg:hidden fixed inset-0 z-40">
            <!-- Overlay -->
            <div @click="sidebarOpen = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            
            <!-- Sidebar Content -->
            <div class="relative w-80 h-full blackscrim-sidebar overflow-y-auto">
                <!-- Close Button -->
                <button @click="sidebarOpen = false" class="absolute top-4 right-4 p-2 rounded-lg bg-gray-800 text-white hover:bg-gray-700">
                    <i class="fas fa-times"></i>
                </button>

                <!-- User Profile Section -->
                <div class="p-6 border-b border-gray-700">
                    <div class="flex flex-col items-center">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=22c55e&color=fff&size=128" 
                            alt="Profile" class="w-16 h-16 rounded-full mb-3 border-4 border-gray-800 shadow-lg">
                        <h3 class="font-semibold text-white text-center"><?= htmlspecialchars($username) ?></h3>
                        <p class="text-sm text-gray-400"><?= ucfirst($role) ?></p>
                    </div>
                </div>

                <!-- Navigation Menu (sama seperti desktop) -->
                <nav class="p-4">
                    <!-- Navigation content sama seperti di atas -->
                    <?php include 'navigation_content.php'; ?>
                </nav>
            </div>
        </div>
    </div>

    <script>
        // Initialize sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.querySelector('[x-data] button');
            
            // Menu category toggle
            const menuCategories = document.querySelectorAll('.menu-category-toggle');
            menuCategories.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const category = this.closest('.menu-category');
                    category.classList.toggle('expanded');
                    
                    // Save state to localStorage
                    const categoryId = category.dataset.category;
                    const isExpanded = category.classList.contains('expanded');
                    localStorage.setItem(`menu-${categoryId}`, isExpanded);
                });
                
                // Restore state from localStorage
                const category = toggle.closest('.menu-category');
                const categoryId = category.dataset.category;
                const isExpanded = localStorage.getItem(`menu-${categoryId}`) === 'true';
                if (isExpanded) {
                    category.classList.add('expanded');
                }
            });

            // Close sidebar when clicking on a link (mobile)
            const sidebarLinks = document.querySelectorAll('#sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        const mobileSidebar = document.querySelector('[x-data]');
                        if (mobileSidebar) {
                            mobileSidebar.__x.$data.sidebarOpen = false;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>