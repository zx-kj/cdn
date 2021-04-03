<?php
error_reporting(0);
header('Content-Type: text/json;charset=UTF-8');

$api = 'https://auth.zapi.ml/ub.php'; //验证服务器接口
$listPass = 'ZxTV'; //获取节目表的密码
$uas = array(); //UA验证，可设置多个UA，为空则不验证UA
$error ='https://error.zapi.ml/m3u8/index.m3u8'; //广告链接

if (isset($_GET['list']) && $_GET['list'] == $listPass) {
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $url = dirname($http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    $list = nget($api . '?list');
    $list = str_replace('UBlive', $url . '/' . basename(__FILE__), $list);
    exit($list);
} elseif (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (!empty($uas) && in_array($user_agent, $uas) == false) {
        header('location:' . $error);
    }
    $id = $_GET['id'];
    $url = cache($id . '.txt', 'nget', [$api . '?id=' . $id], 2 * 3600);
    $token = cache('ubtv_token.txt', 'nget', [$api . '?token'], 3000);
    $headers[] = "playtoken: $token";
    if (!isset($_GET['ts'])) {
        $m3u8 = nget($url, $headers);
        if (strpos($m3u8, "EXTM3U")) {
            $m3u8s = explode("\n", $m3u8);
            $m3u8 = "";
            foreach ($m3u8s as $v) {
                $v = str_replace("\r", '', $v);
                if (strpos($v, ".ts") > 0) {
                    $m3u8 .= basename(__FILE__) . "?id=$id&ts=" . $v . "\n";
                } elseif ($v != '') {
                    $m3u8 .= $v . "\n";
                }
            }
            echom3u8($m3u8);
        }
    } else {
        $index = $_GET['ts'];
        $before = explode("index", $url)[0];
        $url = $before . $index;
        $ts = nget($url, $headers);
        if (isset($ts{500})) {
            echots($ts);
        }
    }
} else {
    header('location:' . $error);
}

function nget($url, $headers = null) {
    if ($headers == null) {
        $headers = array('User-Agent: ZXAPI/1.0.0');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function echom3u8($current) {
    header('Content-Type: application/vnd.apple.mpegurl'); 
    header('Content-Length: ' . strlen($current)); 
    echo $current;
    exit(0);
}

function echots($ts) {
    header('Content-Type: video/mp2t');
    header('Content-Length: ' . strlen($ts));
    echo $ts;
    exit(0);
}

function cache($key, $f_name, $ff = [], $t = ''){
    Cache::$cache_expire = empty($t)?1800:$t;    
    Cache::$cache_path = './cache/';  
    $val = Cache::get($key);
    if (!$val) {
        $data = call_user_func_array($f_name, $ff);  
        Cache::put($key, $data);
        return $data;
    }else {
        return $val;
    }
}

class Cache {
    static $cache_path='cache/';
    static $cache_expire=3600;

    private static function fileName($key){
        return self::$cache_path.$key;
    }

    public static function put($key, $data){
        if(!is_dir(self::$cache_path)){
            mkdir(self::$cache_path, 0777, true);
        }
        if (empty($data)) {
            return false;
        }
        $values = serialize($data);
        $filename = self::fileName($key);
        $file = fopen($filename, 'w');
        if ($file && !empty($data)){
            fwrite($file, $values);
            fclose($file);
        }
        else return false;
    }
    
    public static function get($key){
        $filename = self::fileName($key);
        if (!file_exists($filename) || !is_readable($filename)){
            return false;
        }
        if ( time() < (filemtime($filename) + self::$cache_expire) ) {
            $file = fopen($filename, 'r');
            if ($file){
                $data = fread($file, filesize($filename));
                fclose($file);
                return unserialize($data);
            }
            else return false;
        }
        else return false;
     }
}

?>
