
LitePhp 1.0
===============

[![Total Downloads](https://poser.pugx.org/unntech/litephp/downloads)](https://packagist.org/packages/unntech/litephp)
[![Latest Stable Version](https://poser.pugx.org/unntech/liteapi/v/stable)](https://packagist.org/packages/unntech/litephp)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.0-8892BF.svg)](http://www.php.net/)
[![License](https://poser.pugx.org/unntech/litephp/license)](https://packagist.org/packages/unntech/litephp)

LitePhp的公共库，需先创建LiteApp 或LiteApi项目使用



## 主要新特性

* 采用`PHP7`强类型（严格模式）
* 支持更多的`PSR`规范
* 原生多应用支持
* 对IDE更加友好
* 统一和精简大量用法


> LitePhp 1.0的运行环境要求PHP7.0+，兼容PHP8.1

## 安装

~~~
composer require unntech/litephp
~~~


如果需要更新框架使用
~~~
composer update unntech/litephp
~~~

目录结构
~~~
litephp/
├── lib                                     #类库
|   ├── wxMsgCrypt                          #微信消息加密库
|   ├── phpqrcode.php                       #二维码类库
├── src                                     #
|   ├── Config.php                          #Config类
|   ├── Db.php                              #数据库实例类
|   ├── GoogleAuthenticator.php             #Google二次验证类
│   ├── LiComm.php                          #常用函数方法
│   ├── LiCrypt.php                         #jwt类库，自定义token加解密
│   ├── LiHttp.php                          #Http基础类，curl
│   ├── LiRegular.php                       #常用正则
│   ├── LiRsa.php                           #Rsa加解密
│   ├── Lite.php                            #Lite基础类
│   ├── mongodb.php                         #mongodb操作对象类
│   ├── mysqli.php                          #mysql操作对象类
│   ├── qrCode.php                          #二维码生成类
│   ├── Redis.php                           #Redis静态实例类
│   ├── Session.php                         #Session类
│   ├── SnowFlake.php                       #雪花生成64位int
│   ├── sqlsrv.php                          #mssql server 操作对象类
│   ├── Template.php                        #视图模板文件载入类
│   ├── Tree.php                            #树型通用类
│   ├── UUID.php                            #UUID生成器
│   ├── Validate.php                        #常用数据验证器
│   ├── Weixin.php                          #微信消息基础类
├── tests                                   #测试样例，可删除
├── composer.json                           #
└── README.md
~~~

## 文档

[完全开发手册](#)

## 命名规范

`LitePhp`遵循PSR-2命名规范和PSR-4自动加载规范。

## 参与开发

直接提交PR或者Issue即可

## 版权信息

LitePhp遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2022 by Jason Lin All rights reserved。创建于2022年除夕夜。

