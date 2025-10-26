<?
include "../include/include_function.php";
ini_set('memory_limit','-1');
set_time_limit(0);

require_once '../lib/PHPExcel_1.8.0/Classes/PHPExcel.php';
require_once '../lib/PHPExcel_1.8.0/Classes/PHPExcel/Cell/AdvancedValueBinder.php';

PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

$objPHPExcel = new PHPExcel();

$SearchGubun  = Replace_Check($SearchGubun); //기간, 사업주 검색 구분
$CompanyName  = Replace_Check($CompanyName); //사업주명
$SearchYear   = Replace_Check($SearchYear); //검색 년도
$SearchMonth  = Replace_Check($SearchMonth); //검색 월
$SearchYear2  = Replace_Check($SearchYear2); //사업주검색 년도
$SearchMonth2 = Replace_Check($SearchMonth2); //사업주검색 월
$StudyPeriod  = Replace_Check($StudyPeriod); //검색 기간1(기간검색)
$StudyPeriod2 = Replace_Check($StudyPeriod2); //검색 기간2(사업주검색)
$CompanyCode  = Replace_Check($CompanyCodeA); //사업자 번호
$OpenChapter  = Replace_Check($OpenChapter); //실시회차
$ID           = Replace_Check($ID); //이름, 아이디
$SalesID      = Replace_Check($SalesID); //영업자 이름, 아이디
$Progress1    = Replace_Check($Progress1); //진도율 시작
$Progress2    = Replace_Check($Progress2); //진도율 종료
$TutorStatus  = Replace_Check($TutorStatus); //첨삭 여부
$LectureCode  = Replace_Check($LectureCode); //강의 코드
$PassOk       = Replace_Check($PassOk); //수료여부
$certCount    = Replace_Check($certCount); //실명인증 횟수
$LectureStart = Replace_Check($LectureStart); //교육 시작일
$LectureEnd   = Replace_Check($LectureEnd); //교육 종료일
$Tutor        = Replace_Check($Tutor); //교강사
$EduManager   = Replace_Check($EduManager); //교육담당자

##-- 검색 조건
$where = array();

//기간 검색
if($SearchGubun=="A") { 
    if($SearchYear)     $where[] = "YEAR(a.LectureStart)=".$SearchYear;
    if($SearchMonth)    $where[] = "MONTH(a.LectureStart)=".$SearchMonth;
    if($CompanyCode)    $where[] = "a.CompanyCode='".$CompanyCode."'";
    if($LectureStart)   $where[] = "a.LectureStart='".$LectureStart."'";
    if($LectureEnd)     $where[] = "a.LectureEnd='".$LectureEnd."'";
}

//사업주  검색
if($SearchGubun=="B") { 
    if($SearchYear2)    $where[] = "YEAR(a.LectureStart)=".$SearchYear2;
    if($SearchMonth2)   $where[] = "MONTH(a.LectureStart)=".$SearchMonth2;
    if($CompanyName)    $where[] = "d.CompanyName LIKE '%".$CompanyName."%'";
    if($LectureStart)   $where[] = "a.LectureStart='".$LectureStart."'";
    if($LectureEnd)     $where[] = "a.LectureEnd='".$LectureEnd."'";
}

if($OpenChapter)    $where[] = "a.OpenChapter='".$OpenChapter."'";
if($ID)             $where[] = "(a.ID='".$ID."' OR c.Name='".$ID."')";
if($SalesID)        $where[] = "(a.SalesID='".$SalesID."' OR f.Name='".$SalesID."')";

if($Progress2) {
    if(!$Progress1) $Progress1 = 0;
    $where[] = "(a.Progress BETWEEN ".$Progress1." AND ".$Progress2.")";
}

if($TutorStatus=="N")   $where[] = "a.StudyEnd='N'";
if($LectureCode)        $where[] = "a.LectureCode='".$LectureCode."'";
if($PassOk)             $where[] = "a.PassOk='".$PassOk."'";

if($certCount) {
    if($certCount=="Y") $where[] = "g.CertDate IS NOT NULL";
    else $where[] = "g.CertDate IS NULL";
}

if($Tutor)      $where[] = "a.Tutor='".$Tutor."'";
if($EduManager) $where[] = "c.EduManager='".$EduManager."'";

//첨삭강사의 경우 본인의 건만 체크
if($LoginAdminDept=="C") {
    $where[] = "a.Tutor='".$LoginAdminID."'";
}

