<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bắt đầu bộ đệm đầu ra
ob_start();

// Kiểm tra nếu form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nguoi_cn'])) {
       // Lấy dữ liệu từ form
       $nguoi_cn = $_POST['nguoi_cn'];

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
        $sql = "select ltb.loaihinh_tb, dvn.ten_dv donvi_lhd, dv.ten_dv, b.nguoi_cn nguoi_lhd, c.ten_loaihd, 
                d.ten_kieuld, b.ngay_yc ngay_lhd, e.trangthai_hd, b.ma_gd, a.ma_tb, a.ten_tb, a.diachi_ld
                from css.v_hd_thuebao a, css.v_hd_khachhang b, css.loai_hd c, css.kieu_ld d, 
                css.trangthai_hd e, admin.v_donvi dv, admin.v_donvi dvn, css.loaihinh_tb ltb
                where a.hdkh_id = b.hdkh_id and a.tthd_id not  in (6,7) and b.nguoi_cn <> 'admin' 
                and a.donvi_id = dvn.donvi_id(+) and a.kieuld_id = d.kieuld_id 
                and b.loaihd_id = c.loaihd_id and a.tthd_id = e.tthd_id 
                and b.donvi_id = dv.donvi_id and a.loaitb_id = ltb.loaitb_id
                and b.nguoi_cn = :nguoi_cn
                order by dvn.ten_dv, dv.ten_dv, b.nguoi_cn, c.ten_loaihd, d.ten_kieuld, b.ngay_yc";

        // Thực thi truy vấn
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':nguoi_cn', $nguoi_cn);
        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo "Error in query: " . $e['message'];
            exit;
        }

        // Kiểm tra hành động người dùng
        if (isset($_POST['action']) && $_POST['action'] == 'view') { // Chọn xem báo cáo trực tiếp
            // In báo cáo ra trình duyệt
            echo "<h2>Danh sách các hợp đồng chưa hoàn thành</h2>";
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
                    
                    th,
                    td {
                        padding: 12px 15px;
                        border: 1px solid #ddd;
                    }
                    
                    th {
                        background-color: #f4f4f4;
                    }
                </style>";
            
            // Quay lại phần tìm kiếm
            echo '<form action="index-6.html" method="get">';
            echo '<button type="submit">Quay Lại</button>';
            echo '</form>';

            // Nút tải file
            echo '<form action="query-6.php" method="POST">';
            echo '<input type="hidden" name="nguoi_cn" value="' . htmlspecialchars($nguoi_cn) . '">';
            echo '<button type="submit" name="action" value="download">Tải Báo Cáo CSV</button>';
            echo '</form>';

            // In ra bảng
            echo "<table>";
            echo "<thead>
                    <tr>
                        <th>LOAIHINH_TB</th>
                        <th>DONVI_LHD</th>
                        <th>TEN_DV</th>
                        <th>NGUOI_LHD</th>
                        <th>TEN_LOAIHD</th>
                        <th>TEN_KIEULD</th>
                        <th>NGAY_LHD</th>
                        <th>TRANGTHAI_HD</th>
                        <th>MA_GD</th>
                        <th>MA_TB</th>
                        <th>TEN_TB</th>
                        <th>DIACHI_LD</th>
                    </tr>
                </thead>";
            echo "<tbody>";
            while ($row = oci_fetch_assoc($stid)) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";

        } elseif ($_POST['action'] == 'download') { // Hoặc chọn tải
            // Đặt tên file CSV tải xuống
            $filename = "bao_cao_hop_dong_lam_do" . date('Ymd') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            // Mở bộ đệm đầu ra như một "file" để ghi dữ liệu CSV
            $output = fopen('php://output', 'w');

            // Các dòng tiêu đề
            fputcsv($output, ['LOAIHINH_TB', 'DONVI_LHD', 'TEN_DV', 'NGUOI_LHD', 'TEN_LOAIHD', 
            'TEN_KIEULD', 'NGAY_LHD', 'TRANGTHAI_HD', 'MA_GD', 'MA_TB', 'TEN_TB', 'DIACHI_LD']);

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
}

?>
