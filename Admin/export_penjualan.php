<?php
session_start();

// Cek session dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Include koneksi database dengan path yang benar
include_once '../config/koneksi.php';

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_penjualan_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Query to get penjualan data
    $query = "SELECT p.*, u.username 
              FROM penjualan p 
              LEFT JOIN user u ON p.id_user = u.id_user 
              WHERE DATE(p.tanggal) BETWEEN ? AND ? 
              ORDER BY p.tanggal DESC";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Query to get statistics
    $statsQuery = "SELECT 
                    COUNT(*) as total_transaksi,
                    COALESCE(SUM(total), 0) as total_penjualan,
                    AVG(total) as rata_rata
                   FROM penjualan 
                   WHERE DATE(tanggal) BETWEEN ? AND ?";
    $statsStmt = $koneksi->prepare($statsQuery);
    $statsStmt->bind_param("ss", $start_date, $end_date);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $statsStmt->close();

    // Query to get top products
    $produkQuery = "SELECT pr.nama_produk, SUM(dp.jumlah) as total_terjual, SUM(dp.subtotal) as total_pendapatan
                    FROM detail_penjualan dp 
                    JOIN produk pr ON dp.id_produk = pr.id_produk 
                    JOIN penjualan p ON dp.id_penjualan = p.id_penjualan 
                    WHERE DATE(p.tanggal) BETWEEN ? AND ? 
                    GROUP BY dp.id_produk 
                    ORDER BY total_terjual DESC 
                    LIMIT 10";
    $produkStmt = $koneksi->prepare($produkQuery);
    $produkStmt->bind_param("ss", $start_date, $end_date);
    $produkStmt->execute();
    $produkResult = $produkStmt->get_result();

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
                    <x:Name>Laporan Penjualan</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
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
            mso-number-format: "Rp\ #,##0";
        }
        .date {
            mso-number-format: "dd\/mm\/yyyy hh:mm";
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #7f8c8d;
            margin: 5px 0;
            font-size: 14px;
        }
        .summary {
            background: #ecf0f1;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #bdc3c7;
        }
        .summary h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
            text-align: center;
        }
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .summary-row {
            display: table-row;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 15px;
            background: white;
            border: 1px solid #bdc3c7;
            vertical-align: middle;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            display: block;
        }
        .summary-item .label {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
        }
        th {
            background-color: #34495e;
            color: white;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #2c3e50;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border: 1px solid #bdc3c7;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
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
            background-color: #d4edda !important;
            font-weight: bold;
            color: #155724;
        }
        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            margin: 25px 0 10px 0;
            font-weight: bold;
            font-size: 14px;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #34495e;
        }
        .signature {
            text-align: center;
            margin-top: 50px;
        }
        .company-info {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- Company Information -->
    <div class="company-info">
        <table width="100%">
            <tr>
                <td width="70%" class="text-left">
                    <h2 style="margin:0; color:#2c3e50;">TOKO AKSESORIS BERAK</h2>
                    <p style="margin:5px 0; color:#7f8c8d;">Jl. Contoh Alamat No. 123, Kota Berak</p>
                    <p style="margin:5px 0; color:#7f8c8d;">Telp: (021) 123-4567 | Email: info@berakaksesoris.com</p>
                </td>
                <td width="30%" class="text-right">
                    <div style="border:1px solid #bdc3c7; padding:10px; display:inline-block;">
                        <strong>LAPORAN PENJUALAN</strong><br>
                        Periode: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?><br>
                        Dicetak: <?php echo date('d/m/Y H:i:s'); ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Statistics -->
    <div class="summary">
        <h3>RINGKASAN PENJUALAN</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <span class="value"><?php echo number_format($stats['total_transaksi']); ?></span>
                    <span class="label">TOTAL TRANSAKSI</span>
                </div>
                <div class="summary-item">
                    <span class="value currency">Rp <?php echo number_format($stats['total_penjualan'], 0, ',', '.'); ?></span>
                    <span class="label">TOTAL PENJUALAN</span>
                </div>
                <div class="summary-item">
                    <span class="value currency">Rp <?php echo number_format($stats['rata_rata'], 0, ',', '.'); ?></span>
                    <span class="label">RATA-RATA PER TRANSAKSI</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Data -->
    <div class="section-title">DATA TRANSAKSI PENJUALAN</div>
    <table cellpadding="0" cellspacing="0" border="0">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">ID Transaksi</th>
                <th width="20%">Tanggal & Waktu</th>
                <th width="15%">Kasir</th>
                <th width="15%">Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $grand_total += $row['total'];
                    ?>
                    <tr>
                        <td class="text-center number"><?php echo $no++; ?></td>
                        <td class="text">#<?php echo str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td class="date"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                        <td class="text"><?php echo $row['username'] ?? 'Tidak diketahui'; ?></td>
                        <td class="text-right currency">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data transaksi</td>
                </tr>
                <?php
            }
            ?>
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>GRAND TOTAL</strong></td>
                <td class="text-right"><strong>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Top Products -->
    <div class="section-title">10 PRODUK TERLARIS</div>
    <table cellpadding="0" cellspacing="0" border="0">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Nama Produk</th>
                <th width="25%">Total Terjual</th>
                <th width="25%">Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no_produk = 1;
            if ($produkResult->num_rows > 0) {
                while ($produk = $produkResult->fetch_assoc()) {
                    ?>
                    <tr>
                        <td class="text-center number"><?php echo $no_produk++; ?></td>
                        <td class="text"><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
                        <td class="text-center number"><?php echo number_format($produk['total_terjual']); ?> pcs</td>
                        <td class="text-right currency">Rp <?php echo number_format($produk['total_pendapatan'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="4" class="text-center">Tidak ada data produk</td>
                </tr>
                <?php
            }
            $produkStmt->close();
            ?>
        </tbody>
    </table>

    <!-- Footer & Signature -->
    <div class="footer">
        <table>
            <tr>
                <td width="60%">
                    <div style="background:#f8f9fa; padding:15px; border:1px solid #dee2e6; border-radius:5px;">
                        <strong>Keterangan:</strong><br>
                        - Laporan ini dibuat secara otomatis oleh sistem<br>
                        - Periode laporan: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?><br>
                        - Total transaksi: <?php echo number_format($stats['total_transaksi']); ?> transaksi
                    </div>
                </td>
                <td width="40%">
                    <div class="signature">
                        <p>Mengetahui,</p>
                        <br><br><br>
                        <p style="border-top:1px solid #000; width:200px; margin:0 auto; padding-top:10px;">
                            <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong><br>
                            (<?php echo ucfirst($_SESSION['user']['role']); ?>)
                        </p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <?php
    $stmt->close();
    ?>

</body>
</html>