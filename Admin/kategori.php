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

// Proses tambah kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kategori'])) {
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
            } else {
                $pesan_error = "Gagal menambahkan kategori: " . mysqli_error($koneksi);
            }
        }
    }
}

// Proses edit kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_kategori'])) {
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    if (empty($nama_kategori)) {
        $pesan_error = "Nama kategori harus diisi!";
    } else {
        // Cek apakah kategori sudah ada (kecuali kategori yang sedang diedit)
        $cek_query = "SELECT * FROM kategori WHERE nama_kategori = '$nama_kategori' AND id_kategori != '$id_kategori'";
        $cek_result = mysqli_query($koneksi, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $pesan_error = "Kategori '$nama_kategori' sudah ada!";
        } else {
            $query = "UPDATE kategori SET nama_kategori = '$nama_kategori' WHERE id_kategori = '$id_kategori'";
            if (mysqli_query($koneksi, $query)) {
                $pesan = "Kategori berhasil diupdate!";
            } else {
                $pesan_error = "Gagal mengupdate kategori: " . mysqli_error($koneksi);
            }
        }
    }
}

// Proses hapus kategori
if (isset($_GET['hapus'])) {
    $id_kategori = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Cek apakah kategori digunakan oleh produk
    $cek_produk = "SELECT COUNT(*) as total FROM produk WHERE id_kategori = '$id_kategori'";
    $result_cek = mysqli_query($koneksi, $cek_produk);
    $data_cek = mysqli_fetch_assoc($result_cek);
    
    if ($data_cek['total'] > 0) {
        $pesan_error = "Kategori tidak dapat dihapus karena masih digunakan oleh produk!";
    } else {
        $query = "DELETE FROM kategori WHERE id_kategori = '$id_kategori'";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "Kategori berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus kategori: " . mysqli_error($koneksi);
        }
    }
}

// Ambil data kategori
$query_kategori = "SELECT k.*, 
                  (SELECT COUNT(*) FROM produk p WHERE p.id_kategori = k.id_kategori) as total_produk
                  FROM kategori k 
                  ORDER BY k.nama_kategori";
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
    <title>Kelola Kategori - Sistem Penjualan Aksesoris</title>
    
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
        
        /* Table styles */
        .dark-table {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .dark-table thead {
            background: rgba(30, 41, 59, 0.9);
        }
        
        .dark-table th {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .dark-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .dark-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
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
                <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-2">Kelola Kategori</h1>
                <p class="text-gray-400">Kelola kategori produk aksesoris</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Quick Stats -->
                <div class="glass px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-tags text-primary-400 mr-2"></i>
                    <span>Total: <?php echo count($kategori); ?> Kategori</span>
                </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Form Tambah Kategori -->
            <div class="lg:col-span-1">
                <div class="blackscrim-card rounded-xl p-6">
                    <h2 class="text-xl font-semibold text-white mb-6 flex items-center">
                        <i class="fas fa-plus-circle text-primary-400 mr-3"></i>
                        Tambah Kategori Baru
                    </h2>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="nama_kategori" class="form-label block text-sm font-medium mb-2">
                                Nama Kategori <span class="text-red-400">*</span>
                            </label>
                            <input type="text" id="nama_kategori" name="nama_kategori" required
                                class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="Masukkan nama kategori">
                        </div>
                        
                        <button type="submit" name="tambah_kategori" 
                                class="w-full bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-3 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg">
                            <i class="fas fa-save mr-2"></i>Simpan Kategori
                        </button>
                    </form>
                </div>

                <!-- Informasi -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                        Informasi
                    </h3>
                    <div class="space-y-3 text-sm text-gray-400">
                        <div class="flex items-start">
                            <i class="fas fa-check text-primary-400 text-xs mt-1 mr-2"></i>
                            <span>Kategori digunakan untuk mengelompokkan produk</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-primary-400 text-xs mt-1 mr-2"></i>
                            <span>Kategori yang digunakan produk tidak dapat dihapus</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check text-primary-400 text-xs mt-1 mr-2"></i>
                            <span>Nama kategori harus unik</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Kategori -->
            <div class="lg:col-span-3">
                <div class="blackscrim-card rounded-xl p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h2 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-list text-primary-400 mr-3"></i>
                            Daftar Kategori
                        </h2>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-400">
                                Menampilkan <?php echo count($kategori); ?> kategori
                            </span>
                        </div>
                    </div>

                    <?php if (count($kategori) > 0): ?>
                        <div class="overflow-x-auto dark-table rounded-lg">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">No</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Nama Kategori</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Jumlah Produk</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kategori as $index => $kat): ?>
                                        <tr class="<?php echo $index % 2 === 0 ? 'bg-gray-900/30' : ''; ?>">
                                            <td class="py-4 px-6 text-gray-300">
                                                <?php echo $index + 1; ?>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="font-medium text-white"><?php echo htmlspecialchars($kat['nama_kategori']); ?></div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                                    <?php echo $kat['total_produk'] > 0 ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : 'bg-gray-500/20 text-gray-400 border border-gray-500/30'; ?>">
                                                    <i class="fas fa-box mr-1 text-xs"></i>
                                                    <?php echo $kat['total_produk']; ?> produk
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex space-x-2">
                                                    <!-- Edit Button -->
                                                    <button onclick="openEditModal(<?php echo $kat['id_kategori']; ?>, '<?php echo htmlspecialchars($kat['nama_kategori']); ?>')"
                                                            class="px-3 py-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition duration-200 border border-blue-500/30">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Button -->
                                                    <?php if ($kat['total_produk'] == 0): ?>
                                                        <a href="?hapus=<?php echo $kat['id_kategori']; ?>" 
                                                           onclick="return confirm('Yakin ingin menghapus kategori <?php echo htmlspecialchars($kat['nama_kategori']); ?>?')"
                                                           class="px-3 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition duration-200 border border-red-500/30">
                                                            <i class="fas fa-trash text-sm"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="px-3 py-2 bg-gray-500/20 text-gray-400 rounded-lg cursor-not-allowed border border-gray-500/30"
                                                                title="Kategori tidak dapat dihapus karena masih digunakan">
                                                            <i class="fas fa-trash text-sm"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-gray-700/30 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-600/30">
                                <i class="fas fa-tags text-gray-500 text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-400 mb-2">Belum ada kategori</h3>
                            <p class="text-gray-500">Mulai dengan menambahkan kategori pertama Anda</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Edit Kategori -->
    <div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="blackscrim-card rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-white">Edit Kategori</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="id_kategori" id="edit_id_kategori">
                
                <div class="mb-4">
                    <label for="edit_nama_kategori" class="form-label block text-sm font-medium mb-2">
                        Nama Kategori <span class="text-red-400">*</span>
                    </label>
                    <input type="text" id="edit_nama_kategori" name="nama_kategori" required
                        class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                        placeholder="Masukkan nama kategori">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 glass text-gray-300 rounded-lg hover:bg-white/10 transition duration-200 border border-gray-700">
                        Batal
                    </button>
                    <button type="submit" name="edit_kategori"
                            class="px-4 py-2 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-lg hover:from-primary-600 hover:to-primary-700 transition duration-200 shadow-lg">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal(id, nama) {
            document.getElementById('edit_id_kategori').value = id;
            document.getElementById('edit_nama_kategori').value = nama;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target.id === 'editModal') {
                closeEditModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });

        // Reset form after successful submission
        <?php if ($pesan): ?>
            document.getElementById('nama_kategori').value = '';
        <?php endif; ?>
    </script>
</body>
</html>