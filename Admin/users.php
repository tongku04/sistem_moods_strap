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

// Proses tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    if (empty($username) || empty($password) || empty($role)) {
        $pesan_error = "Semua field harus diisi!";
    } else {
        // Cek apakah username sudah ada
        $cek_query = "SELECT * FROM user WHERE username = '$username'";
        $cek_result = mysqli_query($koneksi, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $pesan_error = "Username '$username' sudah ada!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO user (username, password, role) VALUES ('$username', '$hashed_password', '$role')";
            
            if (mysqli_query($koneksi, $query)) {
                $pesan = "User berhasil ditambahkan!";
            } else {
                $pesan_error = "Gagal menambahkan user: " . mysqli_error($koneksi);
            }
        }
    }
}

// Proses edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    
    if (empty($username) || empty($role)) {
        $pesan_error = "Username dan role harus diisi!";
    } else {
        // Cek apakah username sudah ada (kecuali user yang sedang diedit)
        $cek_query = "SELECT * FROM user WHERE username = '$username' AND id_user != '$id_user'";
        $cek_result = mysqli_query($koneksi, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $pesan_error = "Username '$username' sudah ada!";
        } else {
            if (!empty($password)) {
                // Update dengan password baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE user SET username = '$username', password = '$hashed_password', role = '$role' WHERE id_user = '$id_user'";
            } else {
                // Update tanpa mengubah password
                $query = "UPDATE user SET username = '$username', role = '$role' WHERE id_user = '$id_user'";
            }
            
            if (mysqli_query($koneksi, $query)) {
                $pesan = "User berhasil diupdate!";
            } else {
                $pesan_error = "Gagal mengupdate user: " . mysqli_error($koneksi);
            }
        }
    }
}

