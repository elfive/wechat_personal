<?PHP

class Facepp{
    var $server = 'https://api-cn.faceplusplus.com/facepp/v3';
    var $api_key = '你的face++apikey';
    var $api_secret = '你的apisecret';

    public function __construct($api_key=NULL, $api_secret=NULL, $server=NULL){
        if($api_key){
            $this->api_key = $api_key;
        }
        if($api_secret){
            $this->api_secret = $api_secret;
        }
        if($server){
            $this->server = $server;
        }
    }

    public function execute($method,$picUrl){
        $params=array();
        $params['image_url'] = $picUrl;
        $params['api_key'] = $this->api_key;
        $params['api_secret'] = $this->api_secret;
        $params['return_attributes'] = "gender,age,smiling";//识别数据，请求性别、年龄和是否微笑

        return $this->request("{$this->server}{$method}",$params);
    }

    private function request($request_url , $request_body){
        $useragent = 'Faceplusplus PHP SDK/1.0';
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $request_url);
        curl_setopt($curl_handle, CURLOPT_FILETIME, TRUE);
        curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, FALSE);
        curl_setopt($curl_handle, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curl_handle, CURLOPT_HEADER, FALSE);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5184000);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl_handle, CURLOPT_NOSIGNAL, TRUE);
        curl_setopt($curl_handle, CURLOPT_REFERER, $request_url);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $useragent);
        if (extension_loaded('zlib')){
            curl_setopt($curl_handle, CURLOPT_ENCODING, '');
        }
        curl_setopt($curl_handle, CURLOPT_POST, TRUE);
        if(array_key_exists('img',$request_body)){
            $request_body['img'] = '@'.$request_body['img'];
        }else{
            $request_body=http_build_query($request_body);
        }
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $request_body);
        $response_text = curl_exec($curl_handle);
        $reponse_header = curl_getinfo($curl_handle);
        curl_close($curl_handle);
        return array('http_code'=>$reponse_header['http_code'],'request_url'=>$request_url,'body'=>$response_text);
    }
}