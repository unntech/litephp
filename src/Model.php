<?php

namespace LitePhp;

abstract class Model
{
    public static $instance = null;

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

    /**
     * Db对象
     * @var mysqli
     */
    protected static $db;

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
     * 设置Db对象
     * @access public
     * @param Mysqli|SqlSrv|MongoDB $db Db对象
     * @return void
     */
    public static function setDb($db)
    {
        self::$db = $db;
    }

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
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

        self::setDb(Db::$db);
        self::$db->table($this->table . $this->suffix);

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
     * @return mysqli
     */
    public function db()
    {
        // 返回当前模型的数据库查询对象
        return self::$db->table($this->table . $this->suffix);
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
     * @return mysqli
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->db(), $method], $args);
    }

    /**
     * @param $method
     * @param $args
     * @return mysqli
     */
    public static function __callStatic($method, $args)
    {
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
     * @param string|array $condition
     * @return mysqli
     */
    public static function where($condition = '')
    {
        return self::instance()->db()->where($condition);
    }

    /**
     * 按条件删除记录
     * @param string|array|null $condition
     * @return bool|Models\MysqliResult|string
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
     * @return bool|Models\MysqliResult|string
     */
    public static function deleteByKey($id)
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
     * @param array $data
     * @return bool|int|string|null
     */
    public static function insert(array $data)
    {
        return self::instance()->db()->insert($data);
    }

    /**
     * 根据主键更新记录
     * @param $id
     * @param array $data
     * @return bool|Models\MysqliResult|string
     */
    public static function update($id, array $data)
    {
        $model = self::instance();
        return $model->db()->where([$model->key => $id])->update($data);
    }

}