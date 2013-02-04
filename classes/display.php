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
//	Display Class
//
//	The Display class contains all of the functions that parse the
//	data. This includes native bbcode, various script features such
//	as auto-hyperlinking urls, ect.
//
//	In addition to the above, this class will also parse the templates
//	with the given posts. In other words, the display class handles all
//	of the display / display manipulation functions of the script, with
//	exception to a final output (such as echo).
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Display
{
	var $TEMPLATES;
	var $tid;

	var $news_output;
	var $headline_output;

	function EP_Dev_Forum_News_Display(&$templates)
	{
		$this->TEMPLATES = $templates;

		// set default template
		$this->set_Template(0);
	}

	
	/* ------------------------------------------------------------------ */
	//	Set Template Id
	//  Set the working template id, creates fresh template output
	/* ------------------------------------------------------------------ */

	function set_Template($template_id)
	{
		// check if template exists & update tid if it does
		if (isset($this->TEMPLATES[$template_id]))
			$this->tid = $template_id;

		// reset output
		$this->news_output = $this->TEMPLATES[$this->tid]['post'];
		$this->headline_output = $this->TEMPLATES[$this->tid]['headlines'];
	}


	/* ------------------------------------------------------------------ */
	//	Page Break Feature
	//  Parse $text for page break feature
	/* ------------------------------------------------------------------ */
	
	function feature_Page_Break(&$text, $keyword)
	{
		// get break position
		$page_break_pos = strpos($text, $keyword);

		// if break position, insert break template
		if ($page_break_pos !== false)
			$text = substr($text, 0, $page_break_pos) . $this->TEMPLATES[$this->tid]['read_more'];

		// we want to return actual boolean
		return ($page_break_pos === false ? false : true);
	}


	/* ------------------------------------------------------------------ */
	//	Character Limit Feature
	//  Parse $text for character limit feature
	/* ------------------------------------------------------------------ */
	
	function feature_Character_Limit(&$text, $limit)
	{
		// if length is longer than limit, then truncate to limit & add cut off
		if (strlen($text) > $limit)
		{
			$tmpText = substr($text, 0, $limit);

			// check if character is word or number
			if (preg_match("/[\w-]/", $text{$limit}) != 0)
			{
				// find last space occurence
				$spacePosition = strrpos($tmpText, " ");

				// if found, use it as limit
				if ($spacePosition != 0)
					$text = substr($text, 0, $spacePosition) . $this->TEMPLATES[$this->tid]['read_more_cut_off'];
				else // use cut off string
					$text = $tmpText . $this->TEMPLATES[$this->tid]['read_more_cut_off'];
			}

			// character is not [a-zA-Z0-9_-]
			else
			{
				$text = $tmpText . $this->TEMPLATES[$this->tid]['read_more_cut_off'];
			}
		}
	}


	/* ------------------------------------------------------------------ */
	//	Hyperlink URLs Feature
	//  Parse text to auto-link URLs
	/* ------------------------------------------------------------------ */
	
	function feature_Hyperlink_URLs(&$text)
	{
		// regular expression replacement for url formatting
		$text = preg_replace("/([^=\"'>]+?)(http|https|ftp|news):\/\/([^\s]+)/i", "\$1<a href=\"\$2://\$3\">\$2://\$3</a>" , $text);
	}


	/* ------------------------------------------------------------------ */
	//	Fix Line Breaks Feature
	//  Parse text fix line breaks from <br> to <br />
	/* ------------------------------------------------------------------ */
	
	function feature_Fix_LineBreaks(&$text)
	{
		// regular expression replacement for url formatting
		$text = preg_replace("/<(br)>/i", "<\$1 />" , $text);
	}


	/* ------------------------------------------------------------------ */
	//	BBcode Feature
	//  Call bbcode replace function
	/* ------------------------------------------------------------------ */
	
	function feature_BBcode(&$text)
	{
		$this->replace_BBcode($text);
	}

	
	/* ------------------------------------------------------------------ */
	//	Author Image Feature
	//  Parses post for author image url (avatar url)
	/* ------------------------------------------------------------------ */
	
	function feature_Author_Image($image_url)
	{
		// if avatar url available
		if ($image_url != NULL)
		{
			$this->news_output = str_replace("!!AUTHOR_IMAGE!!", str_replace("!!AVATAR_URL!!", $image_url, $this->TEMPLATES[$this->tid]['author_image']), $this->news_output);
		}

		// else remove avatar completely
		else
		{
			$this->news_output = str_replace("!!AUTHOR_IMAGE!!", "", $this->news_output);
		}
	}


	/* ------------------------------------------------------------------ */
	//	Replace Date
	//  Replace date section of template
	/* ------------------------------------------------------------------ */
	
	function replace_Date($post_data, &$template)
	{
		// Construct date array
		$search_array = array(
						"[YEAR-4]",
						"[YEAR-2]",
						"[MONTH-NUM]",
						"[MONTH]",
						"[MONTH-ABR]",
						"[DAY-NUM]",
						"[DAY]",
						"[DAY-ABR]",
						"[HOUR-24]",
						"[HOUR-12]",
						"[MIN]",
						"[SEC]",
						"[AM-PM]"
						);

		$replace_array =  explode("|", date("Y|y|m|F|M|j|l|D|H|g|i|s|A", $post_data['date']));

		$template = str_replace($search_array, $replace_array, $template);
	}


	/* ------------------------------------------------------------------ */
	//	Replace post template
	//  Replace post section of template
	/* ------------------------------------------------------------------ */
	
	function replace_Post($post_data, &$template)
	{
		$search_array = array(
							"!!TITLE!!",
							"!!CONTENT!!",
							"!!CATEGORYID!!",
							"!!VIEWS!!",
							"!!THREADID!!",
							"!!NUM_COMMENTS!!",
							"!!AUTHOR_NAME!!",
							"!!AUTHOR_ID!!",
							"!!NEWS_URL!!",
							"!!AUTHOR_URL!!",
							"!!SOURCE_ID!!"
						);

		$replace_array = array(
							$post_data['title'],
							$post_data['text'],
							$post_data['cat_id'],
							$post_data['view_num'],
							$post_data['post_id'],
							$post_data['reply_num'],
							$post_data['author_name'],
							$post_data['author_id'],
							$post_data['post_url'],
							$post_data['author_url'],
							$post_data['forum_id']+1
						); // NOTICE: forum_id is actually used for arrays, so we add one

		$template = str_replace($search_array, $replace_array, $template);
	}


	/* ------------------------------------------------------------------ */
	//	Replace post template
	//  Replace post section of template
	/* ------------------------------------------------------------------ */
	
	function replace_BBcode(&$text)
	{
		// i = case insensitive, s = white space (newlines included in dot)
		// Notice the most complex come first, that way we don't accidentally
		// replace (for ex) a [/url] that is part of a [url] [/url] statement
		$search_array = array(
						"/\[url=[\"]?([^\]]*)\](.*?)[\"]?\[\/url\]/i",
						"/\[url\](.*?)\[\/url\]/i",
						"/\[email=[\"]?([^\]]*)\](.*?)[\"]?\[\/email\]/i",
						"/\[email\](.*?)\[\/email\]/i",
						"/\[(face|font)=[\"]?([^\]]*)\](.*?)[\"]?\[\/\\1\]/is",
						"/\[color=[\"]?([^\]]*)\](.*?)[\"]?\[\/color\]/is",
						"/\[size=[\"]?([^\]]*)\](.*?)[\"]?\[\/size\]/is",
						"/\[quote\](.*?)\[\/quote\]/is",
						"/\[b\](.*?)\[\/b\]/is",
						"/\[i\](.*?)\[\/i\]/is",
						"/\[u\](.*?)\[\/u\]/is",
						"/\[img\](.*?)\[\/img\]/i",
						"/\[code\](.*?)\[\/code\]/is",
						"/\[highlight\](.*?)\[\/highlight\]/is",
						);
		
		$replace_array = array(
						"<a href=\"\$1\" target=\"_blank\">\$2</a>",
						"<a href=\"\$1\" target=\"_blank\">\$1</a>",
						"<a href=\"mailto:\$1\">\$2</a>",
						"<a href=\"mailto:\$1\">\$1</a>",
						"<span style=\"font-family: \$2;\">\$3</span>",
						"<span style=\"color: \$1;\">\$2</span>",
						"<font size=\"\$1\">\$2</font>",
						str_replace("!!POST_TEXT!!", "\$1", $this->TEMPLATES[$this->tid]['quotes']),
						"<span style=\"font-weight: bold;\">\$1</span>",
						"<span style=\"font-style: italic;\">\$1</span>",
						"<span style=\"text-decoration: underline;\">\$1</span>",
						"<img src=\"\$1\" boarder=\"0\">",
						str_replace("!!POST_TEXT!!", "\$1", $this->TEMPLATES[$this->tid]['code']),
						str_replace("!!POST_TEXT!!", "\$1", $this->TEMPLATES[$this->tid]['highlight'])
						);

		$text = preg_replace($search_array, $replace_array, $text);

		// PHP syntax highlighting
		if($php_number_matched = preg_match_all("/(\[php\])(.*?)(\[\/php\])/is", $text, $php_matches))
		{
			// cycle through matches and replace
			for($i=0; $i<$php_number_matched; $i++)
			{
				$text = str_replace($php_matches[1][$i] . $php_matches[2][$i] . $php_matches[3][$i], str_replace("!!POST_TEXT!!", highlight_string($php_matches[2][$i], true), $this->TEMPLATES[$this->tid]['php']), $text);
			}
		}
	}


	/* ------------------------------------------------------------------ */
	//	Post Template
	//  Combines calls to replace template data
	/* ------------------------------------------------------------------ */
	
	function template_Post($post_data)
	{
		// replace date
		$this->replace_Date($post_data, $this->news_output);

		// replace post data
		$this->replace_Post($post_data, $this->news_output);
	}


	/* ------------------------------------------------------------------ */
	//	Headline Template
	//  Combines calls to replace headline data
	/* ------------------------------------------------------------------ */

	function template_Headline($post_data)
	{
		// replace date
		$this->replace_Date($post_data, $this->headline_output);

		// replace post data
		$this->replace_Post($post_data, $this->headline_output);
	}


	/* ------------------------------------------------------------------ */
	//	Get Post
	//  Returns post output
	/* ------------------------------------------------------------------ */
	
	function get_Post()
	{
		return $this->news_output;
	}


	/* ------------------------------------------------------------------ */
	//	Get Post
	//  Returns headline output
	/* ------------------------------------------------------------------ */
	
	function get_Headline()
	{
		return $this->headline_output;
	}

}


