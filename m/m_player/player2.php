<?
// ##########################################################################
// 차시있는 컨텐츠 (모바일)
// ##########################################################################
include "../m_include/include_function.php"; // DB연결 및 각종 함수 정의
include "../m_include/login_check.php";      // 로그인 여부 체크
include "../m_include/play_check.php";       // 이중 학습 방지

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$_SESSION["EndTrigger"] = "N"; // EndTrigger 초기화

$today = date("Y-m-d");

$Chapter_Number   = Replace_Check_XSS2($Chapter_Number);   // 해당과정의 차시순서
$LectureCode      = Replace_Check_XSS2($LectureCode);      // 강의코드
$Study_Seq        = Replace_Check_XSS2($Study_Seq);        // Study_Seq
$StudyLectureCode = Replace_Check_XSS2($StudyLectureCode); // Study의 lecturecode

// ## 테스트 아이디 여부 체크 ############################################################
$TestID = "N";
$Sql = "SELECT * FROM Member WHERE ID='$LoginMemberID'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) { $TestID = $Row['TestID']; }
// #####################################################################################

// ## 조회수 증가 #######################################################################
$SqlCnt = "UPDATE Course SET cnt = cnt+1 WHERE LectureCode='$LectureCode'";
mysqli_query($connect, $SqlCnt);
// #####################################################################################

// ## 수강 정보 구하기 ##################################################################
$Sql = "SELECT * FROM Study WHERE ID = '$LoginMemberID' ORDER BY Seq DESC LIMIT 1";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $LectureStart     = $Row['LectureStart']; // 수강시작일
    $LectureEnd       = $Row['LectureEnd'];   // 수강종료일
    $LectureTerme_idx = $Row['LectureTerme_idx'];
}
// #####################################################################################

// ## 수강기간 아닐 경우 차단 ###########################################################
if($LectureStart>$today || $LectureEnd<$today){
?>
<script type="text/javascript">
    alert("수강기간이 종료되었습니다.\n문의 1811-9530");
    location.reload();
</script>
<?php
    exit;
}
// #####################################################################################

// ## 과정 정보 구하기 ##################################################################
$Sql = "SELECT * FROM Course WHERE LectureCode='$LectureCode'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $Course_idx   = $Row['idx'];
    $ContentsName = $Row['ContentsName'];
    $attachFile   = $Row['attachFile'];
    $ContentsURL  = $Row['ContentsURL'];
    $MobileURL    = $Row['MobileURL'];
    $Keyword3     = $Row['Keyword3'];
    $EduGoal      = nl2br($Row['EduGoal']);
    $EduTarget    = nl2br($Row['EduTarget']);
    $Category1    = $Row['Category1'];
    $Professor    = $Row['Professor'];
    $Price        = $Row['Price'];
    $Price01View  = $Row['Price01View'];
    $Price02View  = $Row['Price02View'];
    $Price03View  = $Row['Price03View'];
    $PassTime     = $Row['PassTime'];
    $ContentsURLSelectGlobal = $Row['ContentsURLSelect']; // A:주, B:예비
}
// #####################################################################################

// ## 관심분야 구하기 ###################################################################
$keyword3Arr = explode(',', $Keyword3);
$SQLKey3     = " SELECT * FROM ArchiveQuestion WHERE aType = 'B' AND aDepth = 'step01' AND aGroup = 'A' AND aBind = 'col3' ORDER BY aOrder ASC ";
$QUERYKey3   = mysqli_query($connect, $SQLKey3);
if( $QUERYKey3 && mysqli_num_rows($QUERYKey3) ) {
    while( $ROWKey3 = mysqli_fetch_array($QUERYKey3) ) {
        extract($ROWKey3);
        if ( in_array($idx, $keyword3Arr) ) $Keyword3 = "<b>#</b>".$aValue." ";
    }
}
// #####################################################################################

// ## 차시 정보 구하기 ##################################################################
$Sql = "SELECT Sub_idx, Seq AS Chapter_Seq , OrderByNum
            FROM Chapter
            WHERE LectureCode='$LectureCode' AND ChapterType='A' AND OrderByNum='$Chapter_Number'
            ORDER BY OrderByNum ASC LIMIT 0,1";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
$Contents_idx   = $Row['Sub_idx'];
$Chapter_Seq    = $Row['Chapter_Seq'];
$Chapter_Number = $Row['OrderByNum'];

