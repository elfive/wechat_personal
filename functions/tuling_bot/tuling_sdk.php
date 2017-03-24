<?php
//http://www.tuling123.com/help/h_cent_webapi.jhtml?nav=doc
class tulingBot
{
	var $apiKey = "你的图灵apikey";
	var $apiServer = "http://www.tuling123.com/openapi/api";
    var $secret = "你的图灵加密的secret";//需要在图灵后台打开加密功能。
    var $iv = [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00];

    public function __construct($apiKey=NULL,$apiServer=NULL,$secret=NULL,$iv=NULL){
        if($apiKey){
            $this->apiKey = $apiKey;
        }
        if($apiServer){
            $this->apiServer = $apiServer;
        }
        if($secret){
            $this->secret = $secret;
        }
        if($iv){
            $this->iv = $iv;
        }
    }


	public function excute($info,$userid,$loc="")
	{
        $param = [
            'key' => $this->apiKey,
            'info' => $info,
            'userid' => $userid,
            'loc' => $loc,
        ];

        return $this->request($param);
	}

    private function request($param){
        $timestamp = time();
        $request_url = $this->apiServer;
        
        $encryptedBody = [
            'key' => $this->apiKey,
            'timestamp' => $timestamp,
            'data' => self::encrypt($param,$timestamp),
        ];
        $postdata = json_encode($encryptedBody);
        $useragent = 'tuling PHP SDK/1.0';
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $request_url);
        curl_setopt($curl_handle, CURLOPT_FILETIME, TRUE);
        curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, FALSE);
        curl_setopt($curl_handle, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5184000);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl_handle, CURLOPT_NOSIGNAL, TRUE);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $useragent);
        if (extension_loaded('zlib')){
            curl_setopt($curl_handle, CURLOPT_ENCODING, '');
        }
        curl_setopt($curl_handle, CURLOPT_POST, TRUE);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);

        $response_text = curl_exec($curl_handle);
        $reponse_header = curl_getinfo($curl_handle);
        curl_close($curl_handle);
        return array('http_code'=>$reponse_header['http_code'],'request_url'=>$request_url,'body'=>$response_text);
    }

    private function encrypt($param,$timestamp)
    {
        $keyParam = $this->secret . $timestamp . $this->apiKey;
        $key = md5($keyParam);
        $str = json_encode($param);
        $pad = 16 - (strlen($str) % 16);
        $str .= str_repeat(chr($pad), $pad);
        $key = hash('md5', $key, true);
        $iv = implode(array_map("chr", $this->iv));;
        $td = mcrypt_module_open('rijndael-128', '', 'cbc', '');
        mcrypt_generic_init($td, $key, $iv);
        $encrypted = mcrypt_generic($td, $str);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return base64_encode($encrypted);
    }
}
?>