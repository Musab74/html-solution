<?php
include "../include/include_function.php";
include "../include/login_check.php";

$LectureCode = Replace_Check_XSS2($_GET['LectureCode'] ?? '');
$Chapter_Seq = (int)($_GET['Chapter_Seq'] ?? 0);

$last = 0;

$sql = "SELECT PositionSec
        FROM ProgressLog
        WHERE ID='$LoginMemberID'
          AND LectureCode='$LectureCode'
          AND Chapter_Seq=$Chapter_Seq
          AND PositionSec IS NOT NULL
        ORDER BY idx DESC
        LIMIT 1";
$res = mysqli_query($connect, $sql);
if ($res && ($row = mysqli_fetch_array($res)) && (int)$row['PositionSec'] > 0) {
  $last = (int)$row['PositionSec'];
}

// 순수 JSON
if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { ob_end_clean(); } }
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['last' => $last], JSON_UNESCAPED_UNICODE);
mysqli_close($connect);
exit;
