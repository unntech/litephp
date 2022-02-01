<?php

namespace LitePhp;

class Redis {
    public static $pre, $redis;
    
    /**
     * 构造方法
     * @access public
     */
    public static function Create($rediscfg)
    {
        $cfg = $rediscfg['connections'];
        self::$pre = $cfg['prefix'];
        $redis = new \redis();
        $redis->connect( $cfg['host'], $cfg['port'] );
        if(!empty($cfg['password'])){
            $redis->auth($cfg['password']);
        }
        if(!empty($cfg['db'])){
            $redis->select($cfg['db']); 
        }
        self::$redis = $redis;
        return $redis;
    }

}