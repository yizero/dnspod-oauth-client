<?php
require_once 'lib/config.php';
require_once 'lib/DNSPod.php';

session_start();
header('Content-Type: text/html; charset=UTF-8');

OAuth::init(APP_KEY, APP_SECRET);
DNSPOD::$debug = false;

//授权通过，你可以在这里执行你的操作，比如说添加一个域名、修改域名的记录...
if (isset($_SESSION['token']['access_token'])) {
    echo "<h1>授权成功</h1>";
    echo "<ul>
            <li><a href='index.php?action=domaincreate'>创建一个新域名</a></li>
            <li><a href='index.php?action=domainlist'>显示用户域名列表</a></li>
          <ul>";
    echo "<br/>";

//没有通过授权，跳转到授权页
} else {
    $url = OAuth::getAuthorizeURL(APP_CALLBACK, rand(10000, 99999999));
    header("Location:{$url}");
    return false;
}


//处理GET请求
if ( isset($_GET['action']) ) {
    switch ( $_GET['action'] ) {
    
        case 'domainlist': {
            $r          = DNSPOD::api('Domain.List');
            $result     = json_decode($r, true);
            $domains    = isset($result['domains'])? $result['domains'] : array();
            foreach ($domains as $k => $v) {
                echo "ID：{$v['id']} | Domain：{$v['name']} | Grade：{$v['grade_title']} | Created_on：{$v['created_on']} <br/ >";            
            }
        } break;
        
        case 'domaincreate': {
            $r          = DNSPOD::api('Domain.Create', array('domain' => time().'.com'));
            $result     = json_decode($r, true);
            var_dump($result);
        } break;

        default : 
            die('Unknow action');
    }
}

?>