// Proses hapus user
if (isset($_GET['hapus'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Cek apakah user sedang login
    if ($id_user == $_SESSION['user']['id_user']) {
        $pesan_error = "Tidak dapat menghapus user yang sedang login!";
    } else {
        $query = "DELETE FROM user WHERE id_user = '$id_user'";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "User berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus user: " . mysqli_error($koneksi);
        }
    }
}

// Ambil data users
$query_users = "SELECT * FROM user ORDER BY role, username";
$result_users = mysqli_query($koneksi, $query_users);
$users = [];
if ($result_users) {
    while ($row = mysqli_fetch_assoc($result_users)) {
        $users[] = $row;
    }
}

// Hitung statistik
$total_users = count($users);
$total_admin = count(array_filter($users, function($user) {
    return $user['role'] === 'admin';
}));
$total_kasir = count(array_filter($users, function($user) {
    return $user['role'] === 'kasir';
}));
$total_owner = count(array_filter($users, function($user) {
    return $user['role'] === 'owner';
}));
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Sistem Penjualan Aksesoris</title>
    
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
        
        /* Role badges */
        .badge-admin {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .badge-kasir {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .badge-owner {
            background: rgba(168, 85, 247, 0.2);
            color: #a855f7;
            border: 1px solid rgba(168, 85, 247, 0.3);
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Kelola Users</h1>
                <p class="text-gray-400">Kelola data pengguna sistem</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Quick Stats -->
                <div class="glass px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-users text-primary-400 mr-2"></i>
                    <span>Total: <?php echo $total_users; ?> Users</span>
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

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Form Tambah User -->
            <div class="lg:col-span-1">
                <div class="blackscrim-card rounded-xl p-6">
                    <h2 class="text-xl font-semibold text-white mb-6 flex items-center">
                        <i class="fas fa-user-plus text-primary-400 mr-3"></i>
                        Tambah User Baru
                    </h2>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="username" class="form-label block text-sm font-medium mb-2">
                                Username <span class="text-red-400">*</span>
                            </label>
                            <input type="text" id="username" name="username" required
                                class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="Masukkan username">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label block text-sm font-medium mb-2">
                                Password <span class="text-red-400">*</span>
                            </label>
                            <input type="password" id="password" name="password" required
                                class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="Masukkan password">
                        </div>
                        
                        <div class="mb-6">
                            <label for="role" class="form-label block text-sm font-medium mb-2">
                                Role <span class="text-red-400">*</span>
                            </label>
                            <select id="role" name="role" required
                                class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500">
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="tambah_user" 
                                class="w-full bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-3 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg">
                            <i class="fas fa-save mr-2"></i>Simpan User
                        </button>
                    </form>
                </div>

                <!-- Statistik Users -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-chart-pie text-blue-400 mr-2"></i>
                        Statistik Users
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 rounded-lg bg-gray-800/30 border border-gray-700/50">
                            <span class="text-gray-300 text-sm">Total Users</span>
                            <span class="text-white font-semibold"><?php echo $total_users; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                            <span class="text-gray-300 text-sm">Admin</span>
                            <span class="text-red-400 font-semibold"><?php echo $total_admin; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <span class="text-gray-300 text-sm">Kasir</span>
                            <span class="text-blue-400 font-semibold"><?php echo $total_kasir; ?></span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <span class="text-gray-300 text-sm">Owner</span>
                            <span class="text-purple-400 font-semibold"><?php echo $total_owner; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Users -->
            <div class="lg:col-span-3">
                <div class="blackscrim-card rounded-xl p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h2 class="text-xl font-semibold text-white flex items-center">
                            <i class="fas fa-users text-primary-400 mr-3"></i>
                            Daftar Users
                        </h2>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-400">
                                Menampilkan <?php echo $total_users; ?> users
                            </span>
                        </div>
                    </div>

                    <?php if (count($users) > 0): ?>
                        <div class="overflow-x-auto dark-table rounded-lg">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">No</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Username</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Role</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Status</th>
                                        <th class="text-left py-4 px-6 text-gray-300 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr class="<?php echo $index % 2 === 0 ? 'bg-gray-900/30' : ''; ?>">
                                            <td class="py-4 px-6 text-gray-300">
                                                <?php echo $index + 1; ?>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="font-medium text-white flex items-center">
                                                    <i class="fas fa-user-circle text-gray-400 mr-2"></i>
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                    <?php if ($user['id_user'] == $_SESSION['user']['id_user']): ?>
                                                        <span class="ml-2 text-xs bg-primary-500/20 text-primary-400 px-2 py-1 rounded border border-primary-500/30">Anda</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <?php 
                                                $badge_class = '';
                                                if ($user['role'] === 'admin') $badge_class = 'badge-admin';
                                                if ($user['role'] === 'kasir') $badge_class = 'badge-kasir';
                                                if ($user['role'] === 'owner') $badge_class = 'badge-owner';
                                                ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $badge_class; ?>">
                                                    <i class="fas fa-shield-alt mr-1 text-xs"></i>
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                                                    <i class="fas fa-check-circle mr-1 text-xs"></i>
                                                    Active
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex space-x-2">
                                                    <!-- Edit Button -->
                                                    <button onclick="openEditModal(
                                                        <?php echo $user['id_user']; ?>, 
                                                        '<?php echo htmlspecialchars($user['username']); ?>',
                                                        '<?php echo $user['role']; ?>'
                                                    )" class="px-3 py-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition duration-200 border border-blue-500/30">
                                                        <i class="fas fa-edit text-sm"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Button -->
                                                    <?php if ($user['id_user'] != $_SESSION['user']['id_user']): ?>
                                                        <a href="?hapus=<?php echo $user['id_user']; ?>" 
                                                           onclick="return confirm('Yakin ingin menghapus user <?php echo htmlspecialchars($user['username']); ?>?')"
                                                           class="px-3 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition duration-200 border border-red-500/30">
                                                            <i class="fas fa-trash text-sm"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="px-3 py-2 bg-gray-500/20 text-gray-400 rounded-lg cursor-not-allowed border border-gray-500/30"
                                                                title="Tidak dapat menghapus user sendiri">
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
                                <i class="fas fa-users text-gray-500 text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-400 mb-2">Belum ada users</h3>
                            <p class="text-gray-500">Mulai dengan menambahkan user pertama Anda</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Informasi Roles -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle text-yellow-400 mr-2"></i>
                        Informasi Roles
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 rounded-lg bg-red-500/10 border border-red-500/20">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-shield-alt text-red-400 mr-2"></i>
                                <h4 class="font-semibold text-white">Admin</h4>
                            </div>
                            <p class="text-gray-400 text-sm">Akses penuh ke semua fitur sistem</p>
                        </div>
                        <div class="p-4 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-cash-register text-blue-400 mr-2"></i>
                                <h4 class="font-semibold text-white">Kasir</h4>
                            </div>
                            <p class="text-gray-400 text-sm">Hanya dapat melakukan transaksi penjualan</p>
                        </div>
                        <div class="p-4 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-chart-line text-purple-400 mr-2"></i>
                                <h4 class="font-semibold text-white">Owner</h4>
                            </div>
                            <p class="text-gray-400 text-sm">Akses laporan dan monitoring bisnis</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Edit User -->
    <div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="blackscrim-card rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-white">Edit User</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="id_user" id="edit_id_user">
                
                <div class="mb-4">
                    <label for="edit_username" class="form-label block text-sm font-medium mb-2">
                        Username <span class="text-red-400">*</span>
                    </label>
                    <input type="text" id="edit_username" name="username" required
                        class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                        placeholder="Masukkan username">
                </div>
                
                <div class="mb-4">
                    <label for="edit_password" class="form-label block text-sm font-medium mb-2">
                        Password Baru
                    </label>
                    <input type="password" id="edit_password" name="password"
                        class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                        placeholder="Kosongkan jika tidak ingin mengubah">
                    <p class="text-gray-400 text-xs mt-1">Biarkan kosong jika tidak ingin mengubah password</p>
                </div>
                
                <div class="mb-6">
                    <label for="edit_role" class="form-label block text-sm font-medium mb-2">
                        Role <span class="text-red-400">*</span>
                    </label>
                    <select id="edit_role" name="role" required
                        class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500">
                        <option value="">Pilih Role</option>
                        <option value="admin">Admin</option>
                        <option value="kasir">Kasir</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 glass text-gray-300 rounded-lg hover:bg-white/10 transition duration-200 border border-gray-700">
                        Batal
                    </button>
                    <button type="submit" name="edit_user"
                            class="px-4 py-2 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-lg hover:from-primary-600 hover:to-primary-700 transition duration-200 shadow-lg">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal(id, username, role) {
            document.getElementById('edit_id_user').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_password').value = '';
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
        <?php if ($pesan && isset($_POST['tambah_user'])): ?>
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('role').value = '';
        <?php endif; ?>
    </script>
</body>
</html>