<?
##########################################################################
# 차시있는 컨텐츠
##########################################################################
include "../include/include_function.php"; //DB연결 및 각종 함수 정의
include "../include/login_check.php"; //로그인 여부 체크
include "../include/play_check.php";// Brad (2021.11.27): 이중 학습 방지

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$_SESSION["EndTrigger"] = "N"; //EndTrigger 초기화

$today = date("Y-m-d");

$Chapter_Number   = Replace_Check_XSS2($Chapter_Number); //해당과정의 차시순서
$LectureCode      = Replace_Check_XSS2($LectureCode);    //강의코드
$Study_Seq        = Replace_Check_XSS2($Study_Seq);      //Study_Seq
$StudyLectureCode = Replace_Check_XSS2($StudyLectureCode); //Study의 lecturecode

##테스트 아이디 여부 체크 #####################################################################
$TestID = "N";
$Sql = "SELECT * FROM Member WHERE ID='$LoginMemberID'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) { $TestID = $Row['TestID']; }
##테스트 아이디 여부 체크 #####################################################################

## 조회수 증가 ########################################################################
$SqlCnt = "UPDATE Course SET cnt = cnt+1 WHERE LectureCode='$LectureCode'";
mysqli_query($connect, $SqlCnt);
## 조회수 증가 ########################################################################

## 수강 정보 구하기 ########################################################################
$Sql = "SELECT * FROM Study WHERE ID = '$LoginMemberID' ORDER BY Seq DESC LIMIT 1";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $LectureStart     = $Row['LectureStart']; //수강시작일
    $LectureEnd       = $Row['LectureEnd'];   //수강종료일
    $LectureTerme_idx = $Row['LectureTerme_idx'];
}
## 수강 정보 구하기 ########################################################################

## 수강기간 아닐 경우 차단 ########################################################################
if($LectureStart>$today || $LectureEnd<$today){
?>
<script type="text/javascript">
    alert("수강기간이 종료되었습니다.\n문의 1811-9530");
    location.reload();
</script>
<?
    exit;
}
## 수강기간 아닐 경우 차단 ########################################################################

## 과정 정보 구하기 ########################################################################
$Sql = "SELECT * FROM Course WHERE LectureCode='$LectureCode'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $Course_idx   = $Row['idx'];           //과정 고유번호
    $ContentsName = $Row['ContentsName'];  //과정명
    $attachFile   = $Row['attachFile'];    //학습자료
    $ContentsURL  = $Row['ContentsURL'];   //컨텐츠URL
    $MobileURL    = $Row['MobileURL'];     //모바일URL
    $Keyword3     = $Row['Keyword3'];      //관심분야
    $EduGoal      = nl2br($Row['EduGoal']);//학습목표
    $EduTarget    = nl2br($Row['EduTarget']);//대상자
    $Category1    = $Row['Category1'];     //상위카테고리
    $Professor    = $Row['Professor'];     //교강사
    $Price        = $Row['Price'];         //교육비용 일반
    $Price01View  = $Row['Price01View'];   //교육비용 우선지원
    $Price02View  = $Row['Price02View'];   //교육비용 대규모 1000인 미만
    $Price03View  = $Row['Price03View'];   //교육비용 대규모 1000인 이상
    $PassTime     = $Row['PassTime'];      //수료기준 시간
    $ContentsURLSelectGlobal = $Row['ContentsURLSelect']; //컨텐츠 URL 주/예비 선택
}
## 과정 정보 구하기 ########################################################################

## 관심분야  구하기 ########################################################################
$keyword3Arr = explode(',', $Keyword3);
$SQLKey3     = " SELECT * FROM ArchiveQuestion WHERE aType = 'B' AND aDepth = 'step01' AND aGroup = 'A' AND aBind = 'col3' ORDER BY aOrder ASC ";
$QUERYKey3   = mysqli_query($connect, $SQLKey3);
if( $QUERYKey3 && mysqli_num_rows($QUERYKey3) ) {
    while( $ROWKey3 = mysqli_fetch_array($QUERYKey3) ) {
        extract($ROWKey3);
        if ( in_array($idx, $keyword3Arr) ) $Keyword3 = "<b>#</b>".$aValue." ";
    }
}
## 관심분야  구하기 ########################################################################

## 차시 정보 구하기 ########################################################################
$Sql = "SELECT Sub_idx, Seq AS Chapter_Seq , OrderByNum
            FROM Chapter
            WHERE LectureCode='$LectureCode' AND ChapterType='A' AND OrderByNum='$Chapter_Number'
            ORDER BY OrderByNum ASC LIMIT 0,1";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
$Contents_idx = $Row['Sub_idx'];
$Chapter_Seq = $Row['Chapter_Seq'];
$Chapter_Number = $Row['OrderByNum'];

$Sql = "SELECT * FROM Contents WHERE idx='$Contents_idx'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $ContentsTitle = $Row['ContentsTitle']; //차시명
    $LectureTime = $Row['LectureTime'] * 60; //수강시간(초)
    $Expl01 = nl2br($Row['Expl01']); //차시 목표
    $Expl02 = nl2br($Row['Expl02']); //훈련 내용
    $Expl03 = nl2br($Row['Expl03']); //학습 활동
}
## 차시 정보 구하기 ########################################################################

## 최종 수강내역 정보 구하기 ########################################################################
$Sql = "SELECT * FROM Progress
        WHERE ID='$LoginMemberID' AND LectureCode='$LectureCode' AND Chapter_Seq=$Chapter_Seq";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $ContentsDetail_Seq = $Row['ContentsDetail_Seq'];
    $LastStudy = $Row['LastStudy'];
    $Progress = $Row['Progress'];
    $StudyTime = $Row['StudyTime'];
    $mode = "C";
}else{
    $Progress = 0;
    $StudyTime = 0;
    $mode = "S";
}
if($Progress>=100) {
    $_SESSION["EndTrigger"] = "Y";
    $_SESSION['IsPlaying'] = 'N';
}
## 최종 수강내역 정보 구하기 ########################################################################