$Sql = "SELECT * FROM Contents WHERE idx='$Contents_idx'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $ContentsTitle = $Row['ContentsTitle'];
    $LectureTime   = $Row['LectureTime'] * 60; // 수강시간(초)
    $Expl01        = nl2br($Row['Expl01']);
    $Expl02        = nl2br($Row['Expl02']);
    $Expl03        = nl2br($Row['Expl03']);
}
// #####################################################################################

// ## 최종 수강내역 정보 구하기 ########################################################
$Sql = "SELECT * FROM Progress
        WHERE ID='$LoginMemberID' AND LectureCode='$LectureCode' AND Chapter_Seq=$Chapter_Seq";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $ContentsDetail_Seq = $Row['ContentsDetail_Seq'];
    $LastStudy          = $Row['LastStudy']; // A형 경로 or B형 초
    $Progress           = $Row['Progress'];
    $StudyTime          = $Row['StudyTime'];
    $mode               = "C";               // 이어보기
}else{
    $Progress = 0;
    $StudyTime = 0;
    $mode = "S";                             // 신규
}
if($Progress>=100) {
    $_SESSION["EndTrigger"] = "Y";
    $_SESSION['IsPlaying'] = 'N';
}
// #####################################################################################

// ## 컨텐츠 정보 구하기 ###############################################################
// 하부 컨텐츠 수
$Sql = "SELECT COUNT(*) FROM ContentsDetail WHERE Contents_idx=$Contents_idx AND UseYN='Y'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
$ContentsDetail_count = $Row[0];

// 강의 처음부터 시작
if($mode == "S"){
    $Sql = "SELECT * FROM ContentsDetail 
            WHERE Contents_idx=$Contents_idx AND (ContentsType='A' OR ContentsType='B') 
            ORDER BY OrderByNum ASC, Seq ASC LIMIT 0,1";
    $Result = mysqli_query($connect, $Sql);
    $Row = mysqli_fetch_array($Result);
    if($Row) {
        $ContentsDetail_Seq = $Row['Seq'];
        $ContentsType       = $Row['ContentsType'];
        $ContentsURLSelect  = $Row['ContentsURLSelect'];
        $ContentsURL        = $Row['ContentsURL'];
        $MobileURL          = $Row['MobileURL'];  
        $ContentsURL2       = $Row['ContentsURL2'];
        $Caption            = $Row['Caption']; // 자막

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
<?php
        exit;
    }
}

// 이어보기로 시작
if($mode == "C"){
    $Sql = "SELECT * FROM ContentsDetail WHERE Contents_idx=$Contents_idx";
    $Result = mysqli_query($connect, $Sql);
    $Row = mysqli_fetch_array($Result);
    if($Row) {
        $ContentsDetail_Seq = $Row['Seq'];
        $ContentsType       = $Row['ContentsType'];
        $ContentsURLSelect  = $Row['ContentsURLSelect'];
        $ContentsURL        = $Row['ContentsURL'];
        $MobileURL          = $Row['MobileURL'];  
        $ContentsURL2       = $Row['ContentsURL2'];
        $Caption            = $Row['Caption']; // 자막

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
<?php
        exit;
    }
}
// #####################################################################################

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
  $StartLastStudy = $ContentsURL;
  $StartPosSec    = 0;
} else { // C
  $StartLastStudy = ($LastStudy && $LastStudy !== 'blank') ? $LastStudy : $ContentsURL;
  $StartPosSec    = $PrevPositionSec;
}
?>

<div id="__start_defaults"
     data-last="<?= htmlspecialchars($StartLastStudy, ENT_QUOTES) ?>"
     data-pos="<?= (int)$StartPosSec ?>"></div>
<?php
// #####################################################################################

// 현재 과정 본인 인증 횟수
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

// 금일 수강한 수강시간
$StudyTimeSum = 0;
$eight_hours = 8 * 3600; // 8시간

