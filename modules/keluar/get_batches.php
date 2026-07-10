<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if (!isset($_GET['obat_id'])) {
    http_response_code(400);
    exit("Missing obat_id");
}

$obat_id = (int)$_GET['obat_id'];

$query = mysqli_query($conn, "SELECT id, no_batch, tgl_kadaluarsa, stok FROM batch_obat WHERE obat_id = $obat_id AND stok > 0 ORDER BY tgl_kadaluarsa ASC");

$options = "<option value=''>Pilih Batch</option>";
while ($row = mysqli_fetch_assoc($query)) {
    $tgl_ed = date('d-m-Y', strtotime($row['tgl_kadaluarsa']));
    $is_expired = strtotime($row['tgl_kadaluarsa']) < strtotime(date('Y-m-d'));
    $expired_label = $is_expired ? " [KADALUARSA]" : "";
    $options .= "<option value='{$row['id']}' data-stok='{$row['stok']}'>{$row['no_batch']} - Exp: {$tgl_ed} (Stok: {$row['stok']}){$expired_label}</option>";
}

echo $options;
?>
