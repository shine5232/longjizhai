<?php
namespace aliyun;

class Send
{   
    protected $_cfg = array();
    public $lastmsg = '';
	public $lastcode = 1;
    public function __construct()
    {
		$this->accessKeyId = 'LTAIgnbDANWBRO2m';
        $this->accessKeySecret = 'S770KmMALtEpKttMHMLXbdasdo9lyJ';
    }

    public function send($mobile, $content){
        $ver = $content['code'];
		$response = $this->sendSms("龙吉宅同城装修共享平台","SMS_189245282","$mobile",Array("code"=>"$ver"),"123");
		$array = get_object_vars($response);
		if($array['Code'] == 'OK'){
            $this->lastcode = 'ok';
            $ret = ['code' => '200','msg'  => 'ok'];
		}else{
			switch($array['Code']){
                case 'isv.MOBILE_NUMBER_ILLEGAL':$error='手机号码不正确';break;
                case 'isv.MOBILE_COUNT_OVER_LIMIT':$error='手机号码数量超过限制';break;
                case 'isv.ACCOUNT_ABNORMAL':$error='账户不合法';break;
                default:$error='未知的错误';
            }
			$this->lastcode = $error;
            $this->lastmsg = $error;
            $ret = ['code' => '0','msg'  => $error];
        }
        return $ret;
    }

	public function sendSms($signName, $templateCode, $phoneNumbers, $templateParam = null, $outId = null, $smsUpExtendCode = null) {
        $params = array (
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
            "PhoneNumbers" => $phoneNumbers,
            "SignName" => $signName,
            "TemplateCode" => $templateCode,
        );
        if($templateParam) {
            $params['TemplateParam'] = json_encode($templateParam);
        }
        if($outId) {
            $params['OutId'] = $outId;
        }
        if($smsUpExtendCode) {
            $params['SmsUpExtendCode'] = $smsUpExtendCode;
        }
        $content = $this->request(
            $this->accessKeyId,
            $this->accessKeySecret,
            "dysmsapi.aliyuncs.com",
            $params
        );
        return $content;
    }
	
	public function request($accessKeyId, $accessKeySecret, $domain, $params) {
        $apiParams = array_merge(array (
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "GET&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));

        $signature = $this->encode($sign);

        $url = "http://{$domain}/?Signature={$signature}{$sortedQueryStringTmp}";

        try {
            $content = $this->fetchContent($url);
            return json_decode($content);
        } catch( \Exception $e) {
            return false;
        }
    }

    public function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    public function fetchContent($url) {
        if(function_exists("curl_init")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "x-sdk-client" => "php/2.0.0"
            ));
            $rtn = curl_exec($ch);

            if($rtn === false) {
                trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
            }
            curl_close($ch);

            return $rtn;
        }

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => array("x-sdk-client: php/2.0.0"),
            )
        ));

        return file_get_contents($url, false, $context);
    }
}