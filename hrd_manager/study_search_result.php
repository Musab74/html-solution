<?
include "../include/include_function.php";
include "./include/include_admin_check.php";

$SearchGubun  = Replace_Check($SearchGubun); //기간, 사업주 검색 구분
$CompanyName  = Replace_Check($CompanyName); //사업주명
$SearchYear   = Replace_Check($SearchYear); //검색 년도
$SearchMonth  = Replace_Check($SearchMonth); //검색 월
$SearchYear2  = Replace_Check($SearchYear2); //사업주검색 년도
$SearchMonth2 = Replace_Check($SearchMonth2); //사업주검색 월
$StudyPeriod  = Replace_Check($StudyPeriod); //검색 기간1(기간검색)
$StudyPeriod2 = Replace_Check($StudyPeriod2); //검색 기간2(사업주검색)
$CompanyCode  = Replace_Check($CompanyCode); //사업자 번호
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
$PageCount    = Replace_Check($PageCount);
$pg           = Replace_Check($pg); //페이지

##-- 페이지 조건
if(!$pg) $pg = 1;
$page_size = $PageCount;
$block_size = 10;

##-- 검색 조건
$where = array();

//기간검색
if($SearchGubun=="A") {
    if($SearchYear)    $where[] = "YEAR(a.LectureStart)=".$SearchYear;
    if($SearchMonth)   $where[] = "MONTH(a.LectureStart)=".$SearchMonth;
    if($CompanyCode)   $where[] = "a.CompanyCode='".$CompanyCode."'";
    if($LectureStart)  $where[] = "a.LectureStart='".$LectureStart."'";
    if($LectureEnd)    $where[] = "a.LectureEnd='".$LectureEnd."'";
}

//사업주  검색 
if($SearchGubun=="B") {
    if($SearchYear2)    $where[] = "YEAR(a.LectureStart)=".$SearchYear2;
    if($SearchMonth2)   $where[] = "MONTH(a.LectureStart)=".$SearchMonth2;
    if($CompanyName)    $where[] = "d.CompanyName LIKE '%".$CompanyName."%'";
    if($LectureStart)  $where[] = "a.LectureStart='".$LectureStart."'";
    if($LectureEnd)    $where[] = "a.LectureEnd='".$LectureEnd."'";
}

if($OpenChapter)    $where[] = "a.OpenChapter='".$OpenChapter."'";
if($ID)             $where[] = "(a.ID='".$ID."' OR c.Name='".$ID."')";
if($SalesID)        $where[] = "(a.SalesID='".$SalesID."' OR f.Name='".$SalesID."')";

if($Progress2) {
    if(!$Progress1)    $Progress1 = 0;
	$where[] = "(a.Progress BETWEEN ".$Progress1." AND ".$Progress2.")";
}
if($TotalScore2) {
    if(!$TotalScore1)  $TotalScore1 = 0;
	$where[] = "(a.TotalScore BETWEEN ".$TotalScore1." AND ".$TotalScore2.")";
}

if($TutorStatus=="N")   $where[] = "a.StudyEnd='N'";
if($LectureCode)        $where[] = "a.LectureCode='".$LectureCode."'";
if($PassOk)             $where[] = "a.PassOk='".$PassOk."'";

