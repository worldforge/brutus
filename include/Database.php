<?php

require_once('DB.php');

class Database
{
	var $db;
	var $maxExecutionLimit;
	var $executions;
	var $lastQuery;
	var $pendingActions;
	
	function __construct($path="")
	{
		$this->pendingActions = array();
		$this->executions = 0;
		if( !isset($GLOBALS['DSN']) )
		{
			if( !$this->db = new SQLiteDatabase($path) )
			{
				print("Cannot open database $path.");
				die();
			}
		}
		elseif( stristr($GLOBALS['DSN'],"mysql") )
		{
			$options = array("persistent" => false,"autofree" => true);
			$result = &DB::connect($GLOBALS['DSN'], $options);
			if (DB::isError($result))
			{
				$notice = new Notice("Database Error", "Cannot connect to MySQL database.");
				print $notice->Render();
				var_dump($result);
				die();
			}
			$this->db =& $result;
			// Makes associative arrays, with the column names as the array keys
			$this->db->setFetchMode(DB_FETCHMODE_ASSOC);
			$this->db->query("SET NAMES 'utf8'");
			$this->_dsn = $GLOBALS['DSN'];
			
			$tables =& $this->db->query("SHOW TABLES;");
			if( DB::isError($result) )
			{
				$Notice = new Notice("Database Error","Cannot retrieve tables from database.");
				print $Notice->Render();
			}
			if( $tables->numRows() == 0 )
			{
				// set up the fucking database... whew that as a lot more work than it should have been.
				$this->setupMySQLDatabase();
			}
		}
		return $this;
	}
	
	/**
	 * @function Database:SetupMySQLDatabase
	 * @author tingham
	 * @created 9/4/08 12:00 AM
	 **/
	function setupMySQLDatabase()
	{
		$tables = array();
		$tables['messages'] = "CREATE TABLE messages (id INT NOT NULL AUTO_INCREMENT,\n".
										  "stamp INT,\n".
										  "channel TEXT,\n".
										  "user TEXT,\n".
										  "content TEXT,\n".
										  "rating INT,\n".
										  "PRIMARY KEY(id)".
										  ") ENGINE=InnoDB DEFAULT CHARSET=utf8\n;";
		$tables['searches'] = "CREATE TABLE searches (id INT NOT NULL AUTO_INCREMENT,\n".
											"user INT,\n".
											"data TEXT,\n".
											"lastrun INT,\n".
											"runcount INT,\n".
											"active INT,\n".
											"PRIMARY KEY(id)\n".
											") ENGINE=InnoDB DEFAULT CHARSET=utf8\n;";
		$tables['user'] = "CREATE TABLE user(\n".
									   "id INT NOT NULL AUTO_INCREMENT,\n".
									   "user TEXT,\n".
									   "email TEXT,\n".
									   "password TEXT,\n".
									   "reference INT,\n".
									   "settings TEXT,\n".
									   "confirmed INT,\n".
									   "PRIMARY KEY(id)\n".
									   ") ENGINE=InnoDB DEFAULT CHARSET=utf8\n;";
		$tables['meta'] = "CREATE TABLE meta(\n".
									   "id INT NOT NULL AUTO_INCREMENT,\n".
									   "message INT,\n".
									   "owner INT,\n".
									   "data TEXT,\n".
									   "PRIMARY KEY(id)\n".
									   ") ENGINE=InnoDB DEFAULT CHARSET=utf8\n;";
		foreach($tables as $tableName=>$table)
		{
			$result =& $this->db->query($table);
			if( DB::isError($result) )
			{
				$notice = new Notice("Database Error", "Error creating tables.");
				print $notice->Render();
				var_dump($result);
			}
		}
	} 
	
	function fetchAll( $result )
	{
		$returnVal = array();		
		if( stristr($GLOBALS['DSN'],"mysql") )
		{
			while($row = $result->fetchRow())
			{
				$returnVal[] = $row;
			}
		}
		else
		{
			$returnVal = $query->fetchALL(SQLITE_ASSOC);
		}
		return $returnVal;
	}
	
