<?php

namespace LitePhp;

/**
 * SESSION类
 */
class Session
{
    protected static $session = [];
    protected static $save = 'file';
    protected static $redisHandle, $option = [];
    public static $session_id = '';

    /**
     * SESSION启动
     * @param $option ['save'=>'redis', 'handle'=$redis]
     * @return void
     */
    public static function start($option = [])
    {
        self::$save = $option['save'] ?? 'file';
        if(empty($_COOKIE['LSESSID'])){
            self::$session_id = uniqid() . dechex(SnowFlake::generateParticle());
            setcookie('LSESSID', self::$session_id, 0, '/');
        }else{
            self::$session_id = $_COOKIE['LSESSID'];
        }
        switch (self::$save){
            case 'redis':
                self::$redisHandle = $option['handle'];
                if(self::$redisHandle->exists('SESSID:'.self::$session_id)){
                    $var = self::$redisHandle->get('SESSID:'.self::$session_id);
                    self::$session = unserialize($var);
                }else{
                    self::$session = [];
                }
                break;
            default: //file
                self::$option['path'] = $option['path'] ?? '/tmp/';
                $filename = self::$option['path'] . 'lite_' . self::$session_id;
                if(file_exists($filename)){
                    $var = file_get_contents($filename);
                    self::$session = unserialize($var);
                }else{
                    self::$session = [];
                }
        }
    }

    /**
     * 获取SESSION ID
     * @return string
     */
    public static function id(): string
    {
        return self::$session_id;
    }

    /**
     * 获取一个SESSION变量，参数为空则读取所有
     * @param string|null $name 变量名，支持多级直接读取 .号分隔
     * @return array|mixed|null
     */
    public static function get(string $name = null)
    {
        // 无参数时获取所有
        if (empty($name)) {
            return self::$session;
        }

        if (false === strpos($name, '.')) {
            return self::$session[$name] ?? null;
        }

        $names    = explode('.', $name);
        $var  = self::$session;

        // 按.拆分成多维数组进行判断
        foreach ($names as $val) {
            if (isset($var[$val])) {
                $var = $var[$val];
            } else {
                return null;
            }
        }

        return $var;
    }

    /**
     * 保存SESSION变量
     * @param string $name
     * @param $value
     * @return void
     */
    public static function set(string $name, $value)
    {
        $var[$name] = $value;
        self::$session = array_merge(self::$session, $var);
        self::save();
    }

    /**
     * 删除一个SESSION变量
     * @param string $name
     * @return void
     */
    public static function delete(string $name)
    {
        unset(self::$session[$name]);
        self::save();
    }

    /**
     * 清空所有SESSION变量
     * @return void
     */
    public static function clear()
    {
        self::$session = [];
        self::save();
    }

    /**
     * SESSION存入
     * @return void
     */
    protected static function save()
    {
        switch (self::$save){
            case 'redis':
                if(empty(self::$session)){
                    self::$redisHandle->del('SESSID:'.self::$session_id);
                }else{
                    self::$redisHandle->set('SESSID:'.self::$session_id, serialize(self::$session), 86400);
                }
                break;
            default: //file
                $filename = self::$option['path'] . 'lite_' . self::$session_id;
                if(empty(self::$session)){
                    @unlink($filename);
                }else{
                    file_put_contents($filename, serialize(self::$session));
                }

        }
    }

}