if($certCount) {
    if($certCount=="Y")    $where[] = "g.CertDate IS NOT NULL";
    else   $where[] = "g.CertDate IS NULL";
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
			$SQL = "SELECT DeptString FROM DeptStructure WHERE DeptString LIKE '$DeptString%' ORDER BY Deep ASC";
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
            (SELECT SUM(StudyTime) FROM Progress WHERE ID = a.ID) AS TotalStudyTime ";

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
//echo $TOT_NO;

##-- 페이지 클래스 생성
$PageFun = "StudySearch"; //페이지 호출을 위한 자바스크립트 함수

include_once("./include/include_page2.php");

$PAGE_CLASS = new Page($pg,$TOT_NO,$page_size,$block_size,$PageFun); ##-- 페이지 클래스
$BLOCK_LIST = $PAGE_CLASS->blockList(); ##-- 페이지 이동관련
$PAGE_UNCOUNT = $PAGE_CLASS->page_uncount; ##-- 게시물 번호 한개씩 감소
?>
<table width="100%" cellpadding="0" cellspacing="0" class="list_ty01 gapT20">
	<tr>
		<th><input type="checkbox" name="AllCheck" id="AllCheck" value="Y" onclick="CheckBox_AllSelect('check_seq')" class="checkbox" /></th>
		<th>번호</th>
		<th>이름<br />ID</th>
		<th>과정명<br />수강기간</th>
		<th>수강시간</th>
		<th>진도율</th>
		<th>수료여부</th>
		<th>교·강사<br>영업담당자<br>영업담당자 소속</th>
		<th>사업주<br />부서</th>
		<th>실명인증<br />날짜</th>
		<th>실시회차</th>
		<th>수강신청일</th>
	</tr>
	<?
	$SQL = "SELECT $Colume FROM $JoinQuery $where $str_orderby LIMIT $PAGE_CLASS->page_start, $page_size";
	//echo $SQL;
	$QUERY = mysqli_query($connect, $SQL);
	if($QUERY && mysqli_num_rows($QUERY)){
		while($ROW = mysqli_fetch_array($QUERY)){
			extract($ROW);
			
			$today = date("Y-m-d");
			
			//최종수강시간
			if($TotalStudyTime)  $TotalStudyTime = gmdate("H시간 i분", $TotalStudyTime);
			else $TotalStudyTime = "-";

			switch($PassOK) {
				case "N":
					$PassOK_View = "<span class='fcOrg01B'>미수료</span>";
				break;
				case "Y":
					if($AdminWrite=="Y") {
						$PassOK_View = "<a href='Javascript:CertificatePrint(".$Seq.");'><span class='fcSky01B'>수료</span></a>";
					}else{
						$PassOK_View = "<span class='fcSky01B'>수료</span>";
					}
				break;
				default :
					$PassOK_View = "";
			}
	?>
	<tr>
		<td align="center" bgcolor="#FFFFFF" class="text01"><font color="#FFFFFF"><?=$Seq?></font><br><input type="checkbox" name="check_seq" id="check_seq" value="<?=$Seq?>" class="checkbox" /></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><?=$PAGE_UNCOUNT--?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><a href="Javascript:MemberInfo('<?=$ID?>');"><?=$Name?><br /><?=$ID?></a></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><b><?=$ContentsName?></b><br /><?=$LectureStart?> ~ <?=$LectureEnd?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><a href="Javascript:StudyTimeInfo('<?=$ID?>', '<?=$today?>');"><?=$TotalStudyTime?></a></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><a href="Javascript:ProgressInfo('<?=$ID?>','<?=$LectureCode?>',<?=$Seq?>);"><?=$Progress?>%</a></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><?=$PassOK_View?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><?=$TutorName?><br><?=$SalesName?><br><?=$SalesTeam?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><a href="Javascript:CompanyInfo('<?=$CompanyCode?>');"><?=$CompanyName?></a><?if($Depart) {?><br />부서 : <?=$Depart?><?}?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><?=$CertDate?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><?=$OpenChapter?></td>
		<td align="center" bgcolor="#FFFFFF" class="text01"><?=$InputDate?></td>
	</tr>
	<?
		}
	}else{
	?>
	<tr>
		<td height="28" align="center" bgcolor="#FFFFFF" class="text01" colspan="20">검색된 내용이 없습니다.</td>
	</tr>
	<? } ?>
</table>

<!--페이지 버튼-->
<table width="100%"  border="0" cellspacing="0" cellpadding="0" style="margin-top:15px;">
	<tr>
		<td align="center" valign="top"><?=$BLOCK_LIST?></td>
	</tr>
</table>

<?
mysqli_close($connect);
?>