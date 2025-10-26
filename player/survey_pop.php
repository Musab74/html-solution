<?
include "../include/include_function.php"; //DB연결 및 각종 함수 정의
include "../include/login_check.php";

$LectureCode = Replace_Check_XSS2($LectureCode);
$ChkData = Replace_Check_XSS2($ChkData);
?>
<div class="layerArea wid550">
	<div class="title" id="drag">수강후기 등록</div>
	<div class="infoArea">
		<div class="comment_1">
			<ul><li>등록하신 수강후기는 <span class="fcSky01B">나의학습실 &gt; 수강후기</span>에서 확인하실 수 있습니다.</li></ul>
		</div>
		<form name="SurveyForm" method="POST" action="/player/survey_ok.php" target="ScriptFrame">
    		<input type="hidden" name="LectureCode" id="LectureCode" value="<?=$LectureCode?>">
    		<input type="hidden" name="ChkData" id="ChkData" value="<?=$ChkData?>">
    		<div class="info mt20">
    			<table cellpadding="0" class="pan_reg">
    			  <colgroup>
    				  <col width="16%" />
    				  <col width="" />
    			  </colgroup>
    			  <tr>
    				<td class="item">제목</td>
    				<td><input type="text" name="Title" id="Title" placeholder="제목 입력" class="widp100" /></td>
    			  </tr>
    			  <tr>
    				<td class="item">별점</td>
    				<td>
    					<select name="StarPoint" id="StarPoint"  class="wid400">
    						<option value="">선택하세요</option>
    					<?
                        $i = 5;
                        while ($i > 0) {
                        ?>
                            <option value="<?=$i?>"><?=$i?>점</option>
                        <?
                            $i--;
                        }
                        ?>
    					</select>
    				</td>
    			  </tr>
    			  <tr>
    				<td colspan="2"><textarea name="Contents" id="Contents" rows="14" class="widp100" placeholder="내용 입력"></textarea></td>
    			  </tr>
    			</table>
    		</div>
		</form>
		
		<!-- btn -->
		<p class="btnAreaTc02" id="SubmitBtn"><span class="btnSmSky01"><a href="Javascript:uploadSurvey();">등록하기</a></span></p>
		<p id="WaitMag" style="display:none"><br>처리중입니다. 기다려 주세요.</p>
		<!-- area // -->
	</div>
</div>
<script type="text/javascript">
$(document).ready(function() {

	$("#drag").css("cursor","move");

	$("#drag").mouseover(function(){
		$("div[id='StudyInformation']").draggable();
		$("div[id='StudyInformation']").draggable("option","disabled",false);
	})

	$("#drag").mouseleave(function(){
		$("div[id='StudyInformation']").draggable("option","disabled",true);
	});

});
</script>