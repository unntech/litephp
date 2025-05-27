<?php

namespace LitePhp\Models;

class DbResult
{
    protected $result;
    protected $sql;
    protected $insertId = 0;
    protected $errorMessage = '';
    public $affected_rows = 0;
    public $errorCode = 0;

    public function __construct($data = [])
    {
        $this->result = $data['result'] ?? null;
        $this->sql = $data['sql'] ?? null;
        $this->insertId = $data['insertId'] ?? 0;
        $this->errorCode = $data['errorCode'] ?? 0;
        $this->errorMessage = $data['errorMessage'] ?? '';
        $this->affected_rows = $data['affected_rows'] ?? 0;
    }

    public static function instance($data = [])
    {
        return new static($data);
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function result()
    {
        return $this->result;
    }

    public function insert_id()
    {
        return $this->insertId;
    }

    public function affected_rows()
    {
        return $this->affected_rows;
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

    protected function showMessage(int $errorCode, string $errorMessage)
    {
        $html = "<div style='background: #6c757d; color: #eee; padding: 30px 15px 30px 15px;line-height: 1.5rem; width: 100%; display: block; text-align: center;'><h3>{$errorCode}: {$errorMessage}</h3></div>";
        echo $html;
    }

}