<?php

namespace LitePhp;

class Lite {
    const VERSION = '1.0.8';
    const Framework = 'LitePhp';
    
    /**
     * 应用根目录
     * @var string
     */
    protected static $rootPath = '';

    /**
     * 框架目录
     * @var string
     */
    protected static $LiPath = '';
    
    /**
     * 获取应用根目录
     * @return string
     */
    public static function getRootPath(){
        if(empty(self::$rootPath)){
            self::$rootPath = dirname(__DIR__ , 4);
        }
        
        return self::$rootPath;
    }
    
    /**
     * 设置应用根目录
     * @param string $path 目录
     * @return void
     */
    public static function setRootPath( string $path){
        self::$rootPath = $path;
    }
    
    /**
     * 获取框架根目录
     * @return string
     */
    public static function getLitePhpPath(){
        if(empty(self::$LiPath)){
            self::$LiPath = __DIR__ ;
        }
        
        return self::$LiPath;
    }
    
    
}