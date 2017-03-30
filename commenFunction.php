<?php
	//获取UTC任意时区时间戳
	function get_timestamp_from_timezone($tz_offset = 0)
	{
		if ($tz_offset > 14 || $tz_offset < -12)
			$tz_offset = 0; // timezone offset range: -12 ~ 14
		return time() + mktime(0, 0, 0, 1, 1, 1970) + (($tz_offset -1) * 60 * 60);
	}

	//清空文件夹内所有文件和文件夹
	function rrmdir($src) {
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				$full = $src . '/' . $file;
				if ( is_dir($full) ) {
					rrmdir($full);
				}
				else {
					unlink($full);
				}
			}
		}
		closedir($dir);
		// rmdir($src);
	}

	//人脸识别，根据用户发送的图片识别图中人物
	function face_recognition($picUrl)
	{
		require_once 'functions/face_recognition/facepp_sdk.php';
		$facepp = new Facepp();
		$pic_url = (string)$picUrl[0];
		$response = $facepp->execute('/detect',$pic_url);
		// print_r($response);
		$jsonArray = json_decode((string)$response['body'],true);
		$recoRes = "";
		$faceCount = 0;

		foreach($jsonArray['faces'] as $face)
		{
			$gender = $face['attributes']['gender']['value'];
			if ($gender == "Female") {
				$gender = "美女";
			} else {
				$gender = "帅哥";
			}
			
			$age = $face['attributes']['age']['value'];
			$threshold = $face['attributes']['smile']['threshold'];
			$value = $face['attributes']['smile']['value'];

			$expression = "";
			if ($value > $threshold) {
				//表情为笑脸
				$expression = "面带微笑";
			} else {
				//表情不带笑容
				$expression = "不带笑容";
			}
			$faceCount ++;
			$recoRes .= "识别结果：\n人物".$faceCount."\n性别：".$gender."\n年龄：".$age."\n表情：".$expression."\n\n";
		}
		if ($faceCount == 0) {
			$recoRes = "抱歉，我好像在图片中看不到一个人诶，你是不是上传错了图片呐~";
		}

		return $recoRes;
	}


	function tuling_chat($msg,$wechatUserID)
	{
		require_once 'functions/tuling_bot/tuling_sdk.php';

		$tuling_bot = new tulingBot();
		$response = $tuling_bot->excute($msg,md5($wechatUserID));

		$jsonStr = json_decode((string)$response['body'],true);

		return $jsonStr;	
	}

	function send_email($mail_content,$mail_recipient,$mail_subject)
	{
		require_once 'functions/email/email.php';
		$mail = new mailer();
		$res = json_decode($mail->excute($mail_recipient,$mail_subject,$mail_content),true);

		if ($res['code'] == 0) {
			return "邮件发送成功";
		} else {
			return "邮件发送失败：\n".$res['description'];
		}
	}

	//回复第三方接口消息
	function replyThirdParty($url, $rawData)
	{
		$headers = array("Content-Type: text/xml; charset=utf-8");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
?>