$SqlD1 = "SELECT LectureCode , Chapter_Seq , ID , RegDate FROM Progress WHERE ID = '$LoginMemberID' AND RegDate LIKE '%$today%'";
$QueryD1 = mysqli_query($connect, $SqlD1);
if($QueryD1 && mysqli_num_rows($QueryD1)){
    while($RowD1 = mysqli_fetch_array($QueryD1)){
        $LectureCodeD = $RowD1['LectureCode'];
        $ChapterSeqD  = $RowD1['Chapter_Seq'];
        $SqlD2 = "SELECT MAX(StudyTime) - MIN(StudyTime) FROM ProgressLog
                  WHERE ID = '$LoginMemberID' AND RegDate LIKE '%$today%' AND LectureCode = '$LectureCodeD' AND Chapter_Seq = $ChapterSeqD";
        $ResultD2 = mysqli_query($connect, $SqlD2);
        $RowD2 = mysqli_fetch_array($ResultD2);
        $StudyTimeSum += $RowD2[0];
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
<?php
        exit;
    }
}

// 본인 인증 필요시 팝업
if($MobileAuth_need=="Y" && $TestID=="N") {
?>
<script type="text/javascript">
var StudyAuthMsg = "<?=$StudyAuthMsg?>";
alert(StudyAuthMsg ? StudyAuthMsg : "본인인증이 필요합니다.");
PlayDenyClose();
PlayStudyAuth(1, '<?=$LectureCode?>', '<?=$Study_Seq?>', '<?=$StudyLectureCode?>', '<?=$EvalCd?>')
</script>
<?php
exit;
}
$Captcha_need='N';

if($MobileAuth_need2=="Y" && $TestID=="N") {
?>
<form name="form_motp" method="post" target="popupMotp" action="">&nbsp;&nbsp;</form>
<script type="text/javascript">
    var StudyAuthMsg = "<?=$StudyAuthMsg?>";
    alert(StudyAuthMsg ? StudyAuthMsg : "본인인증이 필요합니다.");

    function fnPopupmotp(){
        var COURSE_AGENT_PK = "<?=$StudyLectureCode?>";
        var CLASS_AGENT_PK = "<?=$StudyLectureCode?>,<?=$LectureTerme_idx?>";
        window.open('', 'popupMotp', 'width=552, height=962, top=100, left=100, fullscreen=no, menubar=no, status=no, toolbar=no, titlebar=yes, location=no, scrollbar=no');
        document.form_motp.target = "popupMotp";
        document.form_motp.action = "/m_player/motp.php?class_tme=<?=$Chapter_Number?>&Chapter_Number=<?=$Chapter_Number?>&EvalCd=<?=$EvalCd?>&LectureCode=<?=$LectureCode?>&COURSE_AGENT_PK=<?=$StudyLectureCode?>&CLASS_AGENT_PK=<?=$StudyLectureCode?>,<?=$LectureTerme_idx?>&Study_Seq=<?=$Study_Seq?>&Chapter_Seq=<?=$Chapter_Seq?>&Contents_idx=<?=$Contents_idx?>&mode=<?=$mode?>"
        document.form_motp.submit();
    }
    fnPopupmotp();
</script>
<?php
exit;
}

// 수강중여부 체크 세션
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
// ## 플래시/iframe형 강의 (A)
if($ContentsType=="A") {
    $mobileAgents = ['Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Opera Mini', 'IEMobile'];
    $isMobile = false;
    foreach ($mobileAgents as $agent) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], $agent) !== false) { $isMobile = true; break; }
    }
    if ($isMobile) {
        (strpos($ContentsURL, "https://") === false)?  $PlayPath = $FlashServerURL.$ContentsURL : $PlayPath = $ContentsURL;
    } else {
        (strpos($ContentsURL, "https://") === false)?  $PlayPath = $FlashServerURL.$ContentsURL : $PlayPath = $ContentsURL;
    }
?>
<div id="CloseBtn" style="z-index:10000">
    <a href="Javascript:PlayerClose();" style="position:absolute; top:56px; right:40px; color:#fff; display:flex; align-items:center; font-size:19px; z-index:1000">학습종료<img src="/m_archive/contents/img/common/btnbul_close02.png"></a>
