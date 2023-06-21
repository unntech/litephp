<?php

namespace LitePhp;

class qrCode {
    
    /**
     * 生成二维码
     * @param $text 数据; $size 尺寸; $margin边距; $level = 'L';// 纠错级别：L、M、Q、H $saveandprint = true;// true直接输出屏幕  false 保存到文件中 $outfile 为false直接输出至屏幕;
     * $back_color = 0xFFFFFF;//白色底色 $fore_color = 0x000000;//黑色二维码色 若传参数要hexdec处理，如 $fore_color = str_replace('#','0x',$fore_color); $fore_color = hexdec('0xCCCCCC');
     */
    public static function png($text, $size = 10, $margin = 1, $level = 'L', $saveandprint=true, $outfile = false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    {
        require_once __DIR__ . "/../lib/phpqrcode.php";

        \LitePhpLib\QRcode::png($text, $outfile, $level, $size, $margin, $saveandprint, $back_color, $fore_color);

    }
    
    public static function svg($text, $size = 10, $margin = 1, $level = 'L', $saveandprint=true, $outfile = false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    {
        require_once __DIR__ . "/../lib/phpqrcode.php";

        \LitePhpLib\QRcode::svg($text, $outfile, $level, $size, $margin, $saveandprint, $back_color, $fore_color);
    }

    public static function eps($text, $size = 10, $margin = 1, $level = 'L', $saveandprint=true, $outfile = false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    {
        require_once __DIR__ . "/../lib/phpqrcode.php";

        \LitePhpLib\QRcode::eps($text, $outfile, $level, $size, $margin, $saveandprint, $back_color, $fore_color);
    }

    public static function text($text, $size = 10, $margin = 1, $level = 'L', $outfile = false)
    {
        require_once __DIR__ . "/../lib/phpqrcode.php";

        return \LitePhpLib\QRcode::text($text, $outfile, $level, $size, $margin);
    }

}