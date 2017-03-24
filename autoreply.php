<?php
require_once('commenFunction.php');
require_once('functions/wechat_crypt/wxBizMsgCrypt.php');
//注意，因为使用了mb_substr等函数，所以需要在php.ini中打开php_mbstring.dll扩展，然后重启apache2或者nginx服务


//启用记录收发消息到日志功能，只记录明文（解析后加密前）。
define("EnableLogger",true);
//启用语音转命令功能，需要开发者在公众号后台开启语音识别功能，对于新关注者立即生效，对于已关注的人由于缓存功能，24小时候才能生效。
define("voicToMessage",true);

//自动回复机器人
class autoReplyBot
{	
	var $token = "";
	var $appID = "";
	var $encodingAESKey = "";
	var $encrypt_type = "";
	var $nonce = "";
	var $FromUserName = "";

	public function __construct($token,$encodingAESKey,$appID){
		$this->token = $token;
		$this->appID = $appID;
		$this->encodingAESKey = $encodingAESKey;
		$this->encrypt_type = (isset($_GET['encrypt_type']) && ($_GET['encrypt_type'] == 'aes')) ? "aes" : "raw";
		$this->nonce = $_GET["nonce"];

	}

	//自动回复主接口
	public function response()
	{
		$timestamp  = $_GET['timestamp'];
		$msg_signature  = $_GET['msg_signature'];
		$postStr = file_get_contents('php://input');

		if (!empty($postStr)){
			//解密
			$postObj = $this->decryptMessage($this->encrypt_type,$msg_signature,$timestamp,$this->nonce,$postStr);
			$this->FromUserName = $postObj->FromUserName;

			$result = '';
			$replyResult = $this->distributeMsg($postObj,$result);

			//加密
			$responseMsg = $this->encryptMessage($result,$this->encrypt_type,$this->nonce,$this->FromUserName);

			echo $responseMsg;
		}else {
			echo "";
			exit;
		}
	}

	//信息解密
	private function decryptMessage($encrypt_type,$msg_signature,$timestamp,$nonce,$postStr)
	{
		if ($encrypt_type == 'aes'){
			$pc = new WXBizMsgCrypt($this->token, $this->encodingAESKey, $this->appID);
			// $this->logger(" 收到加密信息： \r\n".$postStr);
			$decryptMsg = "";  //解密后的明文
			$errCode = $pc->DecryptMsg($msg_signature, $timestamp, $nonce, $postStr, $decryptMsg);
			$postStr = $decryptMsg;
		}
		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		if(EnableLogger == true)
		{
			$this->logger(" 解析获得明文信息： \r\n".$postStr,$postObj->FromUserName);
		}
		return $postObj;
	}


	//信息加密
	private function encryptMessage($msg,$encrypt_type,$nonce,$userName)
	{
		if(EnableLogger == true)
		{
			$this->logger(" 待发送明文信息： \r\n".$msg,$userName);
		}
		//加密
		if ($encrypt_type == 'aes'){
			$pc = new WXBizMsgCrypt($this->token, $this->encodingAESKey, $this->appID);
			$encryptMsg = ''; //加密后的密文
			$errCode = $pc->encryptMsg($msg, time(), $nonce, $encryptMsg);
			// $this->logger(" 发送加密信息： \r\n".$encryptMsg);
			return $encryptMsg;
		} else {
			return $msg;
		}
	}


	//自动回复消息类型分离函数
	public function distributeMsg($msgObject,&$replyMsg)
	{
		$RX_TYPE = trim($msgObject->MsgType);
		//消息类型分离
		switch ($RX_TYPE)
		{
			case "event":
				$replyMsg = $this->receiveEvent($msgObject);
				break;
			case "text":
				$replyMsg = $this->receiveText($msgObject);
				break;
			case "image":
				$replyMsg = $this->receiveImage($msgObject);
				break;
			case "location":
				$replyMsg = $this->receiveLocation($msgObject);
				break;
			case "voice":
				$replyMsg = $this->receiveVoice($msgObject);
				break;
			case "video":
			case "shortvideo":
				$replyMsg = $this->receiveVideo($msgObject);
				break;
			case "link":
				$replyMsg = $this->receiveLink($msgObject);
				break;
			default:
				$replyMsg = "未知消息类型: ".$RX_TYPE;
				return false;
				break;
		}
		return false;
	}

