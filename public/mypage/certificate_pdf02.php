<?
header("content-type:text/html; charset=EUC-KR");
include "../../include/include_function.php";
require('../../lib/fpdf181/korean.php');

function truncateText($text, $maxLength) {
    if (mb_strlen($text, 'UTF-8') > $maxLength) {
        return mb_substr($text, 0, $maxLength, 'UTF-8'). '...';
    }
    return $text;
}

$CompanyCode = Replace_Check($CompanyCode);
$LectureStart = Replace_Check($LectureStart);
$LectureEnd = Replace_Check($LectureEnd);
$LectureCode = Replace_Check($LectureCode);
$ServiceType = Replace_Check($ServiceType);
$ServiceTypeYN = Replace_Check($ServiceTypeYN);

$where = array();
$where[] = "a.CompanyCode='$CompanyCode'";
$where[] = "a.LectureStart='$LectureStart'";
$where[] = "a.LectureEnd='$LectureEnd'";
if($LectureCode) {
	$where[] = "a.LectureCode='$LectureCode'";
}
$where[] = "a.PassOk='Y'";
if($ServiceTypeYN) {
// 	if($ServiceTypeYN=="Y") {
// 		$where[] = "a.ServiceType=1";
// 	}else{
		$where[] = "a.ServiceType IN ('X','Y','Z')";
// 	}
}else{
	if($ServiceType) {
		$where[] = "a.ServiceType='$ServiceType'";
	}
}
$where = implode(" AND ",$where);
if($where) $where = "WHERE $where";

$orderby = "ORDER BY b.Name ASC";

$NotPDF = "N";

$Sql2 = "SELECT COUNT(a.Seq) FROM Study AS a 
		LEFT OUTER JOIN Member AS b ON a.ID=b.ID 
		LEFT OUTER JOIN Company AS c ON a.CompanyCode=c.CompanyCode 
		LEFT OUTER JOIN Course AS d ON a.LectureCode=d.LectureCode 
		$where";
$Result2 = mysqli_query($connect, $Sql2);
$Row2 = mysqli_fetch_array($Result2);
$TOT_NO = $Row2[0];


if($TOT_NO>0) {
	$NotPDF = "Y";
}

