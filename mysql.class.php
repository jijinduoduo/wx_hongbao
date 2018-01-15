<?php
header('Content-Type: text/html; charset=UTF-8');

class mysqlJi{//数据库
	private static $mysqli;
	public function __construct(){  
   		self::$mysqli = @new mysqli("10.10.10.1","","",'');//数据库连接
//      self::$mysqli = @new mysqli("localhost","root","root",'baoming');//数据库连接
		if(self::$mysqli->connect_errno){
			die('数据库连接失败: ' .self::$mysqli->connect_error);
		}
		self::$mysqli->set_charset('utf8');//设置字符集
	}

	static function mysqlQu($sql){//sql语句
		return self::$mysqli->query($sql);		
	}
	static function mysqlFeAs($result){//遍历结果
		$i=0;//变量i
		while($row=$result->fetch_assoc()){
		$reslist[$i] = $row;
		$i++;
		}
		return $reslist;	
	}
	static function jsonEn($reslist){//输出json
		echo json_encode($reslist);
	}
	static function mysqlRows(){ //受影响的行数		
		$a_rows = self::$mysqli->affected_rows; //受影响的行数		
		return $a_rows; 		 
	}
	static function insertId(){//最后一条记录的ID
		$getID=self::$mysqli->insert_id;//$getID即为最后一条记录的ID
		return $getID;
	}
	static function mysqlCl(){//关闭数据库
		self::$mysqli->close();
	}
}
	new mysqlJi();	
?>