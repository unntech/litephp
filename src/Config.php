<?php

namespace LitePhp;

/**
 * 配置管理类
 * @package LiteApi
 */
class Config
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [];

    /**
     * 配置文件目录
     * @var string
     */
    protected $path;

    /**
     * 构造方法
     * @access public
     */
    public function __construct($path = '')
    {
        if(empty($path)){
            $this->path = dirname(__DIR__ , 4) . '/config/';
        }else{
            $this->path = $path;
        }
    }
    
    /**
     * 加载配置文件
     * @access public
     * @param  array $file 配置文件名
     * @return array
     */
    public function load($file)
    {
        if(is_array($file)){
            foreach($file as $k=>$v){
                $fn = $this->path.$v.'.php';
                if(file_exists($fn)){
                    $config = include $fn;
                    $this->set($config, $v);    
                }else{
                    echo "{$fn} not found!\n";
                }
                
            }
        }else{
            $fn = $this->path.$file.'.php';
            if(file_exists($fn)){
                $config = include $fn;
                $this->set($config, $file);
            }else{
                echo "{$fn} not found!\n";
            }
        }

        return $this->config;
    }
    
    /**
     * 获取一级配置
     * @access protected
     * @param  string $name 一级配置名
     * @return array
     */
    protected function pull(string $name)
    {
        return isset($this->config[$name]) ? $this->config[$name] :  [];
    }
    
    /**
     * 获取配置参数 为空则获取所有配置
     * @access public
     * @param  string $name    配置参数名（支持多级配置 .号分割）
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function get(string $name = null, $default = null)
    {
        // 无参数时获取所有
        if (empty($name)) {
            return $this->config;
        }

        if (false === strpos($name, '.')) {
            return $this->pull($name);
        }

        $name    = explode('.', $name);
        $config  = $this->config;

        // 按.拆分成多维数组进行判断
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }
    
    /**
     * 设置配置参数 name为数组则为批量设置
     * @access public
     * @param  array  $config 配置参数
     * @param  string $name 配置名
     * @return array
     */
    public function set(array $config, string $name = null)
    {
        if (!empty($name)) {
            if (isset($this->config[$name])) {
                $result = array_merge($this->config[$name], $config);
            } else {
                $result = $config;
            }

            $this->config[$name] = $result;
        } else {
            $result = $this->config = array_merge($this->config, $config);
        }

        return $result;
    }
    
    public function exist($key){
        if(isset($this->config[$key])){
            return true;
        }else{
            return false;
        }
    }
    
}