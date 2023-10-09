<?php

namespace LitePhp;

class LiHttp {
    
    public static function get($url, $aHeader = null){
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        if($aHeader != null){
            foreach($aHeader as $k=>$v){
                $pHeader[] = "{$k}: {$v}";
            }
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $pHeader);
        }
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if(strtolower(parse_url($url, PHP_URL_SCHEME)) == 'https'){
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        }else{
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        }
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0' );
        $strRes = curl_exec( $ch );
        curl_close( $ch );
        return $strRes;
    }
    
    public static function post($url, $data = null, $aHeader = null){
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        if($aHeader != null){
            foreach($aHeader as $k=>$v){
                $pHeader[] = "{$k}: {$v}";
            }
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $pHeader);
        }
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if(strtolower(parse_url($url, PHP_URL_SCHEME)) == 'https'){
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
        }else{
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        }
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0' );
        $strRes = curl_exec( $ch );
        curl_close( $ch );
        return $strRes;
    }
    
    public static function requestHeaders(){
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        $ret = array_change_key_case($header);

        return $ret;
    }
    
    /**
     * 发送HTTP状态码
     * @param int $status http 状态码
     * @return bool
     */
    public static function sendStatus($status){
        $message = self::getStatusMessage($status);
        if(!headers_sent() && !empty($message)){
            if(substr(php_sapi_name(), 0, 3) == 'cgi'){//CGI 模式
                header("Status: $status $message");
            }else{ //FastCGI模式
                header("{$_SERVER['SERVER_PROTOCOL']} $status $message");
            }
            return true;
        }
        return false;
    }

    /**
     * 发送 HTTP 头部字符集
     * @param string $charset
     * @return bool 是否成功
     */
    public static function sendCharset($charset){
        if(!headers_sent()){
            header('Content-Type:text/html; charset='.$charset);
            return true;
        }
        return false;
    }

    /**
     * 获取HTTP状态码对应描述
     * @param int $status
     * @return string|null
     */
    public static function getStatusMessage($status){
        static $msg = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        ];
        return isset($msg[$status]) ? $msg[$status] : null;
    }

    /**
     * HTTP方式跳转
     * @param string $url 跳转路径
     * @param bool $permanently 是否为长期资源重定向
     */
    public static function redirect($url, $permanently = false){
        self::sendStatus($permanently ? 301 : 302);
        header('Location:'.$url);
        exit(0);
    }
    
    public static function getCertificateInformation(string $domain, int $port=443)
    {
        if(empty($domain)){
            return false;
        }
        // 创建一个流上下文，其中包含一个ssl选项，该选项用于捕获对等证书。
        $streamContext = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        // 使用stream_socket_client函数建立一个安全的SSL连接。连接的目标是在指定的域名后面的端口443上建立连接。函数的参数包括域名，错误号，错误消息，超时时间，连接选项和流上下文。
        $secureConnection = stream_socket_client("ssl://{$domain}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $streamContext);

        if (!$secureConnection) {
            return false;
        }
        // 获取流上下文参数中的SSL信息，并将其赋值给$sslInfo变量。
        $sslInfo = stream_context_get_params($secureConnection);
        //使用openssl_x509_parse函数来解析传入的SSL证书。它首先从$sslInfo数组中获取"options"下的"ssl"数组，然后从中获取"peer_certificate"字段的值，即对等证书。然后将该证书作为参数传递给openssl_x509_parse函数，该函数将解析证书并返回一个关联数组$certInfo，包含了证书的各种信息。
        $certInfo = openssl_x509_parse($sslInfo["options"]["ssl"]["peer_certificate"]);

        return $certInfo;
    }

    public static function getCertificateExpirationTime(string $domain, int $port=443)
    {
        $certInfo = self::getCertificateInformation($domain, $port);
        if($certInfo == false){
            return false;
        }

        return $certInfo["validTo_time_t"] ?? false;
    }
}