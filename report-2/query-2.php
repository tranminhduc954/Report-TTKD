<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ma_tb = $_POST['ma_tb'];

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
        )
        SELECT * 
        FROM (
            SELECT ct.* 
            FROM chi_tiet ct, css.v_bd_goi_dadv goi
            WHERE ct.goi_id = goi.goi_id AND trangthai = 1 AND ct.thuebao_id = goi.thuebao_id
            UNION 
            SELECT * 
            FROM chi_tiet 
            WHERE goi_id IS NULL
        )
        ORDER BY goi_id, nhom_datcoc, ten_km, so_thang_dtc
    ";

    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':p_ma_tb', $ma_tb);
    oci_execute($stid);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="report.csv"');

    $output = fopen('php://output', 'w');

    // In ra tiêu đề cột
    $ncols = oci_num_fields($stid);
    $headers = [];
    for ($i = 1; $i <= $ncols; $i++) {
        $headers[] = oci_field_name($stid, $i);
    }
    fputcsv($output, $headers);

    // In ra các dòng dữ liệu
    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        fputcsv($output, $row);
    }

    fclose($output);
    oci_free_statement($stid);
    oci_close($conn);
    exit;
}
?>