<?php

namespace LitePhp;

class mongodb {
	public $manager, $dbname;
	
	
	/**
     * 构造方法
     * @access public
     */
    public function __construct($cfg)
    {
		$this->dbname  = $cfg['dbname'];
        $this->manager = new \MongoDB\Driver\Manager($cfg['uri']);
    }
	
	//插入数据
	public function insert($table, $data){
		$bulk = new \MongoDB\Driver\BulkWrite;
		$bulk->insert($data);
		$result = $this->manager->executeBulkWrite($this->dbname . '.' . $table, $bulk);
		return $result;
	}
	
	public function inserts($table, $datas){
		$bulk = new \MongoDB\Driver\BulkWrite;
		foreach($datas as $k=>$v){
			$bulk->insert($v);
		}
		$result = $this->manager->executeBulkWrite($this->dbname . '.' . $table, $bulk);
		return $result;
	}
	
	/*
	 * $deleteOptions = ['limit' => false]
	 * 默认false为删除所有匹配，true则只删除一条
	 */
	public function delete($table, $filter = [], $deleteOptions = null){
		$bulk = new \MongoDB\Driver\BulkWrite;
		$bulk->delete($filter);
		$result = $this->manager->executeBulkWrite($this->dbname . '.' . $table, $bulk);
		return $result;
	}
	
	/*
	 * $updateOptions = ['multi' => false, 'upsert' => false];
	 * multi 为true 则全部更新，默认false则只更新第一条
	 */
	public function update($table, $filter, $newObj, $updateOptions = null){
		$bulk = new \MongoDB\Driver\BulkWrite;
		$bulk->update($filter, $newObj, $updateOptions);
		$result = $this->manager->executeBulkWrite($this->dbname . '.' . $table, $bulk);
		return $result;
	}
	
	public function query($table, $filter = [], $options = null){
		$query = new \MongoDB\Driver\Query($filter, $options);
		$cursor = $this->manager->executeQuery($this->dbname . '.' . $table, $query);
		$ret = [];
		foreach($cursor as $v){
			$ret[] = $v;
		}
		return $ret;
	}
	
	public function ISODate($d = null){
		if($d === null){
			$d = intval(microtime(true) * 1000);
		}else{
			$d = intval($d * 1000);
		}
		$st = new \MongoDB\BSON\UTCDateTime ($d);
		return $st;
	}
}