## 컨텐츠 정보 구하기 ###################################################################
$Sql = "SELECT COUNT(*) FROM ContentsDetail WHERE Contents_idx=$Contents_idx AND UseYN='Y'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
$ContentsDetail_count = $Row[0];

//강의 처음부터 시작
if($mode == "S"){
    $Sql = "SELECT * FROM ContentsDetail 
            WHERE Contents_idx=$Contents_idx AND (ContentsType='A' OR ContentsType='B') 
            ORDER BY OrderByNum ASC, Seq ASC LIMIT 0,1";
    $Result = mysqli_query($connect, $Sql);
    $Row = mysqli_fetch_array($Result);
    if($Row) {
        $ContentsDetail_Seq = $Row['Seq'];
        $ContentsType = $Row['ContentsType'];
        $ContentsURLSelect = $Row['ContentsURLSelect'];
        $ContentsURL = $Row['ContentsURL'];
        $MobileURL    = $Row['MobileURL'];  
        $ContentsURL2 = $Row['ContentsURL2'];
        $Caption = $Row['Caption']; //자막 파일
        
        if($ContentsURLSelectGlobal=="B") {
            $ContentsURLSelect = "B";
            $ContentsURL = $ContentsURL2;
        }else{
            if($ContentsURLSelect=="A") $ContentsURL = $ContentsURL; else $ContentsURL = $ContentsURL2;
        }
    }else{
        ?>
<script type="text/javascript">
    alert("강의 정보에 오류가 발생했습니다.(-1)");
    location.reload();
</script>
<?
        exit;
    }
}

//이어보기로 시작.(이미 이전에 강의 봤음)
if($mode == "C"){
    $Sql = "SELECT * FROM ContentsDetail WHERE Contents_idx=$Contents_idx";
    $Result = mysqli_query($connect, $Sql);
    $Row = mysqli_fetch_array($Result);
    if($Row) {
        $ContentsDetail_Seq = $Row['Seq'];
        $ContentsType = $Row['ContentsType'];
        $ContentsURLSelect = $Row['ContentsURLSelect'];
        $ContentsURL = $Row['ContentsURL'];
        $MobileURL    = $Row['MobileURL'];  
        $ContentsURL2 = $Row['ContentsURL2'];
        $Caption = $Row['Caption']; //자막 파일

        if(!$LastStudy || $LastStudy=="blank") $LastStudy = $ContentsURL;
        if($ContentsType=="A") $ContentsURL = $LastStudy;
        if($ContentsType=="B") {
            if($ContentsURLSelectGlobal=="B") {
                $ContentsURLSelect = "B";
                $ContentsURL = $ContentsURL2;
            }else{
                if($ContentsURLSelect=="A") $ContentsURL = $ContentsURL; else $ContentsURL = $ContentsURL2;
            }
        }
    }else{
?>
<script type="text/javascript">
    alert("강의 정보에 오류가 발생했습니다.");
    location.reload();
</script>
<?
        exit;
    }
}
## 컨텐츠 정보 구하기 ###################################################################

// --- 모드별 Start 기본값 계산 ------------------------------
// 가장 최근 PositionSec (없으면 0)
$PrevPositionSec = 0;
$SqlPos = "
  SELECT PositionSec
  FROM ProgressLog
  WHERE ID='$LoginMemberID'
    AND LectureCode='$LectureCode'
    AND Chapter_Seq=$Chapter_Seq
    AND PositionSec IS NOT NULL
  ORDER BY idx DESC
  LIMIT 1
";
$ResPos = mysqli_query($connect, $SqlPos);
if ($ResPos && ($RowPos = mysqli_fetch_array($ResPos))) {
  $PrevPositionSec = (int)$RowPos['PositionSec'];
}

// Start 시 사용할 LastStudy / PositionSec
if ($mode === 'S') {
  $StartLastStudy = $ContentsURL;                         // 첫 페이지 경로
  $StartPosSec    = 0;                                    // 0초부터
} else { // C
  $StartLastStudy = ($LastStudy && $LastStudy !== 'blank') ? $LastStudy : $ContentsURL;
  $StartPosSec    = $PrevPositionSec;                     // 마지막 저장 위치
}
?>
<!-- <script>
  window.PlayerMode = "<?= $mode ?>";
  window.StartDefaults = {
    lastStudy: "<?= addslashes($StartLastStudy) ?>",
    posSec: <?= (int)$StartPosSec ?>
  };
</script> -->
<div id="__start_defaults"
     data-last="<?= htmlspecialchars($StartLastStudy, ENT_QUOTES) ?>"
     data-pos="<?= (int)$StartPosSec ?>">
</div>
<?php
//현재 과정 본인 인증 횟수 ########################################################################
$Sql = " SELECT COUNT(*) FROM UserCertOTP WHERE ID='$LoginMemberID' AND COURSE_AGENT_PK='$StudyLectureCode' ";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
$MobileAuth_count = $Row[0];

if( $MobileAuth_count < 1 ) {
    if($TestID == "Y")  $MobileAuth_need = "N";
    else                $MobileAuth_need = "Y";
    $EvalCd = "00";
    $StudyAuthMsg = "과정입과 시 본인인증이 필요합니다.";
} else {
    $Sql = " SELECT COUNT(*) FROM UserCertOTP WHERE id = '$LoginMemberID' AND EvalCD <> '00' AND DATE(RegDate)= '".date('Y-m-d')."'";
    $Result = mysqli_query($connect, $Sql);
    $Row = mysqli_fetch_array($Result);
    $CertCount = $Row[0];

    if ( $CertCount < 1 ){
        $Sql = "SELECT COUNT(*) FROM Progress WHERE ID='$LoginMemberID' AND DATE(RegDate)='".date('Y-m-d')."'";
        $Result = mysqli_query($connect, $Sql);
        $Row = mysqli_fetch_array($Result);
        $Today_Progress_count2 = $Row[0];
        if($Today_Progress_count2<1){
            if($TestID == "Y")  $MobileAuth_need2 = "N";
            else                $MobileAuth_need2 = "Y";
            $EvalCd = "01";
            $StudyAuthMsg = "학습 진행 시 본인인증이 필요합니다.";
        }
    }
}

