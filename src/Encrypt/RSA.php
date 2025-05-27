<?php

namespace LitePhp\Encrypt;

/**
 * ## RSA算法类
 * 签名及密文编码：base64字符串/十六进制字符串/二进制字符串流
 * 填充方式: PKCS1Padding（加解密）/NOPadding（解密）
 *
 * Notice:Only accepts a single block. Block size is equal to the RSA key size!
 * 如密钥长度为1024 bit，则加密时数据需小于128字节，加上PKCS1Padding本身的11字节信息，所以明文需小于117字节
 * 超过长度已自动分割加密后拼合返回
 *
 *
 * ### RSAIES 加密方式详解
 *  生成随机AES密钥，使用 RSA 加密方法对其加密
 *  生成随机AES密钥iv值
 *  用随机AES密钥对数据进行AES-128-CFB加密，参数 OPENSSL_RAW_DATA
 *  密文和iv值进行base64处理（支持HEX）
 *  使用SHA256计算哈希值(mac)，用于接收者验证数据完整性
 *  把 加密的随机AES密钥 `cipher` 向量 `iv` 编码方式 `code` 密文哈希值 `mac` 加密类型 `RSAIES` 放入 encryption 字段
 *
 * ### RSAIES 解密方式详解
 *  把接收到的密文使用SHA256计算哈希值，验证mac值是否相同，判定数据是否完整
 *  把接收到的 加密的随机AES密钥 `cipher` 编码还原 `base64_decode` 后，使用 RSA 解密方法对其解密得到AES密钥原文
 *  用得到的随机AES密钥和收到的向量 `iv` 采用 aes-128-cfb 进行解密， 参数 OPENSSL_RAW_DATA
 *  得到原文
 *
 *
 * @author: Jason
 * @version: 1.0.1
 * @date: 2025/05/27
 */

class RSA
{
    protected $private_key = null;
    protected $third_public_key = null;
    protected $self_public_key = null;
    protected $algorithm = OPENSSL_ALGO_SHA256;
    protected $private_key_bits = 1024;

    /**
     * @param string $third_public_key 对方公钥
     * @param string $private_key 自己私钥
     */
    public function __construct(string $third_public_key='', string $private_key='')
    {
        $this->third_public_key = Encode::convert_public_key_string($third_public_key);
        $this->private_key = Encode::convert_private_key_string($private_key);
    }

    /**
     * 设置自己私钥
     * @param string $private_key
     * @return $this
     */
    public function setPrivateKey(string $private_key): RSA
    {
        $this->private_key = Encode::convert_private_key_string($private_key);
        return $this;
    }

    /**
     * 设置对方公钥
     * @param string $third_public_key
     * @return $this
     */
    public function setThirdPublicKey(string $third_public_key): RSA
    {
        $this->third_public_key = Encode::convert_public_key_string($third_public_key);
        return $this;
    }

    /**
     * 设置自己公钥
     * @param string $self_public_key
     * @return $this
     */
    public function setSelfPublicKey(string $self_public_key): RSA
    {
        $this->self_public_key = Encode::convert_public_key_string($self_public_key);
        return $this;
    }

    /**
     * 设置私钥长度
     * @param int $bit 默认384
     * @return $this
     */
    public function setPrivateKeyBits(int $bit = 2048): RSA
    {
        $this->private_key_bits = $bit;
        return $this;
    }

    /**
     * @param int $algorithm
     * @return $this
     */
    public function setAlgorithm(int $algorithm = OPENSSL_ALGO_SHA384): RSA
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * 数据生成签名
     * @param string $data 需签名数据
     * @param string $code 默认使用base64编码返回签名 （支持 base64 | hex | bin）
     * @return false|string
     */
    public function sign(string $data, string $code = 'base64' )
    {
        $private_key = $this->private_key;
        $signature = false;
        if ( openssl_sign( $data, $signature, $private_key, $this->algorithm ) ) {
            $signature = Encode::encode( $signature, $code );
        }

        return $signature;
    }

