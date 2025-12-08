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

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pesan = '';
$pesan_error = '';

// Proses form tambah produk
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);

    // Handle upload foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ekstensi_diperbolehkan = array('jpg', 'jpeg', 'png', 'gif');
        $nama_file = $_FILES['foto']['name'];
        $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $ukuran_file = $_FILES['foto']['size'];
        $file_tmp = $_FILES['foto']['tmp_name'];

        if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
            if ($ukuran_file < 5000000) { // 5MB max
                $nama_file_baru = uniqid() . '.' . $ekstensi;
                $tujuan_upload = '../uploads/produk/' . $nama_file_baru;
                
                // Buat folder uploads jika belum ada
                if (!is_dir('../uploads/produk')) {
                    mkdir('../uploads/produk', 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $tujuan_upload)) {
                    $foto = $nama_file_baru;
                } else {
                    $pesan_error = "Gagal mengupload foto produk.";
                }
            } else {
                $pesan_error = "Ukuran file terlalu besar. Maksimal 5MB.";
            }
        } else {
            $pesan_error = "Ekstensi file tidak diperbolehkan. Hanya JPG, JPEG, PNG, dan GIF.";
        }
    }

    // Validasi input
    if (empty($nama_produk) || empty($harga) || empty($stok)) {
        $pesan_error = "Nama produk, harga, dan stok harus diisi!";
    } else {
        // Insert data ke database
        $query = "INSERT INTO produk (nama_produk, harga, deskripsi, stok, id_kategori, foto, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, 'sisisss', $nama_produk, $harga, $deskripsi, $stok, $id_kategori, $foto, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Produk berhasil ditambahkan!";
            
            // Reset form values
            $nama_produk = $harga = $deskripsi = $stok = '';
            $id_kategori = $status = '';
        } else {
            $pesan_error = "Gagal menambahkan produk: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil data kategori untuk dropdown
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = mysqli_query($koneksi, $query_kategori);
$kategori = [];
if ($result_kategori) {
    while ($row = mysqli_fetch_assoc($result_kategori)) {
        $kategori[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Sistem Penjualan Aksesoris</title>
    
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
        
        /* File upload area */
        .file-upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .file-upload-area:hover {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
        }
        
        /* Preview image */
        .preview-image {
            max-height: 200px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="text-gray-100 min-h-screen">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Tambah Produk Baru</h1>
                <p class="text-gray-400">Tambahkan produk aksesoris baru ke dalam sistem</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Back Button -->
                <a href="produk.php" class="glass px-6 py-3 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Produk
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($pesan): ?>
            <div class="blackscrim-card p-6 mb-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-2 bg-green-500/20 rounded-lg mr-4">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Berhasil!</h3>
                        <p class="text-gray-300"><?php echo $pesan; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="blackscrim-card p-6 mb-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-2 bg-red-500/20 rounded-lg mr-4">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Error!</h3>
                        <p class="text-gray-300"><?php echo $pesan_error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Input -->
            <div class="lg:col-span-2">
                <div class="blackscrim-card rounded-xl p-8">
                    <form method="POST" enctype="multipart/form-data" id="formTambahProduk">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Nama Produk -->
                            <div class="md:col-span-2">
                                <label for="nama_produk" class="form-label block text-sm font-medium mb-3">
                                    <i class="fas fa-tag mr-2 text-primary-400"></i>
                                    Nama Produk <span class="text-red-400">*</span>
                                </label>
                                <input type="text" id="nama_produk" name="nama_produk" required
                                    value="<?php echo isset($_POST['nama_produk']) ? htmlspecialchars($_POST['nama_produk']) : ''; ?>"
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                    placeholder="Masukkan nama produk">
                            </div>

                            <!-- Harga -->
                            <div>
                                <label for="harga" class="form-label block text-sm font-medium mb-3">
                                    <i class="fas fa-money-bill-wave mr-2 text-primary-400"></i>
                                    Harga <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">Rp</span>
                                    <input type="number" id="harga" name="harga" required min="0"
                                        value="<?php echo isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : ''; ?>"
                                        class="form-input w-full pl-10 pr-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                        placeholder="0">
                                </div>
                            </div>

                            <!-- Stok -->
                            <div>
                                <label for="stok" class="form-label block text-sm font-medium mb-3">
                                    <i class="fas fa-boxes mr-2 text-primary-400"></i>
                                    Stok <span class="text-red-400">*</span>
                                </label>
                                <input type="number" id="stok" name="stok" required min="0"
                                    value="<?php echo isset($_POST['stok']) ? htmlspecialchars($_POST['stok']) : '0'; ?>"
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                    placeholder="0">
                            </div>

                            <!-- Kategori -->
                            <div>
                                <label for="id_kategori" class="form-label block text-sm font-medium mb-3">
                                    <i class="fas fa-tags mr-2 text-primary-400"></i>
                                    Kategori
                                </label>
                                <select id="id_kategori" name="id_kategori"
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500">
                                    <option value="" class="bg-dark-800">Pilih Kategori</option>
                                    <?php foreach ($kategori as $kat): ?>
                                        <option value="<?php echo $kat['id_kategori']; ?>" 
                                            class="bg-dark-800"
                                            <?php echo (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="form-label block text-sm font-medium mb-3">
                                    <i class="fas fa-toggle-on mr-2 text-primary-400"></i>
                                    Status
                                </label>
                                <select id="status" name="status"
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500">
                                    <option value="active" class="bg-dark-800" 
                                        <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : 'selected'; ?>>Active</option>
                                    <option value="inactive" class="bg-dark-800"
                                        <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-6">
                            <label for="deskripsi" class="form-label block text-sm font-medium mb-3">
                                <i class="fas fa-align-left mr-2 text-primary-400"></i>
                                Deskripsi Produk
                            </label>
                            <textarea id="deskripsi" name="deskripsi" rows="4"
                                class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500 resize-none"
                                placeholder="Masukkan deskripsi produk..."><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                        </div>

                        <!-- Foto Produk -->
                        <div class="mb-8">
                            <label class="form-label block text-sm font-medium mb-3">
                                <i class="fas fa-camera mr-2 text-primary-400"></i>
                                Foto Produk
                            </label>
                            <div class="file-upload-area rounded-lg p-6 text-center cursor-pointer"
                                id="uploadArea"
                                onclick="document.getElementById('foto').click()">
                                <input type="file" id="foto" name="foto" accept="image/*" class="hidden" onchange="previewImage(this)">
                                <div id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-300 font-medium">Klik untuk upload foto</p>
                                    <p class="text-gray-500 text-sm mt-1">Format: JPG, PNG, GIF (max 5MB)</p>
                                </div>
                                <div id="imagePreview" class="hidden mt-4">
                                    <img id="preview" class="preview-image mx-auto">
                                    <button type="button" onclick="removeImage()" class="mt-3 text-red-400 hover:text-red-300 text-sm transition-colors">
                                        <i class="fas fa-trash mr-1"></i>Hapus Gambar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Action -->
                        <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                            <button type="reset" class="glass px-8 py-3 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700">
                                <i class="fas fa-redo mr-2"></i>Reset Form
                            </button>
                            <button type="submit" class="bg-gradient-to-r from-primary-500 to-primary-600 px-8 py-3 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg">
                                <i class="fas fa-save mr-2"></i>Simpan Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informasi Panel -->
            <div class="lg:col-span-1">
                <div class="blackscrim-card rounded-xl p-8 sticky top-6">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-primary-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border border-primary-500/30">
                            <i class="fas fa-info-circle text-primary-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">Informasi Tambahan</h3>
                        <p class="text-gray-400 text-sm">Panduan pengisian form produk</p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <i class="fas fa-asterisk text-blue-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Field Wajib</p>
                                <p class="text-gray-400 text-xs">Field dengan tanda <span class="text-red-400">*</span> harus diisi</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-green-500/10 border border-green-500/20">
                            <i class="fas fa-money-bill-wave text-green-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Format Harga</p>
                                <p class="text-gray-400 text-xs">Harga diisi dengan angka tanpa titik atau koma</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <i class="fas fa-image text-purple-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Foto Produk</p>
                                <p class="text-gray-400 text-xs">Upload foto dengan resolusi jelas dan format yang didukung</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                            <i class="fas fa-toggle-on text-yellow-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Status Produk</p>
                                <p class="text-gray-400 text-xs">Status Active akan menampilkan produk ke customer</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-700/50">
                        <h4 class="text-white font-semibold mb-3">Tips Upload Foto</h4>
                        <ul class="text-gray-400 text-sm space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Gunakan foto dengan background bersih
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Format JPG/PNG dengan kualitas baik
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Ukuran file maksimal 5MB
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Rasio foto 1:1 untuk hasil terbaik
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Preview image sebelum upload
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const uploadArea = document.getElementById('uploadArea');
            const uploadContent = document.getElementById('uploadContent');
            const imagePreview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    uploadContent.classList.add('hidden');
                    imagePreview.classList.remove('hidden');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Hapus image preview
        function removeImage() {
            const fileInput = document.getElementById('foto');
            const uploadContent = document.getElementById('uploadContent');
            const imagePreview = document.getElementById('imagePreview');
            
            fileInput.value = '';
            uploadContent.classList.remove('hidden');
            imagePreview.classList.add('hidden');
        }

        // Format harga saat input
        document.getElementById('harga').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            e.target.value = value;
        });

        // Validasi form sebelum submit
        document.getElementById('formTambahProduk').addEventListener('submit', function(e) {
            const namaProduk = document.getElementById('nama_produk').value.trim();
            const harga = document.getElementById('harga').value;
            const stok = document.getElementById('stok').value;
            
            if (!namaProduk) {
                alert('Nama produk harus diisi!');
                e.preventDefault();
                return;
            }
            
            if (!harga || harga < 0) {
                alert('Harga harus diisi dengan angka yang valid!');
                e.preventDefault();
                return;
            }
            
            if (!stok || stok < 0) {
                alert('Stok harus diisi dengan angka yang valid!');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>