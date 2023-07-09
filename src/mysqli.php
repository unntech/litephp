<?php

namespace LitePhp;

class mysqli {
	protected $connid;
	public $querynum = 0;
	public $err = 0;
	public $linked = 1;
	public $resultObj;
	public $sql = '';
	protected $options = ['table'=>'', 'alias'=> null, 'fields'=>null, 'condition'=>null, 'param'=>[], 'fetchSql'=>false];
	protected $query_finished = false;
    
    /**
     * 构造方法
     * @access public
     */
    public function __construct($cfg)
    {
        return $this->connect($cfg['hostname'], $cfg['hostport'], $cfg['username'], $cfg['password'], $cfg['dbname'], $cfg['charset']);
    }

	public function connect($dbhost, $dbport, $dbuser, $dbpass, $dbname='', $dbcharset='') {
		$this->connid = mysqli_init();
		if(mysqli_real_connect($this->connid, $dbhost, $dbuser, $dbpass, false, $dbport)) {
			//
		} else {
			$this->linked = 0;
			$retry = 2;
			while($retry-- > 0) {
				if(mysqli_real_connect($this->connid, $dbhost, $dbuser, $dbpass, false, $dbport)) {
					$this->linked = 1;
					break;
				}
			}
			if($this->linked == 0) {
				$this->halt('Can not connect to MySQL server');
			}
		}
		$version = $this->version();
		if($version > '4.1' && !empty($dbcharset)) mysqli_query($this->connid, "SET character_set_connection=".$dbcharset.", character_set_results=".$dbcharset.", character_set_client=binary");
		//if($version > '5.0') mysqli_query($this->connid, "SET sql_mode=''");
		if(!empty($dbname) && !mysqli_select_db($this->connid, $dbname)) $this->halt('Cannot use database '.$dbname);
		return $this->connid;
	}

	public function select_db($dbname) {
		return mysqli_select_db($this->connid, $dbname);
	}

	public function query($sql) {
		try {
            if(!($query = mysqli_query($this->connid, $sql))) $this->halt('MySQL Query Error', $sql);
        }catch (\Throwable $e){
            $this->exception($e, $sql);
        }
		$this->querynum++;
		$this->query_finished = true;
		$this->resultObj = $query;
		return $query;
	}
	
	//返回当前SQL语句
	public function getLastSql(){
		return $this->sql;
	}
	
	//事务操作
	public function startTrans(){
		return $this->query('START TRANSACTION');
	}
	
	public function commit(){
		return $this->query('COMMIT');
	}
	
	public function rollback(){
		return $this->query('ROLLBACK');
	}
	
	/*
	 * 面向对象 链式操作
	 */
	public function table($table, $alias= null){
		if(empty($table)){
			return false;
		}
		$table = preg_replace('/[^A-Za-z0-9_\.`]/', '', $table);
		$this->options = ['table'=>"{$table}", 'alias'=>$alias, 'fields'=>null, 'condition'=>null, 'param'=>[], 'fetchSql'=>false];
		$this->query_finished = false;
		return $this;
	}
	
	public function alias($alias){
		$this->options['alias'] = $alias;
		return $this;
	}
	
	public function fetchSql(bool $fetch = true){
		$this->options['fetchSql'] = $fetch;
		return $this;
	}
	
	public function fields($fields){
		$this->options['fields'] = $this->_fieldsAddAlais($fields);
		return $this;
	}
	
	public function where($condition){
        if(is_array($condition)){
            if(!empty($this->options['condition']) && !is_array($this->options['condition'])){
                $this->options['condition'] = [];
            }
            if($this->_array_is_list($condition)){
                if(empty($this->options['condition']) || !is_array($this->options['condition'])){
                    $this->options['condition'] = $condition;
                }else{
                    $this->options['condition'] = array_merge($this->options['condition'], $condition);
                }
            }else{
                $this->options['condition'][] = $condition;
            }
        }else{
            $this->options['condition'] = $condition;
        }
		return $this;
	}
	