//금일 수강한 수강시간 ########################################################################
$StudyTimeSum = 0;
$eight_hours = (8 * 3600) - 30 ; // 요청으로 30초 일찍 컷

$SqlD1 = "SELECT DISTINCT LectureCode, Chapter_Seq FROM ProgressLog
          WHERE ID = '$LoginMemberID' AND RegDate LIKE '%$today%'";
$QueryD1 = mysqli_query($connect, $SqlD1);
if($QueryD1 && mysqli_num_rows($QueryD1)){
    while($RowD1 = mysqli_fetch_array($QueryD1)){
        $LectureCodeD = $RowD1['LectureCode'];
        $ChapterSeqD = $RowD1['Chapter_Seq'];
        $SqlD2 = "SELECT MAX(StudyTime) - MIN(StudyTime) FROM ProgressLog
                  WHERE ID = '$LoginMemberID' AND RegDate LIKE '%$today%' AND LectureCode = '$LectureCodeD' AND Chapter_Seq  = $ChapterSeqD";
        $ResultD2 = mysqli_query($connect, $SqlD2);
        $RowD2 = mysqli_fetch_array($ResultD2);
        $StudyTimeD2 = $RowD2[0];
        $StudyTimeSum = $StudyTimeSum + $StudyTimeD2;
    }
}
$Today_Progress_count = $StudyTimeSum;

if($TestID=="N") {
    if($Today_Progress_count > $eight_hours) {
?>
        <script type="text/javascript">
        alert("디지털 아카이브 원격훈련 운영 규정 상 하루 최대 8시간까지만 수강이 가능합니다.");
        PlayDenyClose();
        </script>
<?
        exit;
    }
}
//금일 수강한 수강시간 ########################################################################

// 본인 인증이 필요한 경우 ########################################################
if($MobileAuth_need=="Y" && $TestID=="N") {
?>
<script type="text/javascript">
var StudyAuthMsg = "<?=$StudyAuthMsg?>";
if(StudyAuthMsg!=""){ alert(StudyAuthMsg); } else { alert("본인인증이 필요합니다."); }
PlayDenyClose();
PlayStudyAuth(1, '<?=$LectureCode?>', '<?=$Study_Seq?>', '<?=$StudyLectureCode?>', '<?=$EvalCd?>')
</script>
<?
exit;
}
$Captcha_need='N';

if($MobileAuth_need2=="Y" && $TestID=="N") {
?>
<form name="form_motp" method="post" target="popupMotp" action="">&nbsp;&nbsp;</form>
<script type="text/javascript">
    var StudyAuthMsg = "<?=$StudyAuthMsg?>";
    if(StudyAuthMsg!=""){ alert(StudyAuthMsg); } else { alert("본인인증이 필요합니다."); }

    function fnPopupmotp(){
        var COURSE_AGENT_PK = "<?=$StudyLectureCode?>";
        var CLASS_AGENT_PK = "<?=$StudyLectureCode?>,<?=$LectureTerme_idx?>";
        window.open('', 'popupMotp', 'width=552, height=962, top=100, left=100, fullscreen=no, menubar=no, status=no, toolbar=no, titlebar=yes, location=no, scrollbar=no');
        document.form_motp.target = "popupMotp";
        document.form_motp.action = "/player/motp.php?class_tme=<?=$Chapter_Number?>&Chapter_Number=<?=$Chapter_Number?>&EvalCd=<?=$EvalCd?>&LectureCode=<?=$LectureCode?>&COURSE_AGENT_PK=<?=$StudyLectureCode?>&CLASS_AGENT_PK=<?=$StudyLectureCode?>,<?=$LectureTerme_idx?>&Study_Seq=<?=$Study_Seq?>&Chapter_Seq=<?=$Chapter_Seq?>&Contents_idx=<?=$Contents_idx?>&mode=<?=$mode?>"
        document.form_motp.submit(); 
    }
    fnPopupmotp();
</script>
<?
exit;
}

//수강중여부 체크 세션
$_SESSION["IsPlaying"] = "Y";
?>
<input type="hidden" name="Chapter_Number" id="Chapter_Number" value="<?=$Chapter_Number?>">
<input type="hidden" name="LectureCode" id="LectureCode" value="<?=$LectureCode?>">
<input type="hidden" name="Chapter_Seq" id="Chapter_Seq" value="<?=$Chapter_Seq?>">
<input type="hidden" name="Contents_idx" id="Contents_idx" value="<?=$Contents_idx?>">
<input type="hidden" name="ContentsDetail_Seq" id="ContentsDetail_Seq" value="<?=$ContentsDetail_Seq?>">
<input type="hidden" name="CompleteTime" id="CompleteTime" value="<?=$LectureTime?>">
<input type="hidden" id="DefaultPagePath" value="<?= $ContentsURL ?>">

<?if($ContentsDetail_count>1) {?>
<input type="hidden" name="MultiContentType" id="MultiContentType" value="Y">
<?}else{?>
<input type="hidden" name="MultiContentType" id="MultiContentType" value="N">
<?}?>
<input type="hidden" name="timeChk" id="timeChk">
<?
## 플레쉬/iframe 강의의 경우 ###################################################################
if($ContentsType=="A") {
    $mobileAgents = ['Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Opera Mini', 'IEMobile'];
    $isMobile = false;
    foreach ($mobileAgents as $agent) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], $agent) !== false) { $isMobile = true; break; }
    }
    if ($isMobile) {
        (strpos($MobileURL, "https://") === false)?  $PlayPath = $FlashServerURL.$MobileURL : $PlayPath = $MobileURL;
    } else {
        (strpos($ContentsURL, "https://") === false)?  $PlayPath = $FlashServerURL.$ContentsURL : $PlayPath = $ContentsURL;
    }
