<?php

class Database
{
	var $db;
	var $maxExecutionLimit;
	var $executions;
	var $lastQuery;
	var $pendingActions;
	
	function Database($path="")
	{
		$this->pendingActions = array();
		if( !$this->db = new SQLiteDatabase($path) )
		{
			print("Cannot open database $path.");
			die();
		}
		$this->executions = 0;
	}
	
	function insertMessage( $params )
	{
		if( is_array($params) )
		{
			$statement = "INSERT INTO messages (stamp,channel,user,content,rating) VALUES (?1,'?2','?3','?4',?5);";
			$statement = str_replace('?1',$params['stamp'],$statement);
			$statement = str_replace('?2',sqlite_escape_string($params['channel']),$statement);
			$statement = str_replace('?3',sqlite_escape_string($params['user']),$statement);
			$statement = str_replace('?4',sqlite_escape_string($params['content']),$statement );
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
						sqlite_escape_string($sourceRow['channel']).
						"' AND id >= ".
						(($forId>5)?$forId-5:1).
						" AND id <= ".
						($forId+5).
						") OR id = $forId ORDER BY stamp ASC;";
		
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->db->query($statement);
			if( $query )
			{
				$result = $query->fetchAll(SQLITE_ASSOC);
				return $result;
			}
		}
		return null;
		// select posts in order by time, 5 before, 5 after from the same channel centered on forId.
	}
	
	function selectDistinct( $field, $fromTable )
	{
		$statement = "SELECT DISTINCT($field) FROM $fromTable ORDER BY $field ASC;";
		if( $this->db )
		{
			$this->lastQuery = $statement;
			$query = $this->db->query($statement);
			if( $query )
			{
				$result = $query->fetchAll(SQLITE_ASSOC);
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
			$statement = str_replace('?2',sqlite_escape_string($params['email']),$statement);
			$statement = str_replace('?3',sqlite_escape_string(crypt($params['password'],'BT')),$statement);
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
					$result = $this->db->queryExec($statement);
					$this->executions = $this->executions + 1;
					if( $result )
					{
						return $this->db->lastInsertRowid();
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
				return $this->db->queryExec($statement);
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
		$statement = "select sum(runcount) as totalPopularity from searches where data = '".sqlite_escape_string ( $term )."' group by data;";		
		$query = $this->db->query($statement);
		if( $query )
		{
			$result = $query->fetch(SQLITE_ASSOC);
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
					$result = $this->db->queryExec($statement);
					$this->executions = $this->executions + 1;
					if( $result )
					{
						return $this->db->lastInsertRowid();
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
					$result = $this->db->queryExec($statement);
					$this->executions = $this->executions + 1;
					if( $result )
					{
						return $this->db->lastInsertRowid();
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
			$query = $this->db->query($statement);
			if( $query )
			{
				$result = $query->fetchAll(SQLITE_ASSOC);
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
						$sqlArray[] = "$field $comparisonOperator '$comparisonBuffer".sqlite_escape_string($value)."$comparisonBuffer'";	
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
			$query = $this->db->query($statement);
			if( $query )
			{
				$result = $query->fetchAll(SQLITE_ASSOC);
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
			$query = $this->db->query($statement);
			if( $query )
			{
				$result = $query->fetchAll(SQLITE_ASSOC);
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
					$query = $this->db->queryExec($statement);
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
					$query = $this->db->queryExec($statement);
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
			$query = $this->db->query($statement);
			if( $query )
			{
				$result = $query->fetchAll(SQLITE_ASSOC);
				return $result;
			}
		}
		return false;
	}
		
	function __destruct()
	{
		if( sizeOf($this->pendingActions) > 0 )
		{
			$statement = implode("\n",$this->pendingActions);
			$this->db->queryExec( $statement );
		}
	}
}

?>