    /**
     * 验证数据签名
     * @param string $data
     * @param string $sign
     * @param string $code
     * @return bool true成功；false 失败
     */
    public function verifySign(string $data, string $sign, string $code = 'base64' ): bool
    {
        $pubkey = $this->third_public_key;
        $ret = false;
        $sign = Encode::decode( $sign, $code );
        if ( !empty($sign) ) {
            switch ( openssl_verify( $data, $sign, $pubkey , $this->algorithm ) ) {
                case 1: //有效
                    $ret = true;
                    break;
                case 0: //无效
                case -1: //出错
                default:
                    $ret = false;
            }
        }

        return $ret;
    }

    /**
     * 数组生成签名
     * 规则：键值升序排序，转JSON字符串(增加中文不转unicode和不转义反斜杠两个参数)生成签名
     * @param array $data
     * @param string $code 签名编码（base64/hex/bin）
     * @return array 包函sign字段的数组
     */
    public function signArray(array $data, string $code = 'base64' ): array
    {
        if(array_key_exists('sign', $data)){
            unset( $data[ 'sign' ] );
        }
        ksort($data);
        $data['sign'] = $this->sign(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $code);
        return $data;
    }

    /**
     * 验证签名数组
     * @param array $data
     * @param string $code
     * @return bool
     */
    public function verifySignArray(array $data, string $code = 'base64'): bool
    {
        $sign = $data['sign'] ?? '';
        unset($data['sign']);
        ksort($data);
        return $this->verifySign(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $sign, $code);
    }

    /**
     * RSA 加密
     * @param string $plaintext 明文
     * @param string $code 密文编码（base64/hex/bin）
     * @param int $padding 填充方式（貌似php有bug，所以目前仅支持OPENSSL_PKCS1_PADDING）
     * @return false|string
     */
    public function encrypt(string $plaintext, string $code = 'base64', int $padding = OPENSSL_PKCS1_PADDING )
    {

        $publicKey = $this->third_public_key;
        $ret = false;
        if ( !$this->_checkPadding( $padding, 'en' ) )
            return false;
        $_bits = Encode::pem_key_bits($publicKey);
        if($_bits === false){
            return false;
        }
        $length = (int)( $_bits / 8 - 11);
        $crypto = '';
        foreach (str_split($plaintext, $length) as $chunk){
            $result = '';
            if (openssl_public_encrypt( $chunk, $result, $publicKey, $padding ) ) {
                $crypto .= $result;
            }
        }
        if ($crypto != '') {
            $ret = Encode::encode( $crypto, $code );
        }
        return $ret;
    }

    /**
     * RSA 解密
     * @param string $ciphertext 密文
     * @param string $code 密文编码（base64/hex/bin）
     * @param int $padding 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @param bool $rev 是否翻转明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
     * @return false|string 明文
     */
    public function decrypt(string $ciphertext, string $code = 'base64', int $padding = OPENSSL_PKCS1_PADDING, bool $rev = false )
    {
        $ret = false;
        $data = Encode::decode( $ciphertext, $code );
        if ( !$this->_checkPadding( $padding, 'de' ) )
            return false;
        if ( $data !== false ) {
            $_bits = Encode::pem_key_bits($this->private_key);
            if($_bits === false){
                return false;
            }
            $length = (int)($_bits / 8);
            $crypto = '';
            foreach (str_split($data, $length) as $chunk) {
                openssl_private_decrypt($chunk, $decryptData, $this->private_key, $padding);
                $crypto .= $decryptData;
            }
            $ret = $rev ? rtrim( strrev( $crypto ), "\0" ) : $crypto;
        }
        return $ret;
    }

