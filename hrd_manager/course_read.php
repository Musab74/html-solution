<?
$MenuType = "E";
$PageName = "course";
$ReadPage = "course_read";
?>
<? include "./include/include_top.php"; ?>
<?
if($ctype) {
    $_SESSION["ctype_session"] = $ctype;
}else{
    if($ctype_session) {
        $ctype = $ctype_session;
    }else{
        $ctype = "X";
        $_SESSION["ctype_session"] = $ctype;
    }
}
if($ctype == "X") $MenuName = "이러닝";
if($ctype == "Y") $MenuName = "숏폼";
if($ctype == "Z") $MenuName = "마이크로닝";
if($ctype == "W") $MenuName = "비환급";

$idx = Replace_Check($idx);



$Sql = "SELECT a.*, b.CategoryName AS CategoryName1, c.CategoryName AS CategoryName2
        FROM Course AS a
    	LEFT OUTER JOIN CourseCategory AS b ON a.Category1=b.idx
    	LEFT OUTER JOIN CourseCategory AS c ON a.Category2=c.idx 
        WHERE a.idx=$idx AND a.Del='N'";
//echo $Sql;
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
if($Row) {
    $ClassGrade = $Row['ClassGrade']; //등급
    $LectureCode = $Row['LectureCode']; //과정코드
    $UseYN = $Row['UseYN']; //사이트 노출
    $Category1 = $Row['Category1']; //과정분류 대분류
    $Category2 = $Row['Category2']; //과정분류 중분류
    $ServiceType = $Row['ServiceType']; //서비스 구분
    $ContentsName = html_quote($Row['ContentsName']); //과정명
    $ContentsTime = $Row['ContentsTime']; //교육시간
    $ContentsStart = substr($Row['ContentsStart'],0,10); //컨텐츠 유효 시작일
    $ContentsEnd = substr($Row['ContentsEnd'],0,10); //컨텐츠 유효 종료일
    $UploadDate = substr($Row['UploadDate'],0,10); //컨텐츠업로드 날짜
    $Mobile = $Row['Mobile']; //모바일 지원
    $BookPrice = $Row['BookPrice']; //교재비
    $attachFile = html_quote($Row['attachFile']); //학습자료
    $PreviewImage = html_quote($Row['PreviewImage']); //과정 이미지
    $BookImage = html_quote($Row['BookImage']); //교재 이미지
    $Intro = $Row['Intro']; //과정소개
    $EduTarget = $Row['EduTarget']; //교육대상
    $EduGoal = $Row['EduGoal']; //교육목표
    $ContentsURLSelect = $Row['ContentsURLSelect']; //컨텐츠 URL 주경로, 예비경로 선택 여부 A:주, B:예비
    $Keyword1 = $Row['Keyword1']; //난이도(직급)
    $Keyword2 = $Row['Keyword2']; //직무분야
    $Keyword3 = $Row['Keyword3']; //관심분야
    $Keyword4 = $Row['Keyword4']; //역량
    $ContentsURL = $Row['ContentsURL']; //컨텐츠URL
    $MobileURL = $Row['MobileURL']; //모바일URL
    $Chapter = $Row['Chapter']; //차시수
    $CourseCnt = $Row['cnt']; //조회수
    $PackageLectureCode = $Row['PackageLectureCode']; //패키지콘텐츠과정코드
    $HrdSeq = $Row['HrdSeq']; //원격훈련일련번호
    $Professor = $Row['Professor']; //교강사
    $Price = $Row['Price']; //교육비용 일반
    $Price01View = $Row['Price01View']; //교육비용 우선지원
    $Price02View = $Row['Price02View']; //교육비용 대규모 1000인 미만
    $Price03View = $Row['Price03View']; //교육비용 대규모 1000인 이상
    $PassTime = $Row['PassTime']; //수료기준 시간
    
    $CategoryName1 = $Row['CategoryName1']; //과정분류 대분류 Name
    $CategoryName2 = $Row['CategoryName2']; //과정분류 중분류 Name
}

if($attachFile) $attachFileView = "<A HREF='./direct_download.php?code=Course&file=".$attachFile."'><B>".$attachFile."</B></a>";
if($PreviewImage) $PreviewImageView = "<img src='/upload/Course/".$PreviewImage."' width='100' align='absmiddle'>";
if($BookImage) $BookImageView = "<img src='/upload/Course/".$BookImage."' height='100' align='absmiddle'>";