</div>
<div class='flashArea'>
    <div class="field"><?=$Keyword3?></div>
    <div class="title contitle" id="drag_play"><?=$ContentsName?></div>
    <input type="hidden" name="ContentsType" id="ContentsType" value="A">
    <iframe name="mPlayer" id="mPlayer" src="<?=$PlayPath?>" border="0" frameborder="0" scrolling="no" onload="resizeIframe(this)"></iframe>
    <button class="expand" onclick="expandPlayer()">▶ 전체화면으로 보기</button>

    <div class="study_detail">
        <span>수강시간</span>
        <input type="hidden" name="StartTime" id="StartTime" value="<?=$StudyTime?>">
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
        <?php
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
                $ContentsTitleA = $ROWA['ContentsTitle'];
                $LectureTimeA   = $ROWA['LectureTime'];
                $Sub_idxA       = $ROWA['Sub_idx'];
                $OrderByNum     = $ROWA['OrderByNum'];

                if($achkChapter == "Y"){
                    $Chapter_Data = $Sub_idxA;
                    $achkChapter = "N";
                }
        ?>
        <li>
            <?php
            $Sql1 = "SELECT Progress, StudyTime , RegDate FROM ProgressLog WHERE ID='$LoginMemberID' AND LectureCode='$LectureCode' AND Contents_idx='$Sub_idxA' ORDER BY idx DESC Limit 0,1";
            $Result1 = mysqli_query($connect, $Sql1);
            $Row1    = mysqli_fetch_array($Result1);
            $StudyTime1 = gmdate("H시간 i분 s초", $Row1['StudyTime']);
            $ProgressStr = $Row1['Progress'] ? ($Row1['Progress']."%") : "0%";
            $RegDateStr = $Row1['RegDate'] ? $Row1['RegDate'] : "-";
            ?>
            <div class="left">
                <div class='title' <?if($Chapter_Number==$OrderByNum){?> style="font-weight:400; color:#ffe119;" <?}?>><?=$i?>. <?=$ContentsTitleA?></div>
                <div class="detail_wrap">
                    <span><?=$StudyTime1?></span> / <?=$LectureTimeA?>분 (<?=$ProgressStr?>)<br>
                    <em>최종학습시간: </em><?=$RegDateStr?>
                </div>
            </div>
            <div class='right'>
                <button onclick="Javascript:ContentsPlayer3('<?=$LectureCode?>', '<?=$OrderByNum?>', '<?=$Study_Seq?>', '<?=$StudyLectureCode?>');">학습시작</button>
            </div>
        </li>
        <?php
                $i++;
                if($Chapter_Number == $OrderByNum){ $achkChapter = "Y"; }
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
    <div class="txt"><?=$Price?> 원 | 환급비용 : 우선지원 <?=$Price01View?> 원 / 대규모 1000인 미만 <?=$Price02View?> 원 / 대규모 1000인 이상 <?=$Price03View?> 원</div>
    <span>수료기준</span>
    <div class="txt"><?=$PassTime?> 시간 이상</div>
    <?php
    $SqlA = "SELECT ROUND(AVG(StarPoint)) AS AvgStar FROM Review WHERE Del='N' AND UseYN='Y' AND LectureCode = '$LectureCode'";
    $ResultA = mysqli_query($connect, $SqlA);
    $RowA = mysqli_fetch_array($ResultA);
    $AVG_STAR = $RowA[0];
    $Star = StarPointViewA($AVG_STAR);
    ?>
    <span>만족도(평점) &nbsp;<?=$Star?></span>
</div>
<?php
}

// ## 동영상 강의 (B)
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
<div class='flashArea' style='background-color:#000; padding:50px; border-radius:30px'>
    <div class='field'><?=$Keyword3?></div>
    <div class='title' style='font-size:30px; line-height:60px; font-weight:600'><?=$ContentsName?></div>
    <input type='hidden' name='ContentsType' id='ContentsType' value='B'>
    <video id='mPlayer' width='1020' height='655' controls autoplay>
        <source src='<?=$PlayPath?>' type='video/mp4'>
    </video>
</div>
<?php
}
?>

<div id="StudyInformation" style="display:none;"></div>

<script type="text/javascript">
// 반응형/리사이즈
window.addEventListener("resize", function () { if (typeof resizeIframe === 'function') resizeIframe(); });

// ------- StartDefaults 읽기 -------
(function ensureStartDefaults(){
  var el = document.getElementById('__start_defaults');
  if (el) {
    window.StartDefaults = {
      lastStudy: el.getAttribute('data-last') || '',
      posSec: Number(el.getAttribute('data-pos') || 0)
    };
  } else {
    window.StartDefaults = { lastStudy:'', posSec:0 };
  }
  console.log('[MOBILE StartDefaults]', window.StartDefaults);
})();

function hhmmssToSec(str){
  if (!str) return null;
  var p = String(str).trim().split(':').map(function(v){return parseInt(v,10)||0});
  if (p.length===3) return p[0]*3600 + p[1]*60 + p[2];
  if (p.length===2) return p[0]*60 + p[1];
  return null;
}

