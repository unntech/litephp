<?php

namespace LitePhp;

class Db {
    public static $db;
    
    /**
     * 构造方法
     * @access public $i 为配置文件db列表里的第几个配置
     */
    public static function Create($rediscfg, $i=0)
    {
        $cfg = $rediscfg['connections'][$i];
        $dbt = $cfg['database'];
        switch($dbt){
            case 'mysqli':
                $db = new \LitePhp\mysqli($cfg);
                break;
            case 'sqlsrv':
                $db = new \LitePhp\sqlsrv($cfg);
                break;
            case 'mongodb':
				$db = new \LitePhp\mongodb($cfg);
				break;
            default :
                $db = false;
        }
        self::$db = $db;
        return $db;
    }

}