<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bắt đầu bộ đệm đầu ra
ob_start();

// Kiểm tra nếu form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ngay_bd'], $_POST['ngay_kt'], $_POST['don_vi'])) {
       // Lấy dữ liệu từ form
       $ngay_bd = $_POST['ngay_bd'];
       $ngay_kt = $_POST['ngay_kt'];
       $don_vi = $_POST['don_vi'];

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
        $sql = "select case when hdkh.nguoi_cn = 'tranminhduc.hbh' and (hdtb.loaitb_id = 318 or (hdtb.loaitb_id = 288 and hdtb.mucuoctb_id = 22198)) then 'PS0' 
                            when lower(mc.MUCCUOC) like '%edu%' then 'Giáo dục'
                            when lower(mc.MUCCUOC) like '%y tế%' then 'Y tế'
                            when lower(mc.MUCCUOC) like '%nhân viên%' then 'Nhân viên'
                    else 'Trọn gói khác' end loai, 
                    case when hdkh.nguoi_cn = 'tranminhduc.hbh' and (hdtb.loaitb_id = 318 or (hdtb.loaitb_id = 288 and hdtb.mucuoctb_id = 22198)) 
                        then 'KH đăng ký qua SmartCA' else hdkh.nguoi_cn end user_lhd,
                    case when hdkh.nguoi_cn = 'tranminhduc.hbh' and (hdtb.loaitb_id = 318 or (hdtb.loaitb_id = 288 and hdtb.mucuoctb_id = 22198)) 
                        then 'KH đăng ký qua SmartCA' else nv.ten_nv end nguoi_lhd,
                    nv_ctv.ma_nv ma_nv_tu_van, nv_ctv.ten_nv nhan_vien_tu_van,
                    dv.TEN_DV donvi_lhd,  dvql.TEN_DV donvi_ql,
                    ltb.loaitb_id, ltb.loaihinh_tb, mc.MUCUOCTB_ID, mc.MUCCUOC,
                    hdkh.ngay_yc, hdtb.ma_tb, hdtb.ngay_ht, tthd.trangthai_hd, 
                    hdkh.SO_GT, hdtb.ten_tb, hdtb.diachi_tb
                from css.v_hd_khachhang hdkh,
                    css.v_hd_thuebao hdtb,
                    css.v_hd_thanhtoan hdtt,
                    admin.v_nguoidung nd,
                    admin.v_nhanvien nv,
                    admin.v_nhanvien nv_ctv,
                    admin.v_donvi dv,
                    admin.v_donvi dvql,
                    css.loaihinh_tb ltb,
                    css.v_muccuoc_tb mc,
                    css.trangthai_hd tthd
                where hdkh.hdkh_id = hdtb.hdkh_id 
                    and hdtb.loaitb_id = ltb.loaitb_id
                    and hdtb.MUCUOCTB_ID = mc.MUCUOCTB_ID
                    and hdtb.TTHD_ID = tthd.tthd_id
                    and hdkh.nguoi_cn = nd.ma_nd(+)
                    and nd.nhanvien_id = nv.nhanvien_id(+)
                    and hdkh.ctv_id = nv_ctv.nhanvien_id(+)
                    and hdkh.loaihd_id = 1
                    and hdtt.DONVI_ID = dvql.DONVI_ID
                    and nv_ctv.DONVI_ID = dv.DONVI_ID(+)
                    and hdtb.hdtt_id = hdtt.hdtt_id
                    and hdtb.loaitb_id in (318, 288)
                    and hdkh.ngay_yc between to_date(:ngay_bd, 'dd/mm/rrrr') and to_date(:ngay_kt || ' 23:59:50', 'dd/mm/rrrr hh24:mi:ss')
                    and (decode(:don_vi, 0, 0, nv_ctv.DONVI_ID) = :don_vi or decode(:don_vi, 0, 0, hdtt.DONVI_ID) = :don_vi)--311290
                order by dvql.TEN_DV, dv.TEN_DV";

        // Thêm điều kiện đơn vị
        // if (!empty($don_vi)) {
        //     $sql .= " and dv.donvi_id = :don_vi";
        // }

        // Thực thi truy vấn
        $stid = oci_parse($conn, $sql);

        // Gán tham số vào truy vấn
        oci_bind_by_name($stid, ':ngay_bd', $ngay_bd);
        oci_bind_by_name($stid, ':ngay_kt', $ngay_kt);
        oci_bind_by_name($stid, ':don_vi', $don_vi);
        // if (!empty($don_vi)) {
        //     oci_bind_by_name($stid, ':don_vi', $don_vi);
        // }

        // Trả kết quả truy vấn
        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo "Error in query: " . $e['message'];
            exit;
        }

        // Kiểm tra hành động người dùng
        if (isset($_POST['action']) && $_POST['action'] == 'view') { // Chọn xem báo cáo trực tiếp
            // In báo cáo ra trình duyệt
            echo "<h2>Danh sách thuê bao SmartCA phát triển mới</h2>";
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
                        <th>User Lập hợp đồng</th>
                        <th>Người lập hợp đồng</th>
                        <th>Mã Nhân viên TV</th>
                        <th>Nhân viên tư vấn</th>
                        <th>Đơn vị LHĐ</th>
                        <th>Đơn vị quản lý</th>
                        <th>Loại thuê bao ID</th>
                        <th>Loại hình TB</th>
                        <th>Mức cước thuê bao ID</th>
                        <th>Mức cước</th>
                        <th>Ngày bắt đầu</th>
                        <th>Mã thuê bao</th>
                        <th>Ngày hoàn thiện</th>
                        <th>Trạng thái HĐ</th>
                        <th>Số GT</th>
                        <th>Tên thuê bao</th>
                        <th>Địa chỉ thuê bao</th>
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

        } elseif (isset($_POST['action']) && $_POST['action'] == 'download') { // Hoặc chọn tải
            // Đặt tên file CSV tải xuống
            $filename = "bao_cao_smart_ps0" . date('Ymd') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            // Mở bộ đệm đầu ra như một "file" để ghi dữ liệu CSV
            $output = fopen('php://output', 'w');

            // Các dòng tiêu đề
            fputcsv($output, ['LOAI', 'USER_LHD', 'NGUOI_LHD', 'MA_NVGT', 'NHANVIEN_GT', 'DONVI_LHD', 'DONVI_QL', 
            'LOAITB_ID', 'LOAIHINH_TB', 'MUCCUOCTB_ID', 'MUCCUOC' ,'NGAY_YC', 'MA_TB', 'NGAY_HT', 
            'TRANGTHAI_HD', 'SO_GT', 'TEN_TB', 'DIACHI_TB']);

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