// 현재 위치(초)
function getCurrentPosSec(){
  var type = document.getElementById('ContentsType') && document.getElementById('ContentsType').value;
  try {
    if (type === 'A') {
      var t = (document.getElementById('mPlayer').contentWindow.document.querySelector('.time1 .playerText')||{}).textContent || '';
      return hhmmssToSec(t) || 0;
    } else if (type === 'B') {
      var v = document.getElementById('mPlayer');
      return Math.floor((v && v.currentTime) ? v.currentTime : 0);
    }
  } catch(e){}
  return 0;
}

function StudyProgressCheckWorkM(ProgressStep, CloseYN, ContentsURLSelect, forceOpts){
  var LastStudy = '';

  if (forceOpts && (forceOpts.lastStudy || forceOpts.lastStudy === 0)) {
    LastStudy = forceOpts.lastStudy;
  } else if (window.StartDefaults && (window.StartDefaults.lastStudy || window.StartDefaults.lastStudy === 0)) {
    LastStudy = window.StartDefaults.lastStudy;
  } else {
    if (document.getElementById('MultiContentType').value === 'N') {
      if (document.getElementById('ContentsType').value === 'A') {
        var pagePath = document.getElementById('DefaultPagePath').value || '';
        try {
          var p = document.getElementById('mPlayer').contentWindow.location.pathname;
          if (p) pagePath = p;
        } catch(e){}
        LastStudy = (pagePath || '').replace('/contents','');
      } else if (document.getElementById('ContentsType').value === 'B') {
        var mp = document.getElementById('mPlayer');
        LastStudy = (ContentsURLSelect === 'A') ? parseInt((mp && mp.currentTime) || 0, 10) : 30;
      }
    } else {
      LastStudy = (ProgressStep === 'Start') ? '0' : (document.getElementById('PlayNum') ? document.getElementById('PlayNum').value : '0');
    }
  }

  var posSec = 0;
  if (forceOpts && Number.isFinite(forceOpts.posSec)) {
    posSec = forceOpts.posSec;
  } else if (window.StartDefaults && Number.isFinite(window.StartDefaults.posSec) && ProgressStep==='Start') {
    posSec = window.StartDefaults.posSec;
  } else {
    posSec = getCurrentPosSec();
  }

  $.post('/m_player/lecture_progress.php', {
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
  }, function(raw){
    try{ JSON.parse(raw); }catch(e){}
    if (CloseYN==='Y') location.reload();
  });
}

$(document).ready(function(){
  // 시작 진도 (StartDefaults 포함)
  StudyProgressCheckWorkM('Start','N','<?= $ContentsURLSelect ?>', window.StartDefaults);

  var Today_Progress_count = <?=$Today_Progress_count?>;
  var eight_hours = 8 * 3600;

  // 초단위 수강시간 갱신 (기존 유지)
  setInterval(function(){
    var iframeTime = $('#mPlayer').contents().find('.time1 .playerText').html();
    var timeChk = $("#timeChk").val();

    var CompleteTime = Number($("#CompleteTime").val());
    var StartTime    = Number($("#StartTime").val());

    if(StartTime < CompleteTime){
      if(iframeTime != timeChk){
        if (Today_Progress_count >= eight_hours) {
          StudyProgressCheckWorkM('End','N','<?= $ContentsURLSelect ?>');
          PlayDenyClose();
          alert("디지털 아카이브 원격훈련 운영 규정 상 하루 최대 8시간까지만 수강이 가능합니다.");
          return false;
        } else {
          StudyTimeCheck();
          Today_Progress_count++;
        }
      }
    } else {
      StudyTimeDisplay();
    }
    $("#timeChk").val(iframeTime);
  }, 1000);

  // 60초마다 중간 진도 저장 (PositionSec 포함)
  setInterval(function(){
    StudyProgressCheckWorkM('Middle','N','<?= $ContentsURLSelect ?>');
  }, 60000);

  <?php if($ContentsType=="B") { ?>
  (function(){
    var v = document.getElementById('mPlayer');
    if (!v) return;
    var resumeSec = (window.StartDefaults && window.StartDefaults.posSec) || 0;

    <?php if($mode=="C" && $ContentsURLSelect=="A" && $Progress < 100) { ?>
    if (!resumeSec || resumeSec <= 0) resumeSec = <?=$LastStudy?>;
    <?php } ?>

    if (resumeSec > 0) {
      v.addEventListener('loadedmetadata', function(){
        try { if (Math.abs(v.currentTime - resumeSec) > 1) v.currentTime = resumeSec; } catch(e){}
      });
    }

    var seekingFrom = null;
    v.addEventListener('seeking', function(){
      if (seekingFrom === null) seekingFrom = Math.floor(v.currentTime || 0);
    });
    v.addEventListener('seeked', function(){
      var to = Math.floor(v.currentTime || 0);
      var from = (seekingFrom==null) ? to : seekingFrom;
      seekingFrom = null;

      $.post('/m_player/lecture_progress.php', {
        Chapter_Number:     $('#Chapter_Number').val(),
        LectureCode:        $('#LectureCode').val(),
        Chapter_Seq:        $('#Chapter_Seq').val(),
        Contents_idx:       $('#Contents_idx').val(),
        ContentsDetail_Seq: $('#ContentsDetail_Seq').val(),
        ProgressTime:       $('#StartTime').val(),
        LastStudy:          to,
        CompleteTime:       $('#CompleteTime').val(),
        ProgressStep:       'Seek',
        PositionSec:        to,
        SeekFrom:           from,
        SeekTo:             to,
        DeltaSec:           (to - from)
      });
    });
  })();
  <?php } ?>

  $("#drag_play").css("cursor","move");
  $("#drag_play").mouseover(function(){
      $("div[id='DataResult']").draggable();
      $("div[id='DataResult']").draggable("option","disabled",false);
  })
  $("#drag_play").mouseleave(function(){
      $("div[id='DataResult']").draggable("option","disabled",true);
  });
});