	public function param($param){
		$this->options['param'] = $param;
		return $this;
	}
	
	public function limit($limit){
		$this->options['param']['LIMIT'] = $limit;
		return $this;
	}
	
	public function join($join){
		$this->options['param']['JOIN'] = $join;
		return $this;
	}
	
	public function groupby($groupby){
		$this->options['param']['GROUPBY'] = $groupby;
		return $this;
	}
	
	public function order($order){
		$this->options['param']['ORDER'] = $order;
		return $this;
	}
	
	public function getOptions(){
		return $this->options;
	}
	
	//使用buildSql构造子查询
	public function buildSql(){
		$this->options['fetchSql'] =  true;
		$res = $this->select();
		$subQuery = '( ' . $res . ' )';
		return $subQuery;
	}
	
	//查询结果转数组
	public function toArray($indexfield=''){
		$ret = array();
        while($r = $this->resultObj->fetch_assoc()){
            if($indexfield=='' || !isset($r[$indexfield])){
                $ret[] = $r;
            }else{
                $ret[$r[$indexfield]] = $r;
            }    
        }
        return $ret;
	}
	
	/*
	 * 更新数据库表
	 * $table 数据库表名
	 * $fields 更新的字段及值，示例：['field1'=>1,'field2'=>'abc', 'field3'=>true]
	 * $condition 更新条件，默认无，即全部更新。$condition 若为字符串，则直接按条件查询
	 * $condition 若为数组则按条件规则，示例：['id'=>1]  //id=1
	 * ['id'=>1,'m'=>2] //id = 1 AND m = 2
	 * ['id'=>['>',1], 'fed'=>['LIKE','S%']] //id > 1 and fed LIKE 'S%'
	 */
	
	public function update($table = null, $fields = [], $condition = null){
		if(empty($table)){
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
			$fields = $this->options['fields'];
			$condition = $this->options['condition'];
		}
        $table = str_replace('.', '`.`', $table);
		if(!is_array($fields) || empty($fields)){
			return false;
		}
		$this->sql = '';
		$ufields = $this->_fields_strip($fields);
		if(empty($ufields)){
			return false;
		}
		$sql = "UPDATE `{$table}` SET " . implode(', ', $ufields);
		if(!empty($condition)){
			$ct = gettype($condition);
			if($ct == 'string'){
				$sql .= " WHERE " . $condition;
			}elseif($ct == 'array'){
				$cons = $this->_condition_strip($condition);
				$sql .= " WHERE " . implode(' AND ', $cons);
			}else{
				//条件参数类型不正常
				$this->sql = 'condition type error';
				return false;
			}
			
		}
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		return $this->query($sql) ;
	}
	
	/*
	 * 删除数据库表
	 * $table 数据库表名
	 * $condition 删除条件，默认无，即全部删除。$condition 若为字符串，则直接按条件查询
	 * $condition 若为数组则按条件规则，示例：['id'=>1]  //id=1
	 * ['id'=>1,'m'=>2] //id = 1 AND m = 2
	 * ['id'=>['>',1], 'fed'=>['LIKE','S%']] //id > 1 and fed LIKE 'S%'
	 */
	
	public function delete($table = null, $condition = null){
		if(empty($table)){
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
			$condition = $this->options['condition'];
		}
        $table = str_replace('.', '`.`', $table);
		$this->sql = '';
		$sql = "DELETE FROM `{$table}` ";
		if(!empty($condition)){
			$ct = gettype($condition);
			if($ct == 'string'){
				$sql .= " WHERE " . $condition;
			}elseif($ct == 'array'){
				$cons = $this->_condition_strip($condition);
				$sql .= " WHERE " . implode(' AND ', $cons);
			}else{
				//条件参数类型不正常
				$this->sql = 'condition type error';
				return false;
			}
			
		}
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		return $this->query($sql) ;
	}
	