?>
<div id="CloseBtn" style="z-index:10000">
    <a href="Javascript:PlayerClose();" style="position:absolute; top:56px; right:40px; color: #fff;display: flex;align-items: center; font-size: 19px;z-index:1000">학습종료<img src="/archive/contents/img/common/btnbul_close02.png"></a>
</div>
<div class='flashArea'>
    <div class="field"><?=$Keyword3?></div>
    <div class="title contitle" id="drag_play"><?=$ContentsName?></div>
    <input type="hidden" name="ContentsType" id="ContentsType" value="A">
    <iframe name="mPlayer" id="mPlayer"  src="<?=$PlayPath?>" border="0" frameborder="0" onload="resizeIframe(this)" scrolling="no"></iframe>
    <div class="study_detail">
        <span>수강시간</span>
        <input type="hidden" name="StartTime" id="StartTime" value="<?=$StudyTime?>"><!-- 초기 수강시간 시작 초 -->
        <strong id="StudyTimeNow">00:00:00</strong>
    </div>
    <div class="study_detail">
        <span>훈련기간</span>
        <strong><?=$LectureStart?> ~ <?=$LectureEnd?></strong>
    </div>
    <div class="counsel">
        <a href="javascript:PlayStudyCounsel('<?=$LectureCode?>','<?=$Contents_idx?>')">학습내용 질문하기</a>
    </div>
    <span>학습 목차</span>
    <ul class="index">
        <?
        $achkChapter;
        $i=1;
        $SqlA = "SELECT a.Sub_idx, b.ContentsTitle , b.LectureTime  , a.OrderByNum
                FROM Chapter a
                LEFT JOIN Contents b ON b.idx = a.Sub_idx 
                LEFT JOIN ContentsDetail c ON c.Contents_idx = a.Sub_idx 
                WHERE a.LectureCode = '$LectureCode'";
        $QUERYA = mysqli_query($connect, $SqlA);
        if($QUERYA && mysqli_num_rows($QUERYA)){
            while($ROWA = mysqli_fetch_array($QUERYA)){
                $ContentsTitleA    = $ROWA['ContentsTitle'];
                $LectureTimeA      = $ROWA['LectureTime'];
                $Sub_idxA          = $ROWA['Sub_idx'];
                $OrderByNum        = $ROWA['OrderByNum'];
                
                if($achkChapter == "Y"){
                    $Chapter_Data = $Sub_idxA;
                    $achkChapter = "N";
                }
        ?>
        <li>
            <div class='title' <?if($Chapter_Number==$OrderByNum){?> style="font-weight:400; color:#ffe119;" <?}?>><?=$i?>. <?=$ContentsTitleA?></div>
            <div class='right'>
                <?
                $Sql1 = "SELECT Progress, StudyTime , RegDate FROM ProgressLog WHERE ID='$LoginMemberID' AND LectureCode='$LectureCode' AND Contents_idx='$Sub_idxA' ORDER BY idx DESC Limit 0,1";
                $Result1 = mysqli_query($connect, $Sql1);
                $Row1 = mysqli_fetch_array($Result1);
                $StudyTime1 = gmdate("H시간 i분 s초", $Row1['StudyTime']);
                
                if($Row1['Progress'])   $ProgressStr = $Row1['Progress']."%";
                else                    $ProgressStr = "0%";
                    
                if($Row1['RegDate'])    $RegDateStr = $Row1['RegDate'];
                else                    $RegDateStr = "-";
                ?>
                <div class="detail_wrap">
                    <div class='time'><span><?=$StudyTime1?></span> / <?=$LectureTimeA?>분 (<?=$ProgressStr?>)</div>
                    <div class="detail"><em>최종학습시간: </em><?=$RegDateStr?></div>
                </div>
                <button onclick="Javascript:ContentsPlayer3('<?=$LectureCode?>', '<?=$OrderByNum?>', '<?=$Study_Seq?>', '<?=$StudyLectureCode?>');">학습시작</button>
            </div>
        </li>
        <?
                $Detail1 = "0%";
                $Detail2 = "";
                $i++;
                
                if($Chapter_Number == $OrderByNum){
                    $achkChapter = "Y";
                }
            }
        }
        ?>
    </ul>
    <span>학습 목표</span>
    <div class="txt"><?=$EduGoal?></div>
    <span>학습 대상자</span>
    <div class="txt"><?=$EduTarget?></div>
    <span>교강사</span>
    <div class="txt"><?=$Professor?></div>
    <span>수강비용</span>
    <div class="txt"> <?=$Price?> 원  |   환급비용  :  우선지원 : <?=$Price01View?> 원  /   대규모 1000인 미만 : <?=$Price02View?> 원  /   대구모 1000인 이상 : <?=$Price03View?> 원</div>
    <span>수료기준</span>
    <div class="txt"><?=$PassTime?> 시간 이상</div>
    <?
    $SqlA = "SELECT ROUND(AVG(StarPoint)) AS AvgStar FROM Review WHERE Del='N' AND UseYN='Y' AND LectureCode = '$LectureCode'";
    $ResultA = mysqli_query($connect, $SqlA);
    $RowA = mysqli_fetch_array($ResultA);
    $AVG_STAR = $RowA[0];
    $Star = StarPointViewA($AVG_STAR);
    ?>
    <span>
        만족도(평점) &nbsp;<?=$Star?> &nbsp;&nbsp;
        <?
        $SqlR = "SELECT COUNT(*) AS CNT FROM Review WHERE Del='N' AND UseYN='Y' AND LectureCode = '$LectureCode' AND ID = '$LoginMemberID'";
        $ResultR = mysqli_query($connect, $SqlR);
        $RowR = mysqli_fetch_array($ResultR);
        $Review_CNT = $RowR[0];
        if($Review_CNT == 0){
        ?>
        <button class="btn_1" onclick="javascript:SurveyPop('<?=$LectureCode?>', 'N')">수강후기 작성</button> &nbsp;&nbsp;
        <?}?>
        <button class="btn_1" onclick="javascript:SurveyView()">만족도(평점)및후기 보러가기</button>
    </span>
    <?
    $SqlR2 = "SELECT * FROM Review WHERE Del='N' AND UseYN='Y' AND LectureCode = '$LectureCode' ORDER BY StarPoint DESC  LIMIT 5";
    $QueryR2 = mysqli_query($connect, $SqlR2);
    if($QueryR2 && mysqli_num_rows($QueryR2)){
        while($RowR2 = mysqli_fetch_array($QueryR2)){
            $StarPointR2 = $RowR2['StarPoint'];
            $TitleR2 = $RowR2['Title'];
            $StarPointR2 = StarPointViewA($StarPointR2);
    ?>
    <div class="txt"><?=$StarPointR2?>&nbsp;&nbsp;<?=$TitleR2?></div>
    <?
        }
    }
    ?>