	//接收事件消息
	private function receiveEvent($object)
	{
		$content = "";
		switch ($object->Event)
		{
			case "subscribe":
				$content = "感谢订阅我的个人公众号，本公众号正在开发中，如果你有好的建议或意见，请留言告诉我。";
				$content .= "\n说出或者回复【菜单】\n可以显示详细命令索引。";
				$content .= "\n或者直接对我下命令也是也已的哦~";
				$content .= "\n\n".'<a href="https://www.elfive.cn">另外也欢迎访问我的个人博客</a>';
				break;
			case "unsubscribe":
				$content = "您已经取消关注本公众号。";
				break;
			case "CLICK":
				switch ($object->EventKey)
				{
					case "COMPANY":
						$content = array();
						$content[] = array("Title"=>"个人家庭网络终端上网速度优化提速--硬件篇", "Description"=>"", "PicUrl"=>"http://www.xinyuejiaoyu.com/uploadfiles/2014610151712933.jpg", "Url" =>"https://www.elfive.cn/boost-your-network-speed-hardware/");
						break;
					default:
						$content = "点击菜单：".$object->EventKey;
						break;
				}
				break;
			case "VIEW":
				$content = "跳转链接 ".$object->EventKey;
				break;
			case "SCAN":
				$content = "扫描场景 ".$object->EventKey;
				break;
			case "LOCATION":
				$content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
				break;
			case "scancode_waitmsg":
				if ($object->ScanCodeInfo->ScanType == "qrcode"){
					$content = "扫码带提示：类型 二维码 结果：".$object->ScanCodeInfo->ScanResult;
				}else if ($object->ScanCodeInfo->ScanType == "barcode"){
					$codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
					$codeValue = $codeinfo[1];
					$content = "扫码带提示：类型 条形码 结果：".$codeValue;
				}else{
					$content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
				}
				break;
			case "scancode_push":
				$content = "扫码推事件";
				break;
			case "pic_sysphoto":
				$content = "系统拍照";
				break;
			case "pic_weixin":
				$content = "相册发图：数量 ".$object->SendPicsInfo->Count;
				break;
			case "pic_photo_or_album":
				$content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
				break;
			case "location_select":
				$content = "发送位置：标签 ".$object->SendLocationInfo->Label;
				break;
			default:
				$content = "收到一个新的事件消息: ".$object->Event;
				break;
		}

		if(is_array($content)){
			$result = $this->transmitNews($object, $content);
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}

	//接收文本消息
	private function receiveText($object)
	{
 		return $this->autoReplay($object);
	}

	//自动回复主函数
	public function autoReplay($object)
	{
		$keyword = trim($object->Content);
		// //多客服人工回复模式
		// if (strstr($keyword, "请问在吗") || strstr($keyword, "在线客服")){
		//   $result = $this->transmitService($object);
		//   return $result;
		// }

		//自动回复模式
		if ($keyword == "菜单" || $keyword == "帮助"){
			$content = "你是需要帮助嘛？";

		// }else if ($keyword == "音乐"){
		// 	$content = array();
		// 	$content = array("Title"=>"最炫民族风", "Description"=>"歌手：凤凰传奇", "MusicUrl"=>"http://mascot-music.stor.sinaapp.com/zxmzf.mp3", "HQMusicUrl"=>"http://mascot-music.stor.sinaapp.com/zxmzf.mp3"); 

		// }else if (strstr($keyword, "表情")){
		// 	$content = "微笑：/::)\n乒乓：/:oo\n中国：".$this->bytes_to_emoji(0x1F1E8).$this->bytes_to_emoji(0x1F1F3)."\n仙人掌：".$this->bytes_to_emoji(0x1F335);
		}else if (mb_substr($keyword,0,1,"utf-8")=="N"){
			$page = (int)mb_substr($keyword,1,NULL,"utf-8");
			$content = array();
			$pages = $this->loadNews($content,"news/".md5($object->FromUserName),$page);
			if($pages>0)
			{	
				unset($content);
				$content = "哎呀，页码输入错误啦，第".(string)$page."页不存在哦~\n\n提示一下，目前只有".$pages."页新闻。";
			} else if($pages == -1) {
				unset($content);
				$content = "暂时还没有新闻哦，没有新闻就是最好的新闻，不是吗？\n你也可以试着回复【我要看新闻】的哦。";
			}
		} else {
			// 图灵机器人回复模式
			if ($keyword == "") {
				return "";
			}
			$contentArray = tuling_chat($keyword,$object->FromUserName);
			$msgCode = $contentArray['code'];
			// Code	说明
			// 100000-文本类；200000-链接类；302000-新闻类；308000-菜谱类；313000-（儿童版）儿歌类；314000-（儿童版）诗词类
			// 40001-参数key错误；40002-请求内容info为空；40004-当天请求次数已使用完；40007-数据格式异常

			switch ($msgCode)
			{
				case 100000:
					$content = $contentArray['text'];
					break;
				case 200000:
					$content = $contentArray['text']."\n\n";
					$content .= '<a href="'.$contentArray['url'].'">点击查看详细信息</a>';
					break;
				case 302000:
					//autoreplay创建图文消息回复
					$newsCount = 0;
					$currentPage = 0;
					$newsPageCount = 0;

					$log_fileFolder = "news/".md5($object->FromUserName);
					if (!file_exists($log_fileFolder)) {
						if(!mkdir($log_fileFolder, 0770)){return false;}
					} else {
						rrmdir($log_fileFolder);
					}

					foreach($contentArray['list'] as $news)
					{
						$newsArticle = $news['article'];
						$newsSource = $news['source'];
						$newsIcon = $news['icon'];
						$newsUrl = $news['detailurl'];
						$newsArray[] = array("Title"=>"[".$newsSource."]".$newsArticle, "Description"=>"", "PicUrl"=>$newsIcon, "Url" =>$newsUrl);
						$newsCount++;
						$currentPage = 1;
						if ($newsCount % 7 == 0) {
							//每篇多图文最多支持8篇文章，所以在这里最后一篇文章用来显示下一页的命令。
							$newsPageCount ++;
							$saveResult = $this->saveNews($newsArray,$log_fileFolder,$newsPageCount);
							unset($newsArray);
							$newsArray = array();
						}
					}

					if (isset($newsArray[0]))
					{
						$newsPageCount ++;
						$saveResult = $this->saveNews($newsArray,$log_fileFolder,$newsPageCount);
					}

					$newsIndexContent = json_encode(array("NewsCount"=>$newsCount,"PageCount"=>$newsPageCount,"CurrentPage"=>$currentPage));
					file_put_contents($log_fileFolder."/index",$newsIndexContent);

					//加载第一页新闻内容
					$content = array();
					$pages = $this->loadNews($content,$log_fileFolder,$currentPage);
					if($pages!=0)
					{	
						unset($content);
						$content = "好像暂时还没有新闻哦，没有新闻就是最好的新闻，不是吗？";
					}
					break;
				case 308000:
					// $content = $contentArray;		//autoreplay创建图文消息回复
					// print_r($contentArray);
					$content = array();
					$recipeCount = 0;
					foreach($contentArray['list'] as $recipe)
					{
						$recipeArticle = "菜谱".(string)($recipeCount+1)."：".$recipe['name'];
						$recipeIcon = $recipe['icon'];
						$recipeInfo = $recipe['info'];
						$recipeUrl = str_replace("tuling", "elfive", $recipe['detailurl']);
						$content[] = array("Title"=>$recipeArticle, "Description"=>$recipeInfo, "PicUrl"=>$recipeIcon, "Url" =>$recipeUrl);
						$recipeCount++;

						if ($recipeCount >= 8) {
							//每篇多图文最多支持8篇文章
							break;
						}
					}

					if ($recipeCount==0)
					{
						unset($content);
						$content = "抱歉抱歉，这道菜我现在也在学着做，等会学会了再告诉你哦~";
					}
					break;
				case 313000:
					// 儿童版专有功能，免费版用不了。
					// $content = $contentArray;		//autoreplay创建图文消息回复
					break;
				case 314000:
					// 儿童版专有功能，免费版用不了。
					// $content = $contentArray;		//autoreplay创建图文消息回复
					break;
				case 40001:
					$content = "啊，不好意思~\n因为管理员把门钥匙丢了，所以我没办法用电脑回复你...\n建议你打【40001】投诉管理员！";
					break;
				case 40002:
					$content = "咦？你好像什么也没说诶...\n如果你不愿意和我说话的话\n可以悄悄告诉管理员错误码是【40002】吗？\n他应该很乐意听你说的~";
					break;
				case 40004:
					$content = "哼！小气的管理员。\n不让我吃饭，我都饿死了，我要罢工！\n除非你告诉管理员，让他给我买【40004】个茶叶蛋我就原谅他。";
					break;
				case 40007:
					$content = "管理员这个笨蛋\n管理员在旁边叽叽喳喳的！害得我没听清你说的话...\n你把他拖出去枪毙【40007】分钟吧，这样我就能听清楚你说话啦~";
					break;
				default:
					$content = "啊，不好意思，我好晕\n刚刚头上出现了【".$msgCode."】颗星星\n害得我没有听清，你可以试着再说一遍嘛~";
			}

		}

		if(is_array($content)){
			if (isset($content[0])){
				$result = $this->transmitNews($object, $content);
			}else if (isset($content['MusicUrl'])){
				$result = $this->transmitMusic($object, $content);
			}
		}else{
			$result = $this->transmitText($object, $content);
		}
		return $result;
	}

	//接收图片消息
	private function receiveImage($object)
	{
		//人脸识别
		$content = face_recognition($object->PicUrl[0]);
		//回复识别结果
		$result = $this->transmitText($object, $content);


		//直接将图片返回给用户
		// $content = array("MediaId"=>$object->MediaId);
		// result = $this->transmitImage($object, $content);
		return $result;
	}

	//接收位置消息
	private function receiveLocation($object)
	{
		$content = "你发送的是位置，经度为：".$object->Location_Y."；纬度为：".$object->Location_X."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//接收语音消息
	private function receiveVoice($object)
	{

		if (isset($object->Recognition) && !empty($object->Recognition)){
			//需要在后台开启语音识别功能
			if (voicToMessage == true) {
				$object->Content = mb_substr($object->Recognition,0,-1,"utf-8");//去掉句子最后的句号。
				return $this->autoReplay($object);
			} else {
				$content = "你刚才说的是：".$object->Recognition;
				$result = $this->transmitText($object, $content);
			}
		}else{
			//将用户说的话直接返回给用户
			$content = array("MediaId"=>$object->MediaId);
			$result = $this->transmitVoice($object, $content);
		}
		return $result;
	}

	//接收视频消息
	private function receiveVideo($object)
	{
		$content = "上传视频类型：".$object->MsgType;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//接收链接消息
	private function receiveLink($object)
	{
		$content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
		$result = $this->transmitText($object, $content);
		return $result;
	}

	//回复文本消息
	private function transmitText($object, $content)
	{
		if (!isset($content) || empty($content)){
			return "";
		}

		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content></xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);

		return $result;
	}

	//回复图文消息
	private function transmitNews($object, $newsArray)
	{
		if(!is_array($newsArray)){
			return "";
		}
		$itemTpl = "<item><Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url></item>";
		$item_str = "";
		foreach ($newsArray as $item){
			$item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
		}
		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>$item_str</Articles></xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
		return $result;
	}

	//回复音乐消息
	private function transmitMusic($object, $musicArray)
	{
		if(!is_array($musicArray)){
			return "";
		}
		$itemTpl = "<Music><Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<MusicUrl><![CDATA[%s]]></MusicUrl>
<HQMusicUrl><![CDATA[%s]]></HQMusicUrl></Music>";

		$item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
$item_str</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复图片消息
	private function transmitImage($object, $imageArray)
	{
		$itemTpl = "<Image><MediaId><![CDATA[%s]]></MediaId></Image>";

		$item_str = sprintf($itemTpl, $imageArray['MediaId']);

		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
$item_str</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复语音消息
	private function transmitVoice($object, $voiceArray)
	{
		$itemTpl = "<Voice><MediaId><![CDATA[%s]]></MediaId></Voice>";

		$item_str = sprintf($itemTpl, $voiceArray['MediaId']);
		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
$item_str</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复视频消息
	private function transmitVideo($object, $videoArray)
	{
		$itemTpl = "<Video><MediaId><![CDATA[%s]]></MediaId>
<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description></Video>";

		$item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
$item_str</xml>";

		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//回复多客服消息
	private function transmitService($object)
	{
		$xmlTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[transfer_customer_service]]></MsgType></xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
		return $result;
	}

	//字节转Emoji表情
	function bytes_to_emoji($cp)
	{
		if ($cp > 0x10000){ # 4 bytes
			return chr(0xF0 | (($cp & 0x1C0000) >> 18)).chr(0x80 | (($cp & 0x3F000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x800){   # 3 bytes
			return chr(0xE0 | (($cp & 0xF000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x80){  # 2 bytes
			return chr(0xC0 | (($cp & 0x7C0) >> 6)).chr(0x80 | ($cp & 0x3F));
		}else{	# 1 byte
			return chr($cp);
		}
	}

	//保存新闻
	public function saveNews($newsArray,$folderPath,$pageNumber)
	{
		if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
			$newsFilename = $folderPath."/news-".$pageNumber.".json";
			file_put_contents($newsFilename,json_encode($newsArray));
			return true;
		}
	}

	//加载新闻内容，并填充第8条或最后一条内容
	//返回-1表示暂时没有新闻，返回0表示成功，返回正数表示输入页码错误，返回值表示目前只有多少页新闻。
	public function loadNews(&$newsArray,$folderPath,$pageNumber = -1)
	{
		if(!file_exists($folderPath."/index"))
		{
			return -1;
		}
		$newsIndexArray = json_decode(file_get_contents($folderPath."/index"),true);
		$PageCount = $newsIndexArray["PageCount"];
		$newsCount = $newsIndexArray["NewsCount"];
		$currentPage = $newsIndexArray["CurrentPage"];

		if ($pageNumber == -1) {
			$pageNumber = $currentPage + 1;
		}

		if($pageNumber > $PageCount || $pageNumber <= 0)
		{
			//页码输入有误，返回false
			return $PageCount;
		}

		$newsFilename = $folderPath."/news-".$pageNumber.".json";
		if (file_exists($newsFilename)) {
			$newsArray = json_decode(file_get_contents($newsFilename),true);

			if ($pageNumber>=1 && $pageNumber < $PageCount) {
				//多页，非末页
				$title = "回复【N".($pageNumber+1)."】查看下一页。（单击我可不能进入下一页的）";
			} else if($pageNumber == $PageCount && $PageCount != 1){
				//多页，末页
				$title = "回复【N1】查看首页、【N".($pageNumber-1)."】查看上一页。（这已经是最后一页啦）";
			} else {
				//只有一页的
				$title = "目前只有这么多新闻啦！";
			}

			$newsArray[] = array("Title"=>$title, "Description"=>"", "PicUrl"=>"", "Url" =>"");
			$newsIndexArray["CurrentPage"] = $pageNumber;
			file_put_contents($folderPath."/index", json_encode($newsIndexArray));
			return 0;
		} else {
			//新闻文件不存在，返回false
			return $PageCount;
		}
	}

	//日志记录
	public function logger($log_content,$user='system')
	{
		if(EnableLogger != true)
		{
			return;
		}
		if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
			sae_set_display_errors(false);
			sae_debug($log_content);
			sae_set_display_errors(true);
		}else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
			$max_size = 500000;
			if ($user == "") {
				$user = "Error";
			}
			$log_filename = "log/".$user.".log";
			if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size))
				{
					unlink($log_filename);
				}
			file_put_contents($log_filename, "==========================================================================\r\n".date('Y-m-d H:i:s').$log_content."\r\n==========================================================================\r\n\r\n", FILE_APPEND);
		}
	}
}
?>