//영업사원의 경우 본인과 하부 조직의 내용만 체크====================================================================================================================
if($LoginAdminDept=="B") {
	$Sql = "SELECT *, (SELECT DeptString FROM DeptStructure WHERE idx=StaffInfo.Dept_idx) AS DeptString FROM StaffInfo WHERE ID='$LoginAdminID'";
	$Result = mysqli_query($connect, $Sql); 
	$Row = mysqli_fetch_array($Result);
	if($Row) {
		$DeptString = $Row['DeptString'];
		$Dept_idx = $Row['Dept_idx'];
	}
	if($DeptString) {
    	//현재 해당 조직이 하부에 조직이 존재하면 팀장급 이상이므로 하부 조직 모두, 하부조직이 없으면 말단 영업사원이므로 본인것만 나오게한다.
		$Sql2 = "SELECT COUNT(*) AS DeptCount FROM DeptStructure WHERE DeptString LIKE '$DeptString%'";
		$Result2 = mysqli_query($connect, $Sql2);
		$Row2 = mysqli_fetch_array($Result2);
		if ($Row2) {			
			$DeptCount = $Row2['DeptCount'];
		}		

		//하부조직이 있는 경우
		if($DeptCount > 1) {
			$Dept_String = "";
			$SQL = "SELECT REPLACE(DeptString,'$DeptString','') AS DeptString FROM DeptStructure WHERE DeptString LIKE '$DeptString%' ORDER BY Deep ASC";
			$QUERY = mysqli_query($connect, $SQL);
			if($QUERY && mysqli_num_rows($QUERY)){
				while($ROW = mysqli_fetch_array($QUERY)){
					if($ROW['DeptString']) {
						$Dept_String = $Dept_String.$ROW['DeptString'];
					}
				}
			}

			$DeptString_array = explode("|",$Dept_String);
			$DeptString_array = array_unique($DeptString_array);
			$DeptString_array_count = count($DeptString_array);

			$Dept_idx_query = "";
			$i = 0;
			foreach($DeptString_array as $DeptString_array_value) {
				if($DeptString_array_value) {
				    if(!$Dept_idx_query) $Dept_idx_query = "f.Dept_idx=$DeptString_array_value";
				    else $Dept_idx_query = $Dept_idx_query." OR f.Dept_idx=$DeptString_array_value";
				}
                $i++;
			}

			$Dept_idx_query  = "(f.Dept_idx=".$Dept_idx." OR ".$Dept_idx_query.")";

			$where[] = $Dept_idx_query;

		//하부조직이 없는 경우
		}else{ 
			$where[] = "a.SalesID='".$LoginAdminID."'";
		}
	}else{
		$where[] = "a.SalesID='".$LoginAdminID."'";
	}
}
//영업사원 ==========================================================================================================================================================



$where = implode(" AND ",$where);
if($where) $where = "WHERE $where";

$str_orderby = "ORDER BY c.Name ASC, a.Seq DESC";

$Colume = "a.Seq, a.ID, a.LectureStart, a.LectureEnd, a.LectureReStudy, a.Progress, a.PassOK, a.certCount, a.StudyEnd, a.LectureCode, a.CompanyCode, a.InputDate, a.OpenChapter,
			b.ContentsName,
			c.Name, c.Depart,
			d.CompanyName,
			e.Name AS TutorName,
			f.Name AS SalesName, f.Team AS SalesTeam,
			g.CertDate,
            (SELECT SUM(StudyTime) FROM Progress WHERE ID = a.ID AND LastStudy != 'blank') AS TotalStudyTime ";

$JoinQuery = " Study AS a
			LEFT OUTER JOIN Course AS b ON a.LectureCode=b.LectureCode
			LEFT OUTER JOIN Member AS c ON a.ID=c.ID
			LEFT OUTER JOIN Company AS d ON a.CompanyCode=d.CompanyCode
			LEFT OUTER JOIN StaffInfo AS e ON a.Tutor=e.ID
			LEFT OUTER JOIN StaffInfo AS f ON a.SalesID=f.ID
			LEFT OUTER JOIN UserCertOTP AS g ON a.Seq=g.Study_Seq AND a.ID=g.ID AND a.LectureCode = g.LectureCode ";

$Sql2 = "SELECT COUNT(a.Seq) FROM $JoinQuery $where";
$Result2 = mysqli_query($connect, $Sql2);
$Row2 = mysqli_fetch_array($Result2);
$TOT_NO = $Row2[0];

$filename = "학습관리_".date('Ymd');

$TOT_NO2 = $TOT_NO + 1;

