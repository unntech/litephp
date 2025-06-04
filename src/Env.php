<?php

namespace LitePhp;


/**
 * Env管理类
 */
class Env
{
    /**
     * 环境变量数据
     * @var array
     */
    protected static $data = [];

    /**
     * 数据转换映射
     * @var array
     */
    protected static $convert = [
        'true'  => true,
        'false' => false,
        'off'   => false,
        'on'    => true,
    ];


    /**
     * 读取环境变量定义文件
     * @access public
     * @param string $file 环境变量定义文件
     * @return void
     */
    public static function load(string $file): void
    {
        self::$data = $_ENV;
        $env = parse_ini_file($file, true, INI_SCANNER_RAW) ?: [];
        self::set($env);
    }

    /**
     * 获取环境变量值
     * @access public
     * @param string|null $name 环境变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $name = null, $default = null)
    {
        if (is_null($name)) {
            return self::$data;
        }

        $name = strtoupper(str_replace('.', '_', $name));
        if (isset(self::$data[$name])) {
            $result = self::$data[$name];

            if (is_string($result) && isset(self::$convert[$result])) {
                return self::$convert[$result];
            }

            return $result;
        }

        return self::getEnv($name, $default);
    }

    protected static function getEnv(string $name, $default = null)
    {
        $result = getenv('PHP_' . $name);

        if (false === $result) {
            return $default;
        }

        if ('false' === $result) {
            $result = false;
        } elseif ('true' === $result) {
            $result = true;
        }

        if (!isset(self::$data[$name])) {
            self::$data[$name] = $result;
        }

        return $result;
    }

    /**
     * 设置环境变量值
     * @access public
     * @param string|array $env   环境变量
     * @param mixed        $value 值
     * @return void
     */
    public static function set($env, $value = null): void
    {
        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);

            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        self::$data[$key . '_' . strtoupper($k)] = $v;
                    }
                } else {
                    self::$data[$key] = $val;
                }
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));

            self::$data[$name] = $value;
        }
    }

    /**
     * 检测是否存在环境变量
     * @access public
     * @param string $name 参数名
     * @return bool
     */
    public static function has(string $name): bool
    {
        return !is_null(self::get($name));
    }

    /**
     * 检测是否存在环境变量
     * @access public
     * @param string $name 参数名
     * @return bool
     */
    public static function isset(string $name): bool
    {
        return self::has($name);
    }

}