	/*
	 * 插入数据
	 * $table 数据库表名
	 * $data 数据集
	 */
	public function insert($table, $data = null){
		if(is_array($table) && $data == null){  //兼容对象写法
			$data = $table;
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
		}
        $table = str_replace('.', '`.`', $table);
		$this->sql = '';
		if(empty($data)){
			return false;
		}
		$d = $this->_fields_split($data);
		$sql = "INSERT INTO `{$table}` (" . implode(',', $d[0]) . ") VALUES (" . implode(',', $d[1]) .") ";
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		
		$res = $this->query($sql) ;
		if($res){
			return $this->insert_id();
		}else{
			return $res;
		}
	}
	
	/*
	 * 批量插入数据
	 * $table 数据库表名
	 * $data 数据集 二级数组
	 */
	public function insertAll($table, $data = null){
		if(is_array($table) && $data == null){ //兼容对象写法
			$data = $table;
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
		}
        $table = str_replace('.', '`.`', $table);
		$this->sql = '';
		$d = [];
		foreach($data as $da){
			$d[] = $this->_fields_split($da);
		}
		if(empty($d)){
			return false;
		}

		$sql = "INSERT INTO `{$table}` (" . implode(',', $d[0][0]) . ") VALUES ";
		$first = true;
		foreach($d as $di){
			if(!$first){ $sql .= ', ';}
			$sql .= "(" . implode(',', $di[1]) . ") ";
			$first = false;
		}
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		
		$res = $this->query($sql) ;
		return $res;
	}
	
