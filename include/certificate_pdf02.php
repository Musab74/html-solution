<?
header("content-type:text/html; charset=EUC-KR");
include "./include_function.php";
require('../lib/fpdf181/korean.php');

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
    //$where[] = "a.ServiceType IN ('X','Y','Z')";
    $where[] = "a.ServiceType IN ('A')";
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
//echo $Sql2;
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
    		
    		$pdf->Image('../images/certi_print_img01.jpg',2.5,2.5,205,292.5,'','');
    		
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
    	
    		$pdf->Ln(10);
    		$contentsName = iconv("UTF-8", "CP949", $ContentsName);    		
    		$pdf->WriteHTML("                �Ʒð��� : �����п����Ʒ� ��ī�̺�");
   
    		$pdf->Ln(10);
    		$start_end = $LectureStart_Year.'�� '.$LectureStart_Month.'�� '.$LectureStart_Day.'�� ~ '.$LectureEnd_Year.'�� '.$LectureEnd_Month.'�� '.$LectureEnd_Day.'�� (15H)';
    		$pdf->WriteHTML("                �ƷñⰣ : ".$start_end);
    		
    		
    		$pdf->Ln(10);
    		$pdf->SetLeftMargin(10);
    		$pdf->Ln(34);
    		$pdf->SetFont('����','',17);
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
			$pdf->Image('../images/company_stamp.png', $imageX, 235,25,26,'','');
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
	alert("<?=$msg?>");
	self.close();
</script>
<?
}
?>
