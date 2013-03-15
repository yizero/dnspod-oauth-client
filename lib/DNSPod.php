<?php
class OAuth
{
    public static $client_id        = '';
    public static $client_secret    = '';
    private static $accessTokenURL  = 'https://www.dnspod.cn/OAuth/Access.Token';
    private static $authorizeURL    = 'https://www.dnspod.cn/OAuth/Authorize';

    /**
     * 初始化
     * @param $client_id 即 app_key
     * @param $client_secret 即 app_secret
     * @return
     */
    public static function init($client_id, $client_secret) {
        if (!$client_id || !$client_secret) exit('client_id or client_secret is null');
        self::$client_id = $client_id;
        self::$client_secret = $client_secret;
    }

    /**
     * 获取授权URL
     * @param $redirect_uri 授权成功后的回调地址，即第三方应用的url
     * @param $response_type 授权类型，为code
     * @return string
     */
    public static function getAuthorizeURL($redirect_uri, $state, $response_type = 'code') {
        $params = array(
            'client_id'     => self::$client_id,
            'redirect_uri'  => $redirect_uri,
            'state'         => $state,
            'response_type' => $response_type,
        );
        return self::$authorizeURL.'?'.http_build_query($params);
    }

    
    public static function getAccessToken( $type = 'code', $keys ) {
        $params                     = array();
        $params['client_id']        = self::$client_id;
        $params['client_secret']    = self::$client_secret;
        if ( $type === 'token' ) {
            $params['grant_type']   = 'refresh_token';
            $params['refresh_token']= $keys['refresh_token'];
        } elseif ( $type === 'code' ) {
            $params['grant_type']   = 'authorization_code';
            $params['code']         = $keys['code'];
            $params['redirect_uri'] = $keys['redirect_uri'];
        } elseif ( $type === 'password' ) {
            $params['grant_type']   = 'password';
            $params['username']     = $keys['username'];
            $params['password']     = $keys['password'];
        } else {
            echo 'type not support';
            return false;
        }

        $response   = Http::request(self::$accessTokenURL, $params);
        $token      = json_decode($response, true);
        if ( !is_array($token) or isset($token['error']) ) {
            echo 'get access token fail';
            return false;
        }
        return $token;
    }

    
    /**
     * 清除授权
     */
    public static function clearOAuthInfo() {
        unset($_SESSION['token']);
    }
}

class DNSPOD
{
    //接口url
    public static $apiUrl   = 'https://www.dnspod.cn/Api/';
    
    //调试模式
    public static $debug    = false;
    
    /**
     * 发起一个API请求
     * @param $command 接口名称 如：Domain.List
     * @param $params 接口参数  如：array('domains' => 'test.com');
     * @return string
     */
    public static function api($command, $params = array()) {
        if (isset($_SESSION['token']['access_token'])) {
            $params['access_token'] = $_SESSION['token']['access_token'];
            $params['oauth_consumer_key'] = OAuth::$client_id;
            $params['clientip'] = Common::getClientIp();
            $params['scope'] = 'all';
            $params['appfrom'] = 'DNSPod OAuth 2.0';
            $params['seqid'] = time();
            $params['use_oauth'] = 'yes';
            $params['format'] = 'json';
            $url = self::$apiUrl.trim($command, '/');
        } else {
            echo json_encode( array('status' => 'error', 'message' => '没有获取到access_token，请重新认证') );
            return false;
        }         
        
        $r = Http::request($url, $params);
        $r = preg_replace('/[^\x20-\xff]*/', "", $r); //清除不可见字符
        //调试信息
        if (self::$debug) {
            echo '接口：'.$url;
            echo '<br/>请求参数：<br/>';
            var_dump($params);
            echo '<br/>返回结果：<br/>';
            var_dump($r);
        }
        return $r;
    }
}

class Http
{
    /**
     * 发起一个HTTP/HTTPS的请求
     * @param $url 接口的URL  'Domain.List';
     * @param $params 接口参数   array('domain'=>'test.com', 'format'=>'json');
     * @return string
     */
    public static function request( $url , $params = array()) {
        if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, 'DNSPod OAuth');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, 3);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        curl_setopt($ci, CURLOPT_POST, TRUE);
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);

        if (!empty($params)) {
            curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ci);
        curl_close ($ci);
        return $response;
    }
}

class Common
{
    public static function getClientIp() {
        if (isset($_SERVER['HTTP_CDN_SRC_IP']) && $_SERVER['HTTP_CDN_SRC_IP']) {
            $onlineip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (getenv('HTTP_X_CLUSTER_CLIENT_IP') && strcasecmp(getenv('HTTP_X_CLUSTER_CLIENT_IP'), 'unknown')) {
            $onlineip = getenv('HTTP_X_CLUSTER_CLIENT_IP');
        } elseif (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $onlineip = $_SERVER['REMOTE_ADDR'];
        }

        preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
        $onlineip = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
        unset($onlineipmatches);
        return $onlineip;
    }
}