function ContentsPlayer3(LectureCode, Chapter_Number, Study_Seq, StudyLectureCode) {
  var currentWidth = $(window).width();
  var LocWidth     = currentWidth / 2;
  var body_height  = $('html body').height() + 500;
  var ScrollPosition = $(window).scrollTop();

  $("#SysBg_Black").css({ width:"100%", height:body_height, opacity:'0.6', position:'absolute', 'z-index':'100' }).show();
  $("#Roading").css({ top:'400px', left:LocWidth, opacity:'0.6', position:'absolute', 'z-index':'200' }).show();

  StudyProgressCheckWorkM('End','N','<?= $ContentsURLSelect ?>');

  $.ajax({
    url: '/m_player/set_session_isplaying.php',
    type: 'POST',
    dataType: 'text',
    timeout: 3000,
    success: function (res) { console.log("세션 변경 성공:", res); },
    error: function (xhr, status, error) { console.error("세션 변경 실패:", status, error); }
  });

  $('#DataResult').load('/m_player/player2.php', 
  { Chapter_Number: Chapter_Number, LectureCode: LectureCode, Study_Seq: Study_Seq, StudyLectureCode: StudyLectureCode },
  function () {
      $('html, body').animate({ scrollTop: ScrollPosition + 100 }, 500);
      $("#DataResult").css({ top: ScrollPosition, left: currentWidth / 2 - 800, opacity:'1.0', position:'absolute', 'z-index':'1000', 'overflow-y':'auto', 'height':'100vh' }).fadeIn();
      $("#Roading").hide();
      $('html, body').animate({ scrollTop: ScrollPosition }, 500);
      $("#CloseBtn").css({ top:'0', left:'0', opacity:'1.0' });
      $('html').css('overflow', 'hidden');
  });
}

function SurveyView(){
  StudyProgressCheckWorkM('End','N','<?= $ContentsURLSelect ?>');
  window.location.href = "/m_public/support/survey.html";
}