    /**
     * RASIES加密
     * 生成随机AES密钥，使用 RSA 加密方法对其加密
     * 使用AES-128-CFB加密文本，参数选 OPENSSL_RAW_DATA
     * @param string $plaintext 明文数据
     * @param string $code 密文编码支持 base64 | hex | bin
     * @return false | array <p><br>
     *     [ 'cipher' => '加密的AES密钥', <br>
     *       'iv'     => 'iv',  <br>
     *       'code'   => 'base64', <br>
     *       'ciphertext' => '密文', <br>
     *       'mac'    => '密文SHA256哈希' <br>
     *     ]
     * </p>
     */
    public function encrypt_ies(string $plaintext, string $code = 'base64', int $padding = OPENSSL_PKCS1_PADDING )
    {
        $publicKey = $this->third_public_key;
        if ( !$this->_checkPadding( $padding, 'en' ) )
            return false;
        // 生成随机对称密钥
        $cipher_method = 'aes-128-cfb';
        $symmetricKey = openssl_random_pseudo_bytes(16); // 使用 AES-128 密钥长度
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
        // 使用公钥加密对称密钥（使用 RASIES 过程）
        openssl_public_encrypt( $symmetricKey, $encryptedKey, $publicKey, $padding );
        // 使用对称密钥加密消息（AES-128-CFB）
        $encryptedMessage = openssl_encrypt($plaintext, $cipher_method, $symmetricKey, OPENSSL_RAW_DATA, $iv);
        $ciphertext = Encode::encode($encryptedMessage, $code );
        //使用SHA256计算密文哈希值
        $mac = strtoupper(hash("sha256", $ciphertext));

        // AES密钥cipher、向量iv、密文及哈希值
        return [
            'cipher_method' => $cipher_method,
            'cipher'        => Encode::encode($encryptedKey, $code),
            'iv'            => Encode::encode($iv, $code),
            'code'          => $code,
            'ciphertext'    => $ciphertext,
            'mac'           => $mac,
        ];
    }

    /**
     * RASIES 解密
     * 使用RSA解密方法对 $cipher 解密，得到AES密钥
     * 使用AES-128-CFB解密密文，参数选 OPENSSL_RAW_DATA，得到明文
     * @param string $ciphertext 密文
     * @param string $cipher 加密的AES密钥
     * @param string $iv AES加密向量
     * @param string|null $mac 密文哈希值
     * @param string $code 编码
     * @param int $padding 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @return string|null
     */
    public function decrypt_ies(string $ciphertext, string $cipher = '', string $iv = '', ?string $mac = null, string $code = 'base64', int $padding = OPENSSL_PKCS1_PADDING): ?string
    {
        // 解密对称密钥
        openssl_private_decrypt(Encode::decode($cipher, $code), $symmetricKey, $this->private_key, $padding);
        if(empty($symmetricKey)){
            return null;
        }
        // 2. 验证 MAC
        if(!is_null($mac)){
            $_mac = strtoupper(hash("sha256", $ciphertext));
            if($mac != $_mac){
                return null;
            }
        }
        // 3. 解密密文
        $plaintext = openssl_decrypt(Encode::decode($ciphertext, $code), 'aes-128-cfb', $symmetricKey, OPENSSL_RAW_DATA, Encode::decode($iv, $code));

        return $plaintext;
    }

    /**
     * 生成RSA非对称密钥对
     * @param array $options
     * @return array
     */
    public function createKey(array $options = []): array
    {
        $config = empty($options) ? [
            "digest_alg" => "SHA256",
            "private_key_bits" =>$this->private_key_bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ] : $options;

        //创建密钥对
        $res = openssl_pkey_new($config);
        //生成私钥
        openssl_pkey_export($res, $privateKey);
        //生成公钥
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'private_key' => $privateKey,
            'public_key'  => $publicKey,
            'private'     => Encode::convert_pem_key_single($privateKey),
            'public'      => Encode::convert_pem_key_single($publicKey)
        ];
    }

    /**
     * 检测填充类型
     * 加密只支持PKCS1_PADDING
     * 解密支持PKCS1_PADDING和NO_PADDING
     * @param int $padding
     * @param string $type
     * @return bool
     */
    private function _checkPadding(int $padding, string $type): bool
    {
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

}