$Sql = "SELECT COUNT(*) FROM Member WHERE TestLectureCode='$LectureCode'";
$Result = mysqli_query($connect, $Sql);
$Row = mysqli_fetch_array($Result);
$TestIDCount = $Row[0];
?>
<SCRIPT LANGUAGE="JavaScript">
//컨텐츠 삭제하기
function DelOk() {
	del_confirm = confirm("현재 컨텐츠를 삭제하시겠습니까?");
	if(del_confirm==true) {
		DeleteForm.submit();
	}
}

//차시 삭제하기
function ChapterDelete(mode,LectureCode,Chapter_seq) {
	del_confirm = confirm("클릭한 차시 구성을 삭제하시겠습니까?");
	if(del_confirm==true) {
		document.DeleteForm2.LectureCode.value = LectureCode;
		document.DeleteForm2.Chapter_seq.value = Chapter_seq;
		DeleteForm2.submit();
	}
}

//심사용 테스트 아이디 보기
function TestIDView(LectureCode) {
	var currentWidth = $(window).width();
	var LocWidth = currentWidth / 2;
	var body_width = screen.width - 20;
	var body_height = $('html body').height();

	$("div[id='SysBg_White']")
		.css({
			width: body_width,
			height: body_height,
			opacity: '0.4',
			position: 'absolute',
			'z-index': '99',
		})
		.show();

	$("div[id='Roading']")
		.css({
			top: '450px',
			left: LocWidth,
			opacity: '0.6',
			position: 'absolute',
			'z-index': '200',
		})
		.show();

	$('#DataResult').load('./course_testid_view.php', { LectureCode: LectureCode }, function () {
		$("div[id='Roading']").hide();

		$('html, body').animate({ scrollTop: 200 }, 500);
		$("div[id='DataResult']")
			.css({
				top: '250px',
				width: '1100px',
				left: body_width / 2 - 750,
				opacity: '1.0',
				position: 'absolute',
				'z-index': '1000',
			})
			.show();
	});
}

