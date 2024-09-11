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
        <button type="submit" name="export_excel" value="1">Xuất ra Excel</button>
    </form>

    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    // Thêm autoload của Composer
    require '../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    // Bắt đầu bộ đệm đầu ra để tránh lỗi
    ob_start();

    // Kiểm tra nếu form được gửi
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kiểm tra xem biến tu_ngay và den_ngay đã được gửi hay chưa
        if (isset($_POST['tu_ngay']) && isset($_POST['den_ngay'])) {
            // Lấy dữ liệu từ form
            $tu_ngay = $_POST['tu_ngay'];
            $den_ngay = $_POST['den_ngay'];

            // Kết nối đến Oracle Database
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

            // Kết nối đến Oracle
            $conn = oci_connect($username, $password, $connection_string);
            if (!$conn) {
                $e = oci_error();
                echo "Failed to connect to Oracle: " . $e['message'];
                exit;
            }

            // Câu SQL truy vấn
            $sql = "
                SELECT dc.ma_tb, dc.cuoc_dc, dc.ngay_dk ngay_dtc, km.huong_km so_thang, dc.thang_bd, dc.thang_kt, dc.chitietkm_id, 
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
                30882,30770,30760,35123,35122,35129,32638,32730,32732)
            ";

            // Chuẩn bị câu lệnh
            $stid = oci_parse($conn, $sql);

            // Gán giá trị cho các biến
            oci_bind_by_name($stid, ':tu_ngay', $tu_ngay);
            oci_bind_by_name($stid, ':den_ngay', $den_ngay);

            // Thực thi truy vấn
            $result = oci_execute($stid);

            if (!$result) {
                $e = oci_error($stid);
                echo "Error in query: " . $e['message'];
                exit;
            }

            // function validateDate($date, $format = 'd/m/Y') {
            //     $d = DateTime::createFromFormat($format, $date);
            //     return $d && $d->format($format) === $date;
            // }

            // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //     if (isset($_POST['tu_ngay']) && isset($_POST['den_ngay'])) {
            //         $tu_ngay = $_POST['tu_ngay'];
            //         $den_ngay = $_POST['den_ngay'];
            
            //         // Kiểm tra định dạng ngày
            //         if (!validateDate($tu_ngay) || !validateDate($den_ngay)) {
            //             echo "Vui lòng nhập ngày đúng định dạng (dd/mm/yyyy).";
            //             exit;
            //         }
            //     }
            // }

            // Kiểm tra nếu người dùng muốn xuất Excel
            if (isset($_POST['export_excel']) && $_POST['export_excel'] == 1) {
                // Tạo file Excel mới
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
            
                // Đặt tiêu đề cho các cột
                $headers = ['Mã thuê bao', 'Cước đặt cọc', 'Ngày đặt cọc', 'Số Tháng', 'Tháng Bắt Đầu', 'Tháng Kết Thúc', 'Chi tiết KM', 'HDTB ID', 'HDKH ID', 'Nhân Viên ID', 'CTV ID', 'Tên Nhân Viên', 'Mã Nhân Viên', 'Đơn Vị'];
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
            
                ob_end_clean(); // Làm sạch bộ đệm đầu ra
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                
                // Ghi file vào php://output
                $writer->save('php://output');
                exit; // Ngăn không cho bất kỳ mã nào khác được thực thi
            }
             else {
                echo "<h2>Kết quả Báo Cáo:</h2>";
                echo "<table>
                        <tr>
                            <th>Mã thuê bao</th>
                            <th>Cước đặt cọc</th>
                            <th>Ngày Đặt Cọc</th>
                            <th>Số Tháng</th>
                            <th>Tháng Bắt Đầu</th>
                            <th>Tháng Kết Thúc</th>
                            <th>Chi tiết KM ID</th>
                            <th>HDTB ID</th>
                            <th>HDKH ID</th>
                            <th>Nhân Viên ID</th>
                            <th>CTV ID</th>
                            <th>Tên Nhân Viên</th>
                            <th>Mã Nhân Viên</th>
                            <th>Đơn Vị</th>
                        </tr>";

                while (($row = oci_fetch_array($stid, OCI_ASSOC)) != false) {
                    echo "<tr>";
                    foreach ($row as $item) {
                        echo "<td>" . htmlspecialchars($item, ENT_QUOTES) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                

            }
            // Đóng kết nối
            oci_free_statement($stid);
            oci_close($conn);
        } else {
            echo "Vui lòng nhập ngày!";
        }
    }
    echo "<br><a href='../index.html' style='display: inline-block; margin: 20px 0; color: #3498db; text-decoration: none;'>Quay lại trang chủ</a>";
    ?>

</body>
</html>