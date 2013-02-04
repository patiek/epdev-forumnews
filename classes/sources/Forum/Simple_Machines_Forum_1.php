<?php

/* ------------------------------------------------------------------ */
//	Forum Module: Simple Machines Forum (SMF)
//		module version: 1.01
//		forum versions: 1.0
//		12/4/2005
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Simple_Machines_Forum_1_Access
{
	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $DB;


	function EP_Dev_Forum_News_Simple_Machines_Forum_1_Access(&$DB_access, &$forum_conf, &$error_handle)
	{
		// initialize database variable
		$this->DB = $DB_access;

		/* 
			initialize forum configuration
			WARNING: this should be used only to pull url data,
			where only ->url is valid. Any other config data may not
			exist in future versions. Ideally we would make most of
			the data private, but PHP5 isn't widely supported yet.
		*/
		$this->CONF = $forum_conf;

		// initialize error handle
		$this->ERROR = $error_handle;

		// initialize forum-specific links
		$this->LINKS['author'] = $this->CONF['url'] . "index.php&#63;action=profile&#38;u=";
		$this->LINKS['thread'] = $this->CONF['url'] . "index.php&#63;topic=";
	}


	/* ------------------------------------------------------------------ */
	//	Initialize Forum Object
	//  This function is called prior to fetching any news or headlines.	
	//	If something goes wrong, return false on failure. Else return true.
	/* ------------------------------------------------------------------ */
	
	function initialize()
	{
		// connect to database
		return $this->DB->connect();
	}


	/* ------------------------------------------------------------------ */
	//	Get Author Link
	//  Returns full url to author profile 
	/* ------------------------------------------------------------------ */
	
	function get_Author_Link($author)
	{
		// return Author Link
		return $this->LINKS['author'] . $author;
	}


	/* ------------------------------------------------------------------ */
	//	Get Thread Link
	//  Returns full url to thread 
	/* ------------------------------------------------------------------ */
	
	function get_Thread_Link($thread, $board_id = NULL)
	{
		// return author link
		return $this->LINKS['thread'] . $thread;
	}


	/* ------------------------------------------------------------------ */
	//	Fetch Posts
	//  Grabs posts of specified conditions and stores into $POSTS
	//  RETURNS TRUE/FALSE on success/failure
	/* ------------------------------------------------------------------ */
	
	function fetch_Posts($number_to_fetch, $ids_to_fetch, $headlines_only = false)
	{

		// +------------------------------
		//	Fetch forum posts
		// +------------------------------

		// Prepare WHERE clause
		$WHERE = "ID_BOARD='" . implode("' or ID_BOARD='", $ids_to_fetch) . "'";
		
		// Pull from database
		$this->DB->query("SELECT ID_TOPIC, ID_BOARD, ID_MEMBER_STARTED, ID_FIRST_MSG, numReplies, numViews FROM " . $this->DB->prefix . "topics WHERE ". $WHERE ." ORDER BY ID_TOPIC DESC LIMIT " . $number_to_fetch);

		// error if no posts
		if (!$this->DB->rows())
		{
			$this->ERROR->go("no_posts_found");
			return false;
		}

		// assign result to $posts
		$posts = $this->DB->result;

		// initialize post count
		$post_count = 0;

		// cycle through results of $post
		while($current_post = $this->DB->fetch_array($posts))
		{
			
			// +------------------------------
			//	Headline Handling
			// +------------------------------
			
			// If fetching post text too (not just headlines).
			if (!$headlines_only)
			{
				// Get text of post
				$this->DB->query("SELECT subject, posterName, posterTime, body FROM " . $this->DB->prefix . "messages WHERE ID_MSG='" .$current_post['ID_FIRST_MSG']. "' ORDER BY ID_MSG ASC LIMIT 1");

				// store
				list($title, $author, $date, $post_text) = $this->DB->fetch_array();
				//$post_text = $this->DB->value();
			}

			// else if only grabbing headlines
			else
			{
				// Get text of post
				$this->DB->query("SELECT subject, posterName, posterTime FROM " . $this->DB->prefix . "messages WHERE ID_MSG='" .$current_post['ID_FIRST_MSG']. "' ORDER BY ID_MSG ASC LIMIT 1");

				// store
				list($title, $author, $date) = $this->DB->fetch_array();
				//$post_text = $this->DB->value();

				// set post text to empty
				$post_text = "";
			}
			

			// +------------------------------
			//	Store Post Data
			// +------------------------------
			
			// Store into post data
			$this->POSTS[$post_count] = array(
									"text" => $post_text,
									"title" => $title,
									"author_name" => $author,
									"author_id" => $current_post['ID_MEMBER_STARTED'],
									"date" => $date,
									"reply_num" => $current_post['numReplies'],
									"view_num" => $current_post['numViews'],
									"post_id" => $current_post['ID_TOPIC'],
									"cat_id" => $current_post['ID_BOARD'],
									"author_url" => $this->get_Author_Link($current_post['ID_MEMBER_STARTED']),
									"post_url" => $this->get_Thread_Link($current_post['ID_TOPIC'], $current_post['ID_BOARD'])
								);

			// increment post_count
			$post_count++;
		}

		return true;
	}


	/* ------------------------------------------------------------------ */
	//	Parse BB Code
	//  Parses $text for BB code that is specific to this forum.
	/* ------------------------------------------------------------------ */
	
	function parse_BBcode(&$text)
	{	
		// YaBB has alot of custom bbcode
		$search_array = array(
						"/\[ftp=([^\]]*)\]([^\[]*)\[\/ftp\]/i",
						"/\[glow=([^,]*),([^,]*),([^\]]*)\]([^\[]*)\[\/glow\]/i",
						"/\[shadow=([^,]*),([^\]]*)\]([^\[]*)\[\/shadow\]/i",
						"/\[flash=([^,]*),([^\]]*)\]([^\[]*)\[\/flash\]/i",
						"/\[s\]/i",
						"/\[\/s\]/i",
						"/\[move\]/i",
						"/\[\/move\]/i",
						"/\[pre\]/i",
						"/\[\/pre\]/i",
						"/\[left\]/i",
						"/\[\/left\]/i",
						"/\[center\]/i",
						"/\[\/center\]/i",
						"/\[right\]/i",
						"/\[\/right\]/i",
						"/\[hr\]/i",
						"/\[table\]/i",
						"/\[\/table\]/i",
						"/\[tr\]/i",
						"/\[\/tr\]/i",
						"/\[td\]/i",
						"/\[\/td\]/i",
						"/\[sup\]/i",
						"/\[\/sup\]/i",
						"/\[sub\]/i",
						"/\[\/sub\]/i",
						"/\[tt\]/i",
						"/\[\/tt\]/i"
						);

		$replace_array = array(
						"<a href=\"\$1\" target=\"_blank\">\$2</a>",
						"<table style=\"border 0px;\"><tr><td style=\"filter:Glow(color=\$1, strength=\$2);\">\$4</td></tr></table>",
						"<table style=\"border 0px;\"><tr><td style=\"filter:Shadow(color=\$1, direction=270);\">\$3</td></tr></table>",
						"<a href=\"\$3\" target=\"_blank\">\$3</a>",
						"<s>",
						"</s>",
						"<marquee>",
						"</marquee>",
						"<pre>",
						"</pre>",
						"<div align=\"left\">",
						"</div>",
						"<div align=\"center\">",
						"</div>",
						"<div align=\"right\">",
						"</div>",
						"<hr />",
						"<table>",
						"</table>",
						"<tr>",
						"</tr>",
						"<td>",
						"</td>",
						"<sup>",
						"</sup>",
						"<sub>",
						"</sub>",
						"<tt>",
						"</tt>"
						);

		$text = preg_replace($search_array, $replace_array, $text);
	}


	/* ------------------------------------------------------------------ */
	//	Parse Smilies
	//  Parses $text for forum's smilies
	/* ------------------------------------------------------------------ */
	
	function parse_Smilies(&$text)
	{
		// If we haven't fetched smilies yet.
		if (empty($this->SMILIES))
			$this->fetch_Smilies();


		// Cycle through smilies and replace
		foreach($this->SMILIES as $current_smilie)
		{
			// if url already in smilie
			if (eregi("^(http|https|ftp)://", $current_smilie['image']))
				$text = str_replace($current_smilie['text'], "<img src=\"" . $current_smilie['image'] . "\" border=\"0\">", $text);
			else // else put in url
				$text = str_replace($current_smilie['text'], "<img src=\"" . $this->CONF['url'] . "Smileys/default/" . $current_smilie['image'] . "\" border=\"0\">", $text);
		}
	}


	/* ------------------------------------------------------------------ */
	//	Fetch Author Image
	//  Grabs avatar information from DB and stores into $AUTHOR_IMAGES
	/* ------------------------------------------------------------------ */
	
	function fetch_Author_Image($author)
	{
		
		// +------------------------------
		//	Fetch Avatar from database
		// +------------------------------
		
		// grab avatar info from database
		$this->DB->query("SELECT avatar FROM " . $this->DB->prefix . "members WHERE  ID_MEMBER='" . $author . "'");

		// assign result to $avatar
		$avatar = $this->DB->value();

		
		// +------------------------------
		//	Detect Avatar and store accordingly
		// +------------------------------
		
		// if url to avatar
		if (eregi("^(http|https|ftp)://", $avatar))
		{
			$return = $avatar;
		}

		// if avatar contains period, assume a file
		elseif (strpos($avatar, ".") !== false)
		{
			$return = $this->CONF['url'] . "avatars/" . $avatar;
		}

		// else no avatar
		else
		{
			// emtpy means no avatar
			$return = NULL;
		}

		// store into author images array
		$this->AUTHOR_IMAGES[$author] = $return;
	}


	/* ------------------------------------------------------------------ */
	//	Fetch Smilies
	//  Grabs smilies from database and stores into $SMILIES
	/* ------------------------------------------------------------------ */
	
	function fetch_Smilies()
	{
		// Select smilie and sort by string length descending
		$this->DB->query("SELECT code, filename FROM " . $this->DB->prefix . "smileys ORDER BY CHAR_LENGTH(code) DESC");

		// initialize smilie count
		$smilie_count = 0;

		// cycle through smilies, storing into expected format
		while($smilie_data = $this->DB->fetch_array())
		{
			$this->SMILIES[$smilie_count]['text'] = htmlentities($smilie_data['code'], ENT_QUOTES);
			$this->SMILIES[$smilie_count]['image'] = $smilie_data['filename'];
			
			$smilie_count++;
		}
	}


	/* ------------------------------------------------------------------ */
	//	get Author Image
	//  Returns full url to author image
	/* ------------------------------------------------------------------ */
	
	function get_Author_Image($author)
	{
		// only fetch if not cached
		if(!isset($this->AUTHOR_IMAGES[$author]))
			$this->fetch_Author_Image($author);

		// return author image url
		return $this->AUTHOR_IMAGES[$author];
	}


	/* ------------------------------------------------------------------ */
	//	get Posts
	//  Returns data stored in $POSTS
	/* ------------------------------------------------------------------ */
	
	function get_Posts()
	{
		// this is a simple return as posts are already in expected format.
		return $this->POSTS;
	}
}