</div>
<div class='recommendArea'>
    <span>추천 컨텐츠</span>
    <ul>
        <?
        $SqlB = "SELECT * FROM Course WHERE Category1=$Category1 AND LectureCode != '$LectureCode' LIMIT 5";
        $QUERYB = mysqli_query($connect, $SqlB);
        if($QUERYB && mysqli_num_rows($QUERYB)){
            while($ROWB = mysqli_fetch_array($QUERYB)){
                $ContentsNameA = $ROWB['ContentsName'];
                $PreviewImageA = $ROWB['PreviewImage'];
                $ContentsTimeA = $ROWB['ContentsTime'];
                $LectureCodeA  = $ROWB['LectureCode'];
                $Chapter       = $ROWB['Chapter'];
                $PreviewImageView = "/upload/Course/".$PreviewImageA;
        ?>
        <li>
            <?if($Chapter == "0"){?>
            <div class="img" style="background-image: url(<?=$PreviewImageView?>);"onclick="Javascript:ContentsPlayer('<?=$LectureCodeA?>');"></div>
            <?}else{?>
            <div class="img" style="background-image: url(<?=$PreviewImageView?>);" onclick="Javascript:ContentsPlayer3('<?=$LectureCodeA?>', '1', '<?=$Study_Seq?>', '<?=$StudyLectureCode?>');"></div>
            <?}?>
            <div class="title"><?=$ContentsNameA?></div>
            <div class="time"><?=$ContentsTimeA?>분</div>
        </li>
        <?
            }
        }
        ?>
    </ul>  
