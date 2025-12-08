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

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username)) {
        $error = "Username tidak boleh kosong.";
    } elseif (!isset($koneksi)) {
        $error = "Koneksi database tidak tersedia. Silakan coba lagi.";
    } else {
        // Cari user berdasarkan username
        $stmt = $koneksi->prepare("SELECT * FROM user WHERE username = ? LIMIT 1");
        
        if ($stmt === false) {
            $error = "Error preparing statement: " . $koneksi->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                // Verifikasi password (asumsi password disimpan dalam bentuk plain text)
                if ($password === $user['password']) {
                    // Data user
                    $_SESSION['user'] = [
                        'id_user' => $user['id_user'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ];

                    // Ambil data tambahan berdasarkan role
                    loadAdditionalUserData($koneksi, $user);
                    
                    // Log aktivitas login
                    logActivity($koneksi, $user['id_user'], 'LOGIN', 'User berhasil login ke sistem');
                    
                    // Redirect sesuai role
                    redirectBasedOnRole($user['role']);
                    
                } else {
                    $error = "Password salah.";
                }
            } else {
                $error = "Username tidak ditemukan.";
            }
            $stmt->close();
        }
    }
}

/**
 * Load additional user data based on role
 */
function loadAdditionalUserData($koneksi, $user) {
    if (!isset($koneksi)) return;
    
    try {
        switch ($user['role']) {
            case 'admin':
                // Statistik untuk admin
                $stats_query = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admin,
                    COUNT(CASE WHEN role = 'kasir' THEN 1 END) as total_kasir,
                    COUNT(CASE WHEN role = 'owner' THEN 1 END) as total_owner
                    FROM user";
                
                $stats_stmt = $koneksi->prepare($stats_query);
                if ($stats_stmt) {
                    $stats_stmt->execute();
                    $stats_result = $stats_stmt->get_result();
                    if ($stats = $stats_result->fetch_assoc()) {
                        $_SESSION['statistik'] = $stats;
                    }
                    $stats_stmt->close();
                }

                // Statistik produk
                $produk_query = "SELECT 
                    COUNT(*) as total_produk,
                    SUM(stok) as total_stok,
                    AVG(harga) as rata_harga
                    FROM produk";
                
                $produk_stmt = $koneksi->prepare($produk_query);
                if ($produk_stmt) {
                    $produk_stmt->execute();
                    $produk_result = $produk_stmt->get_result();
                    if ($produk_stats = $produk_result->fetch_assoc()) {
                        $_SESSION['statistik_produk'] = $produk_stats;
                    }
                    $produk_stmt->close();
                }
                break;
                
            case 'kasir':
                // Statistik untuk kasir - penjualan hari ini
                $today = date('Y-m-d');
                $penjualan_query = "SELECT 
                    COUNT(*) as total_penjualan_hari_ini,
                    SUM(total) as total_pendapatan_hari_ini
                    FROM penjualan 
                    WHERE DATE(tanggal) = ?";
                
                $penjualan_stmt = $koneksi->prepare($penjualan_query);
                if ($penjualan_stmt) {
                    $penjualan_stmt->bind_param("s", $today);
                    $penjualan_stmt->execute();
                    $penjualan_result = $penjualan_stmt->get_result();
                    if ($penjualan_stats = $penjualan_result->fetch_assoc()) {
                        $_SESSION['statistik_penjualan'] = $penjualan_stats;
                    }
                    $penjualan_stmt->close();
                }

                // Produk terpopuler
                $populer_query = "SELECT p.nama_produk, SUM(dp.jumlah) as total_terjual
                    FROM detail_penjualan dp
                    JOIN produk p ON dp.id_produk = p.id_produk
                    JOIN penjualan pj ON dp.id_penjualan = pj.id_penjualan
                    WHERE DATE(pj.tanggal) = ?
                    GROUP BY p.id_produk
                    ORDER BY total_terjual DESC
                    LIMIT 5";
                
                $populer_stmt = $koneksi->prepare($populer_query);
                if ($populer_stmt) {
                    $populer_stmt->bind_param("s", $today);
                    $populer_stmt->execute();
                    $populer_result = $populer_stmt->get_result();
                    $produk_populer = [];
                    while ($populer = $populer_result->fetch_assoc()) {
                        $produk_populer[] = $populer;
                    }
                    $_SESSION['produk_populer'] = $produk_populer;
                    $populer_stmt->close();
                }
                break;

            case 'owner':
                // Statistik untuk owner - laporan lengkap
                $laporan_query = "SELECT 
                    COUNT(*) as total_penjualan,
                    SUM(total) as total_pendapatan,
                    AVG(total) as rata_penjualan
                    FROM penjualan";
                
                $laporan_stmt = $koneksi->prepare($laporan_query);
                if ($laporan_stmt) {
                    $laporan_stmt->execute();
                    $laporan_result = $laporan_stmt->get_result();
                    if ($laporan_stats = $laporan_result->fetch_assoc()) {
                        $_SESSION['statistik_laporan'] = $laporan_stats;
                    }
                    $laporan_stmt->close();
                }

                // Kategori produk
                $kategori_query = "SELECT k.nama_kategori, COUNT(p.id_produk) as total_produk
                    FROM kategori k
                    LEFT JOIN produk p ON k.id_kategori = p.id_kategori
                    GROUP BY k.id_kategori";
                
                $kategori_stmt = $koneksi->prepare($kategori_query);
                if ($kategori_stmt) {
                    $kategori_stmt->execute();
                    $kategori_result = $kategori_stmt->get_result();
                    $data_kategori = [];
                    while ($kategori = $kategori_result->fetch_assoc()) {
                        $data_kategori[] = $kategori;
                    }
                    $_SESSION['data_kategori'] = $data_kategori;
                    $kategori_stmt->close();
                }
                break;
        }
    } catch (Exception $e) {
        // Tangani error tanpa mengganggu proses login
        error_log("Load additional data error: " . $e->getMessage());
    }
}

