<?php
session_start();

// Error handling untuk koneksi database
try {
    require '../config/koneksi.php';
    
    // Pastikan $koneksi terdefinisi
    if (!isset($koneksi) || $koneksi->connect_error) {
        throw new Exception("Koneksi database gagal");
    }
} catch (Exception $e) {
    $error = "Koneksi database gagal: " . $e->getMessage();
}

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);

    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nama_lengkap) || empty($email)) {
        $error = "Semua field wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak sesuai!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (!isset($koneksi)) {
        $error = "Koneksi database tidak tersedia. Silakan coba lagi.";
    } else {
        // Cek apakah username sudah ada
        $stmt = $koneksi->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username sudah digunakan!";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Cek apakah email sudah ada di tabel pelanggan
            $stmt = $koneksi->prepare("SELECT id_pelanggan FROM pelanggan WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Email sudah terdaftar!";
                $stmt->close();
            } else {
                $stmt->close();
                
                // Mulai transaction
                $koneksi->begin_transaction();
                
                try {
                    // Hash password (jika menggunakan password hashing)
                    // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // Untuk saat ini, kita gunakan plain text sesuai dengan login.php
                    $hashed_password = $password;
                    
                    // Insert ke tabel user dengan role 'pelanggan'
                    $stmt_user = $koneksi->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'pelanggan')");
                    $stmt_user->bind_param("ss", $username, $hashed_password);
                    $stmt_user->execute();
                    
                    $id_user = $koneksi->insert_id;
                    
                    // Insert ke tabel pelanggan
                    $stmt_pelanggan = $koneksi->prepare("INSERT INTO pelanggan (id_user, nama_lengkap, email, telepon, alamat) VALUES (?, ?, ?, ?, ?)");
                    $stmt_pelanggan->bind_param("issss", $id_user, $nama_lengkap, $email, $telepon, $alamat);
                    $stmt_pelanggan->execute();
                    
                    // Commit transaction
                    $koneksi->commit();
                    
                    $success = "Registrasi berhasil! Silakan login.";
                    
                    // Log aktivitas registrasi
                    logActivity($koneksi, $id_user, 'REGISTER', 'Pelanggan baru berhasil registrasi');
                    
                    // Redirect ke halaman login setelah 3 detik
                    header("refresh:3;url=login.php");
                    
                } catch (Exception $e) {
                    // Rollback transaction jika ada error
                    $koneksi->rollback();
                    $error = "Terjadi kesalahan saat registrasi: " . $e->getMessage();
                }
                
                if (isset($stmt_user)) $stmt_user->close();
                if (isset($stmt_pelanggan)) $stmt_pelanggan->close();
            }
        }
    }
}

/**
 * Log aktivitas user
 */
function logActivity($koneksi, $userId, $activity, $description) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        // Pastikan tabel log_aktivitas ada
        $create_table = "CREATE TABLE IF NOT EXISTS log_aktivitas (
            id_log INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT,
            aktivitas VARCHAR(100),
            deskripsi TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES user(id_user)
        )";
        
        $koneksi->query($create_table);
        
        $stmt = $koneksi->prepare("INSERT INTO log_aktivitas (id_user, aktivitas, deskripsi, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $userId, $activity, $description, $ipAddress, $userAgent);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Skip error logging tanpa mengganggu flow aplikasi
        error_log("Log activity skipped: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Penjualan Aksesoris</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
            overflow-x: hidden;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff1493 0%, #ff69b4 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .input-field {
            transition: all 0.3s ease;
            border: 1px solid #d1d5db;
        }

        .input-field:focus {
            border-color: #ff69b4;
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-md overflow-hidden">
        <div class="w-full p-8 md:p-10 form-container">
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-pink-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-white text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Moods Strap</h1>
                <p class="text-gray-600 mt-2">Registrasi Akun Pelanggan</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-4">
                    <label for="nama_lengkap" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap *</label>
                    <input name="nama_lengkap" type="text" id="nama_lengkap" placeholder="Masukkan nama lengkap" required
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none"
                        value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email *</label>
                    <input name="email" type="email" id="email" placeholder="contoh@email.com" required
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none"
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="mb-4">
                    <label for="telepon" class="block text-gray-700 text-sm font-medium mb-2">Nomor Telepon</label>
                    <input name="telepon" type="tel" id="telepon" placeholder="08xxxxxxxxxx"
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none"
                        value="<?= isset($_POST['telepon']) ? htmlspecialchars($_POST['telepon']) : '' ?>">
                </div>

                <div class="mb-4">
                    <label for="alamat" class="block text-gray-700 text-sm font-medium mb-2">Alamat</label>
                    <textarea name="alamat" id="alamat" rows="3" placeholder="Masukkan alamat lengkap"
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none"><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-medium mb-2">Username *</label>
                    <input name="username" type="text" id="username" placeholder="Pilih username" required
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none"
                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    <p class="text-xs text-gray-500 mt-1">Username untuk login ke sistem</p>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password *</label>
                    <input name="password" type="password" id="password" placeholder="••••••••" required
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none">
                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>

                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password *</label>
                    <input name="confirm_password" type="password" id="confirm_password" placeholder="••••••••" required
                        class="w-full px-4 py-3 rounded-lg input-field focus:outline-none">
                </div>

                <button type="submit" name="register"
                    class="w-full py-3 px-4 rounded-lg text-white font-semibold btn-primary shadow mb-4">
                    <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                </button>

                <div class="text-center">
                    <p class="text-gray-600 text-sm">
                        Sudah punya akun? 
                        <a href="login.php" class="text-pink-600 hover:text-pink-800 font-medium">
                            <i class="fas fa-sign-in-alt mr-1"></i>Login di sini
                        </a>
                    </p>
                </div>

                <div class="mt-6 text-center">
                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-gray-600 text-sm">Keuntungan sebagai pelanggan:</p>
                        <div class="flex flex-col space-y-2 mt-2 text-xs text-gray-500">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-shopping-cart mr-2 text-pink-500"></i>
                                <span>Belanja produk aksesoris</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-heart mr-2 text-pink-500"></i>
                                <span>Wishlist produk favorit</span>
                            </div>
                            <div class="flex items-center justify-center">
                                <i class="fas fa-truck mr-2 text-pink-500"></i>
                                <span>Pengiriman ke seluruh Indonesia</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validasi konfirmasi password
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Password tidak sesuai");
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>