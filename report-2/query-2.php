<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    $query = "
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

    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':p_ma_tb', $ma_tb);
    oci_bind_by_name($stid, ':p_huong_dc', $huong_dc);

    // Thực thi truy vấn
    oci_execute($stid);

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
        echo "<td>" . (empty($row['THUEBAO_ID']) ? "Không có dữ liệu" : htmlspecialchars($row['THUEBAO_ID'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['MA_TB']) ? "Không có dữ liệu" : htmlspecialchars($row['MA_TB'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['THUONGHIEU']) ? "Không có dữ liệu" : htmlspecialchars($row['THUONGHIEU'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['GOI_ID']) ? " " : htmlspecialchars($row['GOI_ID'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['TEN_GOI']) ? " " : htmlspecialchars($row['TEN_GOI'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['NHOM_DATCOC']) ? "Không có dữ liệu" : htmlspecialchars($row['NHOM_DATCOC'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['KHUYENMAI_ID']) ? "Không có dữ liệu" : htmlspecialchars($row['KHUYENMAI_ID'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['TEN_KM']) ? "Không có dữ liệu" : htmlspecialchars($row['TEN_KM'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['CHITIETKM_ID']) ? "Không có dữ liệu" : htmlspecialchars($row['CHITIETKM_ID'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['TEN_CTKM']) ? "Không có dữ liệu" : htmlspecialchars($row['TEN_CTKM'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['SO_THANG_DTC']) ? "Không có dữ liệu" : htmlspecialchars($row['SO_THANG_DTC'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['DATCOC_CSD']) ? "Không có dữ liệu" : htmlspecialchars($row['DATCOC_CSD'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['TIEN_TD']) ? "Không có dữ liệu" : htmlspecialchars($row['TIEN_TD'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['NGUOI_CN']) ? "Không có dữ liệu" : htmlspecialchars($row['NGUOI_CN'], ENT_QUOTES)) . "</td>";
        echo "<td>" . (empty($row['NGAY_CN']) ? "Không có dữ liệu" : htmlspecialchars($row['NGAY_CN'], ENT_QUOTES)) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<br><a href='./index-2.html' style='display: inline-block; margin: 20px 0; color: #3498db; text-decoration: none;'>Quay lại phần tìm kiếm</a>";
    echo "<br><a href='../index.html' style='display: inline-block; margin: 20px 0; color: #3498db; text-decoration: none;'>Quay lại trang chủ</a>";


    // Đóng kết nối
    oci_free_statement($stid);
    oci_close($conn);
}
