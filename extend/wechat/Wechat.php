<?php
namespace wechat;

class Wechat{
    //默认配置
    protected $config = [
        'AppID'             => 'wxa7a7a29d68f18d89', // 公众号AppID
        'AppSecret'         => '6c04295980e098de9a0e084dbf0c6c67', // 公众号AppSecret
    ];
    /**
     * 通过code换取网页授权access_token
     */
    public function getAccessTokenByCode($code){
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->config['AppID']."&secret=".$this->config['AppSecret']."&code=$code&grant_type=authorization_code";
        $res = json_decode(self::get($url), true);
        if(isset($res['errcode'])){
            return false;
        }else{
            $ref = $this->refreshAccessTocken($res['refresh_token']);
            if($ref){
                return $ref;
            }
        }
    }
    /**
     * 刷新access_token
     */
    public function refreshAccessTocken($refresh_token) {
        $url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=".$this->config['AppID']."&grant_type=refresh_token&refresh_token=$refresh_token";
        $res = json_decode(self::get($url), true);
        if(isset($res['errcode'])){
            return false;
        }else{
            return $res;
        }
    }
    /**
     * 获取用户信息
     */
    public function getUserInfo($code){
        $res = $this->getAccessTokenByCode($code);
        if($res){
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$res['access_token']."&openid=".$res['openid']."&lang=zh_CN";
            $ref = json_decode(self::get($url), true);
            return $ref;
        }else{
            return false;
        }
    }
    /**
     * 生成签名包
     */
    public function getSignPackage($url) {
        $jsapiTicket = $this->getJsApiTicket();
        if($jsapiTicket){
            $timestamp = time();
            $nonceStr = $this->createNonceStr();
            // 这里参数的顺序要按照 key 值 ASCII 码升序排序
            $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
            $signature = sha1($string);
            $signPackage = array(
                "appId"     => $this->config['AppID'],
                "nonceStr"  => $nonceStr,
                "timestamp" => $timestamp,
                "url"       => $url,
                "signature" => $signature,
                "rawString" => $string
            );
            return $signPackage;
        }else{
            return false;
        }
    }
    /**
     * 生成随机字符串
     */
    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    /**
     * 获取jsapi_ticket
     */
    public function getJsApiTicket() {
        if ($ticket = cache('ticket')) {
            return $ticket;
        } else {
            $accessToken = $this->getAccessToken();
            if($accessToken){
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
                $ref = json_decode(self::get($url),true);
                if (isset($ref['errcode']) && $ref['errcode'] != 0) {
                    return false;
                }else{
                    cache('ticket',$ref['ticket'],$ref['expires_in']);
                    return $ref['ticket'];
                }
            }
        }
    }
    /**
     * 获取基础AccessToken
     */
    public function getAccessToken() {
        if ($access_token = cache('access_token_base')) {
            return $access_token;
        } else {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->config['AppID']."&secret=".$this->config['AppSecret'];
            $ref = json_decode(self::get($url), true);
            if (isset($ref['errcode'])) {
                return false;
            }else{
                cache('access_token_base',$ref['access_token'],$ref['expires_in']);
                return $ref['access_token'];
            }
        }
    }
    /**
     * @method 封装curl get请求
     * @static
     * @param  {string}
     * @return {string|boolen}
     */
    public static function get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        # curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!curl_exec($ch)) {
            error_log(curl_error($ch));
            $data = '';
        } else {
            $data = curl_multi_getcontent($ch);
        }
        curl_close($ch);
        return $data;
    }
    /**
     * @method 封装curl post请求
     * @static
     * @param  {string}        $url URL to post data to
     * @param  {string|array}  $data Data to be post
     * @return {string|boolen} Response string or false for failure.
     */
    public static function post($url, $data) {
        if (!function_exists('curl_init')) {
            return '';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        # curl_setopt( $ch, CURLOPT_HEADER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($ch);
        if (!$data) {
            error_log(curl_error($ch));
        }
        curl_close($ch);
        return $data;
    }
}