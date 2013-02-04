<?php

/* ------------------------------------------------------------------ */
//	Forum Module: Web Burning Board 1.2.x
//		module version: 1.01
//		forum versions: 1.2.x (final)
//		12/4/2005
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Web_Burning_Board_1_2_Access
{
	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $DB;


	function EP_Dev_Forum_News_Web_Burning_Board_1_2_Access(&$DB_access, &$forum_conf, &$error_handle)
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
		$this->LINKS['author'] = $this->CONF['url'] . "members.php&#63;mode=profile&#38;userid=";
		$this->LINKS['thread'] = $this->CONF['url'] . "thread.php&#63;threadid=[tid]&#38;boardid=[bid]";
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
		// if board id not provided
		if ($board_id === NULL)
		{
			// Get text of post
			$this->DB->query("SELECT boardparentid FROM " . $this->DB->prefix . "threads WHERE threadid='" .$thread. "' LIMIT 1");

			// store into $post_text
			$board_id = $this->DB->value();

			$thread_url = str_replace("[tid]", $thread, $this->LINKS['thread']);
			$thread_url = str_replace("[bid]", $board_id, $thread_url);
		}

		// else use provided board id
		else
		{
			$thread_url = str_replace("[tid]", $thread, $this->LINKS['thread']);
			$thread_url = str_replace("[bid]", $board_id, $thread_url);
		}

		// return author link
		return $thread_url;
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
		$WHERE = "boardparentid='" . implode("' or boardparentid='", $ids_to_fetch) . "'";
		
		// Pull from database
		$this->DB->query("SELECT boardparentid, starttime, threadid, threadname, authorid, author, replies, views FROM " . $this->DB->prefix . "threads WHERE ". $WHERE ." ORDER BY threadid DESC LIMIT " . $number_to_fetch);

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
				$this->DB->query("SELECT message FROM " . $this->DB->prefix . "posts WHERE threadparentid='" .$current_post['threadid']. "' ORDER BY postid ASC LIMIT 1");

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
									"title" => $current_post['threadname'],
									"author_name" => $current_post['author'],
									"author_id" => $current_post['authorid'],
									"date" => $current_post['starttime'],
									"reply_num" => $current_post['replies'],
									"view_num" => $current_post['views'],
									"post_id" => $current_post['threadid'],
									"cat_id" => $current_post['boardparentid'],
									"author_url" => $this->get_Author_Link($current_post['authorid']),
									"post_url" => $this->get_Thread_Link($current_post['threadid'], $current_post['boardparentid'])
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
		// convert new lines to <br>
		$text = nl2br($text);
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
				$text = str_replace($current_smilie['text'], "<img src=\"" . $this->CONF['url'] . "images/smilies/" . $current_smilie['image'] . "\" border=\"0\">", $text);
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
		$this->DB->query("SELECT avatarid FROM " . $this->DB->prefix . "user_table WHERE userid='" . $author . "'");

		// assign result to $avatar
		$avatar = $this->DB->value();

		
		// +------------------------------
		//	Detect Avatar and store accordingly
		// +------------------------------
		
		// if avatar upload or file
		if (ereg("^[0-9]+$", $avatar) && $avatar != "0")
		{
			$this->DB->query("SELECT extension FROM " . $this->DB->prefix . "avatars WHERE id='" . $avatar . "'");
			$avatar_ext = $this->DB->value();

			$return = $this->CONF['url'] . "images/avatars/avatar-" . $avatar . "." . $avatar_ext;
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
		$this->DB->query("SELECT * FROM " . $this->DB->prefix . "smilies ORDER BY CHAR_LENGTH(smiliestext) DESC");

		// initialize smilie count
		$smilie_count = 0;

		// cycle through smilies, storing into expected format
		while($smilie_data = $this->DB->fetch_array())
		{
			$this->SMILIES[$smilie_count]['text'] = $smilie_data['smiliestext'];
			$this->SMILIES[$smilie_count]['image'] = $smilie_data['smiliespath'];
			
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