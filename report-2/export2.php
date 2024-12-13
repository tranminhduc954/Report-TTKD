<?php
    require '../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kết nối đến Oracle Database
        $ma_tb = $_POST['ma_tb'];
        $huong_dc = $_POST['huong_dc'];

        $host = "10.165.33.28";
        $port = "1521";
        $sid = "PDB_ONEBSS";
        $username = "DULIEU_HBH";
        $password = "OneBss_HBH_1591";

        $connection_string = "(DESCRIPTION =
        (ADDRESS = (PROTOCOL = TCP)(HOST = 10.165.33.28)(PORT = 1521))
        (CONNECT_DATA =
        (SERVER = DEDICATED)
        (SERVICE_NAME = PDB_ONEBSS)
        )
        )";

        $ma_tb = $_POST['ma_tb'];
        $huong_dc = $_POST['huong_dc'];

        $conn = oci_connect($username, $password, $connection_string);
        if (!$conn) {
            $e = oci_error();
            echo "Failed to connect to Oracle: " . $e['message'];
            exit;
        }
    
        $ma_tb = $_POST['ma_tb'];
        $huong_dc = $_POST['huong_dc'];
        $sql = "
        WITH chi_tiet AS (
            SELECT dbtb.thuebao_id, dbtb.ma_tb, td.thuonghieu, goi.goi_id, goi.ten_goi, 
                   nhom.ten_nhom nhom_datcoc, km.khuyenmai_id, km.ten_km, 
                   ctkm.chitietkm_id, ctkm.ten_ctkm, ctkm.huong_dc so_thang_dtc, 
                   ctkm.datcoc_csd, ctkm.tien_td, ctkm.nguoi_cn, ctkm.ngay_cn
            FROM css.v_khuyenmai km,
                 css.v_ct_khuyenmai ctkm,
                 css.v_ctkm_lhtb ctkm_lhtb,
                 css.v_db_thuebao dbtb,
                 css.v_db_adsl adsl,
                 css.tocdo_adsl td,
                 css.v_ctkm_goi_dadv km_goi,
                 css.v_goi_dadv goi,
                 css.nhom_datcoc nhom
            WHERE km.ngay_kt > sysdate
                  AND km.khuyenmai_id = ctkm.khuyenmai_id
                  AND ctkm.chitietkm_id = ctkm_lhtb.chitietkm_id
                  AND ctkm_lhtb.tocdo_id = adsl.tocdo_id
                  AND ctkm.nhom_datcoc_id = nhom.nhom_datcoc_id
                  AND adsl.thuebao_id = dbtb.thuebao_id
                  AND td.tocdo_id = adsl.tocdo_id
                  AND adsl.muccuoc_id = ctkm_lhtb.muccuoc_id
                  AND ctkm.chitietkm_id = km_goi.chitietkm_id(+)
                  AND km_goi.goi_id = goi.goi_id(+)
                  AND ctkm.tien_td <> 0
                  AND dbtb.ma_tb = :p_ma_tb
                  AND ctkm.huong_dc = :p_huong_dc
        )
        SELECT * FROM (
            SELECT ct.* FROM chi_tiet ct, css.v_bd_goi_dadv goi
            WHERE ct.goi_id = goi.goi_id AND trangthai = 1 AND ct.thuebao_id = goi.thuebao_id
            UNION SELECT * FROM chi_tiet WHERE goi_id IS NULL
        )
        ORDER BY goi_id, nhom_datcoc, ten_km, so_thang_dtc";
    
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':p_ma_tb', $ma_tb);
        oci_bind_by_name($stid, ':p_huong_dc', $huong_dc);
        oci_execute($stid);
    
        if ($_POST['action'] === 'export') {
            // Tạo mới đối tượng Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
    
            // Đặt tiêu đề cột
            $headers = ['Thuebao ID', 'Mã TB', 'Thương Hiệu', 'Gói ID', 'Tên Gói', 'Nhóm Đặt Cọc', 'Khuyến Mãi ID', 'Tên KM', 'Chi Tiết KM ID', 'Tên Chi Tiết KM', 'Số Tháng Đặt Cọc', 'Đặt Cọc CSD', 'Tiền TĐ', 'Người CN', 'Ngày CN'];
            $sheet->fromArray($headers, NULL, 'A1');
    
            // Đổ dữ liệu vào Excel
            $rowIndex = 2; // Bắt đầu từ hàng thứ 2 để dưới tiêu đề
            while (($row = oci_fetch_array($stid, OCI_ASSOC)) != false) {
                $sheet->fromArray(array_values($row), NULL, 'A' . $rowIndex);
                $rowIndex++;
            }
    
            // Tạo file Excel và gửi về cho người dùng
            $writer = new Xlsx($spreadsheet);
            $fileName = 'BaoCao_' . date('Y-m-d_H-i-s') . '.xlsx';
    
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $writer->save('php://output');
            exit;
        } else {
            // Xuất kết quả ra HTML
            echo "<style>
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
                        padding: 12px;
                        border-bottom: 1px solid #ddd;
                    }
                    th {
                        background-color: #f4f4f4;
                        font-weight: bold;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                </style>";
    
            echo "<h2 style='text-align: center;'>Kết quả Báo Cáo:</h2>";
            echo "<table>
                    <tr>
                        <th>Thuebao ID</th>
                        <th>Mã TB</th>
                        <th>Thương Hiệu</th>
                        <th>Gói ID</th>
                        <th>Tên Gói</th>
                        <th>Nhóm Đặt Cọc</th>
                        <th>Khuyến Mãi ID</th>
                        <th>Tên KM</th>
                        <th>Chi Tiết KM ID</th>
                        <th>Tên Chi Tiết KM</th>
                        <th>Số Tháng Đặt Cọc</th>
                        <th>Đặt Cọc CSD</th>
                        <th>Tiền TĐ</th>
                        <th>Người CN</th>
                        <th>Ngày CN</th>
                    </tr>";
    
            while (($row = oci_fetch_array($stid, OCI_ASSOC)) != false) {
                echo "<tr>";
                foreach ($row as $item) {
                    echo "<td>" . htmlspecialchars($item, ENT_QUOTES) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
    
            echo "<br><a href='./index-1.html' style='display: inline-block; margin: 20px 0; color: #3498db; text-decoration: none;'>Quay lại phần tìm kiếm</a>";
            echo "<br><a href='../index.html' style='display: inline-block; margin: 20px 0; color: #3498db; text-decoration: none;'>Quay lại trang chủ</a>";
        }
    
        // Đóng kết nối
        oci_free_statement($stid);
        oci_close($conn);
    }
?>