/**
 * Log aktivitas user
 */
function logActivity($koneksi, $userId, $activity, $description) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        // Buat tabel log_aktivitas jika belum ada
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

/**
 * Redirect berdasarkan role
 */
function redirectBasedOnRole($role) {
    $redirects = [
        'admin' => '../admin/index.php',
        'kasir' => '../kasir/index.php', 
        'owner' => '../owner/index.php',
        'pelanggan' => '../pelanggan/index.php'
    ];
    
    if (isset($redirects[$role])) {
        header("Location: " . $redirects[$role]);
        exit();
    } else {
        header("Location: ../auth/login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Moods Strap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 35px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            transform: rotate(-5deg);
            transition: all 0.4s ease;
        }

        .logo-icon:hover {
            transform: rotate(0deg) scale(1.05);
        }

        .logo-icon i {
            font-size: 32px;
            color: white;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .logo p {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .form-input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            background: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .form-input:focus + .input-icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-checkbox.checked {
            background: #667eea;
            border-color: #667eea;
        }

        .custom-checkbox.checked i {
            opacity: 1;
            transform: scale(1);
        }

        .custom-checkbox i {
            color: white;
            font-size: 12px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .checkbox-label {
            font-size: 14px;
            color: #6b7280;
            cursor: pointer;
            user-select: none;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .register-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .register-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .register-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .register-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.3);
        }

        .roles-info {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .role-kasir { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .role-owner { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .role-pelanggan { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: floatElement 15s infinite linear;
        }

        .element-1 { width: 60px; height: 60px; top: 10%; left: 10%; animation-delay: 0s; }
        .element-2 { width: 40px; height: 40px; top: 20%; right: 15%; animation-delay: -3s; }
        .element-3 { width: 80px; height: 80px; bottom: 15%; left: 15%; animation-delay: -6s; }
        .element-4 { width: 50px; height: 50px; bottom: 25%; right: 10%; animation-delay: -9s; }

        @keyframes floatElement {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
            100% { transform: translateY(0px) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <div class="floating-element element-1"></div>
        <div class="floating-element element-2"></div>
        <div class="floating-element element-3"></div>
        <div class="floating-element element-4"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1>Moods Strap</h1>
                <p>Sistem Penjualan Aksesoris HP</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input name="username" type="text" id="username" placeholder="Masukkan username" required
                            class="form-input"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input name="password" type="password" id="password" placeholder="Masukkan password" required
                            class="form-input">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-forgot">
                    <div class="checkbox-container">
                        <div class="custom-checkbox" onclick="toggleCheckbox()">
                            <i class="fas fa-check"></i>
                        </div>
                        <input type="checkbox" id="remember" name="remember" class="hidden">
                        <label for="remember" class="checkbox-label">Ingat saya</label>
                    </div>
                    <a href="#" class="forgot-link">Lupa password?</a>
                </div>

                <button type="submit" name="login" class="login-btn">
                    <div class="btn-content">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Masuk ke Sistem</span>
                    </div>
                </button>
            </form>

            <div class="register-section">
                <p class="register-text">Belum punya akun?</p>
                <a href="register.php" class="register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Daftar sebagai Pelanggan</span>
                </a>
            </div>

            <div class="roles-info">
                <span class="role-badge role-admin">Admin</span>
                <span class="role-badge role-kasir">Kasir</span>
                <span class="role-badge role-owner">Owner</span>
                <span class="role-badge role-pelanggan">Pelanggan</span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Toggle checkbox
        function toggleCheckbox() {
            const checkbox = document.getElementById('remember');
            const customCheckbox = document.querySelector('.custom-checkbox');
            
            checkbox.checked = !checkbox.checked;
            customCheckbox.classList.toggle('checked', checkbox.checked);
        }

        // Initialize checkbox state
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('remember');
            const customCheckbox = document.querySelector('.custom-checkbox');
            customCheckbox.classList.toggle('checked', checkbox.checked);
        });

        // Add floating animation to login card
        const card = document.querySelector('.login-card');
        card.style.animation = 'float 6s ease-in-out infinite';
    </script>
</body>
</html>