<?php
// --------------------------------------------
// | The EP-Dev Forum News script        
// |                                           
// | Copyright (c) 2002-2004 EP-Dev.com :           
// | This program is distributed as free       
// | software under the GNU General Public     
// | License as published by the Free Software 
// | Foundation. You may freely redistribute     
// | and/or modify this program.               
// |                                           
// --------------------------------------------

/* ------------------------------------------------------------------ */
//	MySQL Access Class
//
//	Controls single database connection / obj. By doing this the script
//	can easily manage multiple databases as well as easily update db
//	access as needed.
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_MYSQL
{
	var $ERROR;
	
	var $link;
	var $result;

	var $DBsettings;
	var $prefix;

	var $query;
	var $debug;


	function EP_Dev_Forum_News_MYSQL($username, $password, $host, $name, $prefix, &$error_handle)
	{	
		// +------------------------------
		//	Initialize error handle
		// +------------------------------

		$this->ERROR =& $error_handle;
		
		
		// +------------------------------
		//	Initialize database settings
		// +------------------------------

		$this->prefix = $prefix;

		// no MySQL link yet
		$this->link = null;

		// store DB settings
		$this->DBsettings = array("username" => $username, "password" => $password, "host" => $host, "name" => $name);
	}


	/* ------------------------------------------------------------------ */
	//	connect
	//  Connects to MySQL Database
	/* ------------------------------------------------------------------ */

	function connect()
	{
		// +------------------------------
		//	Initialize database connection
		// +------------------------------
		
		// connect to mysql
		$this->link = @mysql_connect($this->DBsettings['host'], $this->DBsettings['username'], $this->DBsettings['password'], true);

		if ($this->link === false)
			return false;

		// select database
		$db_selected = mysql_select_db($this->DBsettings['name'], $this->link);
		
		if ($db_selected === false)
			$this->ERROR->stop("mysql_db_error");

		return true;
	}


	function getNiceValue(&$value)
	{
		if (is_array($value))
		{
			foreach($value as $key => $curValue)
			{
				$this->getNiceValue($value[$key]);
			}
		}
		else
		{
			if (get_magic_quotes_gpc())
			{
				$value = stripslashes($value);
			}

			if (!is_numeric($value))
			{
				$value = "'" . $this->getSafeValue($value) . "'";
			}
		}
	}


	function getSafeValue($value)
	{
		return mysql_real_escape_string($value);
	}


	/* ------------------------------------------------------------------ */
	//	query
	//  Runs query on database
	/* ------------------------------------------------------------------ */
	
	function query($query, $fieldVars=NULL)
	{
		// replace prefix
		$query = str_replace("%prefix%", $this->prefix, $query);

		$this->query = $query;

		if (is_array($fieldVars))
		{
			$this->getNiceValue($fieldVars);
			$final_query = vsprintf($query, $fieldVars);
		}
		else if ($fieldVars !== NULL)
		{
			$this->getNiceValue($fieldVars);
			$final_query = sprintf($query, $fieldVars);
		}
		else
		{
			$final_query = $query;
		}

		$this->result = mysql_query( $final_query, $this->link );
		$this->debug['sql'][] = $final_query;

		return $this->result;
	}


	/* ------------------------------------------------------------------ */
	//	rows
	//  Return number of rows in result
	/* ------------------------------------------------------------------ */
	
	function rows($result = NULL)
	{
		if ($result == NULL)
			$result = $this->result;

		return mysql_num_rows($result);
	}


	/* ------------------------------------------------------------------ */
	//	fetch_array
	//  Returns result in array form
	/* ------------------------------------------------------------------ */
	
	function fetch_array($result = NULL)
	{
		if ($result == NULL)
			$result = $this->result;

		$array_result = mysql_fetch_array($result);

		return $array_result;
	}


	/* ------------------------------------------------------------------ */
	//	value
	//  returns first value of result
	/* ------------------------------------------------------------------ */
	
	function value($result = NULL)
	{
		$array_result = $this->fetch_array($result) ;
		return $array_result[0];
	}


	/* ------------------------------------------------------------------ */
	//	insert_id
	//  returns the insert id from autoincrement of result INSERT
	/* ------------------------------------------------------------------ */
	
	function insert_id($link = NULL)
	{
		if ($link == NULL)
			$link = $this->link;

		return mysql_insert_id($link);
	}


	/* ------------------------------------------------------------------ */
	//	affected_rows
	//  returns number of affected rows from result
	/* ------------------------------------------------------------------ */
	
	function affected_rows($link = NULL)
	{
		if ($link == NULL)
			$link = $this->link;

		return mysql_affected_rows($link);
	}


	/* ------------------------------------------------------------------ */
	//	Custom Function
	//  Allows for custom function to be called on database
	/* ------------------------------------------------------------------ */
	
	function custom($func, $result = NULL)
	{
		if ($result == NULL)
			$result = $this->result;
		
		$this->result = @"mysql_".$func($result);

		return $this->result;
	}



	/* ------------------------------------------------------------------ */
	//	Get Query
	//  Returns the last query that was executed
	/* ------------------------------------------------------------------ */
	
	function getQuery()
	{
		return $this->query;
	}

	function debug()
	{
		return $this->debug;
	}


}