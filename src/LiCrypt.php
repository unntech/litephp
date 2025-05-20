<?php

namespace LitePhp;

class LiCrypt
{
    //错误代码
    public $err;
    protected $cipher;
    protected $ckey;
    protected $iv;
    protected $salt;

    public function __construct(string $key = '', string $iv = '', string $cipher = 'aes-128-cfb')
    {
        $this->cipher = $cipher;
        $this->ckey = $key;
        $this->iv = empty($iv) ? $this->ivstr($key) : $iv;
        $this->salt = substr(md5('UNN.TECH' . $key) . $this->iv, 3, 16);
        $this->err = 0;
    }

    /**
     * 获取有效密码方式算法列表
     * @return array
     */
    public function getCipher(): array
    {
        return openssl_get_cipher_methods();
    }

    /**
     * 设置加密算法
     * @param string $cipher
     * @return void
     */
    public function setCipher(string $cipher = 'aes-256-cfb')
    {
        $this->cipher = $cipher;
    }

    /**
     * 重新设置加密密钥
     * @param string $key
     * @param string $iv
     * @return void
     */
    public function setKey(string $key = '', string $iv = '')
    {
        $this->ckey = $key;
        $this->iv = empty($iv) ? $this->ivstr($key) : $iv;
    }

    /**
     * 重新设置加密盐值
     * @param string $salt
     * @return void
     */
    public function setSalt(string $salt = '')
    {
        $this->salt = $salt;
    }

    private function ivstr($key)
    {
        $str = md5($key);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $str = str_pad($str, $ivlen, '=');
        return substr($str, 0, $ivlen);
    }

    /**
     * 加密
     * @param string plaintext 需加密的字符串
     * @return string 加密后字符串
     */
    public function encrypt($plaintext, $key = '', $iv = '')
    {
        $key = $key == '' ? $this->ckey : $key;
        $iv = $iv == '' ? $this->iv : $iv;

        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $key, 1, $iv);
        $ciphertext = $this->base64UrlEncode($ciphertext);
        return $ciphertext;
    }

    /**
     * 解密
     * @param string ciphertext
     * @return string 解密后字符串
     */
    public function decrypt($ciphertext, $key = '', $iv = '')
    {
        $key = $key == '' ? $this->ckey : $key;
        $iv = $iv == '' ? $this->iv : $iv;

        $ciphertext = $this->base64UrlDecode($ciphertext);
        $original_plaintext = openssl_decrypt($ciphertext, $this->cipher, $key, 1, $iv);
        return $original_plaintext;
    }

    /**
     * 加密
     * @param string arr 需加密的字符串或数组
     * @return string 加密后字符串
     */
    public function jencrypt($arr, $key = '', $iv = ''): string
    {
        if (is_array($arr)) {
            $rt = $this->encrypt(json_encode($arr), $key, $iv);
        } else {
            $rt = $this->encrypt($arr, $key, $iv);
        }
        $this->err = 0;
        return $rt;
    }

    /**
     * 解密
     * @param string ciphertext
     * @return string|array 解密后字符串或数组
     */
    public function jdecrypt($ciphertext, $key = '', $iv = '')
    {
        $re = $this->decrypt($ciphertext, $key, $iv);
        if ($re === false) {
            $this->err = 1;  //解密失败
            return false;
        } else {
            $arr = json_decode($re, true);
            if (!empty($arr)) {
                $this->err = 0;
                return $arr;
            } else {
                $this->err = 2;  //非数组
                return $re;
            }
        }
    }


    /**
     * 生成TOKEN
     * @param array $jwt (exp: 过期时间, iat: 签发时间, nbf: 生效时间)
     * @param bool $needSign 是否需要生成签名，防止数据被篡改，提高安全性
     * @return string 加密后字符串
     */
    public function getToken(array $jwt, bool $needSign = false)
    {
        if (is_array($jwt)) {
            if ($needSign) {
                $sign = $this->signature($jwt);
                $jwt['sign'] = $sign;
            }
            $rt = $this->encrypt(json_encode($jwt));
            $this->err = 0;
            return $rt;
        } else {
            return false;
        }
    }

    /**
     * 验证TOKEN
     * @param string $Token
     * @param bool $needSign 需验证签名，防止数据被篡改
     * @return bool|array Jwt数组 失败返回false, err为错误代码
     */
    public function verifyToken(string $Token, bool $needSign = false)
    {
        $re = $this->decrypt($Token);
        if ($re === false) {
            $this->err = 1;  //解密失败
            return false;
        } else {
            $arr = json_decode($re, true);
            if (is_array($arr)) {
                if ($needSign) {
                    $sign = $arr['sign'];
                    unset($arr['sign']);
                    $_sign = $this->signature($arr);
                    if ($sign != $_sign) {
                        $this->err = 2; //签名错，数据被篡改
                        return false;
                    }
                }

                $curtime = time();

                //签发时间大于当前服务器时间验证失败
                if (isset($arr['iat']) && $arr['iat'] > $curtime) {
                    $this->err = 3;
                    return false;
                }

                //过期时间小宇当前服务器时间验证失败
                if (isset($arr['exp']) && $arr['exp'] < $curtime) {
                    $this->err = 4;
                    return false;
                }

                //该nbf时间之前不接收处理该Token
                if (isset($arr['nbf']) && $arr['nbf'] > $curtime) {
                    $this->err = 5;
                    return false;
                }

                $this->err = 0;
                return $arr;
            } else {
                $this->err = 2;  //非数组
                return false;
            }
        }

    }


    protected function signature(array $arr): string
    {
        unset($arr['sign']);
        ksort($arr);
        return md5(http_build_query($arr) . $this->salt);

    }


    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    public function base64UrlEncode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    public function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }


}