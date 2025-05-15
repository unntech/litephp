<?php

namespace LitePhp;

class Db {
    /**
     * @var mysqli | sqlsrv | mongodb
     */
    public static $db;
    
    /**
     * 构造方法
     * @access public $i 为配置文件db列表里的第几个配置
     */
    public static function Create($icfg, $i=0, $new = false)
    {
        $cfg = $icfg['connections'][$i];
        $dbt = $cfg['database'];
        switch($dbt){
            case 'mysqli':
                $db = new mysqli($cfg);
                break;
            case 'sqlsrv':
                $db = new sqlsrv($cfg);
                break;
            case 'mongodb':
				$db = new mongodb($cfg);
				break;
            default :
                $db = false;
        }
        if(!$new) self::$db = $db;
        return $db;
    }
    
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::$db, $method], $args);
    }

}