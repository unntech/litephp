<?php
/*--
 * LitePhp 公共函数库
 *
 */
namespace LitePhp;

class LiComm {
    
    public static function is_weixin() {
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger' ) !== false ) {
            return true;
        }
        return false;
    }

    public static function is_alipay() {
        if ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Alipay' ) !== false ) {
            return true;
        }
        return false;
    }

    public static function is_unionpay() {
        if ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'UnionPay' ) !== false ) {
            return true;
        }
        return false;
    }

    public static function check_client() {
        //0:未知或PC 1:微信 2：支付宝 3：云闪付 10:移动端
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger' ) !== false ) {
            return 1;
        } elseif ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Alipay' ) !== false ) {
            return 2;
        } elseif ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'UnionPay' ) !== false ) {
            return 3;
        } elseif ( self::is_mobile()){
            return 10;
        } else {
            return 0;
        }
    }
    
    public static function is_mobile() { 
      // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
      if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
      } 
      // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
      if (isset($_SERVER['HTTP_VIA'])) { 
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
      } 
      // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
      if (isset($_SERVER['HTTP_USER_AGENT'])) {
          if (stripos($_SERVER['HTTP_USER_AGENT'], 'Pad')) return false;  //排除PAD
        $clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');  //,'MicroMessenger'
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
          return true;
        } 
      } 
      // 协议法，因为有可能不准确，放到最后判断
      if (isset ($_SERVER['HTTP_ACCEPT'])) { 
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
          return true;
        } 
      } 
      return false;
    }
    
    public static function createNonceStr( $length = 16 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ( $i = 0; $i < $length; $i++ ) {
            $str .= substr( $chars, mt_rand( 0, strlen( $chars ) - 1 ), 1 );
        }
        return $str;
    }
    
    public static function url2https($url){
		//return str_replace("http://","https://",$url);
		return preg_replace('/^http:/i','https:',$url);
	}
    
    public static function is_robot() {
        $a = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return preg_match("/(spider|bot|crawl|slurp|lycos|robozilla)/i", $a);
    }

    public static function is_ip($ip) {
        //return preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $ip);
        return filter_var($ip, FILTER_VALIDATE_IP) ? 1 : 0;
    }
    
    public static function ip(){
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				if(self::is_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
				$ip = trim(end(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
				if(self::is_ip($ip)) return $ip;
			}
			if(isset($_SERVER['REMOTE_ADDR']) && self::is_ip($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
			if(isset($_SERVER['HTTP_CLIENT_IP']) && self::is_ip($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
			return '0.0.0.0';
    }
    
    /**
     * 根据传入的两地经纬度，返回距离(m)
     * @param float $lng1 经度, $lat1 纬度
     * @param float $lng2 经度, $lat2 纬度
     * @return float  距离(m)
     */
    public static function getDistance( $lng1, $lat1, $lng2, $lat2){   
        $earthRadius = 6367000; //approximate radius of earth in meters   
        $lat1 = ($lat1 * pi() ) / 180;   
        $lng1 = ($lng1 * pi() ) / 180;   
        $lat2 = ($lat2 * pi() ) / 180;   
        $lng2 = ($lng2 * pi() ) / 180;   
        $calcLongitude = $lng2 - $lng1;   
        $calcLatitude = $lat2 - $lat1;   
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);   
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));   
        $calculatedDistance = $earthRadius * $stepTwo;   
        return round($calculatedDistance);   
    }
    
    /**
     * 根据传入的经纬度，和距离范围，返回所有在距离范围内的经纬度的取值范围
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param float $distance 距离(m)
     * @return array
     */
    public static function getSquarePoint($lng, $lat, $distance)
    {
        $earthRadius = 6371004; //地球半径，km
        $d_lng = 2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
        $d_lng = rad2deg($d_lng);
        $d_lat = $distance / $earthRadius;
        $d_lat = rad2deg($d_lat);
        return array(
            'lng_start' => round(($lng-$d_lng),8),//经度开始
            'lng_end' => round(($lng+$d_lng),8), //经度结束
            'lat_start' => round(($lat-$d_lat),8),//纬度开始
            'lat_end' => round(($lat+$d_lat),8) //纬度结束
        );
    }

    /**
     * 获取一个UUID
     * @param bool $separator 为false则取消分隔符-
     * @return string
     */
    public static function uuid(bool $separator = true): string
    {
        $uuid = UUID::v4();
        if(!$separator){
            $uuid = str_replace('-', '', $uuid);
        }
        return $uuid;
    }
    
    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    public static function base64UrlEncode($input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    public static function base64UrlDecode($input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}