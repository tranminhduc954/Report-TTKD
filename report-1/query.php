<?php
$host = "10.165.33.28";
$port = "1521";
$sid = "PDB_ONEBSS";
$username = "DULIEU_HBH";
$password = "OneBss_HBH_1591";

// Xây dựng chuỗi kết nối
$connection_string = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = 10.165.33.28)(PORT = 1521))
    (CONNECT_DATA =
      (SERVER = DEDICATED)
      (SERVICE_NAME = PDB_ONEBSS)
    )
  )";

$ma_tb = $_POST['ma_tb'];

$conn = oci_connect($username, $password, $connection_string);

if (!$conn) {
    $e = oci_error();
    echo "Failed to connect to Oracle: " . $e['message'];
    exit;
}

$query = "
    SELECT x.ma_tb, x.cuoc_dc, x.ngay_dk, y.khuyenmai_id, y.chitietkm_id, y.ten_ctkm
    FROM (
        SELECT * FROM css.v_db_datcoc
        WHERE ma_tb = :ma_tb
    ) x
    LEFT JOIN (
        SELECT chitietkm_id, khuyenmai_id, ten_ctkm FROM css.v_ct_khuyenmai
    ) y ON x.chitietkm_id = y.chitietkm_id
";

$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ":ma_tb", $ma_tb);
oci_execute($stid);

$results = [];
while ($row = oci_fetch_assoc($stid)) {
    $results[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử đặt cọc</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <h1>Lịch sử đặt cọc của thuê bao: <?php echo htmlspecialchars($ma_tb); ?></h1>
    <table border="1px">
        <thead>
            <tr>
                <th>Mã thuê bao</th>
                <th>Cước đặt cọc</th>
                <th>Ngày đăng ký</th>
                <th>ID khuyến mại</th>
                <th>ID chi tiết km</th>
                <th>Tên chi tiết km</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['MA_TB']); ?></td>
                    <td><?php echo htmlspecialchars($row['CUOC_DC']); ?></td>
                    <td><?php echo htmlspecialchars($row['NGAY_DK']); ?></td>
                    <td><?php echo htmlspecialchars($row['KHUYENMAI_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['CHITIETKM_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['TEN_CTKM']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <a href="./index-1.html">Quay lại phần tìm kiếm</a>
    <br>
    <a href="../index.html">Quay lại trang chủ</a>
</body>
</html>
