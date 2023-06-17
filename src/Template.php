<?php

namespace LitePhp;

class Template {
    protected static $dt_root, $cachePath, $template='default';
    protected static $chmod = 0755;
    protected static $template_refresh = true;
    
    /*
     * 模板类初始化数据，refresh 为false时，不会自动刷新修改的模版文件
     */
    public static function init($rootPath, $template, $cachePath, $refresh = true){
        self::$dt_root = $rootPath;
        self::$template = $template;
        self::$cachePath = $cachePath;
        self::$template_refresh = $refresh;
	}
    
    /*
     * 生成模板文件并返回
     */
    public static function load($template = 'index', $dir = ''){
        if(empty(self::$dt_root) || empty(self::$cachePath)){
            self::setDefaultPath();
        }
        
        self::check_name($template) or exit('BAD TPL NAME');
        if(!empty($dir)){
            self::check_name($dir) or exit('BAD TPL DIR');
        }
        $to = self::$cachePath.'/tpl/'.self::$template.'/'.(empty($dir) ? '' : $dir.'/' ). $template.'.php';
        $isfileto = is_file($to);
        if(!$isfileto || self::$template_refresh) {
            if(!empty($dir)) $dir = $dir.'/';
            $from = self::$dt_root.'/template/'.self::$template.'/'.$dir.$template.'.htm';
            if(self::$template != 'default' && !is_file($from)) {
                $from = self::$dt_root.'/template/default/'.$dir.$template.'.htm';
            }
            if(!$isfileto || filemtime($from) > filemtime($to) || (filesize($to) == 0 && filesize($from) > 0)) {
                self::template_compile($from, $to);
            }
        }
        
        return $to;
        
    }
    
    /*
     * 使用消息模板输出提示
     * @param 
     */
    public static function message($promptMessage='', $msgTitle=''){
        $msgTitle = empty($msgTitle) ? '信息提示' : $msgTitle;
        $promptMessage = empty($promptMessage) ? date('Y-m-d H:i:s') : $promptMessage;
        include self::load('message');
        exit(0);
    }
    
    protected static function check_name($name) {
        if(strpos($name, '__') !== false || strpos($name, '--') !== false) return false; 
        return preg_match("/^[a-z0-9]{1}[a-z0-9_\-\/]{0,}[a-z0-9]{1}$/", $name);
    }
    
    protected static function template_compile($from, $to) {
        $content = self::template_parse(self::file_get($from));
        self::file_put($to, $content);
    }

    protected static function template_parse($str) {
        $str = preg_replace("/\<\!\-\-\[(.+?)\]\-\-\>/", "", $str);
        $str = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str);
        $str = preg_replace("/\{template\s+([^\}]+)\}/", "<?php include \\LitePhp\\Template::load(\\1);?>", $str);
        $str = preg_replace("/\{php\s+(.+)\}/", "<?php \\1?>", $str);
        $str = preg_replace("/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str);
        $str = preg_replace("/\{else\}/", "<?php } else { ?>", $str);
        $str = preg_replace("/\{elseif\s+(.+?)\}/", "<?php } else if(\\1) { ?>", $str);
        $str = preg_replace("/\{\/if\}/", "<?php } ?>\r\n", $str);
        $str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>", $str);
        $str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>", $str);
        $str = preg_replace("/\{\/loop\}/", "<?php } } ?>", $str);
        $str = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str);
        $str = preg_replace_callback("/<\?php([^\?]+)\?>/s", function($matchs) {
                return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[0]));
                }, $str);
        $str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_>\+\-\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str);  // 规则添加[>](大于号)，支持对象变量直接输出
        $str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\+\-\x7f-\xff]+)\}/s", function($matchs) {
                return '<?php echo '.str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[1])).';?>';
                }, $str);
        $str = preg_replace("/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str);
        $str = preg_replace("/\'([A-Za-z]+)\[\'([A-Za-z\.]+)\'\](.?)\'/s", "'\\1[\\2]\\3'", $str);
        $str = preg_replace("/(\r?\n)\\1+/", "\\1", $str);
        $str = str_replace("\t", '', $str);
        $str = "<?php defined('IN_LitePhp') or exit('Access Denied');?>\n".$str;
        return $str;
        
    }

    protected static function template_addquote1($matchs) {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[0]));
    }

    protected static function template_addquote2($matchs) {
        return '<?php echo '.str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[1])).';?>';
    }
    
    protected static function file_put($filename, $data) {
        self::dir_create(dirname($filename));	
        if(@$fp = fopen($filename, 'wb')) {
            flock($fp, LOCK_EX);
            $len = fwrite($fp, $data);
            flock($fp, LOCK_UN);
            fclose($fp);
            if(self::$chmod) @chmod($filename, self::$chmod);
            return $len;
        } else {
            return false;
        }
    }

    protected static function file_get($filename) {
        return @file_get_contents($filename);
    }
    
    protected static function dir_create($path) {
        if(is_dir($path)) return true;
        if(self::$cachePath != self::$dt_root.'/runtime/cache' && strpos($path, self::$cachePath) !== false) {
            $dir = str_replace(self::$cachePath.'/', '', $path);
            $dir = self::dir_path($dir);
            $temp = explode('/', $dir);
            $cur_dir = self::$cachePath.'/';
            $max = count($temp) - 1;
            for($i = 0; $i < $max; $i++) {
                $cur_dir .= $temp[$i].'/';
                if(is_dir($cur_dir)) continue;
                @mkdir($cur_dir);
                if(self::$chmod) @chmod($cur_dir, self::$chmod);
            }
        } else {
            $dir = str_replace(self::$dt_root.'/', '', $path);
            $dir = self::dir_path($dir);
            $temp = explode('/', $dir);
            $cur_dir = self::$dt_root.'/';
            $max = count($temp) - 1;
            for($i = 0; $i < $max; $i++) {
                $cur_dir .= $temp[$i].'/';
                if(is_dir($cur_dir)) continue;
                @mkdir($cur_dir);
                if(self::$chmod) @chmod($cur_dir, self::$chmod);
            }
        }
        return is_dir($path);
    }
    
    protected static function dir_path($dirpath) {
        $dirpath = str_replace('\\', '/', $dirpath);
        if(substr($dirpath, -1) != '/') $dirpath = $dirpath.'/';
        return $dirpath;
    }
    
    /**
     * 初始化默认目录数据
     * @access protected
     * @return void
     */
    protected static function setDefaultPath()
    {
        self::$dt_root = dirname(__DIR__ , 4);
        self::$cachePath = self::$dt_root."/runtime/cache";
    }

}