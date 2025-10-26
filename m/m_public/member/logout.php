<?php
include $_SERVER['DOCUMENT_ROOT'] . "/m_include/include_function.php"; //DB연결 및 각종 함수 정의

unset($_SESSION["LoginMemberID"]);
unset($_SESSION["LoginName"]);
unset($_SESSION["LoginEduManager"]);
unset($_SESSION["LoginMemberType"]);
unset($_SESSION["LoginTestID"]);

unset($_SESSION["IsPlaying"]); // Brad (2021.11.27)

$url="/m_archive/main/main.html";

//Session_destroy();
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
	location.href="<?=$url?>";
//-->
</SCRIPT>