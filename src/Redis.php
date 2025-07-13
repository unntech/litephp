<?php

namespace LitePhp;

class Redis {
    public static $pre;
    /**
     * @var \redis()
     */
    public static $redis;
    
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
    
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::$redis, $method], $args);
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

    public static function geoadd(string $key, float $lng, float $lat, string $member, ...$other_triples_and_options)
    {
        return self::$redis->geoadd(self::$pre.$key, $lng, $lat, $member, ...$other_triples_and_options);
    }

    public static function geopos(string $key, string $member, string ...$other_members)
    {
        return self::$redis->geopos(self::$pre.$key, $member, ...$other_members);
    }

    public static function geodist(string $key, string $src, string $dst, ?string $unit = null)
    {
        return self::$redis->geodist(self::$pre.$key, $src, $dst, $unit);
    }

    public static function georadius(string $key, float $lng, float $lat, float $radius, string $unit, array $options = [])
    {
        return self::$redis->geoRadius(self::$pre.$key, $lng, $lat, $radius, $unit, $options);
    }

    public static function georadiusbymember(string $key, string $member, float $radius, string $unit, array $options = [])
    {
        return self::$redis->geoRadiusByMember(self::$pre.$key, $member, $radius, $unit, $options);
    }

    public static function hSet(string $key, string $member, $value)
    {
        return self::$redis->hSet(self::$pre.$key, $member, $value);
    }

    public static function hMSet(string $key, array $fieldvals)
    {
        return self::$redis->hMSet(self::$pre.$key, $fieldvals);
    }

    public static function hGet(string $key, string $member)
    {
        return self::$redis->hGet(self::$pre.$key, $member);
    }

    public static function hMGet(string $key, array $fields)
    {
        return self::$redis->hMGet(self::$pre.$key, $fields);
    }

    public static function hGetAll(string $key)
    {
        return self::$redis->hGetAll(self::$pre.$key);
    }

    public static function hExists(string $key, string $field)
    {
        return self::$redis->hExists(self::$pre.$key, $field);
    }

    public static function hKeys(string $key)
    {
        return self::$redis->hKeys(self::$pre.$key);
    }

    public static function hVals(string $key)
    {
        return self::$redis->hVals(self::$pre.$key);
    }

    public static function hLen(string $key)
    {
        return self::$redis->hLen(self::$pre.$key);
    }

    public static function hdel(string $key, string $field, string ...$other_fields)
    {
        return self::$redis->hDel(self::$pre.$key, $field, ...$other_fields);
    }

    public static function hIncrBy(string $key, string $field, int $value)
    {
        return self::$redis->hIncrBy(self::$pre.$key, $field, $value);
    }

    public static function hIncrByFloat(string $key, string $field, float $value)
    {
        return self::$redis->hIncrByFloat(self::$pre.$key, $field, $value);
    }

    public static function lPush(string $key, ...$elements)
    {
        return self::$redis->lPush(self::$pre.$key, ...$elements);
    }

    public static function rPush(string $key, ...$elements)
    {
        return self::$redis->rPush(self::$pre.$key, ...$elements);
    }

    public static function lLen(string $key)
    {
        return self::$redis->lLen(self::$pre.$key);
    }

    public static function lRange(string $key, int $start, int $end)
    {
        return self::$redis->lrange(self::$pre.$key, $start, $end);
    }

    public static function lPop(string $key, int $count = 0)
    {
        return self::$redis->lPop(self::$pre.$key, $count);
    }

    public static function rPop(string $key, int $count = 0)
    {
        return self::$redis->rPop(self::$pre.$key, $count);
    }

    public static function lIndex(string $key, int $index)
    {
        return self::$redis->lindex(self::$pre.$key, $index);
    }

    public static function lRem(string $key, $value, int $count = 0)
    {
        return self::$redis->lrem(self::$pre.$key, $value, $count);
    }

    public static function lSet(string $key, int $index, $value)
    {
        return self::$redis->lSet(self::$pre.$key, $index, $value);
    }

    public static function lTrim(string $key, int $start, int $end)
    {
        return self::$redis->ltrim(self::$pre.$key, $start, $end);
    }

    public static function sAdd(string $key, mixed $value, ...$other_values)
    {
        return self::$redis->sAdd(self::$pre.$key, $value, ...$other_values);
    }

    public static function sAddArray(string $key, array $values)
    {
        return self::$redis->sAddArray(self::$pre.$key, $values);
    }

    public static function sMembers(string $key)
    {
        return self::$redis->sMembers(self::$pre.$key);
    }

    public static function sIsMember(string $key, $value)
    {
        return self::$redis->sismember(self::$pre.$key, $value);
    }

    public static function sCard(string $key)
    {
        return self::$redis->sCard(self::$pre.$key);
    }

    public static function sPop(string $key, int $count = 0)
    {
        return self::$redis->sPop(self::$pre.$key, $count);
    }

    public static function sPop4(string $key, int $count = 0)
    {
        if ($count === 0) {
            $r = self::$redis->sPop(self::$pre.$key, 1);
            return $r[0];
        }else{
            return self::$redis->sPop(self::$pre.$key, $count);
        }
    }

    public static function sRandMember(string $key, int $count = 0)
    {
        return self::$redis->sRandMember(self::$pre.$key, $count);
    }

    public static function sRandMember4(string $key, int $count = 0)
    {
        if ($count === 0) {
            $r = self::$redis->sRandMember(self::$pre.$key, 1);
            return $r[0];
        }else{
            return self::$redis->sRandMember(self::$pre.$key, $count);
        }
    }

    public static function sRem(string $key, mixed $value, mixed ...$other_values)
    {
        return self::$redis->srem(self::$pre.$key, $value, ...$other_values);
    }

    public static function zAdd(string $key, $score_or_options, ...$more_scores_and_mems)
    {
        return self::$redis->zAdd(self::$pre.$key, $score_or_options, ...$more_scores_and_mems);
    }

    public static function zCard(string $key)
    {
        return self::$redis->zCard(self::$pre.$key);
    }

    public static function zScore(string $key, string $member)
    {
        return self::$redis->zScore(self::$pre.$key, $member);
    }

    public static function zRank(string $key, $member)
    {
        return self::$redis->zRank(self::$pre.$key, $member);
    }

    public static function zRevRank(string $key, $member)
    {
        return self::$redis->zRevRank(self::$pre.$key, $member);
    }

    public static function zRangeByScore(string $key, $start, $end, array $options = [])
    {
        return self::$redis->zRangeByScore(self::$pre.$key, $start, $end, $options);
    }

    public static function zRevRangeByScore(string $key, $max, $min, $options = [])
    {
        return self::$redis->zRevRangeByScore(self::$pre.$key, $max, $min, $options);
    }

    public static function zRange(string $key, $start, $end, $options = null)
    {
        return self::$redis->zRange(self::$pre.$key, $start, $end, $options);
    }

    public static function zRevRange(string $key, $start, $end, $options = null)
    {
        return self::$redis->zRevRange(self::$pre.$key, $start, $end, $options);
    }

    public static function zIncrBy(string $key, float $value, $member)
    {
        return self::$redis->zIncrBy(self::$pre.$key, $value, $member);
    }

    public static function zRem(string $key, $member, ...$other_members)
    {
        return self::$redis->zRem(self::$pre.$key, $member, ...$other_members);
    }

    public static function close(): bool
    {
        return self::$redis->close();
    }

}