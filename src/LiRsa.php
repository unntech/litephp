<?php
/**
 * RSA算法类
 * 签名及密文编码：base64字符串/十六进制字符串/二进制字符串流
 * 填充方式: PKCS1Padding（加解密）/NOPadding（解密）
 *
 * Notice:Only accepts a single block. Block size is equal to the RSA key size! 
 * 如密钥长度为1024 bit，则加密时数据需小于128字节，加上PKCS1Padding本身的11字节信息，所以明文需小于117字节
 * 超过长度已自动分割加密后拼合返回 @data 2023/06/30
 *
 * @author: UNN.tech
 * @version: 1.0.1
 * @date: 2021/11/21
 */

namespace LitePhp;

class LiRsa {
    protected $pubKey = null;
    protected $priKey = null;
    protected $thirdPubKey = '';
    protected $algorithm = OPENSSL_ALGO_SHA256;
    protected $private_key_bits = 1024;
    /**
     * 自定义错误处理
     */
    private function _error( $msg ) {
        die( 'RSA Error:' . $msg ); //TODO 
    }
    /**
     * 构造函数
     *
     * @param string 公钥文件（验签和加密时传入）
     * @param string 私钥文件（签名和解密时传入）
     */
    public function __construct( $public_key_file = '', $private_key_file = '', $isFile = false, $private_key_bits = 1024 ) {
        if ( $isFile ) {
            $this->_getPublicKey( $public_key_file );
            $this->_getPrivateKey( $private_key_file );
        } else {
            $this->pubKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($public_key_file, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
            $this->priKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap( $private_key_file, 64, "\n", true ) . "\n-----END RSA PRIVATE KEY-----";
        }
        $this->private_key_bits = $private_key_bits;
    }
    
    public function setRsaKey($pubkey,$prikey,$inline=false){
        if($inline){
            $this->pubKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubkey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
            $this->priKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap( $prikey, 64, "\n", true ) . "\n-----END RSA PRIVATE KEY-----";
        }else{
            $this->pubKey = $pubkey;
            $this->priKey = $prikey;
        }
    }

    /**
     * 设置密钥长度
     * @param int $bits
     * @return void
     */
    public function private_key_bits(int $bits = 1024)
    {
        $this->private_key_bits = $bits;
    }

    /**
     * 设置RSA签名算法
     * @param $algorithm
     * @return void
     */
    public function setAlgorithm($algorithm = OPENSSL_ALGO_SHA1)
    {
        $this->algorithm = $algorithm;
    }
    
    /**
     * 生成签名
     *
     * @param string 签名材料
     * @param string 签名编码（base64/hex/bin）
     * @return 签名值
     */
    public function sign( $data, $code = 'base64' ) {
        $priKey = $this->priKey;
        //echo $priKey;
        $ret = false;
        if ( openssl_sign( $data, $ret, $priKey, $this->algorithm ) ) {
            $ret = $this->_encode( $ret, $code );
        }
        return $ret;
    }
    /**
     * 数组生成签名
     *
     * @param array 签名数组
     * @param string 签名编码（base64/hex/bin）
     * @return 包函sign字段的数组
     * 数组签名规则，键值排序，转JSON字符串
     */
    public function signArray( $data, $code = 'base64' ) {
        if(array_key_exists('sign', $data)){
            unset( $data[ 'sign' ] );
        }
        ksort($data);
        $data['sign'] = $this->sign(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $code);
        return $data;
    }
    public function verifySignArray($data, $pubkey='', $code = 'base64'){
        $sign = isset($data['sign']) ? $data['sign'] : '';
        unset($data['sign']);
        ksort($data);
        return $this->verifySign(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),$sign,$pubkey,$code);
    }
    
    /**
     * 验证签名
     *
     * @param string 签名材料
     * @param string 签名值
     * @param string 签名编码（base64/hex/bin）
     * @return bool 
     */
    public function verifySign( $data, $sign, $pubkey = '', $code = 'base64' ) {
        if($pubkey == ''){
            $pubkey = $this->thirdPubKey == '' ? $this->pubKey : $this->thirdPubKey;
        }else{
            $pubkey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubkey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }
        $ret = false;
        $sign = $this->_decode( $sign, $code );
        if ( $sign !== false ) {
            switch ( openssl_verify( $data, $sign, $pubkey , $this->algorithm ) ) {
                case 1:
                    $ret = true;
                    break;
                case 0:
                case -1:
                default:
                    $ret = false;
            }
        }
        return $ret;
    }
    /**
     * 加密
     *
     * @param string 明文
     * @param string 密文编码（base64/hex/bin）
     * @param int 填充方式（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING）
     * @return string 密文
     */
    public function encrypt( $data, $pubkey ='', $code = 'base64', $padding = OPENSSL_PKCS1_PADDING ) {
        if($pubkey == ''){
            $pubkey = $this->thirdPubKey == '' ? $this->pubKey : $this->thirdPubKey;
        }else{
            $pubkey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubkey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }
        $ret = false;
        if ( !$this->_checkPadding( $padding, 'en' ) )$this->_error( 'padding error' );
        $length = (int)($this->private_key_bits / 8 - 11);
        $crypto = '';
        foreach (str_split($data, $length) as $chunk){
            $result = '';
            if (openssl_public_encrypt( $chunk, $result, $pubkey, $padding ) ) {
                $crypto .= $result;
            }
        }
        if ($crypto != '') {
            $ret = $this->_encode( $crypto, $code );
        }
        return $ret;
    }
    /**
     * 解密
     *
     * @param string 密文
     * @param string 密文编码（base64/hex/bin）
     * @param int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @param bool 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
     * @return string 明文
     */
    public function decrypt( $data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false ) {
        $ret = false;
        $data = $this->_decode( $data, $code );
        if ( !$this->_checkPadding( $padding, 'de' ) )$this->_error( 'padding error' );
        if ( $data !== false ) {
            $length = (int)($this->private_key_bits / 8);
            $crypto = '';
            foreach (str_split($data, $length) as $chunk) {
                openssl_private_decrypt($chunk, $decryptData, $this->priKey, $padding);
                $crypto .= $decryptData;
            }
            $ret = $rev ? rtrim( strrev( $crypto ), "\0" ) : '' . $crypto;
        }
        return $ret;
    }
    
