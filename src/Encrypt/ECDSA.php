<?php

namespace LitePhp\Encrypt;

/**
 * ## ECDSA
 * （Elliptic Curve Digital Signature Algorithm，椭圆曲线数字签名算法）
 *
 * ### ECIES 加密方式详解
 *  生成临时ECDSA 密钥对
 *  使用对方公钥和临时私钥通过ECDH算法生成共享密钥
 *  生成随机AES密钥iv值
 *  用共享密钥对数据进行AES-128-CFB加密 参数 OPENSSL_RAW_DATA
 *  密文和iv值进行base64处理（支持HEX）
 *  使用SHA256计算哈希值(mac)，用于接收者验证数据完整性
 *  把 临时公钥 `tempPublicKey` 向量 `iv` 编码方式 `code` 密文哈希值 `mac` 加密类型 `ECIES` 放入 encryption 字段
 *
 * ### ECIES 解密方式详解
 *  把接收到的密文使用SHA256计算哈希值，验证mac值是否相同，判定数据是否完整
 *  把接收到的 临时公钥 `tempPublicKey` 和自己的私钥通过ECDH算法生成共享密钥
 *  用共享密钥当作AES密钥和收到的向量 `iv` 采用 aes-128-cfb 进行解密， 参数 OPENSSL_RAW_DATA
 *  得到原文
 *
 * @author: Jason
 * @version: 1.0.1
 * @date: 2025/05/27
 */
class ECDSA
{
    protected $private_key = null;
    protected $third_public_key = null;
    protected $self_public_key = null;
    protected $algorithm = OPENSSL_ALGO_SHA256;
    protected $private_key_bits = 384;
    protected $curve = 'secp256k1';

    /**
     * @param string $third_public_key 对方公钥
     * @param string $private_key 自己私钥
     */
    public function __construct(string $third_public_key='', string $private_key='')
    {
        $this->third_public_key = Encode::convert_public_key_string($third_public_key);
        $this->private_key = Encode::convert_private_key_string($private_key, 'EC PRIVATE KEY');
    }

    /**
     * 设置自己私钥
     * @param string $private_key
     * @return $this
     */
    public function setPrivateKey(string $private_key): ECDSA
    {
        $this->private_key = Encode::convert_private_key_string($private_key, 'EC PRIVATE KEY');
        return $this;
    }

    /**
     * 设置对方公钥
     * @param string $third_public_key
     * @return $this
     */
    public function setThirdPublicKey(string $third_public_key): ECDSA
    {
        $this->third_public_key = Encode::convert_public_key_string($third_public_key);
        return $this;
    }

    /**
     * 设置自己公钥
     * @param string $self_public_key
     * @return $this
     */
    public function setSelfPublicKey(string $self_public_key): ECDSA
    {
        $this->self_public_key = Encode::convert_public_key_string($self_public_key);
        return $this;
    }

    /**
     * 设置私钥长度
     * @param int $bit 默认384
     * @return $this
     */
    public function setPrivateKeyBits(int $bit = 384): ECDSA
    {
        $this->private_key_bits = $bit;
        return $this;
    }

    /**
     * @param int $algorithm
     * @return $this
     */
    public function setAlgorithm(int $algorithm = OPENSSL_ALGO_SHA384): ECDSA
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * openssl_get_curve_names() 获取可支持的曲线
     * @param string $curve
     * @return $this
     */
    public function setCurve(string $curve): ECDSA
    {
        $this->curve = $curve;
        return $this;
    }

    /**
     * 数据生成签名
     * @param string $data 需签名数据
     * @param string $code 默认使用base64编码返回签名 （支持 base64 | hex | bin）
     * @return false|string
     */
    public function sign(string $data, string $code = 'base64')
    {
        $private_key = $this->private_key;
        $signature = false;
        if (openssl_sign($data, $signature, $private_key, $this->algorithm)) {
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
     * ECIES加密
     * 使用 openssl_pkey_derive('对方公钥', '自己私钥') 获取共享密钥， 椭圆典线 Diffie-Hellman (ECDH)算法生成
     * 为增加安全性，使用临时密钥对的私钥，然后把临时公钥跟结果一并给对方
     * 使用AES-128-CFB加密文本，参数选 OPENSSL_RAW_DATA
     * @param string $plaintext 明文数据
     * @param string $code 密文编码支持 base64 | hex | bin
     * @return false | array <p><br>
     *     [ 'tempPublicKey' => '临时公钥', <br>
     *       'iv'     => 'iv',  <br>
     *       'code'   => 'base64', <br>
     *       'ciphertext' => '密文', <br>
     *       'mac'    => '密文SHA256哈希' <br>
     *     ]
     * </p>
     */
    public function encrypt(string $plaintext, string $code = 'base64')
    {
        $publicKey = $this->third_public_key;
        //获取公钥信息，用相同的类型申请临时密钥对
        $key = openssl_pkey_get_public($publicKey);
        if($key !== false){
            $det = openssl_pkey_get_details($key);
        }else{
            return false;
        }
        if($det === false || $det['type'] != 3){
            return false;
        }
        $tempKeyPair = $this->createKey([
            "digest_alg" => "SHA256",
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => $det['ec']['curve_name']
        ]);
        $symmetricKey = openssl_pkey_derive($publicKey, $tempKeyPair['private_key']); // ECDH算法生成共享密钥

        // 使用对称密钥加密消息（AES-128-CFB）
        $cipher_method = 'aes-128-cfb';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
        $encryptedMessage = openssl_encrypt($plaintext, $cipher_method, $symmetricKey, OPENSSL_RAW_DATA, $iv);
        $ciphertext = Encode::encode($encryptedMessage, $code );
        //使用SHA256计算密文哈希值
        $mac = strtoupper(hash("sha256", $ciphertext));

        // 返回临时公钥、AES向量iv、密文及哈希值
        return [
            'tempPublicKey' => $tempKeyPair['public'],
            'cipher_method' => $cipher_method,
            'iv'            => Encode::encode($iv, $code),
            'code'          => $code,
            'ciphertext'    => $ciphertext,
            'mac'           => $mac,
        ];
    }

    /**
     * ECIES 解密
     * @param string $ciphertext 密文
     * @param string $tempPublicKey 临时公钥
     * @param string $iv AES加密向量
     * @param string|null $mac 密文哈希值
     * @param string $code 编码
     * @return string|null
     */
    public function decrypt(string $ciphertext, string $tempPublicKey = '', string $iv = '', ?string $mac = null, string $code = 'base64'): ?string
    {
        $tempPublicKey = Encode::convert_public_key_string($tempPublicKey);
        // 1. 计算共享密钥
        $symmetricKey = openssl_pkey_derive($tempPublicKey, $this->private_key); // ECDH算法生成共享密钥
        if($symmetricKey === false){
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
     * 生成ECDSA非对称密钥对
     * @param array $options
     * @return array
     */
    public function createKey(array $options=[]): array
    {
        $config = empty($options) ? [
            "digest_alg" => "SHA256",
            //'private_key_bits' => $this->private_key_bits,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => $this->curve
        ] : $options;

        // 生成密钥对
        $res = openssl_pkey_new($config);

        // 提取私钥
        openssl_pkey_export($res, $privateKey);

        // 获取公钥
        $publicKey = openssl_pkey_get_details($res)['key'];

        return [
            'private_key' => $privateKey,
            'public_key'  => $publicKey,
            'private'     => Encode::convert_pem_key_single($privateKey),
            'public'      => Encode::convert_pem_key_single($publicKey)
        ];
    }

}