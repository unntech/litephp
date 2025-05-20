<?php

namespace LitePhp\Library;

class wxBizMsgCrypt
{
    public static $OK = 0;
    public static $ValidateSignatureError = -40001;
    public static $ParseXmlError = -40002;
    public static $ComputeSignatureError = -40003;
    public static $IllegalAesKey = -40004;
    public static $ValidateAppidError = -40005;
    public static $ValidateCorpidError = -40005;
    public static $EncryptAESError = -40006;
    public static $DecryptAESError = -40007;
    public static $IllegalBuffer = -40008;
    public static $EncodeBase64Error = -40009;
    public static $DecodeBase64Error = -40010;
    public static $GenReturnXmlError = -40011;
    public static $block_size = 32;

    protected $key=null, $iv = null;

    private $token;
    private $encodingAesKey;
    private $appId;

    /**
     * 构造函数
     * @param $token string 公众平台上，开发者设置的token
     * @param $encodingAesKey string 公众平台上，开发者设置的EncodingAESKey
     * @param $appId string 公众平台的appId
     */
    public function __construct(string $token, string $encodingAesKey, string $appId)
    {
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->appId = $appId;
    }

    public function setReceiveId($receiveId)
    {
        $this->appId = $receiveId;
    }

    /**
     * @param string $sMsgSignature 签名串，对应URL参数的msg_signature
     * @param string $sTimeStamp 时间戳，对应URL参数的timestamp
     * @param string $sNonce 随机串，对应URL参数的nonce
     * @param string $sEchoStr 随机串，对应URL参数的echostr
     * @param ?string $sReplyEchoStr 解密之后的echostr，当return返回0时有效
     * @return int 成功0，失败返回对应的错误码
     */
    public function VerifyURL(string $sMsgSignature, string $sTimeStamp, string $sNonce, string $sEchoStr, ?string &$sReplyEchoStr): int
    {
        //echo $sMsgSignature.'<BR>'.$sTimeStamp.'<BR>'.$sNonce.'<BR>'.$sEchoStr.'<BR>';
        if (strlen($this->encodingAesKey) != 43) {
            return self::$IllegalAesKey;
        }
        $this->setEncodingAesKey($this->encodingAesKey);
        //verify msg_signature
        $array = $this->getSHA1($this->token, $sTimeStamp, $sNonce, $sEchoStr);
        $ret = $array[0];

        if ($ret != 0) {
            return $ret;
        }

        $signature = $array[1];
        if ($signature != $sMsgSignature) {
            return self::$ValidateSignatureError;
        }

        $result = $this->decrypt($sEchoStr, $this->appId);
        //var_dump($result);

        if ($result[0] != 0) {
            return $result[0];
        }
        $sReplyEchoStr = $result[1];

        return self::$OK;
    }

    /**
     * 将公众平台回复用户的消息加密打包.
     * <ol>
     *    <li>对要发送的消息进行AES-CBC加密</li>
     *    <li>生成安全签名</li>
     *    <li>将消息密文和安全签名打包成xml格式</li>
     * </ol>
     *
     * @param $replyMsg string 公众平台待回复用户的消息，xml格式的字符串
     * @param $timeStamp string 时间戳，可以自己生成，也可以用URL参数的timestamp
     * @param $nonce string 随机串，可以自己生成，也可以用URL参数的nonce
     * @param &$encryptMsg ?string 加密后的可以直接回复用户的密文，包括msg_signature, timestamp, nonce, encrypt的xml格式的字符串,
     *                      当return返回0时有效
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function encryptMsg(string $replyMsg, string $timeStamp, string $nonce, ?string &$encryptMsg): int
    {
        $this->setEncodingAesKey($this->encodingAesKey);

        //加密
        $array = $this->encrypt($replyMsg, $this->appId);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }

        if ($timeStamp == null) {
            $timeStamp = time();
        }
        $encrypt = $array[1];

        //生成安全签名
        $array = $this->getSHA1($this->token, $timeStamp, $nonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];

        //生成发送的xml
        $encryptMsg = $this->generate($encrypt, $signature, $timeStamp, $nonce);
        return self::$OK;
    }


    /**
     * 检验消息的真实性，并且获取解密后的明文.
     * <ol>
     *    <li>利用收到的密文生成安全签名，进行签名验证</li>
     *    <li>若验证通过，则提取xml中的加密消息</li>
     *    <li>对消息进行解密</li>
     * </ol>
     *
     * @param $msgSignature string 签名串，对应URL参数的msg_signature
     * @param $timestamp string 时间戳 对应URL参数的timestamp
     * @param $nonce string 随机串，对应URL参数的nonce
     * @param $postData string 密文，对应POST请求的数据
     * @param &$msg ?string 解密后的原文，当return返回0时有效
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptMsg(string $msgSignature, string $timestamp, string $nonce, string $postData, ?string &$msg): int
    {
        if (strlen($this->encodingAesKey) != 43) {
            return self::$IllegalAesKey;
        }

        $this->setEncodingAesKey($this->encodingAesKey);

        //提取密文
        $array = $this->extract($postData);
        $ret = $array[0];

        if ($ret != 0) {
            return $ret;
        }

        if ($timestamp == null) {
            $timestamp = time();
        }

        $encrypt = $array[1];
        $touser_name = $array[2];

        //验证安全签名
        $array = $this->getSHA1($this->token, $timestamp, $nonce, $encrypt);
        $ret = $array[0];

        if ($ret != 0) {
            return $ret;
        }

        $signature = $array[1];
        if ($signature != $msgSignature) {
            return self::$ValidateSignatureError;
        }

        $result = $this->decrypt($encrypt, $this->appId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $msg = $result[1];

        return self::$OK;
    }

    public function decryptTkt($timestamp, $postData, &$msg)
    {
        if (strlen($this->encodingAesKey) != 43) {
            return self::$IllegalAesKey;
        }

        $this->setEncodingAesKey($this->encodingAesKey);

        //提取密文
        $array = $this->extract($postData);
        $ret = $array[0];

        if ($ret != 0) {
            return $ret;
        }

        if ($timestamp == null) {
            $timestamp = time();
        }

        $encrypt = $array[1];
        $touser_name = $array[2];


        $result = $this->decrypt($encrypt, $this->appId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $msg = $result[1];

        return self::$OK;
    }


    /**
     * 用SHA1算法生成安全签名
     * @param string $token 票据
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @param string $encrypt_msg 密文消息
     * @return array
     */
    protected function getSHA1(string $token, string $timestamp, string $nonce, string $encrypt_msg): array
    {
        //排序
        try {
            $array = [$encrypt_msg, $token, $timestamp, $nonce];
            sort($array, SORT_STRING);
            $str = implode($array);
            return [self::$OK, sha1($str)];
        } catch (\Throwable $e) {
            //print $e . "\n";
            return [self::$ComputeSignatureError, null];
        }
    }

