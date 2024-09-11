<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bắt đầu bộ đệm đầu ra
ob_start();

// Kiểm tra nếu form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tu_ngay']) && isset($_POST['den_ngay'])) {
        // Lấy dữ liệu từ form
        $tu_ngay = $_POST['tu_ngay'];
        $den_ngay = $_POST['den_ngay'];

        // Kết nối đến Oracle Database
        $connection_string = "(DESCRIPTION =
        (ADDRESS = (PROTOCOL = TCP)(HOST = 10.165.33.28)(PORT = 1521))
        (CONNECT_DATA =
        (SERVER = DEDICATED)
        (SERVICE_NAME = PDB_ONEBSS)
        )
        )";

        $conn = oci_connect('DULIEU_HBH', 'OneBss_HBH_1591', $connection_string);
        if (!$conn) {
            $e = oci_error();
            echo "Failed to connect to Oracle: " . $e['message'];
            exit;
        }

        // Câu SQL truy vấn
        $sql = "SELECT dc.ma_tb, dc.cuoc_dc, dc.ngay_dk ngay_dtc, km.huong_km so_thang, dc.thang_bd, dc.thang_kt, dc.chitietkm_id, 
                       hdc.HDTB_ID, hd.hdkh_id, kh.nhanvien_id, kh.ctv_id, nv.ten_nv, nv.ma_nv, nv.diachi_nv donvi
                FROM css.v_db_datcoc dc, css.v_hdtb_datcoc hdc, css.v_hd_thuebao hd, css.v_hd_khachhang kh, 
                     admin.v_nhanvien nv, css.v_ct_khuyenmai km
                WHERE dc.rkm_id = hdc.rkm_id 
                AND hdc.hdtb_id = hd.hdtb_id
                AND hd.hdkh_id = kh.hdkh_id
                AND dc.chitietkm_id = km.chitietkm_id
                AND NVL(kh.ctv_id, kh.nhanviengt_id) = nv.nhanvien_id(+)
                AND dc.ngay_cn BETWEEN TO_DATE(:tu_ngay, 'DD/MM/YYYY') 
                AND TO_DATE(:den_ngay, 'DD/MM/YYYY')";

        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':tu_ngay', $tu_ngay);
        oci_bind_by_name($stid, ':den_ngay', $den_ngay);
        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo "Error in query: " . $e['message'];
            exit;
        }

        // Tạo tệp CSV và ghi dữ liệu
        $filename = "bao_cao_dat_coc_" . date('Ymd') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Mở bộ đệm đầu ra như một "file" để ghi dữ liệu CSV
        $output = fopen('php://output', 'w');

        // Ghi dòng tiêu đề
        fputcsv($output, ['Ma TB', 'Cuoc DC', 'Ngay DTC', 'So Thang', 'Thang BD', 'Thang KT', 'Chitiet KM ID', 
                          'HDTB ID', 'HDKH ID', 'Nhanvien ID', 'CTV ID', 'Ten NV', 'Ma NV', 'Donvi']);

        // Ghi các dòng dữ liệu từ truy vấn
        while ($row = oci_fetch_assoc($stid)) {
            fputcsv($output, $row);
        }

        // Đóng file CSV
        fclose($output);

        // Kết thúc script để đảm bảo không xuất thêm HTML nào nữa
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo Đặt Cọc</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            color: #333;
            font-family: Arial, sans-serif;
            font-size: 14px;
            text-align: left;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: auto;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <h1>Nhập Thông Tin Báo Cáo</h1>
    <form action="index-4.php" method="POST">
        <label for="tu_ngay">Từ Ngày (dd/mm/yyyy):</label>
        <input type="text" id="tu_ngay" name="tu_ngay" required>
        <br><br>
        <label for="den_ngay">Đến Ngày (dd/mm/yyyy):</label>
        <input type="text" id="den_ngay" name="den_ngay" required>
        <br><br>
        <button type="submit">Xem Báo Cáo</button>
    </form>
</body>
</html>