</div>
<style>
/* (기존 스타일 유지) */
#DataResult{max-width: 1300px;}
#DataResult::-webkit-scrollbar {display: none;}
.flashArea{background-color:#2f2f2e;  padding:50px; border-radius:30px;color: #fff;}
.flashArea .title{font-size:30px; line-height:60px; font-weight:600}
.flashArea .study_detail{margin-top:10px; margin-bottom:0px}
.flashArea .study_detail span{margin-right:5px; color:#ccc;}
.flashArea > span{display:block; font-size:20px; font-weight:600; padding:15px 0; margin-top:30px; border-bottom:1px solid #ccc}
.flashArea .index li{display:flex; padding:14px 0; border-bottom:1px solid #ccc; justify-content: space-between;}
.flashArea .index li .title{font-size:18px; line-height:40px; font-weight:300; color:#ccc;}
.flashArea .index li .right{display:flex; align-items:center; justify-content: space-between; gap: 15px;}
.flashArea .index li .right .detail_wrap{text-align: right;}
.flashArea .index li .right .time{color:#ccc;}
.flashArea .index li .right .detail{font-size: 13px;color: #999;margin-top:3px;}
.flashArea .index li .right .time span{ color:#ffe119}
.flashArea .index li .right button{background-color:transparent; border:1px solid #ffe119; padding:8px 35px; border-radius:50px; color:#ffe119; font-size:16px; transition:all .3s ease;}
.flashArea .index li .right button:hover{background-color:#ffe11924}
.flashArea .txt{padding:15px 0; font-weight:300; font-size:18px; color: #ccc;}
.recommendArea{position: fixed;right: 0;top: 0px; width: 300px; height: 100%; background-color: #2f2f2e; border-radius: 30px; padding: 20px;color: #fff;}
.recommendArea span{font-size: 20px; position:relative; display:block; margin-bottom:20px}
.recommendArea span::before{content:''; position:absolute; bottom:-10px; left:-13px;width:112px; height:1px; background-color:#fff;}
.recommendArea ul{height:100%; overflow-y:scroll;}
.recommendArea ul::-webkit-scrollbar {display: none;}
.recommendArea ul li{margin-bottom:20px;}
.recommendArea .img{width: 264px; height:152px; background-size:cover; background-position-x: center;}
.recommendArea .title{text-align:center;padding:7px 0 2px 0; }
.recommendArea .time{text-align:center; color:#ffe119}
.counsel{text-align: right; margin-top: -40px;}
.counsel a{ color: #FFC107;border: 1px solid;padding: 5px 10px;border-radius: 10px; transition: all .3s ease}
.counsel a:hover{background-color: #ffc1072b;}
.btn_1{background-color: transparent; border: 1px solid #ffe119; padding: 8px 35px; border-radius: 50px; color: #ffe119;font-size: 16px;transition: all .3s ease;}
@media(max-width:1600px){ .recommendArea{width:270px;} }
@media(max-width:1550px){
    .recommendArea{display:none;}
    #DataResult{width:100%; left:50% !important; transform:translateX(-50%)}
}
@media(max-width:700px){
    .contitle{ margin: 10px 0 20px 0;}
    .flashArea{ padding: 40px 25px;}
    .flashArea .title{font-size:20px; line-height:30px;}    
    .counsel{ display:none;}
    .flashArea > span{font-size: 18px; margin-top:0px;}
    .flashArea .index{margin-bottom:10px;}
    .flashArea .index li{ padding: 6px 0;}
    .flashArea .index li .title{ font-size: 13px;line-height: 20px;}
    .flashArea .index li .right{width: 150px;}
    .flashArea .index li .right .time{ min-width: 90px;}
    .flashArea .index li .right button{padding: 6px 8px;font-size: 12px; min-width: 63px;}
    .flashArea .goal li{font-size:13px;}
    .flashArea .index li .right .detail{display:none;}
    .btn_1{display: none;}
    #CloseBtn a{top: 25px !important; right: 20px !important;font-size: 15px !important;}
    #CloseBtn a img{width: 20px;margin-left: 5px;}
}
</style>
<?
}

## 동영상 강의의 경우 ###################################################################
if($ContentsType=="B") {
    $mobileAgents = ['Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Opera Mini', 'IEMobile'];
    $isMobile = false;
    foreach ($mobileAgents as $agent) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], $agent) !== false) { $isMobile = true; break; }
    }
    if ($isMobile) {
        (strpos($MobileURL, "https://") === false)?  $PlayPath = $MovieServerURL.$MobileURL : $PlayPath = $MobileURL;
    } else {
        (strpos($ContentsURL, "https://") === false)?  $PlayPath = $MovieServerURL.$ContentsURL : $PlayPath = $ContentsURL;
    }
    
?>
<div id='CloseBtn' style='position:relative; z-index:10000'>
    <a href='Javascript:DataResultClose();' style='position:absolute; top:10px; right:10px'><img src='/img/common/btnbul_close02.png'></a>
</div>
<div class='flashArea' style='background-color:#000;  padding:50px; border-radius:30px'>
    <div class='field'><?=$Keyword3?></div>
    <div class='title' style='font-size:30px; line-height:60px; font-weight:600'><?=$ContentsName?></div>
    <input type='hidden' name='ContentsType' id='ContentsType' value='B'>
    <video id='mPlayer' width='1020' height='655' controls autoplay>
        <source src='<?=$PlayPath?>' type='video/mp4'>
    </video>
</div>
<?
}
## 동영상 강의의 경우 ###################################################################
?>

<div id="StudyInformation" style="display:none;"></div>

<script>

function hhmmssToSec(str) {
  if (!str) return null;
  var parts = String(str).trim().split(':').map(function(v){return parseInt(v, 10) || 0});
  if (parts.length === 3) return parts[0]*3600 + parts[1]*60 + parts[2];
  if (parts.length === 2) return parts[0]*60 + parts[1];
  return null;
}

// 현재 플레이 위치(초) 구하기
function getCurrentPosSec() {
  var type = $('#ContentsType').val();
  try {
    if (type === 'A') {
      var t = $('#mPlayer').contents().find('.time1 .playerText').text() || '';
      return hhmmssToSec(t);
    } else if (type === 'B') {
      var v = document.getElementById('mPlayer');
      return Math.floor((v && v.currentTime) ? v.currentTime : 0);
    }
  } catch (e) {
    
  }
  return 0; 
}

function StudyProgressCheckWork(ProgressStep, CloseYN, ContentsURLSelect, forceOpts) {
    var LastStudy = '';

    if (forceOpts && (forceOpts.lastStudy || forceOpts.lastStudy === 0)) {
        LastStudy = forceOpts.lastStudy; // 모드별 강제 경로(또는 숫자)
    } else {
        if ($('#MultiContentType').val() === 'N') {
            if ($('#ContentsType').val() === 'A') {
                var pagePath = $('#DefaultPagePath').val() || '';
                try {
                    var p = document.getElementById('mPlayer').contentWindow.location.pathname;
                    if (p) pagePath = p; // 가능하면 실제 경로로 덮어씀
                } catch(e) {}
                    LastStudy = (pagePath || '').replace('/contents', '');
                } else if ($('#ContentsType').val() === 'B') {
                    LastStudy = (ContentsURLSelect === 'A') ? parseInt(mPlayer.currentTime || 0, 10) : 30;
                }
            } else {
                LastStudy = (ProgressStep === 'Start') ? '0' : $('#PlayNum').val();
            }
        }

        var posSec;
        if (forceOpts && Number.isFinite(forceOpts.posSec)) {
            posSec = forceOpts.posSec; // 모드별 강제 위치
        } else {
            posSec = getCurrentPosSec(); // 읽기 실패시 0 반환
        }

        $.post('/player/lecture_progress.php', {
            Chapter_Number:     $('#Chapter_Number').val(),
            LectureCode:        $('#LectureCode').val(),
            Chapter_Seq:        $('#Chapter_Seq').val(),
            Contents_idx:       $('#Contents_idx').val(),
            ContentsDetail_Seq: $('#ContentsDetail_Seq').val(),
            ProgressTime:       $('#StartTime').val(),
            LastStudy:          LastStudy,
            CompleteTime:       $('#CompleteTime').val(),
            ProgressStep:       ProgressStep,
            PositionSec:        posSec
        }, function (raw) {
            try { JSON.parse(raw); } catch(e) {}
                if (CloseYN === 'Y') location.reload();
        });
    }

    var lastKnownPosSec = null;
    var seekSendTimer = null;
    var SEEK_DEBOUNCE_MS = 250;


    var TRUSTED_ORIGIN = location.origin;

    window.addEventListener('message', function (e) {
        console.log('[postMessage] origin:', e.origin, 'data:', e.data);

        if (e.origin !== TRUSTED_ORIGIN) return;
            var d = e.data || {};
            if (d.type !== 'ArchivePlayerSeek') return;

            var from  = Math.floor(d.from || 0);
            var to    = Math.floor(d.to   || 0);
            var delta = to - from;

            // 전역 최신 위치 업데이트
            lastKnownPosSec = to;

            // 디바운스 전송
            if (seekSendTimer) clearTimeout(seekSendTimer);
            seekSendTimer = setTimeout(function () {
        
            $.ajax({
                url: '/player/lecture_progress.php',
                method: 'POST',
                dataType: 'text',
                data: {
                    Chapter_Number:     $('#Chapter_Number').val(),
                    LectureCode:        $('#LectureCode').val(),
                    Chapter_Seq:        $('#Chapter_Seq').val(),
                    Contents_idx:       $('#Contents_idx').val(),
                    ContentsDetail_Seq: $('#ContentsDetail_Seq').val(),
                    ProgressTime:       $('#StartTime').val(),
                    LastStudy:          getSeekLastStudy(),
                    CompleteTime:       $('#CompleteTime').val(),
                    ProgressStep:       'Seek',
                    PositionSec:        to,
                    SeekFrom:           from,
                    SeekTo:             to,
                    DeltaSec:           delta
                }
            })

            .done(function (raw) {
                try { var res = JSON.parse(raw); } catch (_) {}
            })
            .fail(function (xhr) {
                console.warn('Seek 로그 전송 실패:', xhr.status, xhr.responseText);
            });
    }, SEEK_DEBOUNCE_MS);
});

function attachSeekBridgeIntoIframe(iframe) {
  try {
    const win = iframe.contentWindow;
    const doc = win && win.document;
    if (!doc) return;

    // 중복 방지
    if (win.__seekBridgeBound) return;
    win.__seekBridgeBound = true;

	function bindToVideo(v) {
		if (!v || v.__seekListenersBound) return;
		v.__seekListenersBound = true;

		let lastTime = 0;        // 직전 재생 위치(초)
		let seekingFrom = null;  // 시크 시작점

		// 평소엔 시크 중이 아닐 때만 lastTime 갱신
		v.addEventListener('timeupdate', function () {
			if (seekingFrom === null) {
			lastTime = Math.floor(v.currentTime || 0);
			}
		});

		// 시크 시작: 직전 재생 위치를 from으로 사용
		v.addEventListener('seeking', function () {
			if (seekingFrom === null) {
			seekingFrom = lastTime;
			}
		});

		// 시크 끝: 현재 시간을 to로
		v.addEventListener('seeked', function () {
			const to   = Math.floor(v.currentTime || 0);
			const from = (seekingFrom == null) ? lastTime : seekingFrom;

			// 부모 창으로 알림
			// (여기서는 부모 컨텍스트에서 바인딩하니 window는 부모임)
			window.postMessage({ type: 'ArchivePlayerSeek', from, to }, location.origin);

			seekingFrom = null;
			lastTime = to;
		});
	}

    const nowVideo = doc.querySelector('video#video1, video');
    if (nowVideo) bindToVideo(nowVideo);

    const mo = new MutationObserver(() => {
      const v = doc.querySelector('video#video1, video');
      if (v) bindToVideo(v);
    });
    mo.observe(doc.documentElement, { childList: true, subtree: true });

    let tries = 0;
    const poll = setInterval(() => {
      const v = doc.querySelector('video#video1, video');
      if (v) { bindToVideo(v); clearInterval(poll); }
      if (++tries > 50) clearInterval(poll);
    }, 100);
  } catch (e) {
    console.warn('[seek-bridge] inject failed:', e);
  }
}

function resumeIntoIframe(iframe, sec) {
  try {
    const win = iframe.contentWindow;
    const doc = win && win.document;
    if (!doc) return;

    let appliedOnce = false;

    function applyOnce(v) {
      if (!v) return;
      if (!appliedOnce && Number.isFinite(sec) && sec > 0) {
        try {
          if (Math.abs(v.currentTime - sec) > 1) v.currentTime = sec;
          appliedOnce = true;
        } catch (e) {
          console.warn('resume apply failed:', e);
        }
      }
    }

    function bind(v) {
      if (!v || v.__resumeBound) return;
      v.__resumeBound = true;

      v.addEventListener('loadedmetadata', function () { applyOnce(v); });
      v.addEventListener('play', function () { applyOnce(v); }, { once: true });

      if (v.readyState >= 1) applyOnce(v);
    }

    let tries = 0;
    const timer = setInterval(() => {
      const v = doc.querySelector('video#video1, video');
      if (v) { bind(v); clearInterval(timer); }
      if (++tries > 50) clearInterval(timer); // 최대 5초
    }, 100);
  } catch (e) {
    console.warn('resumeIntoIframe failed:', e);
  }
}

function getSeekLastStudy() {
  if ($('#ContentsType').val() === 'A') {

    var pagePath = $('#DefaultPagePath').val() || '';
    try {
      var p = document.getElementById('mPlayer').contentWindow.location.pathname;
      if (p) pagePath = p;
    } catch(e) {}
    return (pagePath || '').replace('/contents', '');
  } else if ($('#ContentsType').val() === 'B') {
 
    var v = document.getElementById('mPlayer');
    return (v && v.currentTime) ? parseInt(v.currentTime, 10) : 0;
  }
  return '';
}
</script>

<script type="text/javascript">
if (typeof window.intervalId === 'undefined') {
    window.intervalId = null;
}

$(document).ready(function() { 
    if (window.progressIntervalId) clearInterval(window.progressIntervalId);
    if (window.intervalId) clearInterval(window.intervalId);

	(function ensureStartDefaults(){
	if (!window.StartDefaults) {
		var el = document.getElementById('__start_defaults');
		if (el) {
		window.StartDefaults = {
			lastStudy: el.getAttribute('data-last') || ($('#DefaultPagePath').val() || ''),
			posSec: Number(el.getAttribute('data-pos') || 0)
		};
		} else {

		window.StartDefaults = { lastStudy: ($('#DefaultPagePath').val() || ''), posSec: 0 };
		}
	}
	console.log('[StartDefaults]', window.StartDefaults);
	})();

    // 시작 진도
    StudyProgressCheckWork('Start','N','<?=$ContentsURLSelect?>', window.StartDefaults);

    var Today_Progress_count = <?=$Today_Progress_count?>; //오늘들은 총 시간
    var eight_hours = 8 * 3600; // 8시간

    // 수강 시간 초단위 갱신
    window.intervalId = setInterval(function(){
        var iframeTime = $('#mPlayer').contents().find('.time1 .playerText').html();
        var timeChk = $("#timeChk").val();

        var CompleteTime = Number($("#CompleteTime").val());
        var StartTime    = Number($("#StartTime").val());

        if(StartTime < CompleteTime){
            if(iframeTime != timeChk){
                if( Today_Progress_count >= eight_hours ) {
                    StudyProgressCheckWork('End','N','<?=$ContentsURLSelect?>');
                    PlayDenyClose();
                    alert("디지털 아카이브 원격훈련 운영 규정 상 하루 최대 8시간까지만 수강이 가능합니다.");
                    return false;
                } else {
                    StudyTimeCheck();
                    Today_Progress_count++;
                }
            }
        }else{
            StudyTimeDisplay();
        }
        $("#timeChk").val(iframeTime);
    },1000);
    
    // 60초마다 중간 진도
    window.progressIntervalId = setInterval(function(){
        StudyProgressCheckWork('Middle','N','<?=$ContentsURLSelect?>');
    },60000);

    // 동영상 이어보기
    <?if($mode=="C" && $ContentsType=="B" && $ContentsURLSelect=="A" && $Progress < 100) {?>
    setTimeout(function(){
        mPlayer.currentTime=<?=$LastStudy?>;
    },2000);
    <?}?>

    // 제목 드래그 이동
    $("#drag_play").css("cursor","move");
    $("#drag_play").mouseover(function(){
        $("div[id='DataResult']").draggable();
        $("div[id='DataResult']").draggable("option","disabled",false);
    })
    $("#drag_play").mouseleave(function(){
        $("div[id='DataResult']").draggable("option","disabled",true);
    });
});

if (typeof jQuery === 'undefined') {
    console.log('jQuery가 로드되지 않았습니다.');
}

function ContentsPlayer3(LectureCode, Chapter_Number, Study_Seq, StudyLectureCode) {
    var currentWidth = $(window).width();
    var LocWidth = currentWidth / 2;
    var body_width = screen.width;
    var body_height = $('html body').height() + 500;
    var ScrollPosition = $(window).scrollTop();

    $("div[id='SysBg_Black']").css({
        width:  "100%",
        height: body_height,
        opacity: '0.6',
        position: 'absolute',
        'z-index': '100',
    }).show();

    $("div[id='Roading']").css({
        top: '400px',
        left: LocWidth,
        opacity: '0.6',
        position: 'absolute',
        'z-index': '200',
    }).show();
    
    StudyProgressCheckWork('End','N','<?=$ContentsURLSelect?>');
    
    //IsPlaying 세션 변경
    $.ajax({
        url: '/player/set_session_isplaying.php',
        type: 'POST',
        dataType: 'text',
        timeout: 3000,
        success: function (res) { console.log("세션 변경 성공:", res); },
        error: function (xhr, status, error) { console.error("세션 변경 실패:", status, error); }
    });

    $('#DataResult').load('/player/player2.php', 
    {   Chapter_Number: Chapter_Number,
        LectureCode: LectureCode, 
        Study_Seq : Study_Seq, 
        StudyLectureCode : StudyLectureCode
    },
    function () {
        $('html, body').animate({ scrollTop: ScrollPosition + 100 }, 500);

        $("div[id='DataResult']")
            .css({
                top: ScrollPosition,
                left: currentWidth / 2 - 800,
                opacity: '1.0',
                position: 'absolute',
                'z-index': '1000',
                'overflow-y' :'auto',
                'height':'100vh'
            })
            .fadeIn();

        $("div[id='Roading']").hide();
        $('html, body').animate({ scrollTop: ScrollPosition }, 500);

        var CloseBtnLeft = 1200;
        CloseBtnLeft = CloseBtnLeft / 2 - 38;

        $("div[id='CloseBtn']").css({ top: '0', left: '0', opacity: '1.0' });
        $('html').css('overflow', 'hidden');
    });
}

function SurveyView(){
    StudyProgressCheckWork('End','N','<?=$ContentsURLSelect?>');
    window.location.href = "/public/support/survey.html";
}

function PlayerClose(){
    var ReviewCnt = "<?=$Review_CNT?>";
    if(ReviewCnt == 0){
        StudyProgressCheckWork('End', 'N', '<?=$ContentsURLSelect?>');
        clearInterval(window.intervalId);
        SurveyPop('<?=$LectureCode?>', 'Y');
    }else{
        StudyProgressCheckWork('End', 'Y', '<?=$ContentsURLSelect?>');
    }
}
</script>

<script>
window._didInitialMiddle = false;

document.getElementById('mPlayer').onload = function () {
    const iframe = this;
    try {
        const iframeWin = iframe.contentWindow;
        const iframeDoc = iframeWin.document;

        // 우클릭 방지
        iframeDoc.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // 키보드 차단
        iframeDoc.addEventListener('keydown', function (e) {
            if (
                e.key === 'F12' ||
                (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J')) ||
                (e.ctrlKey && e.key.toLowerCase() === 'u')
            ) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // iframe 사이즈 조정
        resizeIframe(iframe);

		attachSeekBridgeIntoIframe(iframe);
		
		resumeIntoIframe(iframe, (window.StartDefaults && window.StartDefaults.posSec) || 0);

		if (!window._didInitialMiddle) {
			window._didInitialMiddle = true;
			setTimeout(function () {
				StudyProgressCheckWork('Middle','N','<?=$ContentsURLSelect?>');
			}, 400);
		}
    } catch (e) {
        console.warn('iframe 보안스크립트 적용 실패:', e);
    }
};


</script>
