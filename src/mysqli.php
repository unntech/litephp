<?php

namespace LitePhp;

class mysqli {
	public $connid;
	public $querynum = 0;
	public $cursor = 0;
	public $err = 0;
	public $linked = 1;
	public $result = array();
    
    /**
     * 构造方法
     * @access public
     */
    public function __construct($cfg)
    {
        return $this->connect($cfg['hostname'], $cfg['hostport'], $cfg['username'], $cfg['password'], $cfg['dbname'], $cfg['charset']);
    }

	function connect($dbhost, $dbport, $dbuser, $dbpass, $dbname='', $dbcharset='') {
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

	function select_db($dbname) {
		return mysqli_select_db($this->connid, $dbname);
	}

	function query($sql) {
		if(!($query = mysqli_query($this->connid, $sql))) $this->halt('MySQL Query Error', $sql);
		$this->querynum++;
		return $query;
	}

	function get_one($sql) {
		$sql = str_replace(array('select ', ' limit '), array('SELECT ', ' LIMIT '), $sql);
		if(strpos($sql, 'SELECT ') !== false && strpos($sql, ' LIMIT ') === false) $sql .= ' LIMIT 0,1';
		$query = $this->query($sql);
		$r = $this->fetch_array($query);
		$this->free_result($query);
		return $r;
	}
	
	function get_value($sql) {
		$sql = str_replace(array('select ', ' limit '), array('SELECT ', ' LIMIT '), $sql);
		if(strpos($sql, 'SELECT ') !== false && strpos($sql, ' LIMIT ') === false) $sql .= ' LIMIT 0,1';
		$query = $this->query($sql);
		$r = $this->fetch_row($query);
		$this->free_result($query);
		return $r[0];
	}
    
    function get_rows($sql, $indexfield=''){
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
	
	function count($table, $condition = '') {
		$sql = 'SELECT COUNT(*) AS amount FROM '.$table;
		if($condition) $sql .= ' WHERE '.$condition;
		$r = $this->get_one($sql);
		return $r ? $r['amount'] : 0;
	}

	function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return is_array($query) ? $this->_fetch_array($query) : mysqli_fetch_array($query, $result_type);
	}

	function affected_rows() {
		return mysqli_affected_rows($this->connid);
	}

	function num_rows($query) {
		return mysqli_num_rows($query);
	}

	function num_fields($query) {
		return mysqli_num_fields($query);
	}

	function result($query, $row) {//DEBUG
		return @mysqli_result($query, $row);
	}

	function free_result($query) {
		return @mysqli_free_result($query);
	}

	function insert_id() {
		return mysqli_insert_id($this->connid);
	}

	function fetch_row($query) {
		return mysqli_fetch_row($query);
	}

	function version() {
		return mysqli_get_server_info($this->connid);
	}

	function close() {
		return mysqli_close($this->connid);
	}

	function error() {
		return @mysqli_error($this->connid);
	}

	function errno() {
		return intval($this->error());
	}

	function halt($message = '', $sql = '')	{
		if($message && DT_DEBUG) echo "\t\t<query>".$sql."</query>\n\t\t<errno>".$this->errno()."</errno>\n\t\t<error>".$this->error()."</error>\n\t\t<errmsg>".$message."</errmsg>\n";
	}

	function _fetch_array($query = array()) {
		if($query) $this->result = $query; 
		if(isset($this->result[$this->cursor])) {
			return $this->result[$this->cursor++];
		} else {
			$this->cursor = 0;
			return array();
		}
	}
}
?>