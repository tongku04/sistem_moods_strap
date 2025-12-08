<?php
session_start();

// Cek session dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kasir') {
    header("Location: ../auth/login.php");
    exit;
}

// Include koneksi database dengan path yang benar
include_once '../config/koneksi.php';

// Get filter parameters
$stock_filter = $_GET['stock_filter'] ?? 'all';
$category = $_GET['category'] ?? '';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_stok_produk_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Fungsi untuk mendapatkan label status stok
function getStockStatus($stok) {
    if ($stok == 0) {
        return ['status' => 'STOK HABIS', 'color' => '#DC2626', 'priority' => 'TINGGI'];
    } elseif ($stok <= 5) {
        return ['status' => 'STOK KRITIS', 'color' => '#EA580C', 'priority' => 'TINGGI'];
    } elseif ($stok <= 10) {
        return ['status' => 'STOK MENIPIS', 'color' => '#D97706', 'priority' => 'SEDANG'];
    } else {
        return ['status' => 'STOK AMAN', 'color' => '#059669', 'priority' => 'RENDAH'];
    }
}

// Fungsi untuk format angka
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

try {
    // Query untuk statistik stok - PERBAIKAN: gunakan backticks untuk reserved keyword
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN stok > 10 THEN 1 END) as available,
                    COUNT(CASE WHEN stok > 5 AND stok <= 10 THEN 1 END) as low,
                    COUNT(CASE WHEN stok > 0 AND stok <= 5 THEN 1 END) as critical,
                    COUNT(CASE WHEN stok = 0 THEN 1 END) as `out`,
                    COALESCE(SUM(stok * harga), 0) as total_value,
                    COALESCE(SUM(stok), 0) as total_stock,
                    COALESCE(AVG(stok), 0) as avg_stock
                   FROM produk 
                   WHERE status = 'active'";
    
    $statsResult = $koneksi->query($statsQuery);
    
    if (!$statsResult) {
        throw new Exception("Error dalam query statistik: " . $koneksi->error);
    }
    
    $stats = $statsResult->fetch_assoc();

    // Query untuk laporan stok
    $query = "SELECT p.*, k.nama_kategori 
              FROM produk p 
              LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
              WHERE p.status = 'active'";

    // Filter stok
    if ($stock_filter === 'critical') {
        $query .= " AND p.stok > 0 AND p.stok <= 5";
    } elseif ($stock_filter === 'low') {
        $query .= " AND p.stok > 5 AND p.stok <= 10";
    } elseif ($stock_filter === 'out') {
        $query .= " AND p.stok = 0";
    } elseif ($stock_filter === 'available') {
        $query .= " AND p.stok > 10";
    }

    if (!empty($category) && $category !== 'all') {
        // Gunakan prepared statement untuk menghindari SQL injection
        $stmt = $koneksi->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
        $stmt->bind_param("i", $category);
        $stmt->execute();
        $categoryResult = $stmt->get_result();
        if ($categoryResult->num_rows > 0) {
            $categoryData = $categoryResult->fetch_assoc();
            $categoryLabel = $categoryData['nama_kategori'];
        }
        $stmt->close();
        
        $query .= " AND p.id_kategori = '$category'";
    } else {
        $categoryLabel = 'Semua Kategori';
    }

    // Order by priority
    $query .= " ORDER BY 
                CASE 
                    WHEN p.stok = 0 THEN 1
                    WHEN p.stok <= 5 THEN 2
                    WHEN p.stok <= 10 THEN 3
                    ELSE 4
                END,
                p.stok ASC,
                p.nama_produk ASC";

    $result = $koneksi->query($query);
    
    if (!$result) {
        throw new Exception("Error dalam query laporan: " . $koneksi->error);
    }

    // Stock filter label
    $stockFilterLabels = [
        'all' => 'Semua Stok',
        'critical' => 'Stok Kritis (≤ 5)',
        'low' => 'Stok Menipis (6-10)',
        'out' => 'Stok Habis',
        'available' => 'Stok Aman (> 10)'
    ];
    $stockFilterLabel = $stockFilterLabels[$stock_filter] ?? 'Semua Stok';

} catch (Exception $e) {
    die("Error dalam mengambil data: " . $e->getMessage());
}

// Hitung persentase dengan pengecekan null
$critical_percent = isset($stats['total']) && $stats['total'] > 0 ? ($stats['critical'] / $stats['total']) * 100 : 0;
$low_percent = isset($stats['total']) && $stats['total'] > 0 ? ($stats['low'] / $stats['total']) * 100 : 0;
$out_percent = isset($stats['total']) && $stats['total'] > 0 ? ($stats['out'] / $stats['total']) * 100 : 0;
$available_percent = isset($stats['total']) && $stats['total'] > 0 ? ($stats['available'] / $stats['total']) * 100 : 0;

