<?php
error_reporting(0);
header('Content-Type: text/json;charset=UTF-8');

$ip = getip();
$ips = '
|111.60.49.153
|2409:8a4c:c812:690:656a:bd0f:2e5d:3b35
';

//$api = 'https://www.vipub1818.com/ubpad/';
//$api = 'https://api.zxjm.ml/ubpad/';
$api = 'https://www.twtvcdn.com/ubpad/';
$key = "Tv@Pad20210><iXm";
$iv = random(16);
$time = time();
// $serial = random(16);
// $mac = '002752'  . random(6);
$serial = '87368923748';
$mac = '00275242008e';
$sign = openssl_encrypt('{"app_laguage":2,"brand":"unblocktech","cpu_api":"armeabi-v7a","cpu_api2":"armeabi","device_flag":"4045","mac":"' . $mac . '","model":"S900PROBT","serial":"' . $serial . '","time":' . $time . ',"token":""}', "AES-128-CBC", $key, 0, $iv);
$headers = array("device_info: " . json_encode(array("iv"=>$iv, "sign"=>$sign)), 'User-Agent: okhttp/3.12.0');

//$user_agent = $_SERVER['HTTP_USER_AGENT'];
//if (strpos($ips, '|' . $ip) !== false && $user_agent == 'ZXAPI/1.0.0') {
if (strpos($ips, $ip) !== false) {
    $id = $_GET['id'];

	if (isset($id) && !empty($id) && is_numeric($id)) {
		$c_list = cache('C_list_id.txt', 'getlist', [$api, $key, $iv, $headers, 1], 24 * 3600);
		if (strpos($c_list, "|$id|") !== false) {
			$url = cache("$id.txt", 'geturi', [$api, $id, $key, $iv, $headers], 2 * 3600);
			$token = cache("ubtv_token.txt", 'gettoken', [$api, $key, $headers], 3000);
			$str = json_encode(array('uri' => $url, 'playtoken' => $token), JSON_UNESCAPED_UNICODE);
			exit(stripslashes($str));
		} else {
			exit('该节目ID不存在，请使用正确的节目ID访问。');
		} 
	} elseif (isset($_GET['list'])) {
        $Channel_list = cache("Channel_list.txt", 'getlist', [$api, $key, $iv, $headers], 24 * 3600);
        $Channel_list = str_replace(array('にっぽん','한국','भारत'), array('日本頻道','韩国頻道','印度頻道'), $Channel_list);
        exit($Channel_list);
	} else {
		exit('传递参数不正确。');
	} 
} else {
	$str = json_encode(array('Server IP' => $ip, 'Status' => 'Not authorized'),JSON_UNESCAPED_UNICODE);
	exit(stripslashes($str));
}

function getlist($api, $key, $iv, $headers, $type = null) {
	$headers[] = 'Host: ' . parse_url($api)['host'];
	$headers[] = 'Connection: Keep-Alive';
    $listApi = $api . 'get_pad_live.php';
    //$post = json_encode(array("iv"=>$iv, "sign"=>openssl_encrypt('{"g":1}', "AES-128-CBC", $key, 0, $iv)));
    $con = nget($listApi, $headers);
    $obj = json_decode($con);
    $iv = $obj->{'iv'} ;
    $sign = $obj->{'sign'} ;	
    $decrypt = json_decode('[' . openssl_decrypt(base64_decode($sign), "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv) . ']', 1);
    foreach($decrypt as $return){
        foreach($return['return_live'] as $channellist){
            $output .= $channellist['name'] . "\n";
            foreach($channellist['channel'] as $channel){
                if(empty($type)) {
                    $output .= $channel['title'] . ",UBlive?id=" . $channel['id'] . "\n";
                } else {
                    $output .= "|" . $channel['id'] . "|\n";
                }
            }
        }
    }
    return $output;
}

function geturi($api, $id, $key, $iv, $headers) {
    $geturl = $api . 'geturi.php';
    $post = json_encode(array("iv"=>$iv, "sign"=>openssl_encrypt('{"id":"' . $id . '","re":0}', "AES-128-CBC", $key, 0, $iv)));
    $json = nget($geturl, $headers, $post);
    $json = json_decode($json, true);
    $data = openssl_decrypt($json["sign"], "AES-128-CBC", $key, 0, $json['iv']);
    $data = json_decode($data,true);
    return $data["return_uri"];
}

function gettoken($api, $key, $headers) {
    $gettoken = $api . 'gettoken.php';
    $json = nget($gettoken, $headers);
    $json = json_decode($json, true);
    $playtoken = openssl_decrypt($json["sign"], "AES-128-CBC", $key, 0, $json['iv']);
    $playtoken = json_decode($playtoken, true);
    return $playtoken['return_playtoken'];
}

function nget($url, $headers = NULL, $post = NULL, $getInfo = NULL) {
	if ($headers == NULL) {
		$headers = array('User-Agent: Mozilla/5.0 (Linux; U; Android 4.3; en-us; SM-N900T Build/JSS15J)');
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (!empty($post)) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if (empty($getInfo)) {
		$output = curl_exec($ch);
	} else {
		curl_exec($ch);
		$output = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
	}
	curl_close($ch);
	return $output;
}

function random($length) {
    if ($length == 6) {
        $characters = '0123456789ABCDE';
        $len = 14;
    } else {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $len = 35;
    }
    for ($randomString = '',$i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $len)];
    }
    return $randomString;
}

function getip(){
    $ip=false;
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ips=explode (', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if($ip){
            array_unshift($ips, $ip);
            $ip=FALSE;
        }
        for ($i=0; $i < count($ips); $i++){
            if(!preg_match ('/^(10│172.16│192.168)./i', $ips[$i])){
                $ip=$ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

function cache($key, $f_name, $ff = [], $t = ""){
	Cache::$cache_expire = empty($t)?1800:$t;    
	Cache::$cache_path = "./cache/";  
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
	static $cache_path="cache/";
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
			$file = fopen($filename, "r");
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
