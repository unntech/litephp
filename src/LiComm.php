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
        //0:未知或PC 1:微信 2：支付宝 3：云闪付
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger' ) !== false ) {
            return 1;
        } elseif ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Alipay' ) !== false ) {
            return 2;
        } elseif ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'UnionPay' ) !== false ) {
            return 3;
        } else {
            return 0;
        }
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
        return preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $ip);
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
}