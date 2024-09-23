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
                AND TO_DATE(:den_ngay, 'DD/MM/YYYY')
                AND dc.chitietkm_id IN (13827,34330,34314,34299,34299,13177,32730,30893,
                30772,34299,30882,32732,32730,30770,30893,30772,30772,30893,30770,30882,
                32730,32732,35410,15162,15173,32730,32732,30772,30893,35129,35122,30893,
                30772,30682,32650,15162,15173,30693,30893,30772,34299,30882,30770,34314,
                34330,32730,32732,32731,32730,32732,32730,34299,15162,15173,30882,38720,
                34330,34314,30882,33820,38719,15162,33820,30882,15173,15162,30772,30893,
                32650,38713,15185,15165,15162,15173,32635,32650,35129,35122,34334,34313,
                34310,35116,35123,34322,15162,15173,38719,35129,35122,32730,32732,15162,
                15173,30770,30882,38721,35122,35129,15162,15173,35123,35116,15162,34299,
                35134,35116,32732,32730,30882,30770,30882,33820,35432,35120,35123,35116,
                30882,30770,30760,35123,35122,35129,32638,32730,32732)";

        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':tu_ngay', $tu_ngay);
        oci_bind_by_name($stid, ':den_ngay', $den_ngay);
        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo "Error in query: " . $e['message'];
            exit;
        }

        // Kiểm tra hành động người dùng chọn
        if ($_POST['action'] === 'view') {
            // Hiển thị báo cáo trên trình duyệt
            echo "<h2>Kết Quả Báo Cáo</h2>";
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
            echo '<form action="index.html" method="get">';
            echo '<button type="submit">Quay Lại</button>';
            echo '</form>';

            // Nút tải file
            echo '<form action="index-5.php" method="POST">';
            echo '<input type="hidden" name="tu_ngay" value="' . htmlspecialchars($tu_ngay) . '">';
            echo '<input type="hidden" name="den_ngay" value="' . htmlspecialchars($den_ngay) . '">';
            echo '<button type="submit" name="action" value="download">Tải Báo Cáo CSV</button>';
            echo '</form>';

            echo "<table>";
            echo "<thead>
                    <tr>
                        <th>Ma TB</th>
                        <th>Cuoc DC</th>
                        <th>Ngay DTC</th>
                        <th>So Thang</th>
                        <th>Thang BD</th>
                        <th>Thang KT</th>
                        <th>Chitiet KM ID</th>
                        <th>HDTB ID</th>
                        <th>HDKH ID</th>
                        <th>Nhanvien ID</th>
                        <th>CTV ID</th>
                        <th>Ten NV</th>
                        <th>Ma NV</th>
                        <th>Donvi</th>
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
        } elseif ($_POST['action'] === 'download') {
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
}
?>