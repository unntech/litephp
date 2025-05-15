<?php

namespace LitePhp;

class Exception extends \Exception
{

    public function errorMessage()
    {
        if (defined('DT_DEBUG') && DT_DEBUG) {
            $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{margin: 0 auto;} .header{background: #6c757d; color: #eee; padding: 50px 15px 30px 15px;line-height: 1.5rem} .msg{padding: 15px 15px;line-height: 1.25rem}</style></head><body>';
            $html .= '<div class="header"><h3>' . $this->getMessage() . '</h3>Code: ' . $this->getCode() . '<BR>File: ' . $this->getFile() . '<BR>Line: ' . $this->getLine() . '</div>';
            $html .= '<div class="msg">' . LiComm::dv($this, false) . '</div>';
            $html .= '</body></html>';
        }else{
            $msg = $this->getCode() . ': ' . $this->getMessage();
            $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{background-color:#444;font-size:16px;}h3{font-size:32px;color:#eee;text-align:center;padding-top:50px;font-weight:normal;}</style></head>';
            $html .= '<body><h3>' . $msg . '</h3></body></html>';
        }
        echo $html;
        exit(0);
    }

}