//cell border
$objPHPExcel->getActiveSheet()->getStyle('A1:Q'.$TOT_NO2)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
//align
$objPHPExcel->getActiveSheet()->getStyle('A1:Q'.$TOT_NO2)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('A1:Q'.$TOT_NO2)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('A1:Q'.$TOT_NO2)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


//1행 처리
$objPHPExcel->getActiveSheet()->getStyle('A1:Q1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E8E8E8E8');
$objPHPExcel->getActiveSheet()->getCell('A1')->setValue("번호"); 
$objPHPExcel->getActiveSheet()->getCell('B1')->setValue("ID");
$objPHPExcel->getActiveSheet()->getCell('C1')->setValue("이름");
$objPHPExcel->getActiveSheet()->getCell('D1')->setValue("전화번호");
$objPHPExcel->getActiveSheet()->getCell('E1')->setValue("과정명");
$objPHPExcel->getActiveSheet()->getCell('F1')->setValue("수강기간");
$objPHPExcel->getActiveSheet()->getCell('G1')->setValue("첨삭완료");
$objPHPExcel->getActiveSheet()->getCell('H1')->setValue("진도율");
$objPHPExcel->getActiveSheet()->getCell('I1')->setValue("수료여부");
$objPHPExcel->getActiveSheet()->getCell('J1')->setValue("교강사");
$objPHPExcel->getActiveSheet()->getCell('K1')->setValue("사업주");
$objPHPExcel->getActiveSheet()->getCell('L1')->setValue("사업자번호");
$objPHPExcel->getActiveSheet()->getCell('M1')->setValue("부서");
$objPHPExcel->getActiveSheet()->getCell('N1')->setValue("영업자");
$objPHPExcel->getActiveSheet()->getCell('O1')->setValue("영업자소속");
$objPHPExcel->getActiveSheet()->getCell('P1')->setValue("실시회차");
$objPHPExcel->getActiveSheet()->getCell('Q1')->setValue("수강신청일");


$i=2;
$k = 1;
$SQL = "SELECT $Colume FROM $JoinQuery $where $str_orderby";

//echo $SQL;
$QUERY = mysqli_query($connect, $SQL);
if($QUERY && mysqli_num_rows($QUERY)){
    while($ROW = mysqli_fetch_array($QUERY)){
    	extract($ROW);
    
    	$LectureDate = $LectureStart."~".$LectureEnd;
    
    	$Tutor_limit_day = strtotime("$LectureEnd +4 days");
    	$Tutor_limit_day2 = date("Y-m-d", $Tutor_limit_day)."까지";
    
    	$Progress2 = $Progress."%";
    
    	switch($PassOK) {
    		case "N":
    			$PassOK_View = "미수료";
    		break;
    		case "Y":
    			$PassOK_View = "수료";
    		break;
    		default :
    			$PassOK_View = "";
    	}
    	
    	$objPHPExcel->getActiveSheet()->getCell('A'.$i)->setValueExplicit($k, PHPExcel_Cell_DataType::TYPE_NUMERIC);
    	$objPHPExcel->getActiveSheet()->getCell('B'.$i)->setValueExplicit($ID, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('C'.$i)->setValueExplicit($Name, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('D'.$i)->setValueExplicit($Mobile, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('E'.$i)->setValueExplicit($ContentsName, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('F'.$i)->setValueExplicit($LectureDate, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('G'.$i)->setValueExplicit($Tutor_limit_day2, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('H'.$i)->setValueExplicit($Progress2, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('I'.$i)->setValueExplicit($PassOK_View, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('J'.$i)->setValueExplicit($TutorName, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('K'.$i)->setValueExplicit($CompanyName, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('L'.$i)->setValueExplicit($CompanyCode, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('M'.$i)->setValueExplicit($Depart, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('N'.$i)->setValueExplicit($SalesIDName, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('O'.$i)->setValueExplicit($SalesTeam, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('P'.$i)->setValueExplicit($OpenChapter, PHPExcel_Cell_DataType::TYPE_STRING);
    	$objPHPExcel->getActiveSheet()->getCell('Q'.$i)->setValueExplicit($InputDate, PHPExcel_Cell_DataType::TYPE_STRING);
    
    	$i++;
    	$k++;
	}
}

$objPHPExcel->getActiveSheet()->setTitle('Sheet1');
$objPHPExcel->setActiveSheetIndex(0);
$filename = iconv("UTF-8", "EUC-KR", $filename);

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=".$filename.".xlsx");
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
?>