	function query( $statement )
	{
		$returnVal = false;
		if( stristr($GLOBALS['DSN'],"mysql") )
		{
			$result =& $this->db->query($statement);
			if( DB::isError($result) )
			{
				$notice = new Notice("Database Error","Error executing query.");
				print $notice->Render();
				var_dump($result);
			}
			else
			{
				$returnVal = $result;
			}
		}
		else
		{
			$result = $this->db->query($statement);
			if( !$result )
			{
				$returnVal = $result;
			}
			else
			{
				$notice = new Notice("Database Error","Error executing query.");
				print $notice->Render();
			}
		}
		return $returnVal;
	}
	
	function execute( $statement )
	{
		$returnVal = false;
		if( stristr($GLOBALS['DSN'],"mysql") )
		{
			$result =& $this->db->query($statement);
			if( DB::isError($result) )
			{
				$notice = new Notice("Database Error","Error executing statement.");
				print $notice->Render();
				var_dump($result);
			}
			else
			{
				$returnVal = $result;
			}
		}
		else
		{
			$result = $this->db->queryExec($statement);
			if( !$result )
			{
				$returnVal = $result;
			}
			else
			{
				$notice = new Notice("Database Error","Error executing statement.");
				print $notice->Render();
			}
		}
		return $returnVal;
	}
	
	function lastId($query)
	{
		if( stristr($GLOBALS['DSN'],"mysql") )
		{
			return $this->db->nextId()-1;
		}
		else
		{
			return $this->db->lastInsertId();
		}
	}
	
	function string($string)
	{
		if( stristr($GLOBALS['DSN'],"mysql") )
		{
			return addslashes($string);
		}
		else
		{
			return sqlite_escape_string ( $string );
		}
	}
	
	function insertMessage( $params )
	{
		if( is_array($params) )
		{
			$statement = "INSERT INTO messages (stamp,channel,user,content,rating) VALUES (?1,'?2','?3','?4',?5);";
			$statement = str_replace('?1',$params['stamp'],$statement);
			$statement = str_replace('?2',$this->string($params['channel']),$statement);
			$statement = str_replace('?3',$this->string($params['user']),$statement);
			$statement = str_replace('?4',$this->string($params['content']),$statement );
			$statement = str_replace('?5',1,$statement);
			
			$testForPrevious = array("explicit"=>true,
									 "all"=>true,
									 "table"=>"messages",
									 "attributes"=>array(
									 	"channel"=>$params['channel'],
									 	"stamp"=>$params['stamp'],
									 	"content"=>$params['content'],
									 	"user"=>$params['user']));
			$testResult = $this->select($testForPrevious);
			if( $testResult == null )
			{
				$this->pendingActions[] = $statement;
				/*
				if( $this->db &&
					$this->executions < $this->maxExecutionLimit )
				{
					$result = $this->db->queryExec($statement);
					$this->executions = $this->executions + 1;
					return 1;
				}
				else
				{
					//print("Either we reached the limit of available executions this cycle or the database went away. You figure it out.<br />\n");
				}
				*/
			}
			else
			{
				//print("Row exists, skipping.<br />\n");
				return 1;
			}

		}
		return 0;
	}
	
