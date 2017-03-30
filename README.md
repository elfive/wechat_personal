## 2017-03-30  
1、新增加了一个发送邮件的模块，通过引用commenFunction.php之后，直接调用send_mail发送邮件即可；  
  
【send_mail语法】  
send_email($mail_content,$mail_recipient,$mail_subject)  
  
参数说明：  
$mail_content：邮件内容，必填，utf-8编码，支持中文；  
$mail_recipient：邮件接收方，必填；  
$mail_subject：邮件主题，选填，默认为“（无主题）”。  
  
返回值：  
json字符串，包含code和description两个字段：  
code为0表示发送成功；  
code为非零表示发送失败，此时description字段将显示发送失败的原因。  
  
2、更新了微信加密模块中构造函数，修改为了__construct()。  
  
## 2017-03-29
为了clone本仓库的速度，去除了对在线游戏的支持。  
  
## 2017-03-24
修改face++代码，支持同一张图片多人识别。  
  
## 2017-03-20
第一版本php代码  
主要功能如下：  
1、支持上传图片进行人脸识别（性别、年龄、表情）；  
2、支持图灵机器人自动回复，包括执行各种命令，包括但不限于：快递、天气、航班车次、菜谱等等日常生活查询；  
3、支持语音回复，后台能执行语音命令；  
4、支持关键字自定义回复；  
5、图灵机器人通讯加密，微信服务器通讯加密；  
~~6、支持在线玩小游戏且【2048】等支持保存记录，而且不需要微信授权（不获取用户微信信息）。~~  
7、支持新闻分页浏览，已经内置了一个命令。  
   
   
   
   
   
# 使用前的准备：
## face++（人脸识别）接入：
1、注册face++，并获取相关apikey等信息；  
2、打开functions\face_recognition\facepp_sdk.php，修改好你的api(server)地址、api_key和api_secret。  
  
 
## 图灵机器人接入：
1、注册图灵机器人，并获取相关apikey等信息；  
2、打开functions\tuling_bot\tuling_sdk.php，修改好你的apiServer、secret和apiKey；  
3、图灵机器人需要在图灵后台打开加密功能。  
  
  
## 微信接入：
1、如果要开启语音命令功能，需要在微信后台开启语音识别功能，该功能对新关注用户立即生效，对以关注用户在24小时内生效；  
2、微信公众平台后台接入地址要定位到wechat.php文件；  
3、微信消息加解密方式需要使用安全模式：打开wechat.php，将Token、appID和EncodingAESKey修改好；  
4、因为使用了mb_substr等函数，所以需要在php.ini中打开php_mbstring.dll扩展，然后重启apache2或者nginx服务；  
5、打开autoreply.php文件可以设置是否记录消息接收记录和是否启用语音命令功能。
