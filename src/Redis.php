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
    
    public static function set($key, $value, $ttl=0){
        
        return $ttl > 0 ? self::$redis->set(self::$pre.$key, $value, $ttl) : self::$redis->set(self::$pre.$key, $value);
        
    }
    
    public static function get($key){
        
        return self::$redis->get(self::$pre.$key);
        
    }
    
    public static function del($key){
        
        return self::$redis->del(self::$pre.$key);
        
    }
    
    public static function incr($key){
        
        return self::$redis->incr(self::$pre.$key);
        
    }
    
    public static function decr($key){
        
        return self::$redis->decr(self::$pre.$key);
        
    }
    
    public static function exists($key){
        
        return self::$redis->exists(self::$pre.$key);
        
    }
    
    public static function expire($key, $ttl=0){
        
        return self::$redis->expire(self::$pre.$key, $ttl);
        
    }

}