    public function SetThirdPubKey($pubkey='', $isFile = false){
        if ( $isFile ) {
            $key_content = $this->_readFile( $pubkey );
            if ( $key_content ) {
                $this->thirdPubKey = openssl_get_publickey( $key_content );
            }
        } else {
            $this->thirdPubKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubkey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }
    }
    
    /**
     * 创建新的公私钥
     *
     * @return 公私钥
     */

    public function createKey(array $options = []){
        $config = empty($options) ? [
            "digest_alg" => "SHA256",
            "private_key_bits" =>$this->private_key_bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ] : $options;
 
 
        //创建密钥对
        $res = openssl_pkey_new($config);
        //生成私钥
        openssl_pkey_export($res, $privkey, null, $config);
        //生成公钥
        $pubKey = openssl_pkey_get_details($res)['key'];
        $ckey['privkey'] = $privkey;
        $ckey['pubkey'] = $pubKey;
        $ckey['priv'] = $this->privone($privkey);
        $ckey['pub'] = $this->pubone($pubKey);
        
        return $ckey;

    }
    
    public function privone($key){
        $key = str_replace("-----BEGIN PRIVATE KEY-----",'',$key);
        $key = str_replace("-----END PRIVATE KEY-----",'',$key);
        $key = str_replace("\n",'',$key);
        return $key;
        
    }
    public function pubone($key){
        $key = str_replace("-----BEGIN PUBLIC KEY-----",'',$key);
        $key = str_replace("-----END PUBLIC KEY-----",'',$key);
        $key = str_replace("\n",'',$key);
        return $key;
    }
    
