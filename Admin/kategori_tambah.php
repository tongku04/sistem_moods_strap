<?php
session_start();
include_once '../config/koneksi.php';

// Cek koneksi database
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$pesan_error = '';

// Proses form tambah kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    if (empty($nama_kategori)) {
        $pesan_error = "Nama kategori harus diisi!";
    } else {
        // Cek apakah kategori sudah ada
        $cek_query = "SELECT * FROM kategori WHERE nama_kategori = '$nama_kategori'";
        $cek_result = mysqli_query($koneksi, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $pesan_error = "Kategori '$nama_kategori' sudah ada!";
        } else {
            $query = "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')";
            if (mysqli_query($koneksi, $query)) {
                $pesan = "Kategori berhasil ditambahkan!";
                // Reset form
                $nama_kategori = '';
            } else {
                $pesan_error = "Gagal menambahkan kategori: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori - Sistem Penjualan Aksesoris</title>
    
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Tambah Kategori</h1>
                <p class="text-gray-400">Tambahkan kategori baru untuk produk aksesoris</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Back Button -->
                <a href="kategori.php" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($pesan): ?>
            <div class="blackscrim-card p-4 mb-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-2 bg-green-500/20 rounded-lg mr-4">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Berhasil!</h3>
                        <p class="text-gray-300 text-sm"><?php echo $pesan; ?></p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Input -->
            <div class="lg:col-span-2">
                <div class="blackscrim-card rounded-xl p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-primary-500/20 rounded-lg flex items-center justify-center mr-4 border border-primary-500/30">
                            <i class="fas fa-tags text-primary-400 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white">Form Tambah Kategori</h2>
                            <p class="text-gray-400 text-sm">Isi form berikut untuk menambahkan kategori baru</p>
                        </div>
                    </div>

                    <form method="POST" id="formTambahKategori">
                        <div class="mb-6">
                            <label for="nama_kategori" class="form-label block text-sm font-medium mb-2">
                                Nama Kategori <span class="text-red-400">*</span>
                            </label>
                            <input type="text" id="nama_kategori" name="nama_kategori" required
                                value="<?php echo isset($_POST['nama_kategori']) ? htmlspecialchars($_POST['nama_kategori']) : ''; ?>"
                                class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="Contoh: Gantungan HP, Strap, Charm">
                            <p class="text-gray-400 text-xs mt-1">Masukkan nama kategori yang deskriptif dan mudah dipahami</p>
                        </div>

                        <!-- Tombol Action -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700/50">
                            <button type="reset" class="glass px-6 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                            <button type="submit" class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg text-sm">
                                <i class="fas fa-save mr-2"></i>Simpan Kategori
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
                        Informasi
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <i class="fas fa-asterisk text-blue-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Field Wajib</p>
                                <p class="text-gray-400 text-xs">Field dengan tanda <span class="text-red-400">*</span> harus diisi</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-green-500/10 border border-green-500/20">
                            <i class="fas fa-lightbulb text-green-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Tips Penamaan</p>
                                <p class="text-gray-400 text-xs">Gunakan nama yang singkat dan jelas</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <i class="fas fa-exclamation-triangle text-purple-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Keunikan</p>
                                <p class="text-gray-400 text-xs">Nama kategori harus unik</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-700/50">
                        <h4 class="text-white font-semibold mb-3 text-sm">Manfaat Kategori</h4>
                        <ul class="text-gray-400 text-xs space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Mengelompokkan produk dengan jenis sama
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Memudahkan pencarian produk
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Manajemen inventori lebih baik
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-bolt text-yellow-400 mr-2"></i>
                        Aksi Cepat
                    </h3>
                    <div class="space-y-3">
                        <a href="kategori.php" class="flex items-center p-3 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700">
                            <div class="p-2 bg-blue-500/20 rounded-lg mr-3">
                                <i class="fas fa-list text-blue-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-white text-sm font-medium">Lihat Kategori</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 text-sm"></i>
                        </a>
                        
                        <a href="produk.php" class="flex items-center p-3 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700">
                            <div class="p-2 bg-green-500/20 rounded-lg mr-3">
                                <i class="fas fa-box text-green-400 text-sm"></i>
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
        // Validasi form sebelum submit
        document.getElementById('formTambahKategori').addEventListener('submit', function(e) {
            const namaKategori = document.getElementById('nama_kategori').value.trim();
            
            if (!namaKategori) {
                alert('Nama kategori harus diisi!');
                e.preventDefault();
                return;
            }
            
            if (namaKategori.length < 2) {
                alert('Nama kategori terlalu pendek! Minimal 2 karakter.');
                e.preventDefault();
                return;
            }
        });

        // Auto focus pada input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nama_kategori').focus();
        });

        // Reset form setelah submit berhasil
        <?php if ($pesan): ?>
            setTimeout(function() {
                document.getElementById('nama_kategori').value = '';
            }, 100);
        <?php endif; ?>
    </script>
</body>
</html>