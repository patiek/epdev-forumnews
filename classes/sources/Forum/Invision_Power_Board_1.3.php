<?php

/* ------------------------------------------------------------------ */
//	Forum Module: Invision Power Board 1.3.x
//		module version: 1.03
//		forum versions: 1.3.x
//		12/4/2005
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Invision_Power_Board_1_3_Access
{
	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $DB;


	function EP_Dev_Forum_News_Invision_Power_Board_1_3_Access(&$DB_access, &$forum_conf, &$error_handle)
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
		$this->LINKS['author'] = $this->CONF['url'] . "index.php&#63;showuser=";
		$this->LINKS['thread'] = $this->CONF['url'] . "index.php&#63;showtopic=";
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
	
	function get_Thread_Link($thread)
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
		$WHERE = "forum_id='" . implode("' or forum_id='", $ids_to_fetch) . "'";
		
		// Pull from database
		$this->DB->query("SELECT tid, title, posts, starter_id, start_date, starter_name, views, forum_id FROM " . $this->DB->prefix . "topics WHERE ". $WHERE ." ORDER BY tid DESC LIMIT " . $number_to_fetch);

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
				$this->DB->query("SELECT post FROM " . $this->DB->prefix . "posts WHERE topic_id='" .$current_post['tid']. "' ORDER BY pid ASC LIMIT 1");

				// store into $post_text
				$post_text = $this->DB->value();
			}

			// else if only grabbing headlines
			else
			{
				// set post text to empty
				$post_text = "";
			}
			

			// +------------------------------
			//	Store Post Data
			// +------------------------------
			
			// Store into post data
			$this->POSTS[$post_count] = array(
									"text" => $post_text,
									"title" => $current_post['title'],
									"author_name" => $current_post['starter_name'],
									"author_id" => $current_post['starter_id'],
									"date" => $current_post['start_date'],
									"reply_num" => $current_post['posts'],
									"view_num" => $current_post['views'],
									"post_id" => $current_post['tid'],
									"cat_id" => $current_post['forum_id'],
									"author_url" => $this->get_Author_Link($current_post['starter_id']),
									"post_url" => $this->get_Thread_Link($current_post['tid'])
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
		$search_array = array(
						"/\[s\](.*?)\[\/s\]/is",
						"/<(br)>/i"
						);
		
		$replace_array = array(
						"<s>\$1</s>",
						"<\$1 />"
						);

		$text = preg_replace($search_array, $replace_array, $text);
	}



	function parse_HTMLcode(&$text)
	{
		// matching [doHTML] tags
		$numberMatches = preg_match_all("/\[dohtml\](.*?)\[\/dohtml\]/is", $text, $matches, PREG_SET_ORDER);
		
		for($i=0; $i<$numberMatches; $i++)
		{
			$replaceText = html_entity_decode($matches[$i][1], ENT_QUOTES);
			$text = str_replace($matches[$i][0], $replaceText, $text);
		}
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
				$text = str_replace($current_smilie['text'], "<img src=\"" . $this->CONF['url'] . $current_smilie['image'] . "\" border=\"0\">", $text);
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
		$this->DB->query("SELECT avatar FROM " . $this->DB->prefix . "members WHERE id='" . $author . "'");

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

		// if avatar upload
		elseif (eregi("^upload:", $avatar))
		{
			// $real_avatar contains filename of uploaded avatar
			list( , $real_avatar) = explode(":", $avatar);

			$return = $this->CONF['url'] . "uploads/" . $real_avatar;
		}

		// if avatar contains period, assume a file
		elseif (strpos($avatar, ".") !== false)
		{
			$return = $this->CONF['url'] . "html/avatars/" . $avatar;
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
		$this->DB->query("SELECT * FROM " . $this->DB->prefix . "emoticons ORDER BY CHAR_LENGTH(typed) DESC");

		// initialize smilie count
		$smilie_count = 0;

		// cycle through smilies, storing into expected format
		while($smilie_data = $this->DB->fetch_array())
		{
			$this->SMILIES[$smilie_count]['text'] = $smilie_data['typed'];
			$this->SMILIES[$smilie_count]['image'] = $smilie_data['image'];
			
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