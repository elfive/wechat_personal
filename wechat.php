<?php
define("TOKEN", "wechat");
define("AppID", "你的appID");
define("EncodingAESKey", "你的EncodingAESKey");

require_once('autoreply.php');

$wechatObj = new wechat_instance();
if (!isset($_GET['echostr'])) {
	$wechatObj->responseMsg();
}else{
	$wechatObj->valid();
}

class wechat_instance
{
	//验证签名和token
	public function valid()
	{
		$echoStr = $_GET["echostr"];
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		$tmpArr = array(TOKEN, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		if($tmpStr == $signature){
			echo $echoStr;
			exit;
		}
	}

	//响应消息
	public function responseMsg()
	{
		$autoReplyObject = new autoReplyBot(TOKEN,EncodingAESKey,AppID);
		$result = $autoReplyObject->response();
	}

}
?>