class EP_Dev_Forum_News_Error_Handle
{
	/* ------------------------------------------------------------------ */
	//	Stop with $code
	//  Dies with error ouput
	/* ------------------------------------------------------------------ */

	function stop($code)
	{
		$this->kill($this->go($code));
	}


	/* ------------------------------------------------------------------ */
	//	Go $code
	//  Outputs textual error based on $code
	/* ------------------------------------------------------------------ */
	
	function go($code)
	{
		echo $this->getError($code);
	}


	/* ------------------------------------------------------------------ */
	//	Get Error $code
	//  Returns textual error based on $code
	/* ------------------------------------------------------------------ */
	
	function getError($code)
	{
		switch ($code)
		{
			case "no_posts_found" : 
				$return = "No posts found in selected forum category.";
			break;

			case "mysql_error_recycle" :
				$return = "Error connecting to specified news source with username and password specified. Additionally, no recycled data was found that could be used. You must enable both Caching support and Failsafe Support in order to use cache on failure feature.";
			break;

			case "mysql_connect_error" : 
				$return = "Error connecting to mysql database with username and password specified.";
			break;

			case "mysql_db_error" : 
				$return = "Error connecting to specified database name.";
			break;

			case "invalid_number" : 
				$return = "ERROR: Invalid post number specified.";
			break;

			case "invalid_ids" : 
				$return = "ERROR: Invalid forum id(s) specified.";
			break;

			case "invalid_forums" : 
				$return = "ERROR: Invalid forum(s) specified.";
			break;

			case "forum_disabled" :
				$return = "ERROR: Forum specified is disabled in the script.";
			break;

			default : $return = "An unknown error occurred!";
		}

		return $return;
	}


	/* ------------------------------------------------------------------ */
	//	Kill with $error
	//  dies with textual $error
	/* ------------------------------------------------------------------ */
	
	function kill($error)
	{
		die($error);
	}
}