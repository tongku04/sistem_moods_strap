<?php
session_start();

// Cek session dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Include koneksi database
include_once '../config/koneksi.php';

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'harian';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_analitik_penjualan_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Query untuk statistik utama
    $statsQuery = "SELECT 
                    COUNT(*) as total_transaksi,
                    COALESCE(SUM(total), 0) as total_penjualan,
                    AVG(total) as rata_rata,
                    MIN(total) as transaksi_terkecil,
                    MAX(total) as transaksi_terbesar,
                    COUNT(DISTINCT id_user) as total_kasir
                   FROM penjualan 
                   WHERE DATE(tanggal) BETWEEN ? AND ?";
    $stmt = $koneksi->prepare($statsQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $statsResult = $stmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $stmt->close();

    // Query untuk laporan periodik
    if ($report_type === 'harian') {
        $reportQuery = "SELECT 
                        DATE(tanggal) as periode,
                        COUNT(*) as total_transaksi,
                        SUM(total) as total_penjualan,
                        AVG(total) as rata_rata
                       FROM penjualan 
                       WHERE DATE(tanggal) BETWEEN ? AND ? 
                       GROUP BY DATE(tanggal) 
                       ORDER BY periode DESC";
        $periode_label = "Harian";
    } elseif ($report_type === 'bulanan') {
        $reportQuery = "SELECT 
                        DATE_FORMAT(tanggal, '%Y-%m') as periode,
                        COUNT(*) as total_transaksi,
                        SUM(total) as total_penjualan,
                        AVG(total) as rata_rata
                       FROM penjualan 
                       WHERE DATE(tanggal) BETWEEN ? AND ? 
                       GROUP BY DATE_FORMAT(tanggal, '%Y-%m') 
                       ORDER BY periode DESC";
        $periode_label = "Bulanan";
    } else {
        $reportQuery = "SELECT 
                        YEAR(tanggal) as periode,
                        COUNT(*) as total_transaksi,
                        SUM(total) as total_penjualan,
                        AVG(total) as rata_rata
                       FROM penjualan 
                       WHERE DATE(tanggal) BETWEEN ? AND ? 
                       GROUP BY YEAR(tanggal) 
                       ORDER BY periode DESC";
        $periode_label = "Tahunan";
    }

    $stmt = $koneksi->prepare($reportQuery);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $reportResult = $stmt->get_result();

    // Query untuk produk terlaris
    $produkQuery = "SELECT 
                    pr.nama_produk,
                    pr.harga,
                    SUM(dp.jumlah) as total_terjual,
                    SUM(dp.subtotal) as total_pendapatan,
                    ROUND(SUM(dp.subtotal) / SUM(dp.jumlah)) as rata_harga_jual
                   FROM detail_penjualan dp 
                   JOIN produk pr ON dp.id_produk = pr.id_produk 
                   JOIN penjualan p ON dp.id_penjualan = p.id_penjualan 
                   WHERE DATE(p.tanggal) BETWEEN ? AND ? 
                   GROUP BY dp.id_produk 
                   ORDER BY total_terjual DESC 
                   LIMIT 15";
    $stmtProduk = $koneksi->prepare($produkQuery);
    $stmtProduk->bind_param("ss", $start_date, $end_date);
    $stmtProduk->execute();
    $produkResult = $stmtProduk->get_result();

    // Query untuk top kasir
    $kasirQuery = "SELECT 
                    u.username,
                    COUNT(p.id_penjualan) as total_transaksi,
                    SUM(p.total) as total_penjualan,
                    ROUND(AVG(p.total)) as rata_transaksi
                   FROM penjualan p 
                   JOIN user u ON p.id_user = u.id_user 
                   WHERE DATE(p.tanggal) BETWEEN ? AND ? 
                   GROUP BY p.id_user 
                   ORDER BY total_penjualan DESC 
                   LIMIT 10";
    $stmtKasir = $koneksi->prepare($kasirQuery);
    $stmtKasir->bind_param("ss", $start_date, $end_date);
    $stmtKasir->execute();
    $kasirResult = $stmtKasir->get_result();

    // Query untuk trend harian (7 hari terakhir)
    $trendQuery = "SELECT 
                    DATE(tanggal) as tanggal,
                    COUNT(*) as total_transaksi,
                    SUM(total) as total_penjualan,
                    HOUR(tanggal) as jam,
                    COUNT(CASE WHEN HOUR(tanggal) BETWEEN 8 AND 12 THEN 1 END) as pagi,
                    COUNT(CASE WHEN HOUR(tanggal) BETWEEN 13 AND 18 THEN 1 END) as siang,
                    COUNT(CASE WHEN HOUR(tanggal) BETWEEN 19 AND 22 THEN 1 END) as malam
                   FROM penjualan 
                   WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   GROUP BY DATE(tanggal) 
                   ORDER BY tanggal DESC 
                   LIMIT 7";
    $trendResult = $koneksi->query($trendQuery);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Analitik</x:Name>
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
            mso-number-format: "0\.00%";
        }
        .date {
            mso-number-format: "dd\/mm\/yyyy";
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
            background: #ffffff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #34495e;
            margin: 15px 0;
        }
        
        .report-info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #bdc3c7;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .stats-row {
            display: table-row;
        }
        
        .stat-card {
            display: table-cell;
            text-align: center;
            padding: 20px 15px;
            background: #ffffff;
            border: 2px solid #ecf0f1;
            vertical-align: middle;
            width: 20%;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            margin: 30px 0;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #34495e;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 16px;
            border-radius: 5px 5px 0 0;
        }
        
        .section-content {
            border: 1px solid #bdc3c7;
            border-top: none;
            padding: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #34495e;
        }
        
        td {
            padding: 8px;
            border: 1px solid #bdc3c7;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
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
            background: #d4edda !important;
            font-weight: bold;
            color: #155724;
        }
        
        .highlight-row {
            background: #fff3cd !important;
            color: #856404;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #34495e;
            page-break-inside: avoid;
        }
        
        .signature-area {
            text-align: center;
            margin-top: 40px;
        }
        
        .signature-line {
            border-top: 1px solid #2c3e50;
            width: 250px;
            margin: 40px auto 10px auto;
            padding-top: 10px;
        }
        
        .notes {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-size: 11px;
            color: #856404;
        }
        
        .metric-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .positive {
            color: #27ae60;
            font-weight: bold;
        }
        
        .negative {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="company-name"> AKSESORIS MOODS STRAP</div>
        <div class="company-tagline">Solusi Kebutuhan Aksesoris Terlengkap dan Terpercaya</div>
        <div class="report-title">LAPORAN ANALITIK PENJUALAN</div>
        <div style="font-size: 14px; color: #7f8c8d;">
            Periode: <?php echo date('d F Y', strtotime($start_date)) . ' - ' . date('d F Y', strtotime($end_date)); ?>
        </div>
        <div style="font-size: 12px; color: #95a5a6;">
            Dicetak pada: <?php echo date('d F Y H:i:s'); ?> | Tipe Laporan: <?php echo $periode_label; ?>
        </div>
    </div>

    <!-- Report Information -->
    <div class="report-info">
        <table width="100%">
            <tr>
                <td width="50%" style="border: none; padding: 5px;">
                    <strong>Informasi Laporan:</strong><br>
                    • Periode Analisis: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?><br>
                    • Tipe Laporan: <?php echo $periode_label; ?><br>
                    • Total Hari: <?php echo round((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24)) + 1; ?> hari
                </td>
                <td width="50%" style="border: none; padding: 5px;">
                    <strong>Metrik Kinerja:</strong><br>
                    • Rata-rata Transaksi/Hari: Rp <?php echo number_format($stats['total_penjualan'] / max(1, (round((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24)) + 1)), 0, ',', '.'); ?><br>
                    • Efisiensi: <?php echo number_format($stats['total_transaksi'] / max(1, $stats['total_kasir']), 1); ?> transaksi/kasir<br>
                    • Waktu Generate: Real-time
                </td>
            </tr>
        </table>
    </div>

    <!-- Key Statistics -->
    <div class="section">
        <div class="section-title">STATISTIK UTAMA KINERJA PENJUALAN</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-value number"><?php echo number_format($stats['total_transaksi']); ?></span>
                    <span class="stat-label">Total Transaksi</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value currency">Rp <?php echo number_format($stats['total_penjualan'], 0, ',', '.'); ?></span>
                    <span class="stat-label">Total Penjualan</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value currency">Rp <?php echo number_format($stats['rata_rata'], 0, ',', '.'); ?></span>
                    <span class="stat-label">Rata-rata Transaksi</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value currency">Rp <?php echo number_format($stats['transaksi_terbesar'], 0, ',', '.'); ?></span>
                    <span class="stat-label">Transaksi Terbesar</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value number"><?php echo number_format($stats['total_kasir']); ?></span>
                    <span class="stat-label">Total Kasir Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Laporan Periodik -->
    <div class="section">
        <div class="section-title">LAPORAN PENJUALAN <?php echo strtoupper($periode_label); ?></div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th width="20%">Periode</th>
                        <th width="15%">Jumlah Transaksi</th>
                        <th width="20%">Total Penjualan</th>
                        <th width="20%">Rata-rata Transaksi</th>
                        <th width="25%">Kontribusi (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grand_total = 0;
                    $grand_transaksi = 0;
                    $report_data = [];
                    
                    if ($reportResult->num_rows > 0) {
                        while ($row = $reportResult->fetch_assoc()) {
                            $report_data[] = $row;
                            $grand_total += $row['total_penjualan'];
                            $grand_transaksi += $row['total_transaksi'];
                        }
                        
                        foreach ($report_data as $index => $item) {
                            $kontribusi = $grand_total > 0 ? ($item['total_penjualan'] / $grand_total) * 100 : 0;
                            
                            // Format periode berdasarkan tipe laporan
                            if ($report_type === 'harian') {
                                $periode_display = date('d F Y', strtotime($item['periode']));
                            } elseif ($report_type === 'bulanan') {
                                $periode_display = date('F Y', strtotime($item['periode'] . '-01'));
                            } else {
                                $periode_display = $item['periode'];
                            }
                            ?>
                            <tr <?php echo $index < 3 ? 'class="highlight-row"' : ''; ?>>
                                <td class="text-left"><?php echo $periode_display; ?></td>
                                <td class="number"><?php echo number_format($item['total_transaksi']); ?></td>
                                <td class="currency">Rp <?php echo number_format($item['total_penjualan'], 0, ',', '.'); ?></td>
                                <td class="currency">Rp <?php echo number_format($item['rata_rata'], 0, ',', '.'); ?></td>
                                <td class="text-right">
                                    <?php echo number_format($kontribusi, 2); ?>%
                                    <?php if ($kontribusi >= 20): ?>
                                        <span class="metric-badge">TOP</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data laporan</td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr class="total-row">
                        <td><strong>TOTAL</strong></td>
                        <td class="number"><strong><?php echo number_format($grand_transaksi); ?></strong></td>
                        <td class="currency"><strong>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></strong></td>
                        <td class="currency"><strong>Rp <?php echo number_format($grand_total / max(1, $grand_transaksi), 0, ',', '.'); ?></strong></td>
                        <td class="text-right"><strong>100%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="section">
        <div class="section-title">15 PRODUK TERLARIS</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="35%">Nama Produk</th>
                        <th width="12%">Harga</th>
                        <th width="12%">Terjual</th>
                        <th width="18%">Total Pendapatan</th>
                        <th width="18%">Rata Harga Jual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no_produk = 1;
                    $total_terjual = 0;
                    $total_pendapatan_produk = 0;
                    
                    if ($produkResult->num_rows > 0) {
                        while ($produk = $produkResult->fetch_assoc()) {
                            $total_terjual += $produk['total_terjual'];
                            $total_pendapatan_produk += $produk['total_pendapatan'];
                            ?>
                            <tr <?php echo $no_produk <= 3 ? 'class="highlight-row"' : ''; ?>>
                                <td class="text-center number"><?php echo $no_produk++; ?></td>
                                <td class="text-left"><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
                                <td class="currency">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                                <td class="number"><?php echo number_format($produk['total_terjual']); ?> pcs</td>
                                <td class="currency">Rp <?php echo number_format($produk['total_pendapatan'], 0, ',', '.'); ?></td>
                                <td class="currency">
                                    Rp <?php echo number_format($produk['rata_harga_jual'], 0, ',', '.'); ?>
                                    <?php if ($produk['rata_harga_jual'] > $produk['harga']): ?>
                                        <span class="positive">↑</span>
                                    <?php elseif ($produk['rata_harga_jual'] < $produk['harga']): ?>
                                        <span class="negative">↓</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data produk</td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td class="number"><strong><?php echo number_format($total_terjual); ?> pcs</strong></td>
                        <td class="currency"><strong>Rp <?php echo number_format($total_pendapatan_produk, 0, ',', '.'); ?></strong></td>
                        <td class="currency"><strong>Rp <?php echo number_format($total_pendapatan_produk / max(1, $total_terjual), 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Performa Kasir -->
    <div class="section">
        <div class="section-title">TOP 10 PERFORMANSI KASIR</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th width="10%">Peringkat</th>
                        <th width="30%">Nama Kasir</th>
                        <th width="15%">Total Transaksi</th>
                        <th width="20%">Total Penjualan</th>
                        <th width="15%">Rata Transaksi</th>
                        <th width="10%">Kontribusi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no_kasir = 1;
                    $total_penjualan_kasir = 0;
                    $kasir_data = [];
                    
                    if ($kasirResult->num_rows > 0) {
                        while ($kasir = $kasirResult->fetch_assoc()) {
                            $kasir_data[] = $kasir;
                            $total_penjualan_kasir += $kasir['total_penjualan'];
                        }
                        
                        foreach ($kasir_data as $index => $kasir) {
                            $kontribusi_kasir = $stats['total_penjualan'] > 0 ? ($kasir['total_penjualan'] / $stats['total_penjualan']) * 100 : 0;
                            ?>
                            <tr <?php echo $index < 3 ? 'class="highlight-row"' : ''; ?>>
                                <td class="text-center">
                                    <?php echo $index + 1; ?>
                                    <?php if ($index < 3): ?>
                                        <span class="metric-badge"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-left"><?php echo htmlspecialchars($kasir['username']); ?></td>
                                <td class="number"><?php echo number_format($kasir['total_transaksi']); ?></td>
                                <td class="currency">Rp <?php echo number_format($kasir['total_penjualan'], 0, ',', '.'); ?></td>
                                <td class="currency">Rp <?php echo number_format($kasir['rata_transaksi'], 0, ',', '.'); ?></td>
                                <td class="text-right"><?php echo number_format($kontribusi_kasir, 1); ?>%</td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data kasir</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Trend 7 Hari Terakhir -->
    <div class="section">
        <div class="section-title">TREND 7 HARI TERAKHIR</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th width="20%">Tanggal</th>
                        <th width="15%">Total Transaksi</th>
                        <th width="20%">Total Penjualan</th>
                        <th width="15%">Transaksi Pagi</th>
                        <th width="15%">Transaksi Siang</th>
                        <th width="15%">Transaksi Malam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($trendResult->num_rows > 0) {
                        while ($trend = $trendResult->fetch_assoc()) {
                            ?>
                            <tr>
                                <td class="text-left"><?php echo date('d F Y', strtotime($trend['tanggal'])); ?></td>
                                <td class="number"><?php echo number_format($trend['total_transaksi']); ?></td>
                                <td class="currency">Rp <?php echo number_format($trend['total_penjualan'], 0, ',', '.'); ?></td>
                                <td class="number"><?php echo number_format($trend['pagi']); ?></td>
                                <td class="number"><?php echo number_format($trend['siang']); ?></td>
                                <td class="number"><?php echo number_format($trend['malam']); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data trend</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Analisis dan Rekomendasi -->
    <div class="section">
        <div class="section-title">ANALISIS & REKOMENDASI</div>
        <div class="section-content" style="padding: 15px;">
            <div class="notes">
                <strong>📊 INSIGHT KINERJA:</strong><br>
                • Total pendapatan periode ini: <strong>Rp <?php echo number_format($stats['total_penjualan'], 0, ',', '.'); ?></strong><br>
                • Efisiensi operasional: <strong><?php echo number_format($stats['total_transaksi'] / max(1, $stats['total_kasir']), 1); ?> transaksi/kasir</strong><br>
                • Nilai transaksi rata-rata: <strong>Rp <?php echo number_format($stats['rata_rata'], 0, ',', '.'); ?></strong>
            </div>
            
            <div style="margin-top: 15px; font-size: 11px; line-height: 1.5;">
                <strong>🎯 REKOMENDASI STRATEGIS:</strong><br>
                1. <strong>Optimasi Stok</strong> - Fokus pada 5 produk terlaris yang memberikan kontribusi pendapatan tertinggi<br>
                2. <strong>Training Kasir</strong> - Tingkatkan skill kasir dengan performa terbaik untuk sharing best practice<br>
                3. <strong>Promosi Waktu Sepi</strong> - Manfaatkan data trend harian untuk program promo efektif<br>
                4. <strong>Bundle Produk</strong> - Gabungkan produk slow-moving dengan fast-moving untuk meningkatkan penjualan
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="signature-area">
            <div style="margin-bottom: 20px; font-size: 11px; color: #7f8c8d;">
                <strong>CATATAN:</strong> Laporan ini dihasilkan secara otomatis oleh Sistem Manajemen Penjualan Toko Aksesoris Berak.<br>
                Data bersifat real-time dan akurat berdasarkan transaksi yang tercatat dalam sistem.
            </div>
            
            <div class="signature-line">
                <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong><br>
                <em><?php echo ucfirst($_SESSION['user']['role']); ?> - Toko Aksesoris Berak</em><br>
                <span style="font-size: 10px; color: #7f8c8d;">Bertanggung jawab atas keakuratan data</span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #95a5a6;">
            Laporan ini bersifat rahasia dan hanya untuk penggunaan internal manajemen Toko Aksesoris Berak<br>
            Generated by Sales Analytics System © <?php echo date('Y'); ?> - Toko Aksesoris Berak
        </div>
    </div>

    <?php
    // Clean up
    $stmt->close();
    $stmtProduk->close();
    $stmtKasir->close();
    ?>

</body>
</html>