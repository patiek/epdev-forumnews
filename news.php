<?php
// --------------------------------------------
// | EP-Dev Forum News        
// |                                           
// | Copyright (c) 2003-2006 Patrick Brown as EP-Dev.com           
// | This program is free software; you can redistribute it and/or modify
// | it under the terms of the GNU General Public License as published by
// | the Free Software Foundation; either version 2 of the License, or
// | (at your option) any later version.              
// | 
// | This program is distributed in the hope that it will be useful,
// | but WITHOUT ANY WARRANTY; without even the implied warranty of
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// | GNU General Public License for more details.
// --------------------------------------------


/* ------------------------------------------------------------------ */
//	Main Class
//	Master class of script. Contains methods needed to initialize and
//	parse data as needed as well as calls to display and output.
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News
{
	// configuration settings
	var $CONFIG;

	// display obj
	var $DISPLAY;

	// error handle
	var $ERROR;

	// data (unparsed)
	var $DATA;

	// data (parsed)
	var $DATA_PARSED;

	// data (parsed with template)
	var $DATA_FINAL;

	// cache of old/current arguments
	var $CACHE;

	// forum obj
	var $FORUM;


	function EP_Dev_Forum_News()
	{
		// set error reporting level
		error_reporting(E_ALL ^ E_NOTICE);

		// has to be in $this-> format in order for admin panel to write correctly
		$this->absolute_path = "";

		if ($this->absolute_path == "")
			$this->absolute_path = dirname(__FILE__) . "/";

		// Load config file
		require_once($this->absolute_path . "config/config.php");

		// initialize configuration
		$this->CONFIG = new EP_Dev_Forum_News_Config();

		// +------------------------------
		//	Load up common required files
		// +------------------------------
		require_once($this->CONFIG->SCRIPT['files']['display']);
		require_once($this->CONFIG->SCRIPT['files']['template']);


		// +------------------------------
		//	Initialize variables
		// +------------------------------
		
		$template_data = new EP_Dev_Forum_News_Template();
		$this->DISPLAY = new EP_Dev_Forum_News_Display($template_data->TEMPLATES);
		$this->ERROR = new EP_Dev_Forum_News_Error_Handle();
		

		// +------------------------------
		//	Initialize forums
		// +------------------------------
		
		for($i=0; $i < count($this->CONFIG->FORUM); $i++)
		{
			if ($this->CONFIG->FORUM[$i]['enabled'])
			{
				require_once($this->CONFIG->SCRIPT['files'][$this->CONFIG->FORUM[$i]['dbtype']]);

				$database_access_class = "EP_Dev_Forum_News_" . strtoupper($this->CONFIG->FORUM[$i]['dbtype']);

				$database_access = new $database_access_class($this->CONFIG->FORUM[$i]['username'], $this->CONFIG->FORUM[$i]['password'], $this->CONFIG->FORUM[$i]['host'], $this->CONFIG->FORUM[$i]['name'], $this->CONFIG->FORUM[$i]['prefix'], $this->ERROR);

				// load forum file
				require_once($this->CONFIG->SCRIPT['folders']['forums'] . $this->CONFIG->FORUM[$i]['source'] . "/" . $this->CONFIG->FORUM[$i]['type'] . ".php");
				$forum_object_name = "EP_Dev_Forum_News_" . str_replace(".", "_", $this->CONFIG->FORUM[$i]['type']) . "_Access";
				$this->FORUM[$i] = new $forum_object_name($database_access, $this->CONFIG->FORUM[$i], $this->ERROR);
			}
		}
	}


	/* ------------------------------------------------------------------ */
	//	Parse Features.
	//  Runs through various script features that do not affect html
	//	output or conflict with possible custom bbcode.
	/* ------------------------------------------------------------------ */
	
	function parse_Features(&$post_data)
	{
		// Page Break Feature
		if (!empty($this->CONFIG->NEWS['page_break_keyword']))
			$page_break = $this->DISPLAY->feature_Page_Break($post_data['text'], $this->CONFIG->NEWS['page_break_keyword']);

		// Character Limit Feature
		if (!$page_break && $this->CONFIG->NEWS['character_limit'])
			$this->DISPLAY->feature_Character_Limit($post_data['text'], $this->CONFIG->NEWS['character_limit']);
	}


	/* ------------------------------------------------------------------ */
	//	Parse Data.
	//  Runs through various script features that do affect html output but
	//	are non-template & non-forum specific parsing
	/* ------------------------------------------------------------------ */
	
	function  parse_Data(&$post_data)
	{
		// BBcode
		if ($this->CONFIG->NEWS['bbcode'])
			$this->DISPLAY->feature_BBcode($post_data['text']);

		// Hyperlink URLs Feature
		if ($this->CONFIG->NEWS['format_urls'])
			$this->DISPLAY->feature_Hyperlink_URLs($post_data['text']);

		if ($this->CONFIG->NEWS['fix_linebreaks'])
			$this->DISPLAY->feature_Fix_LineBreaks($post_data['text']);
	}


	/* ------------------------------------------------------------------ */
	//	Author Image (avatar) feature
	//  Handles author image feature.
	/* ------------------------------------------------------------------ */
	
	function parse_Author_Image($post_data)
	{
		// Prepare Avatar (Author Image)
		if ($this->CONFIG->NEWS['author_image'])
			$this->DISPLAY->feature_Author_Image($this->FORUM[$post_data['forum_id']]->get_Author_Image($post_data['author_id']));
		else
			$this->DISPLAY->feature_Author_Image(NULL);
	}


	/* ------------------------------------------------------------------ */
	//	Parse Template
	//  Handles the calls to various methods depening on what is being
	//	parsed.
	/* ------------------------------------------------------------------ */
	
	function parse_Template($post_data, $template_id, $headlines)
	{
		// set template
		$this->DISPLAY->set_Template($template_id);

		// +------------------------------
		//	Parse for news posts
		// +------------------------------

		if (!$headlines)
		{

			// WARNING //
			// parsing general template should come last //

			// author image feature (avatar)
			$this->parse_Author_Image($post_data);

			// replace all other template variables
			$this->DISPLAY->template_Post($post_data);

			$return = $this->DISPLAY->get_Post();
		}

		// +------------------------------
		//	Parse for headlines
		// +------------------------------

		else
		{
			$this->DISPLAY->template_Headline($post_data);

			$return = $this->DISPLAY->get_Headline();
		}

		return $return;
	}


	/* ------------------------------------------------------------------ */
	//	Ensure proper input format
	//  Will format input arguments into workable data
	/* ------------------------------------------------------------------ */
	
	function scriptProcess_input($number, $ids, $forums)
	{
		// set number to be fetched to default if not set
		if (empty($number))
			$number = ($headlines ? $this->CONFIG->NEWS['headline_num'] : $this->CONFIG->NEWS['post_num']) ;

		// set forums to fetch from to default if not set
		if (!preg_match("/^[0-9,]+$/",$forums))
		{
			unset($forums);
			// cycle through configuration
			for($i=0; $i<count($this->CONFIG->FORUM); $i++)
			{
				// if forum enabled, add id to list
				if ($this->CONFIG->FORUM[$i]['enabled'])
					$forums[] = $i;
			}
		}

		// else format forums into expected array format
		else
		{
			$tmp_holder = $forums;
			unset($forums);
			$forums = explode(",", $tmp_holder);
		}

		// if ids not set, set to default for given forums
		if (!preg_match("/^[0-9|,]+$/",$ids))
		{
			unset($ids);
			foreach($forums as $forum_id)
				$ids[] = explode(",", $this->CONFIG->FORUM[$forum_id]['default_ids']);
		}

		// else format ids into expected array format
		else
		{
			$temp_ids = explode("|", $ids);

			// unset in order to re-assign as array
			unset($ids);

			foreach($temp_ids as $forum_ids)
			{
				$ids[] = explode(",", $forum_ids);
			}
		}


		// +------------------------------
		//	Validate data
		// +------------------------------
		$this->validate_num($number, "", "invalid_number");
		$this->validate_num($ids, "", "invalid_ids");
		$this->validate_num($forums, "", "invalid_forums");


		return array($number, $ids, $forums);
	}


	/* ------------------------------------------------------------------ */
	//	News
	//  Pulls news & calls specific forum parsing, sorts posts from
	//	multiple forums.
	//	RETURNS TRUE/FALSE on success/error
	/* ------------------------------------------------------------------ */
	
	function news($number, $ids, $forums, $headlines = false)
	{
		// +------------------------------
		//	Pull posts from forum
		// +------------------------------

		for($i=0; $i<count($forums); $i++)
		{
			// error if forum is disabled
			if (!is_object($this->FORUM[$forums[$i]]))
			{
				$this->ERROR->go("forum_disabled");

				// RETURN FALSE :: Script can't go any further.
				return false;
			}

			// fetch data from forum db
			if (!$this->FORUM[$forums[$i]]->fetch_Posts($number, $ids[$i], $headlines))
				return false; // RETURN FALSE :: Script can't go any further

			// get data
			$this->DATA[$i] = $this->FORUM[$forums[$i]]->get_Posts() ;

			// group data into one large post array
			foreach($this->DATA[$i] as $current_post)
			{
				// add in forum_id so we can distinguish later
				$current_post['forum_id'] = $forums[$i];

				$ALL_POSTS[] = $current_post ;
			}
		}


		// +------------------------------
		//	Sort Posts by date
		// +------------------------------
		// Sort posts based on date (most recent first, oldest last)
		usort($ALL_POSTS, array("EP_Dev_Forum_News", "compare_dates"));

		
		// +------------------------------
		//	Parse posts for config code, forum-specific code
		//	and for forum-specific smilies (all smilies)
		// +------------------------------

		// correct number if we do not have the specified amount of posts
		if (count($ALL_POSTS) < $number)
			$number = count($ALL_POSTS);

		for($i=0; $i<$number; $i++)
		{
			// parse for normal code
			$this->parse_Features($ALL_POSTS[$i]);

			// THE ORDER OF THE FOLLOWING ARE __VERY__ IMPORTANT
			
			// parse for smilies
			$this->FORUM[intval($ALL_POSTS[$i]['forum_id'])]->parse_Smilies($ALL_POSTS[$i]['text']);

			// parse for html normal code
			$this->parse_Data($ALL_POSTS[$i]);

			// parse for forum-specific code
			$this->FORUM[intval($ALL_POSTS[$i]['forum_id'])]->parse_BBcode($ALL_POSTS[$i]['text']);

			// store formatted text
			$this->DATA_PARSED[] = $ALL_POSTS[$i];
		}

		return true;
	}


	/* ------------------------------------------------------------------ */
	//	compare dates
	//  Compares date of a with b and returns value expected by usort()
	/* ------------------------------------------------------------------ */
	
	function compare_dates($post_a, $post_b)
	{
		// if same date, return 0 (same)
		if ($post_a['date'] == $post_b['date'])
			return 0;

		// sorting from most recent to oldest
		return ($post_a['date'] > $post_b['date'] ? -1 : 1);
	}


	/* ------------------------------------------------------------------ */
	//	validate number
	//  Checks to see if value is numeric and errors & dies if not
	/* ------------------------------------------------------------------ */
	
	function validate_num($value, $key, $error)
	{
		// search recursively (can't use PHP 5's array_walk_recursive() yet)
		if (is_array($value))
			array_walk($value, array("EP_Dev_Forum_News", "validate_num"), $error); // recursive
		else if (!is_numeric($value))
			$this->ERROR->stop($error); // error if not numeric
	}


	/* ------------------------------------------------------------------ */
	//	Display News
	//  Combines calls to pull news, parse template, and ouput to browser
	/* ------------------------------------------------------------------ */
	
	function display_News($in_number=NULL, $in_ids=NULL, $in_forums=NULL, $template_id=0, $headlines = false)
	{
		// +------------------------------
		//	Check for valid input
		//	Format into expected form
		// +------------------------------
		list($number, $ids, $forums) = $this->scriptProcess_input($in_number, $in_ids, $in_forums);


		// +------------------------------
		//	Recycle old data
		// +------------------------------
		
		// if recycled data is available, use it
		if (
			($recycled_data = $this->getRecycledData($number, $ids, $forums, $template_id, $headlines)) !== false
			&& $this->CONFIG->NEWS['recycle_time'] != 0
			)
		{
			$output = $recycled_data;
		}

		
		// pull / process / display news
		else
		{

			// open up forum connections
			// open each connection, upon failure see if we can use recycled data.
			// if one fails, they all are considered failed (in order to use recycled)
			for($i=0, $connect_success=true; $i<count($forums) && $connect_success; $i++)
			{
				if (!$this->FORUM[$forums[$i]]->initialize() && $this->CONFIG->NEWS['failsafe_recycle'])
					$connect_success = false;
			}

			if ($connect_success)
			{
				// grab / process news
				$this->news($number, $ids, $forums);

				// parse for template data
				for($i=0; $i<count($this->DATA_PARSED); $i++)
					$this->DATA_FINAL[] = $this->parse_Template($this->DATA_PARSED[$i], $template_id, $headlines);

				// store recycled data if recycling enabled
				if ($this->CONFIG->NEWS['recycle_time'] != 0)
					$this->storeRecycledData($number, $ids, $forums, $template_id, $headlines, implode("", $this->DATA_FINAL));

				// output
				$output = implode("", $this->DATA_FINAL);
			}

			// if failure connecting to news source
			else
			{
				$news = $this->getRecycledData($number, $ids, $forums, $template_id, $headlines, true);

				if ($news == false)
				{
					$this->ERROR->go("mysql_error_recycle");
					$output = false;
				}
				else
				{
					$output = $news;
				}
			}
		}

		// output if no error
		if ($output !== false)
			echo $output;
	}


	/* ------------------------------------------------------------------ */
	//	Display Headlines
	//  Simple function to make headlines & news display calls uniform
	/* ------------------------------------------------------------------ */
	
	function display_Headlines($number=NULL, $ids=NULL, $forums=NULL, $template_id=0)
	{
		$this->display_News($number, $ids, $forums, $template_id, true);
	}


	/* ------------------------------------------------------------------ */
	//	Get Recycled Filename
	//  Returns the formatted filename according to the parameters.
	/* ------------------------------------------------------------------ */
	
	function getRecycledFilename($number, $ids, $forums, $template_id, $headlines)
	{
		/*

		FORMAT:
		filename:
		news_TIME_NUMBER_IDS_FORUMS.php
		hd_NUMBER_IDS_FORUMS.php

		*/

		// +------------------------------
		//	Gererate Filename
		// +------------------------------
		$forums_filename = implode("-", $forums);

		foreach($ids as $key_count => $id_array)
		{
			$ids_filename .= $key_count . "__";

			foreach($id_array as $true_id)
				$ids_filename .= $true_id . "-";
		}


		$filename = $this->CONFIG->SCRIPT['folders']['cache'] . ($headlines ? "hd_" : "news_")
					. $number . "_" . $ids_filename . $forums_filename . "_" . $template_id . ".php";

		return $filename;
	}


	/* ------------------------------------------------------------------ */
	//	Recycle Data
	//  Pulls the recycled data if available. Checks to see if up-to-date.
	//	returns output data if up-to-date, otherwise returns false
	/* ------------------------------------------------------------------ */
	
	function getRecycledData($number, $ids, $forums, $template_id, $headlines, $force = false)
	{
		/*

		FILE:

		$time = TIME;
		$data = "":
		return array($time, $data);

		*/

		$filename = $this->getRecycledFilename($number, $ids, $forums, $template_id, $headlines);

		$recycled_data = @include($filename);

		// NOTE: Two return statements, one if data retrieved, the other if error / not up-to-date

		if ($recycled_data)
		{
			if ($recycled_data[0] > (time() - $this->CONFIG->NEWS['recycle_time'] * 60) || $force)
				return $recycled_data[1];
		}

		return false;
	}


	/* ------------------------------------------------------------------ */
	//	Store Recycled Data
	//  Stores recycled data. This function is completely silent (will not
	//	show any errors if something goes wrong).
	/* ------------------------------------------------------------------ */
	
	function storeRecycledData($number, $ids, $forums, $template_id, $headlines, $data)
	{
		$filename = $this->getRecycledFilename($number, $ids, $forums, $template_id, $headlines);

		$handle = @fopen($filename, "wb");

		if ($handle)
		{
			$file_data = "<?php\r\n\$time = " . time() . ";\r\n\$data=\""
						. str_replace("\'", "'", addslashes($data))
						. "\";\r\nreturn array(\$time, \$data);";

			// lock file
			flock($handle, LOCK_EX);
			
			// write to file
			@fwrite($handle, $file_data);

			// unlock file
			flock($handle, LOCK_UN);

			// close
			@fclose($file_data);
		}
	}


}
