加解密及验签文档
===============

## Response
### 数据结构
* head `头部信息`  errcode `错误代码` msg `错误信息`
* body `数据主体`
* signType `签名类型` MD5 | SHA256 | RSA | ECDSA
* sign `签名`
* encrypted `true: 数据加密`
* encryption `加密参数信息，加密类型支持：` RSA | RSAIES | ECIES
* bodyEncrypted `加密密文`

### 签名算法
* #### MD5 | SHA256
* 把 head 和 body 数据按键名升序排序，转JSON + bodyEncrypted 值 + secret密钥
* JSON encode增加中文不转unicode和不转义反斜杠两个参数
* PHP签名字符串连接示例： ```json_encode($head,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $bodyEncrypted. $secret```
* #### RSA | ECDSA
* 签名字符串连接方式相同，只是无需 + secret密钥

### 数据加密
* 使用RSA | ECIES 非对称加密 把 body 数据 JSON 序列化后 加密
* 密文存放 bodyEncrypted，接收者获取解密存回 body 使用

### 使用示例
```php
# 输出数据
Response::success(['abc'=>111]);
# 输出错误
Response::success(101, '错误示例');
```

## Request
* head `头部信息` 
* body `数据主体`
* signType `签名类型` MD5 | SHA256 | RSA | ECDSA
* sign `签名`
* encrypted `true: 数据加密`
* encryption `加密参数信息，加密类型支持：` RSA | RSAIES | ECIES
* bodyEncrypted `加密密文`

### 签名算法
* #### MD5 | SHA256
* 把 head 和 body 数据按键名升序排序，转JSON + bodyEncrypted 值 + secret密钥
* JSON encode增加中文不转unicode和不转义反斜杠两个参数
* PHP签名字符串连接示例： ```json_encode($head,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $bodyEncrypted. $secret```
* #### RSA | ECDSA
* 签名字符串连接方式相同，只是无需 + secret密钥

### 数据加密
* 使用RSA | ECIES 非对称加密 把 body 数据 JSON 序列化后 加密
* 密文存放 bodyEncrypted，接收者获取解密存回 body 使用

### 使用示例
```php
# 封装请求数据集
Request::generate(['abc'=>123], 'json');
# 验签及解密文数据集
Request::verifySign($data);
```

### ECIES 加密方式详解
* 生成临时ECDSA 密钥对
* 使用对方公钥和临时私钥通过ECDH算法生成共享密钥
* 生成随机AES密钥iv值
* 用共享密钥对数据进行AES-128-CFB加密 参数 OPENSSL_RAW_DATA
* 密文和iv值进行base64处理（支持HEX）
* 使用SHA256计算哈希值(mac)，用于接收者验证数据完整性
* 把 临时公钥 `tempPublicKey` 向量 `iv` 编码方式 `code` 密文哈希值 `mac` 加密类型 `ECIES` 放入 encryption 字段

### ECIES 解密方式详解
* 把接收到的密文使用SHA256计算哈希值，验证mac值是否相同，判定数据是否完整
* 把接收到的 临时公钥 `tempPublicKey` 和自己的私钥通过ECDH算法生成共享密钥
* 用共享密钥当作AES密钥和收到的向量 `iv` 采用 aes-128-cfb 进行解密， 参数 OPENSSL_RAW_DATA
* 得到原文

### RSAIES 加密方式详解
* 生成随机AES密钥，使用 RSA 加密方法对其加密
* 生成随机AES密钥iv值
* 用随机AES密钥对数据进行AES-128-CFB加密，参数 OPENSSL_RAW_DATA
* 密文和iv值进行base64处理（支持HEX）
* 使用SHA256计算哈希值(mac)，用于接收者验证数据完整性
* 把 加密的随机AES密钥 `cipher` 向量 `iv` 编码方式 `code` 密文哈希值 `mac` 加密类型 `RSAIES` 放入 encryption 字段

### RSAIES 解密方式详解
* 把接收到的密文使用SHA256计算哈希值，验证mac值是否相同，判定数据是否完整
* 把接收到的 加密的随机AES密钥 `cipher` 编码还原 `base64_decode` 后，使用 RSA 解密方法对其解密得到AES密钥原文
* 用得到的随机AES密钥和收到的向量 `iv` 采用 aes-128-cfb 进行解密， 参数 OPENSSL_RAW_DATA
* 得到原文