    /**
     * 提取出xml数据包中的加密消息
     * @param string $xmltext 待提取的xml字符串
     * @return array 提取出的加密消息字符串
     */
    protected function extract(string $xmltext): array
    {
        try {
            $xml = new \DOMDocument();
            $xml->loadXML($xmltext);
            $array_e = $xml->getElementsByTagName('Encrypt');
            $array_a = $xml->getElementsByTagName('ToUserName');
            $encrypt = $array_e->length ? $array_e->item(0)->nodeValue : null;
            $tousername = $array_a->length ? $array_a->item(0)->nodeValue : null;
            return [0, $encrypt, $tousername];
        } catch (\Throwable $e) {
            //print $e . "\n";
            return [self::$ParseXmlError, null, null];
        }
    }

    /**
     * 生成xml消息
     * @param string $encrypt 加密后的消息密文
     * @param string $signature 安全签名
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     */
    protected function generate(string $encrypt, string $signature, string $timestamp, string $nonce): string
    {
        $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }

    /**
     * 对需要加密的明文进行填充补位
     * @param string $text 需要进行填充补位操作的明文
     * @return string 补齐明文字符串
     */
    protected function encode(string $text): string
    {
        $block_size = self::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = $block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = str_repeat($pad_chr, $amount_to_pad);
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string $text 解密后的明文
     * @return string 删除填充补位后的明文
     */
    protected function decode(string $text): string
    {

        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > self::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    protected function setEncodingAesKey(string $key){
        $this->key = base64_decode($key . '=');
        $this->iv = substr($this->key, 0, 16);
    }

    /**
     * 对明文进行加密
     * @param string $text 需要加密的明文
     * @param string $appid 微信 appid
     * @return array 加密后的密文
     */
    public function encrypt(string $text, string $appid): array
    {
        try {
            //获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();
            $text = $random . pack("N", strlen($text)) . $text . $appid;

            $text = $this->encode($text);
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $this->iv);
            return [self::$OK, base64_encode($encrypted)];
        } catch (\Throwable $e) {
            //print $e;
            return [self::$EncryptAESError, null];
        }
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @param string $appid 微信 appid
     * @return array 解密得到的明文
     */
    public function decrypt(string $encrypted, string $appid): array
    {

        try {
            //使用BASE64对需要解密的字符串进行解码
            $decrypted = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $this->iv);
        } catch (\Throwable $e) {
            return [self::$DecryptAESError, null];
        }

        try {
            $result = $this->decode($decrypted);

            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16)
                return [self::$DecryptAESError, ''];
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
        } catch (\Throwable $e) {
            //print $e;
            return [self::$IllegalBuffer, null];
        }
        if ($from_appid != $appid)
            return [self::$ValidateAppidError, null];
        return array(0, $xml_content);

    }


    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    protected function getRandomStr(): string
    {

        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}