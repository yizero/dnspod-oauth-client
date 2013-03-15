 <?php
session_start();
require_once 'lib/config.php';
require_once 'lib/DNSPod.php';

OAuth::init(APP_KEY, APP_SECRET);
DNSPOD::$debug = false;

if (isset($_REQUEST['code'])) {
    try {
        $params = array('code' => $_REQUEST['code'], 'redirect_uri' => APP_CALLBACK );
        $token  = OAuth::getAccessToken('code', $params) ;
    } catch (OAuthException $e) {
    
    }
    if (empty($token)) {
        echo "授权失败！";
        return false;
    } else {
        $_SESSION['token'] = $token;
        echo "授权成功，<a href='index.php'>开始APP</a>";
    }

} elseif(isset($_REQUEST['error'])) {
    echo $_REQUEST['error_description'];

} else {
    $url = OAuth::getAuthorizeURL(APP_CALLBACK, rand(10000, 99999999));
    echo "<a href='{$url}'>开始认证</a>";
}

?>
