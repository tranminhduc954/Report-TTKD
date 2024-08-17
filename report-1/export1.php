<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kết nối đến Oracle Database
    $ma_tb = $_POST['ma_tb'];

    $host = "10.165.33.28";
    $port = "1521";
    $sid = "PDB_ONEBSS";
    $username = "DULIEU_HBH";
    $password = "OneBss_HBH_1591";

    $connection_string = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
    (CONNECT_DATA =
    (SERVER = DEDICATED)
    (SERVICE_NAME = $sid)
    )
    )";

    $conn = oci_connect($username, $password, $connection_string);
    if (!$conn) {
        $e = oci_error();
        echo "Failed to connect to Oracle: " . $e['message'];
        exit;
    }

    $sql = "
        SELECT x.ma_tb, x.cuoc_dc, x.ngay_dk, y.khuyenmai_id, y.chitietkm_id, y.ten_ctkm
        FROM (
            SELECT * FROM css.v_db_datcoc
            WHERE ma_tb = :ma_tb
        ) x
        LEFT JOIN (
            SELECT chitietkm_id, khuyenmai_id, ten_ctkm FROM css.v_ct_khuyenmai
        ) y ON x.chitietkm_id = y.chitietkm_id
    ";

    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':ma_tb', $ma_tb); // Thay thế :p_ma_tb bằng :ma_tb
    oci_execute($stid);

    if ($_POST['action'] === 'export') {
        // Tạo mới đối tượng Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Đặt tiêu đề cột
        $headers = ['Mã thuê bao', 'Cước đặt cọc', 'Ngày đăng ký', 'ID khuyến mại', 'ID chi tiết km', 'Tên chi tiết km'];
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
        // In ra kết quả trong HTML
        while (($row = oci_fetch_array($stid, OCI_ASSOC)) != false) {
            echo "<tr>";
            foreach ($row as $item) {
                echo "<td>" . htmlspecialchars($item, ENT_QUOTES) . "</td>";
            }
            echo "</tr>";
        }
    }

    // Đóng kết nối
    oci_free_statement($stid);
    oci_close($conn);
}
?>