// Default values jika data tidak ada
$stats = array_merge([
    'total' => 0,
    'available' => 0,
    'low' => 0,
    'critical' => 0,
    'out' => 0,
    'total_value' => 0,
    'total_stock' => 0,
    'avg_stock' => 0
], $stats);
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Stok Produk</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                            <x:HorizontalResolution>600</x:HorizontalResolution>
                            <x:VerticalResolution>600</x:VerticalResolution>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        /* Excel-friendly styles */
        .xl {
            mso-number-format: General;
        }
        .text {
            mso-number-format: "\@";
        }
        .number {
            mso-number-format: "#,##0";
        }
        .currency {
            mso-number-format: "\"Rp\"\ #,##0";
        }
        .percentage {
            mso-number-format: "0.0%";
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 25px;
            color: #1F2937;
            background: #FFFFFF;
            line-height: 1.4;
        }
        
        .header-section {
            border-bottom: 3px solid #1E40AF;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .company-header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .company-info {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        
        .report-info {
            display: table-cell;
            vertical-align: middle;
            width: 30%;
            text-align: right;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1E40AF;
            margin: 0;
        }
        
        .company-address {
            font-size: 12px;
            color: #6B7280;
            margin: 2px 0;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin: 10px 0 5px 0;
            text-align: center;
        }
        
        .report-period {
            font-size: 12px;
            color: #6B7280;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #E5E7EB;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-cell {
            display: table-cell;
            padding: 15px;
            border: 1px solid #E5E7EB;
            vertical-align: top;
            width: 25%;
        }
        
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 11px;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-trend {
            font-size: 10px;
            margin-top: 3px;
        }
        
        .section {
            margin: 25px 0;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #1E40AF;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 4px 4px 0 0;
        }
        
        .section-subtitle {
            background: #F8FAFC;
            color: #374151;
            padding: 8px 15px;
            font-size: 12px;
            border: 1px solid #E5E7EB;
            border-top: none;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #E5E7EB;
        }
        
        .data-table th {
            background: #1E40AF;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1E40AF;
            font-size: 10px;
        }
        
        .data-table td {
            padding: 8px;
            border: 1px solid #E5E7EB;
            vertical-align: top;
        }
        
        .data-table tr:nth-child(even) {
            background: #F8FAFC;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .total-row {
            background: #D1FAE5 !important;
            font-weight: bold;
            color: #065F46;
        }
        
        .critical-row {
            background: #FEF2F2 !important;
            color: #DC2626;
        }
        
        .warning-row {
            background: #FFFBEB !important;
            color: #D97706;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .status-available { background: #10B981; }
        .status-low { background: #F59E0B; }
        .status-critical { background: #EA580C; }
        .status-out { background: #DC2626; }
        
        .priority-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 50px;
        }
        
        .priority-high {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        .priority-medium {
            background: #FFFBEB;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        
        .priority-low {
            background: #F0FDF4;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        
        .stock-bar {
            display: inline-block;
            height: 6px;
            background: #E5E7EB;
            border-radius: 3px;
            overflow: hidden;
            width: 60px;
            margin-right: 8px;
        }
        
        .stock-fill {
            height: 100%;
        }
        
        .fill-available { background: linear-gradient(90deg, #10B981, #34D399); }
        .fill-low { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
        .fill-critical { background: linear-gradient(90deg, #EA580C, #FB923C); }
        .fill-out { background: #DC2626; }
        
        .analysis-section {
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .analysis-title {
            font-weight: bold;
            color: #1E40AF;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .analysis-content {
            font-size: 10px;
            color: #374151;
            line-height: 1.5;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #E5E7EB;
            font-size: 9px;
            color: #6B7280;
            text-align: center;
        }
        
        .signature-area {
            margin-top: 25px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #374151;
            width: 200px;
            margin: 20px auto 5px auto;
            padding-top: 8px;
        }
        
        .alert-banner {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 4px;
            padding: 10px 15px;
            margin: 15px 0;
            font-size: 11px;
        }
        
        .alert-title {
            font-weight: bold;
            color: #92400E;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header-section">
        <div class="company-header">
            <div class="company-info">
                <div class="company-name">TOKO AKSESORIS BERAK</div>
                <div class="company-address">Jl. Contoh Alamat No. 123, Kota Berak | Telp: (021) 123-4567</div>
                <div class="company-address">Email: info@berakaksesoris.com | Website: www.berakaksesoris.com</div>
            </div>
            <div class="report-info">
                <div style="font-size: 11px; color: #6B7280;">
                    <strong>LAPORAN STOK PRODUK</strong><br>
                    Dicetak: <?php echo date('d/m/Y H:i:s'); ?><br>
                    Oleh: <?php echo $_SESSION['user']['username']; ?>
                </div>
            </div>
        </div>
        
        <div class="report-title">LAPORAN ANALISIS PERSEDIAAN STOK PRODUK</div>
        <div class="report-period">
            Periode Laporan: <?php echo date('F Y'); ?> | 
            Filter: <?php echo $stockFilterLabel; ?> | 
            Kategori: <?php echo $categoryLabel; ?>
        </div>
    </div>

    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">RINGKASAN EKSEKUTIF</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">
                    <div class="metric-value number"><?php echo number_format($stats['total']); ?></div>
                    <div class="metric-label">Total Produk Aktif</div>
                    <div class="metric-trend" style="color: #6B7280;">Semua produk dalam sistem</div>
                </div>
                <div class="summary-cell">
                    <div class="metric-value number" style="color: #059669;"><?php echo number_format($stats['available']); ?></div>
                    <div class="metric-label">Stok Aman</div>
                    <div class="metric-trend" style="color: #059669;"><?php echo number_format($available_percent, 1); ?>% dari total</div>
                </div>
                <div class="summary-cell">
                    <div class="metric-value number" style="color: #D97706;"><?php echo number_format($stats['low'] + $stats['critical']); ?></div>
                    <div class="metric-label">Perlu Perhatian</div>
                    <div class="metric-trend" style="color: #D97706;"><?php echo number_format($low_percent + $critical_percent, 1); ?>% dari total</div>
                </div>
                <div class="summary-cell">
                    <div class="metric-value currency"><?php echo formatRupiah($stats['total_value']); ?></div>
                    <div class="metric-label">Nilai Persediaan</div>
                    <div class="metric-trend" style="color: #1E40AF;">Rata-rata: <?php echo formatRupiah($stats['total_value'] / max(1, $stats['total'])); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Distribution Alert -->
    <?php if ($stats['critical'] > 0 || $stats['out'] > 0): ?>
    <div class="alert-banner">
        <div class="alert-title">🚨 PERHATIAN: TERDAPAT PRODUK DENGAN STOK KRITIS</div>
        <div>
            <?php if ($stats['out'] > 0): ?>
            • <strong><?php echo $stats['out']; ?> produk</strong> dengan stok habis (<?php echo number_format($out_percent, 1); ?>%)<br>
            <?php endif; ?>
            <?php if ($stats['critical'] > 0): ?>
            • <strong><?php echo $stats['critical']; ?> produk</strong> dengan stok kritis ≤ 5 (<?php echo number_format($critical_percent, 1); ?>%)<br>
            <?php endif; ?>
            <?php if ($stats['low'] > 0): ?>
            • <strong><?php echo $stats['low']; ?> produk</strong> dengan stok menipis 6-10 (<?php echo number_format($low_percent, 1); ?>%)
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detailed Stock Report -->
    <div class="section">
        <div class="section-title">DETAIL LAPORAN STOK PRODUK</div>
        <div class="section-subtitle">
            Diurutkan berdasarkan prioritas stok (Habis → Kritis → Menipis → Aman)
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="24%">Nama Produk</th>
                    <th width="14%">Kategori</th>
                    <th width="12%">Harga Satuan</th>
                    <th width="10%">Stok</th>
                    <th width="14%">Status</th>
                    <th width="12%">Prioritas</th>
                    <th width="10%">Nilai Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total_stock_value = 0;
                $total_units = 0;
                $critical_count = 0;
                $low_count = 0;
                $out_count = 0;
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $stock_value = $row['stok'] * $row['harga'];
                        $total_stock_value += $stock_value;
                        $total_units += $row['stok'];
                        
                        $status = getStockStatus($row['stok']);
                        $status_class = str_replace(' ', '-', strtolower($status['status']));
                        $priority_class = strtolower($status['priority']);
                        
                        // Count by status
                        if ($row['stok'] == 0) $out_count++;
                        elseif ($row['stok'] <= 5) $critical_count++;
                        elseif ($row['stok'] <= 10) $low_count++;
                        
                        // Determine row class
                        $row_class = '';
                        if ($row['stok'] == 0) {
                            $row_class = 'critical-row';
                            $fill_class = 'fill-out';
                            $fill_width = 0;
                        } elseif ($row['stok'] <= 5) {
                            $row_class = 'critical-row';
                            $fill_class = 'fill-critical';
                            $fill_width = ($row['stok'] / 5) * 100;
                        } elseif ($row['stok'] <= 10) {
                            $row_class = 'warning-row';
                            $fill_class = 'fill-low';
                            $fill_width = ($row['stok'] / 10) * 100;
                        } else {
                            $fill_class = 'fill-available';
                            $fill_width = 100;
                        }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="text-center number"><?php echo $no++; ?></td>
                            <td class="text-left">
                                <strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong>
                                <?php if ($row['deskripsi']): ?>
                                <br><span style="font-size: 9px; color: #6B7280;"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 50)) . (strlen($row['deskripsi']) > 50 ? '...' : ''); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-left"><?php echo $row['nama_kategori'] ?? '-'; ?></td>
                            <td class="text-right currency"><?php echo formatRupiah($row['harga']); ?></td>
                            <td class="text-center">
                                <div style="display: flex; align-items: center; justify-content: center;">
                                    <div class="stock-bar">
                                        <div class="stock-fill <?php echo $fill_class; ?>" style="width: <?php echo $fill_width; ?>%"></div>
                                    </div>
                                    <span class="number" style="font-weight: bold;"><?php echo number_format($row['stok']); ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-indicator status-<?php echo $status_class; ?>"></span>
                                <span style="font-size: 9px; font-weight: bold;"><?php echo $status['status']; ?></span>
                            </td>
                            <td class="text-center">
                                <span class="priority-badge priority-<?php echo $priority_class; ?>">
                                    <?php echo $status['priority']; ?>
                                </span>
                            </td>
                            <td class="text-right currency" style="font-weight: bold;"><?php echo formatRupiah($stock_value); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px; color: #6B7280;">
                            Tidak ada data produk yang sesuai dengan filter yang dipilih
                        </td>
                    </tr>
                    <?php
                }
                ?>
                
                <!-- Summary Row -->
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAL KESELURUHAN</strong></td>
                    <td class="text-center number"><strong><?php echo number_format($total_units); ?></strong></td>
                    <td class="text-center">
                        <span style="font-size: 9px;">
                            <?php echo $out_count; ?> Habis | <?php echo $critical_count; ?> Kritis | <?php echo $low_count; ?> Menipis
                        </span>
                    </td>
                    <td class="text-center">-</td>
                    <td class="text-right currency"><strong><?php echo formatRupiah($total_stock_value); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Analysis & Recommendations -->
    <div class="section">
        <div class="section-title">ANALISIS & REKOMENDASI MANAJEMEN</div>
        
        <div class="analysis-section">
            <div class="analysis-title">📊 ANALISIS KONDISI STOK</div>
            <div class="analysis-content">
                • <strong>Nilai Total Persediaan:</strong> <?php echo formatRupiah($stats['total_value']); ?> <br>
                • <strong>Rata-rata Stok per Produk:</strong> <?php echo number_format($stats['avg_stock'], 1); ?> unit <br>
                • <strong>Produk dengan Prioritas Tinggi:</strong> <?php echo $out_count + $critical_count; ?> produk memerlukan perhatian segera <br>
                • <strong>Efisiensi Pengelolaan Stok:</strong> <?php echo number_format($available_percent, 1); ?>% produk dalam kondisi aman
            </div>
        </div>
        
        <div class="analysis-section">
            <div class="analysis-title">🎯 REKOMENDASI STRATEGIS</div>
            <div class="analysis-content">
                1. <strong>RESTOCK SEGERA</strong> - Fokus pada <?php echo $out_count; ?> produk dengan stok habis dan <?php echo $critical_count; ?> produk stok kritis<br>
                2. <strong>MONITOR KETAT</strong> - <?php echo $low_count; ?> produk dengan stok menipis perlu pengawasan harian<br>
                3. <strong>OPTIMASI PEMBELIAN</strong> - Analisis pola penjualan untuk forecasting yang akurat<br>
                4. <strong>DIVERSIFIKASI SUPPLIER</strong> - Pertimbangkan multiple supplier untuk produk fast-moving<br>
                5. <strong>INVENTORY TURNOVER</strong> - Targetkan rasio perputaran stok yang optimal
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="signature-area">
            <div style="margin-bottom: 10px; font-size: 10px; color: #6B7280;">
                <strong>CATATAN:</strong> Laporan ini bersifat rahasia dan untuk penggunaan internal manajemen Toko Aksesoris Berak.<br>
                Data dihasilkan secara real-time dari sistem dan mencerminkan kondisi aktual persediaan.
            </div>
            
            <div class="signature-line">
                <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong><br>
                <em><?php echo ucfirst($_SESSION['user']['role']); ?> - Toko Aksesoris Berak</em><br>
                <span style="font-size: 8px;">Penanggung Jawab Monitoring Stok</span>
            </div>
        </div>
        
        <div style="margin-top: 15px;">
            Laporan Stok Produk | Generated by Inventory Management System © <?php echo date('Y'); ?><br>
            Toko Aksesoris Berak - Professional Inventory Reporting
        </div>
    </div>

</body>
</html>