//심사용테스트아이디 생성
function TestIDCreat() {
	var CreatCount = $('#CreatCount').val();

	Yes = confirm('심사용 테스트 아이디를 ' + CreatCount + '건 생성하시겠습니까?');
	if (Yes == true) {
		TestIDForm.submit();
	}
}
</SCRIPT>
	<div class="contentBody">
    	<h2><?=$MenuName?> 컨텐츠 관리</h2>
        <div class="conZone">
			<input type="hidden" name="LectureCodeValue" id="LectureCodeValue" value="<?=$LectureCode?>">
			<input type="hidden" name="ChapterValue" id="ChapterValue" value="<?=$Chapter?>">
			<form name="DeleteForm" method="post" action="course_script.php" target="ScriptFrame">
				<INPUT TYPE="hidden" name="mode" value="del">
				<INPUT TYPE="hidden" name="idx" value="<?=$idx?>">
				<INPUT TYPE="hidden" name="LectureCode" value="<?=$LectureCode?>">
			</form>
			<form name="DeleteForm2" method="post" action="chapter_regist_script.php" target="ScriptFrame">
				<INPUT TYPE="hidden" name="mode" id="mode" value="del">
				<INPUT TYPE="hidden" name="LectureCode" id="LectureCode">
				<INPUT TYPE="hidden" name="Chapter_seq" id="Chapter_seq">
			</form>
			<form name="TestIDForm" method="post" action="course_testid_creat.php" target="ScriptFrame">
				<INPUT TYPE="hidden" name="LectureCode" id="LectureCode" value="<?=$LectureCode?>">
				<INPUT TYPE="hidden" name="CreatCount" id="CreatCount" value="5">
			</form>
			<?if($AdminWrite=="Y") {?>
			<table width="100%"  border="0" cellspacing="0" cellpadding="0" style="margin-bottom:10px;">
				<tr>
					<td align="right"><a href="Javascript:TestIDView('<?=$LectureCode?>');">[등록된 심사용 테스트 아이디 <?=$TestIDCount?>건 보기]</a>&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="심사용 테스트 아이디 생성" onclick="TestIDCreat()" class="btn_inputLine01"></td>
				</tr>
			</table>
			<?}?>
            <table width="100%" cellpadding="0" cellspacing="0" class="view_ty01">
            	<colgroup>
                    <col width="130px" />
                    <col width="" />
    				<col width="130px" />
                    <col width="" />
    				<col width="130px" />
                    <col width="" />
    				<col width="140px" />
                    <col width="" />
              	</colgroup>
				<tr>
					<th>등급 / 과정코드</th>
					<td align="left"> <?=$ClassGrade_array[$ClassGrade]?>&nbsp;&nbsp;/&nbsp;&nbsp;<span class="redB"><?=$LectureCode?></span></td>
					<th>사이트노출 / <br>컨텐츠 경로</th>
					<td align="left"> 
						<?=$UseYN_array[$UseYN]?>&nbsp;&nbsp;/&nbsp;&nbsp;
						<input type="radio" name="ContentsURLSelect" id="ContentsURLSelect1" value="A" <?if($ContentsURLSelect=="A") {?>checked<?}?> disabled> <label for="ContentsURLSelect1">주 경로</label>&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="ContentsURLSelect" id="ContentsURLSelect2" value="B" <?if($ContentsURLSelect=="B") {?>checked<?}?> disabled> <label for="ContentsURLSelect2">예비 경로</label>
					</td>
					<th>패키지 콘텐츠 과정코드</th>
					<td align="left"> <?=$PackageLectureCode?></td>
					<th>원격훈련일련번호</th>
					<td align="left"> <?=$HrdSeq?></td>
				</tr>
				<tr>
					<th>과정 분류</th>
					<td align="left" colspan="3"><?=$CategoryName1?> > <?=$CategoryName2?></td>
					<th>서비스 구분</th>
					<td align="left"> <?=$ServiceType_array[$ServiceType]?></td>
					<th>교육시간</th>
					<td align="left"><?=$ContentsTime?> 분</td>
				</tr>
				<tr>
					<th>과정명</th>
					<td align="left" colspan="3"><?=$ContentsName?></td>
					<th>조회수</th>
					<td align="left" colspan="3"> <?=$CourseCnt?>회</td>
				</tr>
				<tr>
					<th>난이도(직급)</th>
					<td align="left" colspan="3">
					<?
					$SQL = "SELECT * FROM ContentsKeyword WHERE Category =1 AND idx=$Keyword1";
					$Result = mysqli_query($connect, $SQL);
					$Row = mysqli_fetch_array($Result);
					echo $Row['Keyword'];
					?>
					</td>
					<th>직무분야</th>
					<td align="left" colspan="3">
					<?
					$SQL = "SELECT * FROM ContentsKeyword WHERE Category =2 AND idx=$Keyword2";
					$Result = mysqli_query($connect, $SQL);
					$Row = mysqli_fetch_array($Result);
					echo $Row['Keyword'];
					?>
					</td>
				</tr>
				<tr>
					<th>관심분야</th>
					<td align="left" colspan="3"> 
                    <?
                        $keyword3Arr = explode(',', $Keyword3);
                        $SQLKey3     = " SELECT aValue, idx AS keywordIdx FROM ArchiveQuestion WHERE aType = 'B' AND aDepth = 'step01' AND aGroup = 'A' AND aBind = 'col3' ORDER BY aOrder ASC ";
                        $QUERYKey3   = mysqli_query($connect, $SQLKey3);
                        if( $QUERYKey3 && mysqli_num_rows($QUERYKey3) ) {
                            while( $ROWKey3 = mysqli_fetch_array($QUERYKey3) ) {
                                extract($ROWKey3);
                                if ( in_array($keywordIdx, $keyword3Arr) ) echo "<b>#</b>".$aValue." ";
                            }
                        }
                    ?>
                    </td>
					<th>역량</th>
					<td align="left" colspan="3"> 
                    <?
                        $keyword4Arr =  explode(',', $Keyword4);
                        $SQLKey4   = " SELECT aValue, idx AS keywordIdx FROM ArchiveQuestion WHERE aType = 'B' AND aDepth = 'step01' AND aGroup = 'A' AND aBind = 'col4' ORDER BY aOrder ASC ";
                        $QUERYKey4 = mysqli_query($connect, $SQLKey4);
                        if( $QUERYKey4 && mysqli_num_rows($QUERYKey4) ) {
                            while( $ROWKey4 = mysqli_fetch_array($QUERYKey4) ) {
                                extract($ROWKey4);
                                if ( in_array($keywordIdx, $keyword4Arr) ) echo "<b>#</b>".$aValue." ";
                            }
                        }
                    ?>
                    </td>
				</tr>
				<!-- 
				<tr>
					<th>컨텐츠URL</th>
					<td align="left"><?if($Chapter != "0") echo "-"; else  echo $ContentsURL;?></td>
					<th>모바일URL</th>
					<td align="left" colspan="3"><?if($Chapter != "0") echo "-"; else  echo $MobileURL;?></td>
				</tr>
				 -->
				<tr>
					<th>차시수</th>
					<td align="left"><?if($Chapter != "0") echo $Chapter."차시"; else  echo "없음";?></td>
					<th>컨텐츠 제작연도기간</th>
					<td align="left"><?=$ContentsStart?>  ~ <?=$ContentsEnd?></td>
					<th>컨텐츠 업로드 일자</th>
					<td align="left" colspan="3"><?=$UploadDate?></td>
				</tr>
				<tr>
					<th>교강사</th>
					<td align="left"> <?=$Professor?></td>
					<th>수료기준</th>
					<td align="left"> <?=$PassTime?> 시간 이상</td>
					<th>모바일 지원</th>
					<td align="left"> <?=$UseYN_array[$Mobile]?></td>
					<th>교재비</th>
					<td align="left"> <?=number_format($BookPrice,0)?> 원</td>
				</tr>
				<tr>
					<th>교육비용</th>
					<td align="left" colspan="7"> 
    					<?=number_format($Price,0)?> 원&nbsp;&nbsp;|&nbsp;&nbsp;
    					<span class="redB">환급비용 </span>&nbsp;:&nbsp;
    					우선지원 : <?=number_format($Price01View,0)?> 원&nbsp;&nbsp;/&nbsp;&nbsp;
    					대규모 1000인 미만 : <?=number_format($Price02View,0)?> 원&nbsp;&nbsp;/&nbsp;&nbsp;
    					대구모 1000인 이상 : <?=number_format($Price03View,0)?> 원
					</td>
				</tr>
				<tr>
					<th>참고도서설명</th>
					<td align="left"> <?=$BookIntro?></td>
					<th>학습자료 등록</th>
					<td align="left"><?=$attachFileView?></td>
					<th>과정 이미지</th>
					<td align="left"><?=$PreviewImageView?></td>
					<th>교재 이미지</th>
					<td align="left"><?=$BookImageView?></td>
				</tr>
				<tr>
					<th>과정소개</th>
					<td align="left" colspan="7"><?=$Intro?></td>
				</tr>
				<tr>
					<th>교육대상</th>
					<td align="left" colspan="7"><?=$EduTarget?></td>
				</tr>
				<tr>
					<th>교육목표</th>
					<td align="left" colspan="7"><?=$EduGoal?></td>
				</tr>
            </table>
            <!-- 버튼 -->
			<table width="100%"  border="0" cellspacing="0" cellpadding="0" class="gapT20">
				<tr>
					<?if($AdminWrite=="Y") {?>
					<td align="left" width="150" valign="top"><input type="button" value="컨텐츠 삭제" onclick="DelOk()" class="btn_inputLine01"></td>
					<td align="center" valign="top">
					<input type="button" value="컨텐츠 수정" onclick="location.href='<?=$PageName?>_write.php?mode=edit&idx=<?=$idx?>&col=<?=$col?>&sw=<?=urlencode($sw)?>'" class="btn_inputBlue01"></td>
					<?}?>
					<td width="150" align="right" valign="top"><input type="button" value="목록" onclick="location.href='<?=$PageName?>.php?pg=<?=$pg?>&sw=<?=urlencode($sw)?>&col=<?=$col?>'" class="btn_inputLine01"></td>
				</tr>
			</table>
			<br><br>
			<?if($Chapter != "0"){?>
			<div id="ChapterList"><br><br><br><center><img src="/images/loader.gif" alt="로딩중" /></center></div>
			<br><br><br><br><br><br>
			<?}?>			
        </div>
    </div>
</div>
<script type="text/javascript">
//차시List
function ChapterListRoading() {
	$("div[id='ChapterList']").html('<br><br><br><center><img src="/images/loader.gif" alt="로딩중" /></center>');

	var LectureCodeValue = $('#LectureCodeValue').val();
	var ChapterValue = $("#ChapterValue").val();

	$("div[id='ChapterList']").load('./course_read_list.php', { LectureCode: LectureCodeValue, Chapter: ChapterValue }, function () {});
}

$(window).load(function() {
	ChapterListRoading();
});
</script>
<!-- Footer -->
<? include "./include/include_bottom.php"; ?>