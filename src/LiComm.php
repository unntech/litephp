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
    
}