if($NotPDF=="Y") {
	$pdf = new PDF_Korean();
	$pdf->AddUHCFont();
	$pdf->AddPage();
	$pdf->AddUHCFont('����', 'Batang');

	$i = 0;
	$SQL = "SELECT a.ID, a.LectureStart, a.LectureEnd, a.PassOk, a.ServiceType, a.LectureCode, 
			       b.Name, AES_DECRYPT(UNHEX(BirthDay),'$DB_Enc_Key') AS BirthDay, 
			       c.CompanyName, d.ContentsName, d.ContentsTime 
			FROM Study AS a 
			LEFT OUTER JOIN Member AS b ON a.ID=b.ID 
			LEFT OUTER JOIN Company AS c ON a.CompanyCode=c.CompanyCode 
			LEFT OUTER JOIN Course AS d ON a.LectureCode=d.LectureCode 
			$where $orderby";
	//echo $SQL;
	$QUERY = mysqli_query($connect, $SQL);
	if($QUERY && mysqli_num_rows($QUERY)){
    	while($ROW = mysqli_fetch_array($QUERY)){
    		extract($ROW);
    
    		$LectureStart_array = explode("-",$LectureStart);
    		$LectureStart_Year = $LectureStart_array[0];
    		$LectureStart_Month = $LectureStart_array[1];
    		$LectureStart_Day = $LectureStart_array[2];
    		$LectureStart_view = $LectureStart_Year."�� ".$LectureStart_Month."�� ".$LectureStart_Day."��";
    
    		$LectureEnd_array = explode("-",$LectureEnd);
    		$LectureEnd_Year = $LectureEnd_array[0];
    		$LectureEnd_Month = $LectureEnd_array[1];
    		$LectureEnd_Day = $LectureEnd_array[2];
    		$LectureEnd_view = $LectureEnd_Year."�� ".$LectureEnd_Month."�� ".$LectureEnd_Day."��";    
    
    		$resultDate00 = date('Y-m-d');
    		$resultDate01 = substr($resultDate00,0,4);
    		$resultDate02 = substr($resultDate00,5,2);
    		$resultDate03 = substr($resultDate00,8,2);
    		$resultDate = $resultDate01." ��  ".(int)$resultDate02."��  ".(int)$resultDate03."��";    
    
    		if($i>0) {
    		$pdf->AddPage();
    		}
    		
    		$pdf->Image('../../images/certi_print_img01.jpg',2.5,2.5,205,292.5,'','');
    
    		$pdf->Ln(25);
    		$pdf->SetLeftMargin(63);    
    		$pdf->SetFont('����','',32);
    		$pdf->WriteHTML("��      ��      ��");
    
    		$pdf->SetLeftMargin(0);
    		$pdf->Ln(26);
    		$pdf->SetFont('����','',14);
    
    		$userName = iconv("UTF-8", "CP949", $Name);
    		$pdf->WriteHTML("                ��      �� : ".$userName);
    
    		$pdf->Ln(10);
    		$birth = iconv("UTF-8", "CP949", $BirthDay);
    		$pdf->WriteHTML("                ������� : ".$birth);
    
    		$pdf->Ln(16);
    		$companyName = iconv("UTF-8", "CP949", $CompanyName);
    		$pdf->WriteHTML("                �Ҽ�ȸ�� : ".$companyName);
    	
            // ���� ������� _ �������� �ڸ���.
    		$pdf->Ln(10);
    		$contentsName = iconv("UTF-8", "CP949", $ContentsName);    		
    		
//     		if(mb_strlen($contentsName, "UTF-8") < 30){
//     			$pdf->WriteHTML("                �Ʒð��� : ". $contentsName);
    			$pdf->WriteHTML("                �Ʒð��� : �����п����Ʒ� ��ī�̺�");
//     		}else{
//     			$contentsNameArr2 = explode("-",$contentsName);
//     			if(count($contentsNameArr2) == 2){    				
//     				$contentsName1 = $contentsNameArr2[0];
//     				$contentsName2 =  "_" . $contentsNameArr2[1];
//     				$pdf->WriteHTML("                �Ʒð��� : ". $contentsName1);
//     				$pdf->Ln(7);
//     				$pdf->WriteHTML("                                ". $contentsName2);    				
//     			}else{
//     				$contentsNameArr = explode("_",$contentsName);    				
//     				if(count($contentsNameArr) == 5) {
//     					$contentsName1 = $contentsNameArr[0] . "_" . $contentsNameArr[1] . "_" . $contentsNameArr[2];
//     					$contentsName2 =  "_" . $contentsNameArr[3] . "_" . $contentsNameArr[4];
//     					$pdf->WriteHTML("                �Ʒð��� : ". $contentsName1);
//     					$pdf->Ln(7);
//     					$pdf->WriteHTML("                                ". $contentsName2);
//     				}else if(count($contentsNameArr) == 4) {
//     					$contentsName1 = $contentsNameArr[0] . "_" . $contentsNameArr[1];
//     					$contentsName2 =  "_" . $contentsNameArr[2] . "_" . $contentsNameArr[3];
//     					$pdf->WriteHTML("                �Ʒð��� : ". $contentsName1);
//     					$pdf->Ln(7);
//     					$pdf->WriteHTML("                                ". $contentsName2);
//     				} else if(count($contentsNameArr) == 3) {
//     					$contentsName1 = $contentsNameArr[0] . "_" . $contentsNameArr[1];
//     					$contentsName2 =  "_" . $contentsNameArr[2];
//     					$pdf->WriteHTML("                �Ʒð��� : ". $contentsName1);
//     					$pdf->Ln(7);
//     					$pdf->WriteHTML("                                ". $contentsName2);
//     				} else if (count($contentsNameArr) == 2) {
//     					$contentsName1 = $contentsNameArr[0];
//     					$contentsName2 =  "_" . $contentsNameArr[1];
//     					$pdf->WriteHTML("                �Ʒð��� : ". $contentsName1);
//     					$pdf->Ln(7);
//     					$pdf->WriteHTML("                                ". $contentsName2);
//     				} else {
//     					$pdf->WriteHTML("                �Ʒð��� : ". $contentsName);
//     				}
//     			}
//     		}    		
    
    		$pdf->Ln(10);
    		$start_end = $LectureStart_Year.'�� '.$LectureStart_Month.'�� '.$LectureStart_Day.'�� ~ '.$LectureEnd_Year.'�� '.$LectureEnd_Month.'�� '.$LectureEnd_Day.'�� (15H)';
    		$pdf->WriteHTML("                �ƷñⰣ : ".$start_end);
    
    		$pdf->Ln(10);
    
//     		if($ServiceType == 1){
//     			$typeText = '����� �����ɷ°��� �Ʒð���(���ͳ� ����)';
//     		}else if($ServiceType == 3 || $ServiceType == 5 || $ServiceType == 9){
//     			$typeText = '���ͳ� �Ʒð���';
//     		}else if($ServiceType == 4){
//     		    $typeText = "�ٷ��� ��������";
//     		}
    		
//     		$pdf->WriteHTML("                ������� : ".$typeText);
    		$pdf->SetLeftMargin(10);
    		$pdf->Ln(34);
    		$pdf->SetFont('����','',17);
//     		$pdf->Cell(0, 10, '�� ����� �� �������� �ǽ���', 0, 0, 'C');
//     		$pdf->Ln(10);
//     		$pdf->Cell(0, 10, '����� ���������� �� �Ⱓ ���� ������ �����Ͽ�', 0, 0, 'C');
//     		$pdf->Ln(10);
//     		$pdf->Cell(0, 10, '�����Ͽ��⿡ �� ������ �����մϴ�.', 0, 0, 'C');
    		$pdf->Cell(0, 10, '�� ����� ���� ��� �����ɷ� ���߹� ��20�� ��', 0, 0, 'C');
    		$pdf->Ln(10);
    		$pdf->Cell(0, 10, '��24���� ������ ���Ͽ� �� �������� �ǽ���', 0, 0, 'C');
    		$pdf->Ln(10);
    		$pdf->Cell(0, 10, '�� �Ʒ� ������ �� �Ⱓ ���� ������', 0, 0, 'C');
    		$pdf->Ln(10);
    		$pdf->Cell(0, 10, '�����Ͽ��⿡ �� ������ �����մϴ�.', 0, 0, 'C');
     
			$pdf->Ln(30);
			$pdf->SetFont('����','',18);
			$pdf->Cell(0, 10, $resultDate, 0, 0, 'C');			
			$pdf->Ln(32);
			$pdf->SetFont('����','',18);
			$SiteName = iconv("UTF-8", "CP949", $CertSiteName);
			
			$textWidth = $pdf->GetStringWidth($SiteName);
			$pageWidth = $pdf->GetPageWidth();
			$centerX = ($pageWidth - $textWidth) / 2;
			
			$pdf->SetXY($centerX, 245);
			$pdf->Cell($textWidth, 10, $SiteName, 0, 0, 'C');
			$imageX = $centerX + $textWidth - ($textWidth * 0.1);
			$pdf->Image('../../images/company_stamp.png', $imageX, 235,25,26,'','');   
		
			/*//���� ������ -----------------------------------------------------------------------------------------------------
			if($ServiceType == 3){
			    $sql4Chapter = "SELECT ContentsTitle
                                FROM Contents
                                WHERE idx IN (SELECT Sub_idx
                                              FROM Chapter
                                              WHERE LectureCode='$LectureCode')";
			    $query = mysqli_query($connect,$sql4Chapter);
			    $printedChaptersCount = 1;
			    $row_count = mysqli_num_rows($query);
			    if($row_count > 1){
			        while($row = mysqli_fetch_assoc($query)){
			            if($printedChaptersCount % 14 == 1){//���� ������ �� �κ� ���� -------------------------
			                $pdf->AddPage();
			                $pdf->Image('../../images/common/certi_print_img01.jpg',2.5,2.5,205,292.5,'','');
			                $pdf->Ln(15);
			                $pdf->SetLeftMargin(20);
			                $pdf->SetFont('����','',12);
			                $pdf->Cell(0, 1, '(����)', 0, 1, 'L');
			                $pdf->Ln(15);
			                $pdf->SetFont('����', '', 12);
			                $pdf->Cell(0, 1, "��. ������ ��������", 0, 1, 'L');
			                $pdf->Ln(5);
			                
			                //ù ��° ǥ (������ ��������)
			                $pdf->SetFont('����', '', 10);
			                $pdf->Cell(60, 10, "����", 1, 0, 'C');
			                $pdf->Cell(40, 10, "�������", 1, 0, 'C');
			                $pdf->Cell(70, 10, "�Ҽ�ȸ��", 1, 1, 'C');
			                
			                $pdf->Cell(60, 10, $userName, 1, 0, 'C');
			                $pdf->Cell(40, 10, $birth, 1, 0, 'C');
			                $pdf->Cell(70, 10, $companyName, 1, 1, 'C');
			                
			                $pdf->Ln(15);
			                $pdf->SetFont('����', '', 12);
			                if($contentsNameArr2[1] != ""){
			                    $pdf->Cell(0, 1, '��. �Ʒð��� : '. $contentsName1.' : ', 0, 1, 'L');
			                    $pdf->Ln(7);
			                    $pdf->Cell(0, 1, $contentsName2, 0, 1, 'L');
			                }else{
			                    $pdf->Cell(0, 1, '��. �Ʒð��� : '. $contentsName, 0, 1, 'L');
			                }
			                $pdf->Ln(5);
			                
			                $pdf->SetFont('����', '', 10);
			                $pdf->Cell(10, 10, "����", 1, 0, 'C');
			                $pdf->Cell(120, 10, "��������", 1, 0, 'C');
			                $pdf->Cell(20, 10, "�����ð�", 1, 0, 'C');
			                $pdf->Cell(20, 10, "�н�����", 1, 1, 'C');
			            }//--------------------------------------------------------------------------------
			            //�ι�° ǥ
			            $chapterName = iconv("UTF-8", "CP949", $row['ContentsTitle']);
			            $pdf->Cell(10, 10, $printedChaptersCount, 1, 0, 'C');
			            $truncatedChapterName = truncateText($chapterName, 55); // ���ϴ� ���� ����
			            $pdf->Cell(120, 10, $truncatedChapterName, 1, 0, 'C');
			            $pdf->Cell(20, 10, "1H", 1, 0, 'C');
			            $pdf->Cell(20, 10, "P", 1, 1, 'C');
			            
			            if($printedChaptersCount % 14 == 0 || $row_count == $printedChaptersCount){//������ �κ�
			                $pdf->Ln(5);
			                $pdf->Cell(0, 1, "*�н����δ� P(Pass) �Ǵ� F(Fail)�� ǥ��Ǿ����ϴ�.", 0, 1, 'L');
			                $pdf->SetFont('����','',18);
			                $pdf->SetXY($centerX, 260);
			                $pdf->Cell($textWidth, 1, $SiteName, 0, 0, 'C');
			                $imageY = 248;
			                $pdf->Image('../../images/common/company_stamp3.png', $imageX, $imageY, 25, 26, '', '');
			            }
			            $printedChaptersCount++;
			        }
			    }
			}*/
			$i++;
		}
	}
	$FileName = iconv("CP949","UTF-8", "������_".$companyName.".pdf");
	$pdf->Output('D',$FileName,true);
}else{
	$msg = "���᳻���� �����ϴ�.";
	$msg = iconv("CP949","UTF-8",$msg);
?>
<script type="text/javascript">
<!--
	alert("<?=$msg?>");
	self.close();
//-->
</script>
<?
}
?>
