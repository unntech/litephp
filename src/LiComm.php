<?php
/*--
 * LitePhp 公共函数库
 *
 */
namespace LitePhp;

class LiComm {

    /**
     * 微信终端
     * @return bool
     */
    public static function is_weixin(): bool
    {
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger' ) !== false ) {
            return true;
        }
        return false;
    }

    /**
     * 支付宝终端
     * @return bool
     */
    public static function is_alipay(): bool
    {
        if ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Alipay' ) !== false ) {
            return true;
        }
        return false;
    }

    /**
     * 云闪付终端
     * @return bool
     */
    public static function is_unionpay(): bool
    {
        if ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'UnionPay' ) !== false ) {
            return true;
        }
        return false;
    }

    /**
     * 检测终端类型
     * @return int 0:未知或PC 1:微信 4:企业微信 2：支付宝 3：云闪付 10:移动端
     */
    public static function check_client(): int
    {
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'wxwork' ) !== false ) {
            return 4;
        } elseif ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger' ) !== false ) {
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

    /**
     * 判断是否移动设备手机
     * @return bool
     */
    public static function is_mobile(): bool
    {
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
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');  //,'MicroMessenger','android'
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

    /**
     * 创建随机字符串
     * @param int $length
     * @return string
     */
    public static function createNonceStr(int $length = 16 ): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ( $i = 0; $i < $length; $i++ ) {
            $str .= substr( $chars, mt_rand( 0, strlen( $chars ) - 1 ), 1 );
        }
        return $str;
    }
    
    public static function url2https($url)
    {
		//return str_replace("http://","https://",$url);
		return preg_replace('/^http:/i','https:',$url);
	}
    
    public static function is_robot()
    {
        $a = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return preg_match("/(spider|bot|crawl|slurp|lycos|robozilla)/i", $a);
    }

    public static function is_ip($ip): int
    {
        //return preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $ip);
        return filter_var($ip, FILTER_VALIDATE_IP) ? 1 : 0;
    }
    
    public static function ip()
    {
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if(self::is_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            $_array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim(end($_array));
            if(self::is_ip($ip))
                return $ip;
        }
        if(isset($_SERVER['REMOTE_ADDR']) && self::is_ip($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER['HTTP_CLIENT_IP']) && self::is_ip($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];
        return '0.0.0.0';
    }

    /**
     * 根据传入的两地经纬度，返回距离(m)
     * @param float $lng1 经度
     * @param float $lat1 纬度
     * @param float $lng2 经度
     * @param float $lat2 纬度
     * @return float 距离(m)
     */
    public static function getDistance(float $lng1, float $lat1, float $lng2, float $lat2): float
    {   
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
    public static function getSquarePoint(float $lng, float $lat, float $distance): array
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
    public static function base64UrlEncode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    public static function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    
    /**
     * 获取从当天0点起的秒数，0 - 86400
     * @param int $dt 0:当前时间，其它为指定时间
     * @return int
     */
    public static function getSecondInDay(int $dt = 0): int
    {
        $DT_TIME = $dt == 0 ? time() : $dt;
        $today = getdate($DT_TIME);
        $tbtime = mktime( 0, 0, 0, $today[ 'mon' ], $today[ 'mday' ], $today[ 'year' ] );
        return $DT_TIME - $tbtime;
    }

    public static function secondsToString(int $seconds): string
    {
        if($seconds < 60){
            $str = $seconds . '秒';
        }elseif($seconds < 3600){
            $str = floor($seconds / 60) . '分'. $seconds % 60 . '秒';
        }else{
            $str = floor($seconds / 3600) . '时' . round(($seconds % 3600) / 60) . '分';
        }
        return $str;
    }

    /**
     * 浏览器友好的变量输出
     *
     * @param  mixed        $var     变量
     * @param  boolean      $echo    是否输出 默认为True 如果为false 则返回输出字符串
     * @param  string|null  $label   标签 默认为空
     * @param  boolean      $strict  是否严谨 默认为true
     */
    public static function dv($var, bool $echo = true, string $label = null, bool $strict = true)
    {
        $label = (null === $label) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        } else {
            return $output;
        }
    }

    /**
     * 命令行输出进度条
     * @param int $total
     * @param int $current
     * @param int $barLength
     * @return void
     */
    public static function consoleProgressBar(int $total, int $current, int $barLength = 30)
    {
        $percent = ($current / $total) * 100;
        $filledLength = floor($barLength * $percent / 100);
        $bar = str_repeat('=', $filledLength) . str_repeat(' ', $barLength - $filledLength);
        echo "\r {$current}/{$total} [{$bar}] " . number_format($percent, 2) . "%";
        if ($current == $total) {
            echo "\n";
        }
    }
}