<?php
/*
 * Model:   PHPMailer SDK
 * Auther:  elfive
 * Version: 1.0
 * Date:    2017.03.29
 */
require 'PHPMailer/PHPMailerAutoload.php';

class mailer
{
	var $smtp_host = "smtp.qq.com";								// 指定smtp服务器，可以指定多个，第一个为主smtp服务器，每个smtp服务器用英文分号(;)隔开
	var $smtp_port = 465;										// smtp访问的tcp端口号
	var $smtp_secure = "ssl";									// 加密方式，tls和ssl可选
	var $smtp_from_useraccount = "your_mail_address@qq.com";	// SMTP用户名
	var $smtp_from_username = "your_mail_address";				// SMTP用户
	var $smtp_from_userpass = "your_mail_password";				// SMTP密码
	var $mail = NULL;

	public function __construct($host=NULL,$port=NULL,$secure=NULL,$from_useraccount=NULL,$from_userpass=NULL,$from_username=NULL)
	{	
		if($host && $port && $secure && $from_useraccount && $from_userpass){
			$this->smtp_host = $host;
			$this->smtp_port = $port;
			$this->smtp_secure = $secure;
			$this->smtp_from_useraccount = $from_useraccount;
			$this->smtp_from_userpass = $from_userpass;
		}
		else if($host || $port || $secure || $from_useraccount || $from_userpass)
		{
			return $this->build_response_json(103,"missing parameter!");
		}

		$this->mail = new PHPMailer;									// 实例化
		$this->mail->setLanguage('zh_cn', 'PHPMailer/language/');		// 设置语言为简体中文
		// $this->mail->SMTPDebug = 3;									// 调试日志输出级别
		$this->mail->isSMTP();											// 启用smtp发送邮件
		$this->mail->Host = $this->smtp_host;							// 指定smtp服务器，可以指定多个，第一个为主smtp服务器，每个smtp服务器用英文分号(;)隔开
		$this->mail->SMTPAuth = true;									// 启用smtp鉴权
		$this->mail->Username = $this->smtp_from_useraccount;			// SMTP用户名
		$this->mail->Password = $this->smtp_from_userpass;				// SMTP密码
		$this->mail->SMTPSecure = $this->smtp_secure;					// 加密方式，tls和ssl可选
		$this->mail->Port = $this->smtp_port;							// smtp访问的tcp端口号

		if ($from_username) {
			$this->mail->setFrom($this->mail->Username, $from_username);		// 为发件人指定一个名称，其中发件人邮箱必须和之前smtp授权邮箱一致。
		} else {
			$this->mail->setFrom($this->mail->Username, $this->smtp_from_username);
		}
	}

	public function excute($mail_recipient,$mail_subject,$mail_content,$mail_isHTML=false,$mail_attachments=NULL,$mail_replyto=NULL,$mail_cc=NULL)
	{
		$this->mail->addAddress($mail_recipient);							// 添加一个收件人，后面的名称可选

		if ($mail_cc) {
			$this->mail->addCC($mail_cc);									// 添加一个抄送，后面的名字可选
		}

		if ($mail_replyto) {
			$this->mail->addReplyTo($mail_replyto);							// 添加一个回复人，后面的名字可选
			// $this->mail->addBCC('bcc@example.com');						// 添加另一个抄送，后面的名字可选
		}

		$this->mail->Subject = $mail_subject;								// 邮件主题
		$this->mail->Body = $mail_content;									// 邮件内容
		$this->mail->isHTML($mail_isHTML);									// 指定邮件内容是否是html格式文本

		if ($mail_attachments) {
			$this->mail->addAttachment($mail_attachments);					// 添加一个本地附件
			// $this->mail->addAttachment('/tmp/image.jpg', 'new.jpg');		// 添加一个本地附件，后面的文件名可选
		}

		if(!$this->mail->send()) {
			return $this->build_response_json(201,$this->mail->ErrorInfo);
		} else {
			return $this->build_response_json(0,"mail successfully sent!");
		}
	}

	private function build_response_json($code,$description)
	{
		$send_result = array('code' => $code, 'description' => $description);
		return json_encode($send_result);
	}
}




?>