    // 私有方法 
    /**
     * 检测填充类型
     * 加密只支持PKCS1_PADDING
     * 解密支持PKCS1_PADDING和NO_PADDING
     * 
     * @param int 填充模式
     * @param string 加密en/解密de
     * @return bool
     */
    private function _checkPadding( $padding, $type ) {
        if ( $type == 'en' ) {
            switch ( $padding ) {
                case OPENSSL_PKCS1_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        } else {
            switch ( $padding ) {
                case OPENSSL_PKCS1_PADDING:
                case OPENSSL_NO_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        }
        return $ret;
    }
    private function _encode( $data, $code ) {
        switch ( strtolower( $code ) ) {
            case 'base64':
                $data = base64_encode( '' . $data );
                break;
            case 'hex':
                $data = bin2hex( $data );
                break;
            case 'bin':
            default:
        }
        return $data;
    }
    private function _decode( $data, $code ) {
        switch ( strtolower( $code ) ) {
            case 'base64':
                $data = base64_decode( $data );
                break;
            case 'hex':
                $data = $this->_hex2bin( $data );
                break;
            case 'bin':
            default:
        }
        return $data;
    }
    private function _getPublicKey( $file ) {
        $key_content = $this->_readFile( $file );
        if ( $key_content ) {
            $this->pubKey = openssl_get_publickey( $key_content );
        }
    }
    private function _getPrivateKey( $file ) {
        $key_content = $this->_readFile( $file );
        if ( $key_content ) {
            $this->priKey = openssl_get_privatekey( $key_content );
        }
    }
    private function _readFile( $file ) {
        $ret = false;
        if ( !file_exists( $file ) ) {
            $this->_error( "The file {$file} is not exists" );
        } else {
            $ret = file_get_contents( $file );
        }
        return $ret;
    }
    private function _hex2bin( $hex = false ) {
        $ret = $hex !== false && preg_match( '/^[0-9a-fA-F]+$/i', $hex ) ? pack( "H*", $hex ) : false;
        return $ret;
    }
}


/*------ For Example  ------

$pubKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwv/GzbJ31PpqwvHoNlGdIcDWngJeEhyHZvTGbBvc10910G4MaKy650JLchBNTQPkCMNYJ/uJbHmegWYsZeRROHXdOKvSGaq5fKJhWbS7Dv5lZDyvQQFtrXaTtaYo2W6VHhIlHabF/vDi6PcdaDWV0hcNb5RptKLDS4F0dE7z2c2K+4gh3M35zLnUNJXiew6+AqpiiuQYa++aomVfS4Ou744FCV0AysnsRY4CD0XKrTa/4/kEboWbwvUd8DmlTkxXoJpwNNR/Nu/KSOHBi6kW/j88fFri5++GfWr422i94CtjXy9nwSb6tmyzfIVoOXrrTai3k7jOn+QZqHCGmdFRaQIDAQAB';
$priKey = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDC/8bNsnfU+mrC8eg2UZ0hwNaeAl4SHIdm9MZsG9zXT3XQbgxorLrnQktyEE1NA+QIw1gn+4lseZ6BZixl5FE4dd04q9IZqrl8omFZtLsO/mVkPK9BAW2tdpO1pijZbpUeEiUdpsX+8OLo9x1oNZXSFw1vlGm0osNLgXR0TvPZzYr7iCHczfnMudQ0leJ7Dr4CqmKK5Bhr75qiZV9Lg67vjgUJXQDKyexFjgIPRcqtNr/j+QRuhZvC9R3wOaVOTFegmnA01H8278pI4cGLqRb+Pzx8WuLn74Z9avjbaL3gK2NfL2fBJvq2bLN8hWg5eutNqLeTuM6f5BmocIaZ0VFpAgMBAAECggEBAJTi5HE0HgjQtuln+OxYfkhCS9vTZO4hOEUlCceLsp/2/LaLABCAKije1moeBleSa+9A77N/fBsF9T9JuwaFQHqCi8l0b3PHhd6iwP/UXasCFHpnV0ykAZEbY4ajerchltuh8RLlvnF8jVRhMePaXi1OCqUyRU91ovWovzj6+3dFAGlMDmenpBeiEpDYTy5hYzJ1QI6ywMeouXlhTElhr2PS3aY8K1cYbYeTeAze5rbiAInGVPHy8fAFLKS06yv6MvqQqU+X1ifOC9PP5Usnma0En0hwbJ3mqZS6QnNVTpncdyPMP4xc4sLvphcmiS57UEe40pXfgb9QvoaavCPpgp0CgYEA/GkszNxlYBHH/yCQJfwNIjh9zFi3e2uvwPR6BHGd9dy1kV9NurOSzf4K1ZujsNhOvVCJeElHCNI6h0YIwJwWaVjCP+9ee328oVkdnpm8eVCBacFfnW/FCU/kQF6rs6ZuQuAtiWeSvl/zerkOBYmROhSJQD79TfPbAmBCwGWuGhMCgYEAxcWclphuOrrLopv0llpNFlL2tw0fXuAKo1TbHP7A09Pm5aIUYbncC4TX1yP3ilbEdO4bgMlYmtWjlrODvM1RiXiLreTF1KhxoiVTu2wcSFxmiZI2qv60kp32LAjPl0c2yR+h9koq2wGxq/D4VTOGWIBJPJ242W+JQ11kwI6xVhMCgYEAvXKfWn+NYybVamrhZnEg1m96E/b+eBciSfv03QL94Twv1xWl/JytcgjbzunLWX9w0ezx0SOGuls37LIm/ZHpzFX/LgeWba+49Y0yiwiuiotfJqYqAruSMuQQ2DN2QheHqJAj/X6MiHDyCUl9+bAAHYyuW1crvedqmQTw9QEcRJsCgYBmONfQ9wSykm5CpD1toUsK6OLghoXacg7NkUSX3g0o7/P+aSIDyR81TPqLFuoRtPtiPNg2XtvPW/FsKWlEIxOr7IS14vNmEZJ6brSywRR1Sl0takebZn9K8R6WcA9sb8CfgBwkwv0Xqe59otWYpEMiZ1xzWkp7CK14BkPXS2nZxQKBgAe8Wj9NKHJqtjTNm+kY1JW/MVfAxRvxW1F/wcWGP76ydaG/fRRozkzoIMnnCR7nH8GoDRbN0k2aUtCn+9bBw7S+e4IMKbpKgLJHfp8B4Ou8vKVUkpZR5El3L53D+LzgtJW4X5W0TYdY4XF/h5sRQ6RU8t1gg9VVirV6K57Txr04';

$unnrsa = new LiRsa( $pubKey, $priKey );
$unnrsa->SetThirdPubKey($pubKey);
$a = '测试RSA2';
$sign = $unnrsa->sign( $a );
$y = $unnrsa->verifySign( $a, $sign, $pubKey );
var_dump( $sign, $y );

$arr = array('order'=>'20200826001','money'=>200);
$arr = $unnrsa->signArray($arr);
$y = $unnrsa->verifySignArray($arr);
var_dump($arr,$y);

$x = $unnrsa->encrypt( $a );
$y = $unnrsa->decrypt( $x );
var_dump( $x, $y );

$c = $unnrsa->createKey();
var_dump($c);

--------------*/

?>