<?

include "../include/include_function.php"; //DB연결 및 각종 함수 정의


    $emon_agentPk = "hrda1746";
    $emon_API_KEY = "RJ8dij3FDeSYjkMupuor39SiYcC+J0FhhtzvSB2PZLE=";

    $requestArr["dataList"]=[];

    $data = array();
    $data['agtId']         = $_REQUEST['AGTID'];
	$data['usrId']         = $_REQUEST['USRID'];
	$data['courseAgentPk'] = $_REQUEST['COURSE_AGENT_PK'];
	$data['classAgentPk']  = $_REQUEST['CLASS_AGENT_PK'];
	$data['evalCd']        = $_REQUEST['EVAL_CD'];
	$data['evalType']      = $_REQUEST['EVAL_TYPE'];
	$data['classTme']      = $_REQUEST['CLASS_TME'];
	$data['mRet']          = $_REQUEST['m_Ret'];
	$data['mRetCd']        = $_REQUEST['m_retCD'];
	$data['mTrnId']        = $_REQUEST['m_trnID'];
	$data['mTrnDt']        = $_REQUEST['m_trnDT'];
	
	// $data['regDate']       = date("Y-m-d H:i:s");
	// $data['tracseTme']     = $_REQUEST['CLASS_TME'];

    array_push($requestArr["dataList"], $data);

    $data = json_encode($requestArr, JSON_UNESCAPED_UNICODE);

	//URL 및 헤더 설정
	$url = "https://emonapi-server.hrdkorea.or.kr/api/v2/phone_auth_hist";
	$headers = array (
		"Content-Type: application/json",
		"X-TQIAPI-HEADER: ".$emon_API_KEY, "X-TQIAPI-USER: ".$emon_agentPk
	);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$response = curl_exec($ch);

	$json_result_arr = json_decode($response, true);
	$result_code = $json_result_arr['code'];
	$result_msg = $json_result_arr['msg'];
	$result_cnt = $json_result_arr['data_cnt'];
    
	if(!$result_cnt) {
		$result_cnt = 0;
	}

	// echo $data;

	curl_close($ch);

	echo $result_code; 
?>