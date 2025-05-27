<?php

namespace LitePhp\Models;

class LiHttpResponse
{
    protected $body = null;
    protected $info = [];
    protected $headers = [];
    protected $statusCode = 0;
    protected $errorCode = 0;
    protected $errorMessage = '';
    protected $method = '';

    public function __construct($data = [])
    {
        $this->method = $data['method'] ?? '';
        $this->body = $data['body'] ?? null;
        $this->info = $data['info'] ?? [];
        $this->headers = $data['headers'] ?? [];
        $this->statusCode = $data['statusCode'] ?? 0;
        $this->errorCode = $data['errorCode'] ?? 0;
        $this->errorMessage = $data['errorMessage'] ?? '';
    }

    public function __toString()
    {
        return $this->body;
    }

    /**
     * 获取返回结果
     * @param bool $AutomaticParsing 根据响应结果Content-Type自动判定数据类型进行解析
     * @return array|mixed|void|null
     */
    public function getBody(bool $AutomaticParsing = false)
    {
        if($AutomaticParsing){
            $string = $this->getContentType();
            if(strpos($string, 'application/json')!==false){
                return $this->getBodyDecodeJson();
            }
            if((strpos($string, 'application/xml')!==false) || (strpos($string, 'text/xml')!==false)){
                return $this->getBodyDecodeXml();
            }
        }

        return $this->body;
    }

    /**
     * 获取返回结果并将JSON解析成数组
     * @return mixed
     */
    public function getBodyDecodeJson()
    {
        return json_decode($this->body, true);
    }

    public function getBodyDecodeXml()
    {
        return (array)simplexml_load_string($this->body);
    }

    /**
     * 获取响应信息
     * @return mixed|null
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * 获取响应头信息
     * @return mixed|null
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 获取响应头数据
     * @param string $header
     * @return mixed|string
     */
    public function getHeader(string $header)
    {
        return $this->headers[$header] ?? '';
    }

    /**
     * 请求响应状态
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 结果内容类型
     * @return string
     */
    public function getContentType(): string
    {
        return $this->info['content_type'] ?? '';
    }

    /**
     * 请求的URL地址
     * @return string
     */
    public function url(): string
    {
        return $this->info['url'] ?? '';
    }

    /**
     * 请求错误代码，成功为0
     * @return int
     */
    public function errorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 请求错误信息
     * @return string
     */
    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

}