<?php

namespace LitePhp;

use LitePhp\Db;

abstract class Model
{
    public static $instance = null;
    /**
     * 数据是否存在
     * @var bool
     */
    private $exists = false;

    /**
     * 模型名称
     * @var string
     */
    protected $name;

    /**
     * 主键值
     * @var string
     */
    protected $key = 'id';

    /**
     * 数据表名称
     * @var string
     */
    protected $table;
    
    /**
     * 数据表后缀（默认为空）
     * @var string
     */
    protected $suffix = '';
    
    protected $data, $origin;

    /**
     * 初始化过的模型.
     * @var array
     */
    protected static $initialized = [];

    /**
     * Db对象
     * @var \LitePhp\mysqli
     */
    protected static $db;

    /**
     * 容器对象的依赖注入方法
     * @var callable
     */
    protected static $invoker;

    /**
     * 服务注入
     * @var Closure[]
     */
    protected static $maker = [];

    /**
     * 方法注入
     * @var Closure[][]
     */
    protected static $macro = [];

    /**
     * 静状实例化
     * @return Model|null
     */
    public static function instance(): ?Model
    {
        if (!static::$instance) static::$instance = new static();
        return static::$instance;
    }

    /**
     * 设置服务注入
     * @access public
     * @param Closure $maker
     * @return void
     */
    public static function maker(Closure $maker)
    {
        static::$maker[] = $maker;
    }

    /**
     * 设置方法注入
     * @access public
     * @param string $method
     * @param Closure $closure
     * @return void
     */
    public static function macro(string $method, Closure $closure)
    {
        if (!isset(static::$macro[static::class])) {
            static::$macro[static::class] = [];
        }
        static::$macro[static::class][$method] = $closure;
    }

    /**
     * 设置Db对象
     * @access public
     * @param Db $db Db对象
     * @return void
     */
    public static function setDb($db)
    {
        self::$db = $db;
    }

    /**
     * 设置容器对象的依赖注入方法
     * @access public
     * @param callable $callable 依赖注入方法
     * @return void
     */
    public static function setInvoker(callable $callable): void
    {
        self::$invoker = $callable;
    }

    /**
     * 调用反射执行模型方法 支持参数绑定
     * @access public
     * @param mixed $method
     * @param array $vars 参数
     * @return mixed
     */
    public function invoke($method, array $vars = [])
    {
        if (self::$invoker) {
            $call = self::$invoker;
            return $call($method instanceof Closure ? $method : Closure::fromCallable([$this, $method]), $vars);
        }

        return call_user_func_array($method instanceof Closure ? $method : [$this, $method], $vars);
    }

    /**
     * 架构函数
     * @access public
     * @param array $data 数据
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;

        // 记录原始数据
        $this->origin = $this->data;

        if (empty($this->name)) {
            // 当前模型名
            $name       = str_replace('\\', '/', static::class);
            $this->name = basename($name);
        }
        if(empty($this->table)){
            $_t = preg_replace('/[A-Z]/', '_$0', $this->name);
            if(substr($_t, 0, 1) === '_'){
                $_t = substr($_t, 1);
            }
            $this->table = strtolower($_t);
        }
        if(!empty($this->pk)){
            $this->key = $this->pk;
        }

        if (!empty(static::$maker)) {
            foreach (static::$maker as $maker) {
                call_user_func($maker, $this);
            }
        }

        self::setDb(Db::$db);
        self::$db->table($this->table . $this->suffix);

        // 执行初始化操作
        $this->initialize();
    }

    /**
     * 获取当前模型名称
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取当前模型的数据库查询对象
     * @access public
     * @return \LitePhp\mysqli
     */
    public function db()
    {

        $query = self::$db->table($this->table . $this->suffix);

        // 返回当前模型的数据库查询对象
        return $query;
    }

    /**
     *  初始化模型
     * @access private
     * @return void
     */
    private function initialize(): void
    {
        if (!isset(static::$initialized[static::class])) {
            static::$initialized[static::class] = true;
            static::init();
        }
    }

    /**
     * 初始化处理
     * @access protected
     * @return void
     */
    protected static function init()
    {
    }

    /**
     * 设置数据是否存在
     * @access public
     * @param bool $exists
     * @return $this
     */
    public function exists(bool $exists = true)
    {
        $this->exists = $exists;
        return $this;
    }

    /**
     * 判断数据是否存在数据库
     * @access public
     * @return bool
     */
    public function isExists(): bool
    {
        return $this->exists;
    }

    /**
     * 判断模型是否为空
     * @access public
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }
    
    /**
     * 设置数据表后缀
     * @param string $suffix
     * @return void
     */
    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * @param $method
     * @param $args
     * @return \LitePhp\mysqli
     */
    public function __call($method, $args)
    {
        if (isset(static::$macro[static::class][$method])) {
            return call_user_func_array(static::$macro[static::class][$method]->bindTo($this, static::class), $args);
        }

        return call_user_func_array([$this->db(), $method], $args);
    }

    /**
     * @param $method
     * @param $args
     * @return \LitePhp\mysqli
     */
    public static function __callStatic($method, $args)
    {
        if (isset(static::$macro[static::class][$method])) {
            return call_user_func_array(static::$macro[static::class][$method]->bindTo(null, static::class), $args);
        }

        $model = self::instance();

        return call_user_func_array([$model->db(), $method], $args);
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {

    }

    /**
     * 按主键查找一条记录
     * @param $id
     * @return array|false|string|null
     */
    public static function find($id)
    {
        $model = self::instance();
        return $model->db()->where([$model->key => $id])->selectOne();
    }

    /**
     * @param $condition
     * @return mysqli
     */
    public static function where($condition = '')
    {
        return self::instance()->db()->where($condition);
    }

    /**
     * 按条件删除记录
     * @param $condition
     * @return bool|\mysqli_result|string
     */
    public static function delete($condition = null)
    {
        if(empty($condition)){
            return self::instance()->db()->delete();
        }else{
            return self::instance()->db()->where($condition)->delete();
        }
    }

    /**
     * 按主键删除记录
     * @param $id, 可以单个，也可以多个数组
     * @return bool|\mysqli_result|string
     */
    public static function deleteByKey(int|array $id)
    {
        $model = self::instance();
        if(is_array($id)){
            $where = [$model->key => ['IN', $id]];
        }else{
            $where = [$model->key => $id];
        }
        return $model->db()->where($where)->delete();
    }

    /**
     * 插入数据
     * @param $data
     * @return bool|int|\mysqli_result|string|null
     */
    public static function insert($data)
    {
        return self::instance()->db()->insert($data);
    }

    /**
     * 根据主键更新记录
     * @param $id
     * @param $data
     * @return bool|\mysqli_result|string
     */
    public static function update($id, $data)
    {
        $model = self::instance();
        return $model->db()->where([$model->key => $id])->fields($data)->update();
    }

}