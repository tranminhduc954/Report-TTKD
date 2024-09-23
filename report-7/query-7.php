<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bắt đầu bộ đệm đầu ra
ob_start();

// Kiểm tra nếu form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ngay_bd']) && ($_POST['ngay_kt'])) {
       // Lấy dữ liệu từ form
       $ngay_bd = $_POST['ngay_bd'];
       $ngay_kt = $_POST['ngay_kt'];

       // Kết nối đến Oracle Database
       $connection_string = "(DESCRIPTION =
       (ADDRESS = (PROTOCOL = TCP)(HOST = 10.13.32.69)(PORT = 1521))
       (CONNECT_DATA =
       (SERVER = DEDICATED)
       (SERVICE_NAME = dhnvhbh)
       )
       )";
    
       $conn = oci_connect('ductm_hbh', 'a123', $connection_string);
        if (!$conn) {
            $e = oci_error();
            echo "Failed to connect to Oracle: " . $e['message'];
            exit;
        }

        // Câu SQL truy vấn
        $sql = "select 'PS0' loai, dv.ten_dv, ltb.loaihinh_tb,
                    hdkh.ngay_yc, hdtb.ngay_ht, hdtb.ma_tb, hdtb.tthd_id, hdtb.ten_tb, hdtb.diachi_tb, dt.doanh_thu,
                    nd.ma_nd user_lhd, nv.ten_nv nguoi_lhd, nv_ctv.ma_nv ma_nv_tu_van, nv_ctv.ten_nv nhan_vien_tu_van
                from css.hd_khachhang hdkh,
                    css.hd_thuebao hdtb,
                    css.hd_thanhtoan hdtt,
                    admin.v_nguoidung@dataguard nd,
                    admin.v_nhanvien@dataguard nv,
                    admin.v_nhanvien@dataguard nv_ctv,
                    (select x.ma_tb, sum(y.tien) doanh_thu
                    from css.hd_thuebao x, css.ct_phieu_tt y
                    where x.hdtb_id = y.hdtb_id and y.khoanmuctt_id <> 5
                    group by x.ma_tb) dt,
                    admin.v_donvi@dataguard dv,
                    css.loaihinh_tb@dataguard ltb
                where hdkh.hdkh_id = hdtb.hdkh_id
                    and hdtb.loaitb_id = ltb.loaitb_id
                    and hdkh.nguoi_cn = nd.ma_nd(+)
                    and nd.nhanvien_id = nv.nhanvien_id(+)
                    and hdkh.ctv_id = nv_ctv.nhanvien_id(+)
                    and hdtb.ma_tb = dt.ma_tb(+)
                    and hdkh.loaihd_id = 1
                    and decode(hdkh.nguoi_cn , 'tranminhduc.hbh', hdtt.donvi_id, nvl(nv_ctv.donvi_id, nv.donvi_id)) = dv.donvi_id
                    and hdtb.hdtt_id = hdtt.hdtt_id
                    and (hdtb.loaitb_id = 318 or (hdtb.loaitb_id = 288 and hdtb.mucuoctb_id = 22198))
                    and trunc(hdkh.ngay_yc) between TO_DATE(:ngay_bd, 'DD/MM/YYYY') and to_date(:ngay_kt, 'DD/MM/YYYY')";

        // Thực thi truy vấn
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':ngay_bd', $ngay_bd);
        oci_bind_by_name($stid, ':ngay_kt', $ngay_kt);
        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo "Error in query: " . $e['message'];
            exit;
        }

        // Kiểm tra hành động người dùng
        if (isset($_POST['action']) && $_POST['action'] == 'view') { // Chọn xem báo cáo trực tiếp
            // In báo cáo ra trình duyệt
            echo "<h2>Danh sách thuê bao SmartCA PS0 phát triển mới</h2>";
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
            echo '<form action="index-7.html" method="get">';
            echo '<button type="submit">Quay Lại</button>';
            echo '</form>';

            // Nút tải file
            echo '<form action="query-7.php" method="POST">';
            echo '<input type="hidden" name="ngay_bd" value="' . htmlspecialchars($ngay_bd) . '">';
            echo '<input type="hidden" name="ngay_kt" value="' . htmlspecialchars($ngay_kt) . '">';
            echo '<button type="submit" name="action" value="download">Tải Báo Cáo CSV</button>';
            echo '</form>';

            // In ra bảng
            echo "<table>";
            echo "<thead>
                    <tr>
                        <th>Loại</th>
                        <th>Tên đơn vị</th>
                        <th>Loại hình TB</th>
                        <th>Ngày yêu cầu</th>
                        <th>Ngày hoàn thiện</th>
                        <th>Mã thuê bao</th>
                        <th>Trạng thái ID HĐ</th>
                        <th>Tên thuê bao</th>
                        <th>Địa chỉ thuê bao</th>
                        <th>Doanh thu</th>
                        <th>User Admin</th>
                        <th>Tên Admin</th>
                        <th>Mã NVGT</th>
                        <th>Người phát triển</th>
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
            $filename = "bao_cao_smart_ps0" . date('Ymd') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            // Mở bộ đệm đầu ra như một "file" để ghi dữ liệu CSV
            $output = fopen('php://output', 'w');

            // Các dòng tiêu đề
            fputcsv($output, ['LOAI', 'TEN_DV', 'LOAIHINH_TB', 'NGAY_YC', 'NGAY_HT', 'MA_TB', 'TTHD_ID', 'TEN_TB', 'DIACHI_TB', 'DOANH_THU' , 'USER_LHD', 'NGUOI_LHD', 'MA_NV_TU_VAN', 'NHAN_VIEN_TU_VAN']);

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
