<?php

/* ------------------------------------------------------------------ */
//	Forum Module: phpBB 2.x
//		module version: 1.11
//		forum versions: 2.x
//		12/4/2005
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_phpBB_2_Access
{
	// !!!!!!!!! -------- CUSTOM USER PREFIX -------- !!!!!!!!! //
	// !!!!!!!! ----- PHP-Nuke ----- !!!!!!!!!!! //
	var $customPrefix = "";

	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $DB;


	function EP_Dev_Forum_News_phpBB_2_Access(&$DB_access, &$forum_conf, &$error_handle)
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
		$this->LINKS['author'] = $this->CONF['url'] . "profile.php&#63;mode=viewprofile&#38;u=";
		$this->LINKS['thread'] = $this->CONF['url'] . "viewtopic.php&#63;t=";
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
		$this->DB->query("SELECT topic_id, forum_id, topic_title, topic_poster, topic_time, topic_views, topic_replies, topic_first_post_id FROM " . $this->DB->prefix . "topics WHERE ". $WHERE ." ORDER BY topic_id DESC LIMIT " . $number_to_fetch);

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
				$this->DB->query("SELECT bbcode_uid, post_text FROM " . $this->DB->prefix . "posts_text WHERE post_id='" .$current_post['topic_first_post_id']. "' ORDER BY post_id ASC LIMIT 1");

				// phpBB has some unique code stuck in with each bbcode. We need to remove it.
				$tmp_post_text_arry = $this->DB->fetch_array();
				$post_text = str_replace(":".$tmp_post_text_arry['bbcode_uid']."]", "]", $tmp_post_text_arry['post_text']);

				// We have to pull username based on id
				$this->DB->query("SELECT username FROM " . ($this->customPrefix != "" ? $this->customPrefix : $this->DB->prefix) . "users WHERE user_id='" .$current_post['topic_poster']. "'");

				// store into 'username'
				$current_post['username'] = $this->DB->value();
			}

			// else if only grabbing headlines
			else
			{
				// We have to pull username based on id
				$this->DB->query("SELECT username FROM " . ($this->customPrefix != "" ? $this->customPrefix : $this->DB->prefix) . "users WHERE user_id='" .$current_post['topic_poster']. "'");

				// store into 'username'
				$current_post['username'] = $this->DB->value();

				// set post text to empty
				$post_text = "";
			}
			

			// +------------------------------
			//	Store Post Data
			// +------------------------------
			
			// Store into post data
			$this->POSTS[$post_count] = array(
									"text" => $post_text,
									"title" => $current_post['topic_title'],
									"author_name" => $current_post['username'],
									"author_id" => $current_post['topic_poster'],
									"date" => $current_post['topic_time'],
									"reply_num" => $current_post['topic_replies'],
									"view_num" => $current_post['topic_views'],
									"post_id" => $current_post['topic_id'],
									"cat_id" => $current_post['forum_id'],
									"author_url" => $this->get_Author_Link($current_post['topic_poster']),
									"post_url" => $this->get_Thread_Link($current_post['topic_id'])
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
				$text = str_replace($current_smilie['text'], "<img src=\"" . $this->CONF['url'] . "images/smiles/" . $current_smilie['image'] . "\" border=\"0\">", $text);
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
		$this->DB->query("SELECT user_avatar, user_avatar_type FROM " . ($this->customPrefix != "" ? $this->customPrefix : $this->DB->prefix) . "users WHERE user_id='" . $author . "'");

		// assign result to $avatar
		list($avatar, $avatar_type) = $this->DB->fetch_array();

		
		// +------------------------------
		//	Detect Avatar and store accordingly
		// +------------------------------
		
		// if url to avatar
		if ($avatar_type == "2")
		{
			$return = $avatar;
		}

		// if avatar upload
		elseif ($avatar_type == "1")
		{
			$return = $this->CONF['url'] . "images/avatars/" . $avatar;
		}

		// if avatar contains period, assume a file
		elseif ($avatar_type == "3")
		{
			$return = $this->CONF['url'] . "images/avatars/gallery/" . $avatar;
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
		$this->DB->query("SELECT * FROM " . $this->DB->prefix . "smilies ORDER BY CHAR_LENGTH(code) DESC");

		// initialize smilie count
		$smilie_count = 0;

		// cycle through smilies, storing into expected format
		while($smilie_data = $this->DB->fetch_array())
		{
			$this->SMILIES[$smilie_count]['text'] = $smilie_data['code'];
			$this->SMILIES[$smilie_count]['image'] = $smilie_data['smile_url'];
			
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