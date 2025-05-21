<?php

namespace LitePhp;

require_once __DIR__ . "/../lib/phpqrcode.php";

class qrCode {

    /**
     * @param $text string 数据
     * @param $size int 尺寸
     * @param $margin int 边距
     * @param $level string  纠错级别：L、M、Q、H
     * @param $saveandprint bool true直接输出屏幕  false 保存到文件中
     * @param $outfile bool|string 为false直接输出至屏幕
     * @param $back_color 0xFFFFFF 白色底色
     * @param $fore_color 0x000000 黑色二维码色 若传参数要hexdec处理，如 $fore_color = str_replace('#','0x',$fore_color); $fore_color = hexdec('0xCCCCCC')
     * @return void
     */
    public static function png($text, $size = 10, $margin = 1, $level = 'L', $saveandprint=true, $outfile = false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    {
        \LitePhpLib\QRcode::png($text, $outfile, $level, $size, $margin, $saveandprint, $back_color, $fore_color);
    }
    
    public static function svg($text, $size = 10, $margin = 1, $level = 'L', $saveandprint=true, $outfile = false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    {
        \LitePhpLib\QRcode::svg($text, $outfile, $level, $size, $margin, $saveandprint, $back_color, $fore_color);
    }

    public static function eps($text, $size = 10, $margin = 1, $level = 'L', $saveandprint=true, $outfile = false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    {
        \LitePhpLib\QRcode::eps($text, $outfile, $level, $size, $margin, $saveandprint, $back_color, $fore_color);
    }

    public static function text($text, $size = 10, $margin = 1, $level = 'L', $outfile = false)
    {
        return \LitePhpLib\QRcode::text($text, $outfile, $level, $size, $margin);
    }

}