<?php

namespace LitePhp;

use LitePhp\Library\wxBizMsgCrypt;

class Weixin
{
    protected $token = '';//token
    protected $encodingAesKey = '';
    protected $debug = false; //是否debug的状态标示，方便我们在调试的时候记录一些中间数据
    protected $encrypt = false;
    protected $wxMsgCrypt;
    protected $appId = '';
    protected $timestamp = 0;
    public $setFlag = false;
    public $msgtype = 'text';   //('text','image','location')
    public $msg = array();

    public function __construct(string $token, string $encodingAesKey = '', bool $encrypt = false, string $appId = '', bool $debug = false)
    {
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->debug = $debug;
        $this->encrypt = $encrypt;
        $this->appId = $appId;
        if($encrypt){
            $this->wxMsgCrypt = new WXBizMsgCrypt($token, $encodingAesKey, $appId);
        }
        $this->timestamp = time();
    }

    /**
     * 获得用户发过来的消息（消息内容和消息类型 ）
     * @param ?string $data 默认POST RAW数据
     * @param ?array $getarr 默认GET参数
     * @return int 0成功 其它为错误代码
     */
    public function getMsg(?string $data = null, ?array $getarr = null): int
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if(empty($data)) {
            $postStr = file_get_contents("php://input");
        }else{
            $postStr = $data;
        }
        if(empty($postStr)){
            return -1;
        }

        if(empty($getarr)){
            $getarr = $_GET;
        }
        $get_msg_signature = $getarr['msg_signature'] ?? '';
        $get_timestamp = $getarr['timestamp'] ?? '';
        $get_nonce = $getarr['nonce'] ?? '';

        if($this->encrypt){
            $msg = '';
            $errCode = $this->wxMsgCrypt->decryptMsg($get_msg_signature, $get_timestamp, $get_nonce, $postStr, $msg);
            if($errCode == 0){
                $this->msg = (array)simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
                $this->msgtype = strtolower($this->msg['MsgType']);
                return 0;
            }else{
                return  $errCode;
            }
        }else{
            if (!empty($postStr)) {
                $this->msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $this->msgtype = strtolower($this->msg['MsgType']);
                return 0;
            }else{
                return  -1;
            }
        }
    }

    /**
     * 回复文本消息
     * @param string $text
     * @return string
     */
    public function makeText(string $text = ''): string
    {
        $CreateTime = $this->timestamp;
        $FuncFlag = $this->setFlag ? 1 : 0;
        $FromUserName = $this->msg[ 'FromUserName' ] ?? ''; //获取发送方帐号（OpenID）
        $ToUserName = $this->msg[ 'ToUserName' ] ?? ''; //获取接收方账号
        $textTpl = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        return sprintf($textTpl, $text, $FuncFlag);
    }

    /**
     * 根据数组参数回复图文消息
     * @param array $newsData
     * @return string
     */
    public function makeNews(array $newsData = []): string
    {
        $CreateTime = $this->timestamp;
        $FuncFlag = $this->setFlag ? 1 : 0;
        $FromUserName = $this->msg[ 'FromUserName' ] ?? ''; //获取发送方帐号（OpenID）
        $ToUserName = $this->msg[ 'ToUserName' ] ?? ''; //获取接收方账号
        $newTplHeader = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <ArticleCount>%s</ArticleCount><Articles>";
        $newTplItem = "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
        $newTplFoot = "</Articles>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        $Content = '';
        $itemsCount = count($newsData['items']);
        $itemsCount = $itemsCount < 10 ? $itemsCount : 10;//微信公众平台图文回复的消息一次最多10条
        if ($itemsCount) {
            foreach ($newsData['items'] as $key => $item) {
                if ($key <= 9) {
                    $Content .= sprintf($newTplItem, $item['title'], $item['description'], $item['picurl'], $item['url']);
                }
            }
        }
        $header = sprintf($newTplHeader, $newsData['content'], $itemsCount);
        $footer = sprintf($newTplFoot, $FuncFlag);
        return $header . $Content . $footer;
    }

    /**
     * 根据数组参数回复对应类型消息
     * @param string $msgType : image | voice | video | music
     * @param array $data ['content'=>'', 'mediaId'=>'', 'title'=>'', 'description'=>'', 'musicUrl'=>'', 'HQmusicUrl'=>'', 'thumbMediaId'=>'']
     * @return string
     */
    public function makeMessage(string $msgType, array $data = []): string
    {
        $CreateTime = $this->timestamp;
        $FromUserName = $this->msg[ 'FromUserName' ] ?? ''; //获取发送方帐号（OpenID）
        $ToUserName = $this->msg[ 'ToUserName' ] ?? ''; //获取接收方账号
        switch ($msgType){
            case 'image':
                $textTpl = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[image]]></MsgType>
            <Image><MediaId><![CDATA[%s]]></MediaId></Image>
            </xml>";
                return sprintf($textTpl, $data['mediaId']);
                break;
            case 'voice':
                $textTpl = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[voice]]></MsgType>
            <Voice><MediaId><![CDATA[%s]]></MediaId></Voice>
            </xml>";
                return sprintf($textTpl, $data['mediaId']);
                break;
            case 'video':
                $textTpl = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[video]]></MsgType>
            <Video>
              <MediaId><![CDATA[%s]]></MediaId>
              <Title><![CDATA[%s]]></Title>
              <Description><![CDATA[%s]]></Description>
            </Video>
            </xml>";
                return sprintf($textTpl, $data['mediaId'], $data['title'], $data['description']);
                break;
            case 'music':
                $textTpl = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[music]]></MsgType>
            <Music>
              <Title><![CDATA[%s]]></Title>
              <Description><![CDATA[%s]]></Description>
              <MusicUrl><![CDATA[%s]]></MusicUrl>
              <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
              <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
            </Music>
            </xml>";
                return sprintf($textTpl, $data['title'], $data['description'], $data['musicUrl'], $data['HQmusicUrl'], $data['thumbMediaId']);
                break;
            default:
                $textTpl = "<xml>
            <ToUserName><![CDATA[{$FromUserName}]]></ToUserName>
            <FromUserName><![CDATA[{$ToUserName}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
                return sprintf($textTpl, $data['content']);
                break;
        }


    }

    /**
     * 输入回复消息
     * @param $data
     * @return void
     */
    public function reply($data)
    {
        $nonce = LiComm::createNonceStr(8);

        if($this->encrypt){
            $errCode = $this->wxMsgCrypt->encryptMsg($data, $this->timestamp, $nonce, $enData);
            if($errCode == 0){
                echo $enData;
            }
        }else{
            echo $data;
        }
    }

    /**
     * 输出空值或原样回显
     * @return void
     */
    public function valid()
    {
        if ($this->checkSignature()) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                echo $_GET['echostr'];
                exit;
            }
        } else {

            exit;
        }
    }

    /**
     * 验证数据签名
     * @return bool
     */
    private function checkSignature(): bool
    {
        $signature = $_GET["signature"] ?? '';
        $timestamp = $_GET["timestamp"] ?? '';
        $nonce = $_GET["nonce"] ?? '';

        $tmpArr = array($this->token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}