	/*
	 * 查询数据库表
	 * $table 数据库表名
	 * $fields 查询的字段，示例：['field1','field2', 'b.field3']
	 * $condition 更新条件，默认无。$condition 若为字符串，则直接按条件查询
	 * $condition 若为数组则按条件规则，示例：['id'=>1]  //id=1
	 * ['id'=>1,'m'=>2] //id = 1 AND m = 2
	 * ['id'=>['>',1], 'fed'=>['LIKE','S%']] //id > 1 and fed LIKE 'S%'
	 * $param = [
	 			'JOIN'=>'LEFT JOIN table as b ON a.id = b.id',
				'GROUPBY'=>'cls',
				'ORDER'=>'id desc',
				'LIMIT'=>[0,10]
	 			]
	 */
	public function select($table = null, $fields = null, $condition = null, $param =[]){
		if($table === true){
			$table = null;
			$returnResult = true;
		}else{
			$returnResult = false;
		}
		if(empty($table)){
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
			$fields = $this->options['fields'];
			$condition = $this->options['condition'];
			$param = $this->options['param'];
			$alias = $this->options['alias'];
		}else{
			$alias = null;
		}
        $table = str_replace('.', '`.`', $table);
		$this->sql = '';
		if(empty($fields)){
			$sql = "SELECT ".(empty($alias) ? '' : "{$alias}.")."* FROM `{$table}` ";
		}else{
			$ct = gettype($fields);
			if($ct == 'string'){
				$fields = preg_replace('/[^A-Za-z0-9_,\. `()\*]/', '', $fields);
				$sql = "SELECT {$fields} FROM `{$table}` ";
			}elseif($ct == 'array'){
				$sql = "SELECT ". implode(',', $fields) ." FROM `{$table}` ";
			}else{
				$sql = "SELECT noFields FROM `{$table}` ";
			}
		}
		if(!empty($alias)){
			$sql .= "AS {$alias} ";
		}
		
		if(!empty($param['JOIN'])){
			$str = preg_replace('/[^A-Za-z0-9_,\. `=]/', '', $param['JOIN']);
			$sql .= " {$str} ";
		}
		
		if(!empty($condition)){
			$ct = gettype($condition);
			if($ct == 'string'){
				$sql .= " WHERE " . $condition;
			}elseif($ct == 'array'){
				$cons = $this->_condition_strip($condition);
				$sql .= " WHERE " . implode(' AND ', $cons);
			}else{
				//条件参数类型不正常
				$this->sql = 'condition type error';
				return false;
			}
		}
		
		if(!empty($param['GROUPBY'])){
			$str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['GROUPBY']);
			$sql .= " GROUP BY " .$str;
		}
		if(!empty($param['ORDER'])){
			$str = preg_replace('/[^A-Za-z0-9_,\. `]/', '', $param['ORDER']);
			$sql .= " ORDER BY " .$str;
		}
		if(!empty($param['LIMIT'])){
			if(is_array($param['LIMIT'])){
                $sql .= " LIMIT ".intval($param['LIMIT'][0]).','.intval($param['LIMIT'][1]);
            }else{
                $sql .= " LIMIT ".intval($param['LIMIT']);
            }
		}
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		
		$query = $this->query($sql) ;
		if($returnResult){
			return $query;
		}else{
			return $this;
		}
		
	}
	
	/*
	 * 查询一条数据
	 */
	public function selectOne($table=null, $fields = null, $condition = null){
		if(empty($table)){
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
			$fields = $this->options['fields'];
			$condition = $this->options['condition'];
		}
        $table = str_replace('.', '`.`', $table);
		$this->sql = '';
		if(empty($fields)){
			$sql = "SELECT * FROM `{$table}` ";
		}else{
			$ct = gettype($fields);
			if($ct == 'string'){
				$fields = preg_replace('/[^A-Za-z0-9_,\. `()\*]/', '', $fields);
				$sql = "SELECT {$fields} FROM `{$table}` ";
			}elseif($ct == 'array'){
				$sql = "SELECT ". implode(',', $fields) ." FROM `{$table}` ";
			}else{
				$sql = "SELECT Fields FROM `{$table}` ";
			}
		}
		
		if(!empty($condition)){
			$ct = gettype($condition);
			if($ct == 'string'){
				$sql .= " WHERE " . $condition;
			}elseif($ct == 'array'){
				$cons = $this->_condition_strip($condition);
				$sql .= " WHERE " . implode(' AND ', $cons);
			}else{
				//条件参数类型不正常
				$this->sql = 'condition type error';
				return false;
			}
		}
		
		$sql .= " LIMIT 1";
		
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		
		$query = $this->query($sql) ;
		$row = $this->fetch_array($query);
		$this->free_result($query);
		return $row;
	}
	
	public function getOne($table=null, $fields = null, $condition = null){
		return $this->selectOne($table, $fields, $condition);
	}

	public function get_one($sql) {
		$sql = str_replace(array('select ', ' limit '), array('SELECT ', ' LIMIT '), $sql);
		if(strpos($sql, 'SELECT ') !== false && strpos($sql, ' LIMIT ') === false) $sql .= ' LIMIT 0,1';
		$query = $this->query($sql);
		$r = $this->fetch_array($query);
		$this->free_result($query);
		return $r;
	}
	
	public function getValue($table = null, $fields = null, $condition = null){
		if(empty($table)){
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
			$fields = $this->options['fields'];
			$condition = $this->options['condition'];
		}
        $table = str_replace('.', '`.`', $table);
		$ct = gettype($fields);
		if(empty($fields) || $ct != 'string'){
			return false;
		}
		$fields = preg_replace('/[^A-Za-z0-9_,\. `()\*]/', '', $fields);
		$sql = "SELECT {$fields} FROM `{$table}` ";
		if(!empty($condition)){
			$ct = gettype($condition);
			if($ct == 'string'){
				$sql .= " WHERE " . $condition;
			}elseif($ct == 'array'){
				$cons = $this->_condition_strip($condition);
				$sql .= " WHERE " . implode(' AND ', $cons);
			}else{
				//条件参数类型不正常
				$this->sql = 'condition type error';
				return false;
			}
		}
		$sql .= " LIMIT 1";
		
		$this->sql = $sql;
		if($this->options['fetchSql']){ return $sql; }
		
		$query = $this->query($sql);
		$r = $this->fetch_row($query);
		$this->free_result($query);
		return $r ? $r[0] : null;
	}
	
	public function get_value($sql) {
		$sql = str_replace(array('select ', ' limit '), array('SELECT ', ' LIMIT '), $sql);
		if(strpos($sql, 'SELECT ') !== false && strpos($sql, ' LIMIT ') === false) $sql .= ' LIMIT 0,1';
		$query = $this->query($sql);
		$r = $this->fetch_row($query);
		$this->free_result($query);
		return $r ? $r[0] : null;
	}
    
    public function get_rows($sql, $indexfield=''){
        //$sql = str_replace(array('select ', ' limit '), array('SELECT ', ' LIMIT '), $sql);
        $res = $this->query($sql);
        $ret = array();
        while($r = $res->fetch_assoc()){
            if($indexfield=='' || !isset($r[$indexfield])){
                $ret[] = $r;
            }else{
                $ret[$r[$indexfield]] = $r;
            }    
        }
        return $ret;
    }
	
	public function count($table = null, $condition = '') {
		if(empty($table)){
			$table = $this->options['table'];
			if(empty($table) || $this->query_finished !== false){ //未设置表名
				return false;
			}
			$condition = $this->options['condition'];
		}
        $table = str_replace('.', '`.`', $table);
		$this->sql = '';
		$sql = "SELECT COUNT(*) AS amount FROM `{$table}`";
		//if($condition) $sql .= ' WHERE '.$condition;
		if(!empty($condition)){
			$ct = gettype($condition);
			if($ct == 'string'){
				$sql .= " WHERE " . $condition;
			}elseif($ct == 'array'){
				$cons = $this->_condition_strip($condition);
				$sql .= " WHERE " . implode(' AND ', $cons);
			}else{
				//条件参数类型不正常
				$this->sql = 'condition type error';
				return false;
			}
			
		}
		$this->sql = $sql;
		$r = $this->get_one($sql);
		return $r ? $r['amount'] : 0;
	}

	public function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return mysqli_fetch_array($query, $result_type);
	}

	public function affected_rows() {
		return mysqli_affected_rows($this->connid);
	}

	public function num_rows($query) {
		return mysqli_num_rows($query);
	}

	public function num_fields($query) {
		return mysqli_num_fields($query);
	}

	public function free_result($query) {
		return @mysqli_free_result($query);
	}

	public function insert_id() {
		return mysqli_insert_id($this->connid);
	}

	public function fetch_row($query) {
		return mysqli_fetch_row($query);
	}

	public function version() {
		return mysqli_get_server_info($this->connid);
	}

	public function close() {
		return mysqli_close($this->connid);
	}

	public function error() {
		return @mysqli_error($this->connid);
	}

	public function errno() {
		return intval($this->error());
	}

	protected function halt($message = '', $sql = '')	{
		if(DT_DEBUG){
            echo "\t\t<query>".$sql."</query>\n\t\t<errno>".$this->errno()."</errno>\n\t\t<error>".$this->error()."</error>\n\t\t<errmsg>".$message."</errmsg>\n";
        }else{
            echo $message;
        }
	}

    protected function exception(\Throwable $e, $sql)
    {
        if (DT_DEBUG) {
            $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{margin: 0 auto;} .header{background: #6c757d; color: #eee; padding: 50px 15px 30px 15px;line-height: 1.5rem} .sql{background: #cce5ff; color: #004085; padding: 15px 15px;line-height: 1.5rem} .msg{padding: 15px 15px;line-height: 1.25rem}</style></head><body>';
            $html .= '<div class="header"><h3>' . $e->getMessage() . '</h3>Code: ' . $e->getCode() . '<BR>File: ' . $e->getFile() . '<BR>Line: ' . $e->getLine() . '</div>';
            $html .= '<div class="sql">Sql: ' .$sql. '</div>';
            $html .= '<div class="msg">' . dv($e, false) . '</div>';
            $html .= '</body></html>';
            echo $html;
        } else {
            $msg = $e->getCode() . ': ' . $e->getMessage();
            $html = '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>HTTP 500</title><style>body{background-color:#444;font-size:16px;}h3{font-size:32px;color:#eee;text-align:center;padding-top:50px;font-weight:normal;}</style></head>';
            $html .= '<body><h3>' . $msg . '</h3></body></html>';
            echo $html;
        }
        exit(0);
    }
	
	/*
	 * 检查是否为注入
	 * 未完工 2022-10-20
	 */
	public function IsInjection($str){
		$isInj = false;
		// /((\%3D)|(=))[^\n]*((\%27)|(\’)|(\-\-)|(\%3B)|(:))/i
		$Exec_Commond = "/(\s|\S)*(exec(\s|\+)+(s|x)p\w+)(\s|\S)*/";
		$Simple_XSS = "/(\s|\S)*((%3C)|)(\s|\S)*/";
		$Eval_XSS = "/(\s|\S)*((%65)|e)(\s)*((%76)|v)(\s)*((%61)|a)(\s)*((%6C)|l)(\s|\S)*/";
		$Image_XSS = "/(\s|\S)*((%3C)|)(\s|\S)*/" ;
		$Script_XSS = "/(\s|\S)*((%73)|s)(\s)*((%63)|c)(\s)*((%72)|r)(\s)*((%69)|i)(\s)*((%70)|p)(\s)*((%74)|t)(\s|\S)*/";
		$SQL_Injection = "/(\s|\S)*((%27)|(')|(%3D)|(=)|(/)|(%2F)|(\")|((%22)|(-|%2D){2})|(%23)|(%3B)|(;))+(\s|\S)*/";
		if(preg_match($Exec_Commond, $str)){
			$isInj = true;
		}
		if(preg_match($Simple_XSS, $str)){
			$isInj = true;
		}
		if(preg_match($Eval_XSS, $str)){
			$isInj = true;
		}
		if(preg_match($Image_XSS, $str)){
			$isInj = true;
		}
		if(preg_match($Script_XSS, $str)){
			$isInj = true;
		}
		return $isInj;
	}
	
	public function removeEscape($str){
		$str = str_replace(array('\'','"','\\'),"",$str);
		return $str;
	}
	
	public function escape_string($str){
		return mysqli_real_escape_string($this->connid, $str);
	}
	
	protected function _fields_strip($fields){
		$ufields = [];
		foreach($fields as $k=>$v){
			//var_dump(gettype($v));
			switch(gettype($v)){
				case 'string':
					$ufields[] = $k . " = '" .$this->escape_string($v). "' ";
					break;
				case 'integer':
					$ufields[] = "{$k} = {$v} ";
					break;
				case 'double':
					$ufields[] = "{$k} = {$v} ";
					break;
				case 'boolean':
					$_v = $v ? '1 ' : '0 ';
					$ufields[] = "{$k} = ". $_v ;
					break;
				case 'NULL':
					$ufields[] = "{$k} = NULL ";
					break;
				default:
					return false;
					break;
			}
		}
		
		return $ufields;
	}
	
	protected function _fields_split($fields){
		$fk = [];
		$fv = [];
		foreach($fields as $k=>$v){
			switch(gettype($v)){
				case 'string':
					$fk[] = "`{$k}`";
					$fv[] = "'" .$this->escape_string($v). "'";
					break;
				case 'integer':
					$fk[] = "`{$k}`";
					$fv[] = "'{$v}'";
					break;
				case 'double':
					$fk[] = "`{$k}`";
					$fv[] = "'{$v}'";
					break;
				case 'boolean':
					$_v = $v ? '1 ' : '0 ';
					$fk[] = "`{$k}`";
					$fv[] = "'{$_v}'";
					break;
				case 'NULL':
					$fk[] = "`{$k}`";
					$fv[] = 'NULL';
					break;
				default:
					$fk[] = "`{$k}`";
					$fv[] = 'NULL';
					break;
			}
		}
		
		return [$fk, $fv];
	}
	
	protected function _fieldsAddAlais($fields){
		if(empty($this->options['alias'])){
			return $fields;
		}
		$ct = gettype($fields);
		if($ct == 'array'){
			$_fields = [];
			foreach($fields as $k=>$v){
				if(false === strpos($v, '.')){
					$_fields[] = $this->options['alias'] .'.'.$v;
				}else{
					$_fields[] = $v;
				}
			}
			$fields = $_fields;
		}
		return $fields;
	}

    protected function _array_is_list($arr){
        if(function_exists('array_is_list')) {
            return array_is_list($arr);
        }else{
            return $this->__array_is_list($arr);
        }
    }
    private function __array_is_list($arr){
        $i = 0;
        foreach ($arr as $k => $v) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }

    protected function _condition_strip($condition){
        if($this->_array_is_list($condition)){
            $cons = [];
            foreach ($condition as $kk=>$cc){
                $_cons = $this->__condition_strip($cc);
                foreach ($_cons as $k=>$v){
                    $cons[] = $v;
                }
            }
            return  $cons;
        }else{
            return $this->__condition_strip($condition);
        }
    }
	protected function __condition_strip($condition){
		$cons = [];
		foreach($condition as $k=>$v){
			if(false === strpos($k, '.') && !empty($this->options['alias'])){
				$k = $this->options['alias'] . '.' . $k;
			}
			switch(gettype($v)){
				case 'string':
					$cons[] = $k . " = '" .$this->escape_string($v). "' ";
					break;
				case 'integer':
					$cons[] = "{$k} = {$v}";
					break;
				case 'double':
					$cons[] = "{$k} = {$v}";
					break;
				case 'boolean':
					$_v = $v ? '1 ' : '0 ';
					$cons[] = "{$k} = ". $_v ;
					break;
				case 'NULL':
					$cons[] = "{$k} = NULL ";
					break;
				case 'array':
					switch(gettype($v[1])){
						case 'string':
							$cons[] = $k . " {$v[0]} '" .$this->escape_string($v[1]). "'";
							break;
						case 'integer':
							$cons[] = "{$k} {$v[0]} {$v[1]}";
							break;
						case 'double':
							$cons[] = "{$k} {$v[0]} {$v[1]}";
							break;
						case 'boolean':
							$_v = $v[1] ? '1' : '0';
							$cons[] = "{$k} {$v[0]} ". $_v ;
							break;
						case 'array':
							$_v1 = [];
							foreach($v[1] as $ik=>$iv){
								$ivtype = gettype($iv);
								if($ivtype == 'string'){
									$_v1[] = "'" . $this->escape_string($iv) . "'";
								}elseif($ivtype == 'integer' || $ivtype == 'double'){
									$_v1[] = $iv;
								}
							}
							if(!empty($_v1)){
                                if($v[0] == 'IN' || $v[0] == 'in'|| $v[0] == 'not in'|| $v[0] == 'NOT IN'){
                                    $cons[] = "{$k} {$v[0]} (". implode(',', $_v1) . ')';
                                }else{
                                    $cons[] = "{$k} {$v[0]} ". implode(' AND ', $_v1) . '';
                                }
							}
							break;
						case 'NULL':
							$cons[] = "{$k} {$v[0]} NULL";
							break;
						default:
							//errtype
							$cons[] = "{$k} = 'IMPORTANT ERROR TYPE'";
							break;
					}
					break;
				default:
					//errtype
					$cons[] = "{$k} = 'IMPORTANT ERROR TYPE'";
					break;
			}
		}
		
		return $cons;
	}
	
}
?>