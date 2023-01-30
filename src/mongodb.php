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
	
	//插入多条数据
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
	
	/*
	 * $filter = ['a'=>['$lt'=>9]];
	 * $options = ['projection' => ['_id' => 0],'sort'=>['a'=> -1], 'limit'=>5, 'skip'=>0];
	 */
	public function query($table, $filter = [], $options = null){
		$query = new \MongoDB\Driver\Query($filter, $options);
		$cursor = $this->manager->executeQuery($this->dbname . '.' . $table, $query);
		
		return $cursor->toArray();
	}
	
	//把query出来的数据对象转换成数组的扩展方法，方便查看使用
	public function cursorObjToArray($cursor){
		$result = [];
		foreach($cursor as $rec){
			$r = [];
			foreach($rec as $k => $v){
				if(gettype($v) == 'object'){
					switch(get_class($v)){
						case 'MongoDB\BSON\ObjectId':
							$_v = $v->__toString();
							$r['_time'] = $v->getTimestamp();
							break;
						case 'MongoDB\BSON\UTCDateTime':
							$_v = $v->toDateTime()->setTimezone(new \DateTimeZone('Asia/Shanghai'));
							break;
						default:
							$_v = $v;
					}
				}else{
					$_v = $v;
				}
				$r[$k] = $_v;
			}
			$result[] = $r;
		}
		return($result);
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