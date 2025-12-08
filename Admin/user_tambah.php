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

// Proses form tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
                // Reset form
                $username = $password = $role = '';
            } else {
                $pesan_error = "Gagal menambahkan user: " . mysqli_error($koneksi);
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
    <title>Tambah User - Sistem Penjualan Aksesoris</title>
    
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
        
        /* Password strength indicator */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #3b82f6; width: 75%; }
        .strength-strong { background: #22c55e; width: 100%; }
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
                <h1 class="text-3xl font-bold gradient-text mb-2">Tambah User</h1>
                <p class="text-gray-400">Tambahkan user baru ke dalam sistem</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Back Button -->
                <a href="users.php" class="glass px-4 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
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
                            <i class="fas fa-user-plus text-primary-400 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-white">Form Tambah User</h2>
                            <p class="text-gray-400 text-sm">Isi form berikut untuk menambahkan user baru</p>
                        </div>
                    </div>

                    <form method="POST" id="formTambahUser">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Username -->
                            <div class="md:col-span-2">
                                <label for="username" class="form-label block text-sm font-medium mb-2">
                                    <i class="fas fa-user mr-2 text-primary-400"></i>
                                    Username <span class="text-red-400">*</span>
                                </label>
                                <input type="text" id="username" name="username" required
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                    placeholder="Masukkan username">
                                <p class="text-gray-400 text-xs mt-1">Gunakan username yang unik dan mudah diingat</p>
                            </div>

                            <!-- Password -->
                            <div class="md:col-span-2">
                                <label for="password" class="form-label block text-sm font-medium mb-2">
                                    <i class="fas fa-lock mr-2 text-primary-400"></i>
                                    Password <span class="text-red-400">*</span>
                                </label>
                                <input type="password" id="password" name="password" required
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500"
                                    placeholder="Masukkan password"
                                    onkeyup="checkPasswordStrength(this.value)">
                                <div class="mt-2">
                                    <div class="password-strength strength-weak" id="passwordStrength"></div>
                                    <p class="text-gray-400 text-xs mt-1" id="passwordHint">Kekuatan password: Lemah</p>
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="md:col-span-2">
                                <label for="role" class="form-label block text-sm font-medium mb-2">
                                    <i class="fas fa-shield-alt mr-2 text-primary-400"></i>
                                    Role <span class="text-red-400">*</span>
                                </label>
                                <select id="role" name="role" required
                                    class="form-input w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-primary-500">
                                    <option value="">Pilih Role</option>
                                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="kasir" <?php echo (isset($_POST['role']) && $_POST['role'] == 'kasir') ? 'selected' : ''; ?>>Kasir</option>
                                    <option value="owner" <?php echo (isset($_POST['role']) && $_POST['role'] == 'owner') ? 'selected' : ''; ?>>Owner</option>
                                </select>
                                <p class="text-gray-400 text-xs mt-1">Pilih role sesuai dengan kebutuhan akses user</p>
                            </div>
                        </div>

                        <!-- Tombol Action -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700/50">
                            <button type="reset" class="glass px-6 py-2 rounded-lg text-gray-300 hover:bg-white/10 transition-all duration-300 border border-gray-700 text-sm">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                            <button type="submit" class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-2 rounded-lg text-white font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-300 shadow-lg text-sm">
                                <i class="fas fa-save mr-2"></i>Simpan User
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
                            <i class="fas fa-lock text-green-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Keamanan Password</p>
                                <p class="text-gray-400 text-xs">Gunakan password yang kuat dan unik</p>
                            </div>
                        </div>

                        <div class="flex items-start p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <i class="fas fa-shield-alt text-purple-400 text-xs mt-1 mr-3"></i>
                            <div>
                                <p class="text-white text-sm font-medium">Pemilihan Role</p>
                                <p class="text-gray-400 text-xs">Pilih role sesuai kebutuhan akses</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-700/50">
                        <h4 class="text-white font-semibold mb-3 text-sm">Tips Password Aman</h4>
                        <ul class="text-gray-400 text-xs space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Minimal 8 karakter
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Kombinasi huruf dan angka
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Gunakan karakter khusus
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-primary-400 text-xs mr-2"></i>
                                Hindari informasi personal
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Informasi Roles -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-users-cog text-yellow-400 mr-2"></i>
                        Informasi Roles
                    </h3>
                    <div class="space-y-4">
                        <div class="p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-shield-alt text-red-400 mr-2"></i>
                                <h4 class="font-semibold text-white text-sm">Admin</h4>
                            </div>
                            <p class="text-gray-400 text-xs">Akses penuh ke semua fitur sistem</p>
                        </div>
                        
                        <div class="p-3 rounded-lg bg-blue-500/10 border border-blue-500/20">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-cash-register text-blue-400 mr-2"></i>
                                <h4 class="font-semibold text-white text-sm">Kasir</h4>
                            </div>
                            <p class="text-gray-400 text-xs">Hanya dapat melakukan transaksi</p>
                        </div>
                        
                        <div class="p-3 rounded-lg bg-purple-500/10 border border-purple-500/20">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-chart-line text-purple-400 mr-2"></i>
                                <h4 class="font-semibold text-white text-sm">Owner</h4>
                            </div>
                            <p class="text-gray-400 text-xs">Akses laporan dan monitoring</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="blackscrim-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-bolt text-green-400 mr-2"></i>
                        Aksi Cepat
                    </h3>
                    <div class="space-y-3">
                        <a href="users.php" class="flex items-center p-3 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 transition-all duration-300 border border-gray-700">
                            <div class="p-2 bg-blue-500/20 rounded-lg mr-3">
                                <i class="fas fa-users text-blue-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-white text-sm font-medium">Lihat Semua Users</p>
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
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            const strengthHint = document.getElementById('passwordHint');
            
            let strength = 0;
            let hints = [];
            
            // Length check
            if (password.length >= 8) strength += 1;
            else hints.push('minimal 8 karakter');
            
            // Mixed case check
            if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
            else hints.push('huruf besar dan kecil');
            
            // Numbers check
            if (password.match(/([0-9])/)) strength += 1;
            else hints.push('angka');
            
            // Special characters check
            if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
            else hints.push('karakter khusus');
            
            // Update strength bar
            strengthBar.className = 'password-strength';
            if (password.length === 0) {
                strengthBar.classList.add('strength-weak');
                strengthHint.textContent = 'Kekuatan password: Lemah';
            } else if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
                strengthHint.textContent = 'Kekuatan password: Lemah';
            } else if (strength === 2) {
                strengthBar.classList.add('strength-fair');
                strengthHint.textContent = 'Kekuatan password: Cukup';
            } else if (strength === 3) {
                strengthBar.classList.add('strength-good');
                strengthHint.textContent = 'Kekuatan password: Baik';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthHint.textContent = 'Kekuatan password: Kuat';
            }
            
            // Add hints if password is weak
            if (strength <= 2 && password.length > 0) {
                strengthHint.textContent += ' - Gunakan: ' + hints.join(', ');
            }
        }

        // Validasi form sebelum submit
        document.getElementById('formTambahUser').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            if (!username) {
                alert('Username harus diisi!');
                e.preventDefault();
                return;
            }
            
            if (username.length < 3) {
                alert('Username terlalu pendek! Minimal 3 karakter.');
                e.preventDefault();
                return;
            }
            
            if (!password) {
                alert('Password harus diisi!');
                e.preventDefault();
                return;
            }
            
            if (password.length < 6) {
                alert('Password terlalu pendek! Minimal 6 karakter.');
                e.preventDefault();
                return;
            }
            
            if (!role) {
                alert('Role harus dipilih!');
                e.preventDefault();
                return;
            }
        });

        // Auto focus pada input username
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Reset form setelah submit berhasil
        <?php if ($pesan): ?>
            setTimeout(function() {
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
                document.getElementById('role').value = '';
                document.getElementById('passwordStrength').className = 'password-strength strength-weak';
                document.getElementById('passwordHint').textContent = 'Kekuatan password: Lemah';
            }, 100);
        <?php endif; ?>
    </script>
</body>
</html>