	function selectSurroundingMessages( $forId )
	{
		// grab the row specified by id
		$params = array("explicit"=>true,
						"all"=>true,
						"table"=>"messages",
						"attributes"=>array(
									 	"id"=>$forId));
		$sourceRows = $this->select($params);
		$sourceRow = array_shift($sourceRows);
		
		$statement = "select * from messages WHERE (channel = '".
						$this->string($sourceRow['channel']).
						"' AND id >= ".
						(($forId>5)?$forId-5:1).
						" AND id <= ".
						($forId+5).
						") OR id = $forId ORDER BY stamp ASC;";
		
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->query($statement);
			if( $query )
			{
				$result = $this->fetchAll($query);
				return $result;
			}
		}
		return null;
		// select posts in order by time, 5 before, 5 after from the same channel centered on forId.
	}
	
	function selectDistinct( $field, $fromTable )
	{
		$statement = "SELECT DISTINCT($field) as $field FROM $fromTable ORDER BY $field ASC;";
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->query($statement);
			if( $query )
			{
				$result = $this->fetchAll($query);
				return $result;
			}
		}
	}
	
	/**
	 * @function Database:InsertUser
	 * @author tingham
	 * @created 8/31/08 9:42 PM
	 **/
	function InsertUser( $params )
	{
		if( is_array($params) )
		{
			$statement = "INSERT INTO user (user,email,password,reference,settings,confirmed) VALUES ('?1','?2','?3',?4,'?5',?6);";
			$statement = str_replace('?1',$params['user'],$statement);
			$statement = str_replace('?2',$this->string($params['email']),$statement);
			$statement = str_replace('?3',$this->string(crypt($params['password'],'BT')),$statement);
			$statement = str_replace('?4',$params['reference'],$statement );
			$statement = str_replace('?5',$params['settings'],$statement);
			$statement = str_replace('?6',$params['confirmed'],$statement);
			
			$testForPrevious = array("explicit"=>true,
									 "all"=>true,
									 "table"=>"user",
									 "attributes"=>array(
									 	"user"=>$params['user'],
									 	"email"=>$params['email'],
									 	"reference"=>$params['reference']));
			$testResult = $this->select($testForPrevious);
			if( $testResult == null )
			{
				if( $this->db &&
					$this->executions < $this->maxExecutionLimit )
				{
					//print $statement;
					$result = $this->execute($statement);
					$this->executions = $this->executions + 1;
					if( $result )
					{
						return $this->db->lastId();
					}
					else
					{
						return 0;
					}
				}
				else
				{
					print("Either we reached the limit of available executions this cycle or the database went away. You figure it out.<br />\n");
				}
			}
			else
			{
				print "I am bob jones.<br />";
				var_dump($testResult);
				return -1;
			}

		}
		return 0;
	}
	
	/**
	 * @function Database:SetSearchActive
	 * @author tingham
	 * @created 9/2/08 1:55 AM
	 **/
	function SetSearchActive( $params )
	{
		if( is_array($params) )
		{
			if( isset($params['id']) &&
				isset($params['active']) )
			{
				$statement = "update searches set active = ".$params['active']." where id = ".$params['id'].";";
				return $this->execute($statement);
			}
		}
	}
	
	/**
	 * @function Database:FetchTotalSearchPopularity
	 * @author tingham
	 * @created 9/2/08 1:14 AM
	 **/
	function FetchTotalSearchPopularity( $term )
	{
		$statement = "select sum(runcount) as totalPopularity from searches where data = '".$this->string ( $term )."' group by data;";		
		$query = $this->query($statement);
		if( $query )
		{
			$result = $this->fetchAll($query);
			$result = array_shift($result);
			return $result['totalPopularity'];
		}
	}
	/**
	 * @function Database:InsertSearch
	 * @author tingham
	 * @created 9/2/08 1:01 AM
	 **/
	function InsertSearch( $params )
	{
		if( is_array($params) )
		{
			//CREATE TABLE searches (id INTEGER PRIMARY KEY,user INTEGER,data TEXT,lastrun REAL,runcount INTEGER,active INTEGER);
			$statement = "INSERT INTO searches (user,data,lastrun,runcount,active) VALUES (?1,'?2',?3,?4,?5);";
			$statement = str_replace('?1',$params['user'],$statement);
			$statement = str_replace('?2',$params['data'],$statement);
			$statement = str_replace('?3',$params['lastrun'],$statement);
			$statement = str_replace('?4',$params['runcount'],$statement);
			$statement = str_replace('?5',$params['active'],$statement);
						
			$testForPrevious = array("explicit"=>true,
									 "all"=>true,
									 "table"=>"searches",
									 "attributes"=>array(
									 	"user"=>$params['user'],
									 	"data"=>$params['data']));
			$testResult = $this->select($testForPrevious);
			if( $testResult == null )
			{
				if( $this->db &&
					$this->executions < $this->maxExecutionLimit )
				{
					//print $statement;
					$result = $this->execute($statement);
					$this->executions = $this->executions + 1;
					if( $result )
					{
						return $this->db->lastId();
					}
					else
					{
						return 0;
					}
				}
				else
				{
					print("Either we reached the limit of available executions this cycle or the database went away. You figure it out.<br />\n");
				}
			}
			else
			{
				$result = array_pop($testResult);
				$statement = "update searches set lastrun = ".time().", runcount = runcount+1, active = 1 where id = ".$result['id'].";";
				if( $this->db &&
					$this->executions < $this->maxExecutionLimit )
				{
					$result = $this->execute($statement);
					$this->executions = $this->executions + 1;
					if( $result )
					{
						return $this->db->lastId();
					}
					else
					{
						return 0;
					}
				}
				else
				{
					print("Either we reached the limit of available executions this cycle or the database went away. You figure it out.<br />\n");
				}
					
			}

		}
		return 0;
	}
	
	function searchMessages( $string )
	{
		$statement = "SELECT * FROM messages WHERE user like '%$string%' OR content like '%$string%' ORDER BY stamp ASC;";
		
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->query($statement);
			if( $query )
			{
				$result = $this->fetchAll($query);
				return $result;
			}
		}
	}
	
	function select( $params )
	{
		if( is_array($params) )
		{
			if( $params['explicit'] )
			{
				$comparisonOperator = "=";
				$comparisonBuffer = "";
			}
			else
			{
				$comparisonOperator = "like";
				$comparisonBuffer = "%";
			}
			if( $params['all'] )
			{
				$booleanOperator = " AND ";
			}
			else
			{
				$booleanOperator = " OR ";
			}
			
			$sqlPrefix = "SELECT * FROM ".$params['table'];
			$sqlArray = array();
			$sql = "";
			$sqlSuffix = "";
			
			if( isset($params["attributes"]) )
			{
				foreach($params["attributes"] as $field=>$value)
				{
					if( $field == "stamp >=" ||
						$field == "stamp <=" )
					{
						$sqlArray[] = "$field ".$value;
					}
					else
					{
						$sqlArray[] = "$field $comparisonOperator '$comparisonBuffer".$this->string($value)."$comparisonBuffer'";	
					}
				}
			}
			
			if( sizeOf($sqlArray) > 0 )
			{
				$sqlPrefix .= " WHERE ";
			}
			$sql = implode($booleanOperator,$sqlArray);
			
			if( isset($params['order']) )
			{
				$sqlSuffix .= " ORDER BY ".$params['order'];
			}
			
			$statement = $sqlPrefix.$sql.$sqlSuffix.";";
		}
		
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->query($statement);
			if( $query )
			{
				$result = $this->fetchAll($query);
				return $result;
			}
		}
		
		return null;
	}
	/**
	 * @function Database:UniqueUsers
	 * @author tingham
	 * @created 8/31/08 9:49 PM
	 **/
	function UniqueUsers()
	{
		$statement = "SELECT DISTINCT(user) FROM messages ORDER BY user ASC;";
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->query($statement);
			if( $query )
			{
				$result = $this->fetchAll($query);
				return $result;
			}
		}
		return false;
	}
	
	/**
	 * @function Database:ConfirmUser
	 * @author tingham
	 * @created 9/1/08 11:35 PM
	 **/
	function ConfirmUser( $params )
	{
		if( is_array($params) )
		{
			if( isset($params['id']) )
			{
				$statement = "update user set confirmed = 1 where id = ".$params['id'].";";
				if( $this->db )
				{
					$this->lastQuery = $statement;
					$query = $this->execute($statement);
					return $query;
				}
			}
		}
	}
	
	function DeclineUser( $params )
	{
		if( is_array($params) )
		{
			if( isset($params['id']) )
			{
				$statement = "update user set confirmed = -1 where id = ".$params['id'].";";
				if( $this->db )
				{
					$this->lastQuery = $statement;
					$query = $this->execute($statement);
					return $query;
				}
			}
		}
	}
	
	/**
	 * @function Database:KnownUsers
	 * @author tingham
	 * @created 8/31/08 10:40 PM
	 **/
	function KnownUsers( )
	{
		$statement = "SELECT id, user FROM user WHERE confirmed > 0 ORDER BY user ASC;";
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->query($statement);
			if( $query )
			{
				$result = $this->fetchAll($query);
				return $result;
			}
		}
		return false;
	}
		
	function __destruct()
	{
		if( sizeOf($this->pendingActions) > 0 )
		{
			if( stristr($GLOBALS['DSN'],'mysql') )
			{
				foreach($this->pendingActions as $action)
				{
					$this->execute($action);
				}
			}
			else
			{
				$statement = implode("\n",$this->pendingActions);
				$this->execute( $statement );
			}
		}
	}
}

?>