function expandPlayer() {
  const dataResult = document.getElementById('DataResult');
  const iframe = document.getElementById('mPlayer');

  const originalStyle = { position: iframe.style.position||'', top: iframe.style.top||'', left: iframe.style.left||'', width: iframe.style.width||'', height: iframe.style.height||'', zIndex: iframe.style.zIndex||'', transform: iframe.style.transform||'' };

  const overlay = document.createElement('div');
  overlay.id = 'fullscreenOverlayBg';
  overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.9); z-index:1100;';
  document.body.appendChild(overlay);

  dataResult.style.zIndex = '';
  iframe.style.position = 'fixed';
  iframe.style.top = '50%';
  iframe.style.left = '50%';
  iframe.style.transform = 'translate(-50%, -50%)';
  iframe.style.zIndex = '1200';
  iframe.style.border = 'none';

  function adjustIframeSize() {
      const isLandscape = window.innerWidth > window.innerHeight;
      if (isLandscape) {
          const h = window.innerHeight;
          const w = h * (1200 / 750);
          iframe.style.height = h + 'px';
          iframe.style.width = w + 'px';
      } else {
          iframe.style.width = '100vw';
      }
  }

  adjustIframeSize();
  window.addEventListener('resize', adjustIframeSize);

  const closeBtn = document.createElement('button');
  closeBtn.innerText = '닫기';
  closeBtn.style.cssText = 'position:fixed; top:20px; right:20px; z-index:1300; padding:10px 20px; background:#fff; border:none; border-radius:4px; font-size:16px; cursor:pointer;';
  closeBtn.onclick = () => {
    Object.assign(iframe.style, originalStyle);
    iframe.style.transform = '';
    dataResult.style.zIndex = '1000';
    document.body.removeChild(overlay);
    document.body.removeChild(closeBtn);
    window.removeEventListener('resize', adjustIframeSize);
  };
  document.body.appendChild(closeBtn);
}

function PlayerClose(){
  var ReviewCnt = "<?=$Review_CNT?>";
  if(ReviewCnt == 0){
    StudyProgressCheckWorkM('End', 'N', '<?= $ContentsURLSelect ?>');
    location.href = "/m_player/survey.php?LectureCode=<?=$LectureCode?>";
  }else{
    StudyProgressCheckWorkM('End', 'Y', '<?= $ContentsURLSelect ?>');
  }
}
</script>

<style>
    #DataResult::-webkit-scrollbar {display: none;}
    .flashArea{background-color:#2f2f2e; padding:50px; border-radius:30px; color:#fff; width:100vw;}
    .flashArea .title{font-size:30px; line-height:60px; font-weight:600}
    .flashArea .study_detail{margin-top:10px; margin-bottom:0px; font-size:12px;}
    .flashArea .study_detail span{margin-right:5px; color:#ccc;}
    .flashArea .study_detail strong{font-size:11px;}
    .detail_wrap{color:#ccc;}
    .flashArea > span{display:block; font-size:20px; font-weight:600; padding:15px 0; margin-top:30px; border-bottom:1px solid #ccc}
    .flashArea .index li{display:flex; padding:14px 0; border-bottom:1px solid #ccc; justify-content:space-between;}
    .flashArea .index li .title{font-size:18px; line-height:40px; font-weight:300; color:#ccc;}
    .flashArea .index li .right{display:flex; align-items:center; justify-content:space-between; gap:15px;}
    .flashArea .index li .right .time{color:#ccc;}
    .flashArea .index li .right .time span{color:#ffe119}
    .flashArea .index li .right button{background-color:transparent; border:1px solid #ffe119; padding:8px 35px; border-radius:50px; color:#ffe119; font-size:16px; transition:all .3s ease;}
    .flashArea .index li .right button:hover{background-color:#ffe11924}
    .flashArea .txt{padding:10px 0; font-weight:300; font-size:14px; color:#ccc;}
    .counsel{text-align:right; margin-top:-40px;}
    .counsel a{ color:#FFC107; border:1px solid; padding:5px 10px; border-radius:10px; transition:all .3s ease}
    .counsel a:hover{background-color:#ffc1072b;}
    .expand{color:#FFC107; border:1px solid; padding:5px 10px; border-radius:10px; background-color:transparent; display:none;}
    #DataResult{max-width:100%; left:0 !important;}
    @media(max-width:1000px){
        .contitle{ margin:10px 0 20px 0;}
        .flashArea{ padding:40px 15px;}
        .flashArea .title{font-size:20px; line-height:30px;}
        .counsel{ display:none;}
        .expand{display:block;}
        .flashArea > span{font-size:16px; margin-top:0;}
        .flashArea .index{margin-bottom:10px;}
        .flashArea .index li{ padding:6px 0;}
        .flashArea .index li .title{ font-size:13px; line-height:20px;}
        .flashArea .index li .right .time{ min-width:90px;}
        .flashArea .index li .right button{padding:6px 8px; font-size:12px; min-width:63px;}
        .flashArea .index li .right .detail{display:none;}
        #CloseBtn a{top:25px !important; right:20px !important; font-size:15px !important;}
        #CloseBtn a img{width:20px; margin-left:5px;}
    }
</style>
