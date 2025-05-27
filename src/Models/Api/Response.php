<?php

namespace LitePhp\Models\Api;

use LitePhp\SnowFlake;
use LitePhp\Encrypt\RSA;
use LitePhp\Encrypt\ECDSA;

class Response
{
    protected static $signType = 'NONE';
    protected static $secret = '';
    protected static $private_key = '';
    protected static $private_key_bits = 1024;
    protected static $public_key = '';
    protected static $headers = [];
    protected static $encrypted = false;
    protected static $encryption = 'RSA';
    protected static $instance;

    /**
     * @param array $options <p><br>
     * [ 'secret'=>'', <br>
     *   'private_key'=>'', <br>
     *   'private_key_bits'=>1024, <br>
     *   'public_key'=>'', <br>
     *   'signType'=>'SHA256', <br>
     *   'headers'=>[] <br>
     *   'encrypted'=>true <br>
     * ]</p>
     * @return static
     */
    public static function instance(array $options = [])
    {
        if(isset($options['secret'])){
            self::$secret = $options['secret'];
        }
        if(isset($options['private_key'])){
            self::$private_key = $options['private_key'];
        }
        if(isset($options['private_key_bits'])){
            self::$private_key_bits = $options['private_key_bits'];
        }
        if(isset($options['public_key'])){
            self::$public_key = $options['public_key'];
        }
        if(isset($options['signType'])){
            self::$signType = $options['signType'];
        }
        if(isset($options['headers'])){
            self::$headers = $options['headers'];
        }
        if(isset($options['encrypted'])){
            self::$encrypted = $options['encrypted'];
        }
        if(isset($options['encryption'])){
            self::$encryption = $options['encryption'];
        }
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 获取签名方式
     * @return string
     */
    public function getSignType(): string
    {
        return self::$signType;
    }

    /**
     * 设置secret
     * @param string $secret
     * @return static
     */
    public static function secret(string $secret)
    {
        self::$secret = $secret;
        return self::instance();
    }

    /**
     * 设置签名类型
     * @param string $signType MD5 | SHA256 | RSA | ECDSA
     * @return static
     */
    public static function signType(string $signType)
    {
        self::$signType = $signType;
        return self::instance();
    }

    /**
     * 设置RSA私钥
     * @param string $privateKey 私钥
     * @param int $bits 私钥长度位
     * @return static
     */
    public static function privateKey(string $privateKey, int $bits = 1024)
    {
        self::$private_key = $privateKey;
        return self::instance();
    }

    /**
     * 设置RSA公钥
     * @param string $publicKey 公钥
     * @return static
     */
    public static function publicKey(string $publicKey)
    {
        self::$public_key = $publicKey;
        return self::instance();
    }

    /**
     * 设置输出公共 header 参数值
     * @param string $headers
     * @return static
     */
    public static function headers(array $headers = [])
    {
        self::$headers = $headers;
        return self::instance();
    }

    /**
     * 设置输出数据为加密
     * @param bool $encrypted
     * @return static
     */
    public static function encrypted(bool $encrypted = true)
    {
        self::$encrypted = $encrypted;
        return self::instance();
    }

    /**
     * 设置加密类型
     * @param string $encryption RSA | ECIES | RSAIES
     * @return static
     */
    public static function encryption(string $encryption = 'RSA')
    {
        self::$encryption = $encryption;
        return self::instance();
    }

    /**
     * 输出完成数据
     * @param array $data
     * @param int $errCode
     * @param string $msg
     * @return void
     */
    public static function success(array $data = [], int $errCode = 0, string $msg = 'success')
    {
        $ret = [
            'head'     => [
                'errcode'   => $errCode,
                'msg'       => $msg,
                'unique_id' => $_SERVER['UNIQUE_ID'] ?? 'id_' . SnowFlake::generateParticle(),
                'timestamp' => time(),
            ],
            'body'     => $data,
            'signType' => self::$signType,
        ];
        self::response($ret);
    }

    /**
     * 输出错误代码信息
     * @param int $errCode
     * @param string $msg
     * @param array $data
     * @return void
     */
    public static function error(int $errCode = 0, string $msg = 'fail', array $data = [])
    {
        $ret = [
            'head'     => [
                'errcode'   => $errCode,
                'msg'       => $msg,
                'unique_id' => $_SERVER['UNIQUE_ID'] ?? 'id_' . SnowFlake::generateParticle(),
                'timestamp' => time(),
            ],
            'body'     => $data,
            'signType' => self::$signType,
        ];
        self::response($ret);
    }

    /**
     * 请求数据生成签名
     * @param array $data
     * signType 提供 MD5、SHA256、RSA、ECDSA，验签时json encode增加中文不转unicode和不转义反斜杠两个参数
     * @return array
     */
    protected static function _generate(array $data) : array
    {
        if(empty($data['body'])){
            $data['body'] = ['data'=>''];
        }
        if(!empty(self::$headers) && is_array(self::$headers)){
            $data['head'] += self::$headers;
        }
        $data['encrypted'] = false;
        $data['bodyEncrypted'] = '';
        if(self::$encrypted){
            switch (self::$encryption){
                case 'ECIES':
                    $ecdsa = new ECDSA(self::$public_key, self::$private_key);
                    $_enda = $ecdsa->encrypt(json_encode($data['body'], JSON_UNESCAPED_SLASHES));
                    if($_enda !== false) { //加密成功
                        $data['encrypted'] = true;
                        $data['bodyEncrypted'] = $_enda['ciphertext'];
                        $data['body'] = ['data'=>'encrypted'];
                        $_encryption = [
                            'type'          => 'ECIES',
                            'tempPublicKey' => $_enda['tempPublicKey'],
                            'iv'            => $_enda['iv'],
                            'code'          => $_enda['code'],
                            'mac'           => $_enda['mac'],
                        ];
                    }
                    break;
                case 'RSAIES':
                    $rsa = new RSA(self::$public_key, self::$private_key);
                    $_enda = $rsa->encrypt_ies(json_encode($data['body'], JSON_UNESCAPED_SLASHES));
                    if($_enda !== false) { //加密成功
                        $data['encrypted'] = true;
                        $data['bodyEncrypted'] = $_enda['ciphertext'];
                        $data['body'] = ['data'=>'encrypted'];
                        $_encryption = [
                            'type'   => 'RSAIES',
                            'cipher' => $_enda['cipher'],
                            'iv'     => $_enda['iv'],
                            'code'   => $_enda['code'],
                            'mac'    => $_enda['mac'],
                        ];
                    }
                    break;
                default:
                    $rsa = new RSA(self::$public_key, self::$private_key);
                    $_enda = $rsa->encrypt(json_encode($data['body'], JSON_UNESCAPED_SLASHES));
                    if($_enda !== false){ //加密成功
                        $data['encrypted'] = true;
                        $data['bodyEncrypted'] = $_enda;
                        $data['body'] = ['data'=>'encrypted'];
                    }
                    $_encryption = ['type'=>'RSA'];
            }
            $data['encryption'] = $_encryption;
        }
        if(isset($data['signType']) && $data['signType'] != 'NONE') {
            $head = $data['head'];
            ksort($head);
            $body = $data['body'];
            ksort($body);
            $_signString = json_encode($head,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $data['bodyEncrypted'];
            switch($data['signType']){
                case 'MD5':
                    $signString = $_signString . self::$secret;
                    $sign = strtoupper(md5($signString));
                    $data['sign'] = $sign;
                    break;
                case 'SHA256':
                    $signString = $_signString . self::$secret;
                    $sign = strtoupper(hash("sha256", $signString));
                    $data['sign'] = $sign;
                    break;
                case 'RSA':
                    $signString = $_signString ;
                    if(empty(self::$private_key)){
                        $data['sign'] = '';
                    }else{
                        $rsa = new RSA(self::$public_key, self::$private_key);
                        $sign = $rsa->sign($signString);
                        $data['sign'] = $sign === false ? '' : $sign;
                    }
                    break;
                case 'ECDSA':
                    $signString = $_signString ;
                    if(empty(self::$private_key)){
                        $data['sign'] = '';
                    }else{
                        $ecdsa = new ECDSA(self::$public_key, self::$private_key);
                        $sign = $ecdsa->sign($signString);
                        $data['sign'] = $sign === false ? '' : $sign;
                    }
                    break;
                default:
                    $data['signType'] = 'NONE';
                    $data['sign'] = '';
            }
        }

        return $data;
    }

    /**
     * 接口数据输出
     * @param array $data
     * signType 提供 MD5、SHA256验签时json encode增加中文不转unicode和不转义反斜杠两个参数
     * @return void
     */
    protected static function response(array $data)
    {
        $data = self::_generate($data);

        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit(0);
    }
}