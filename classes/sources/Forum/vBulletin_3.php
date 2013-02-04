<?php

/* ------------------------------------------------------------------ */
//	Forum Module: vBulletin 3.x
//		module version: 1.02
//		forum versions: 3.6x
//		10/12/2006
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_vBulletin_3_Access
{
	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $DB;


	function EP_Dev_Forum_News_vBulletin_3_Access(&$DB_access, &$forum_conf, &$error_handle)
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
		$this->LINKS['author'] = $this->CONF['url'] . "member.php&#63;u=";
		$this->LINKS['thread'] = $this->CONF['url'] . "showthread.php&#63;t=";
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
		$WHERE = "forumid='" . implode("' or forumid='", $ids_to_fetch) . "'";
		
		// Pull from database
		$this->DB->query("SELECT threadid, title, firstpostid, forumid, replycount, postusername, postuserid, dateline, views FROM " . $this->DB->prefix . "thread WHERE ". $WHERE ." ORDER BY threadid DESC LIMIT " . $number_to_fetch);

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
				$this->DB->query("SELECT pagetext FROM " . $this->DB->prefix . "post WHERE postid='" .$current_post['firstpostid']. "' ORDER BY postid ASC LIMIT 1");

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
									"author_name" => $current_post['postusername'],
									"author_id" => $current_post['postuserid'],
									"date" => $current_post['dateline'],
									"reply_num" => $current_post['replycount'],
									"view_num" => $current_post['views'],
									"post_id" => $current_post['threadid'],
									"cat_id" => $current_post['forumid'],
									"author_url" => $this->get_Author_Link($current_post['postuserid']),
									"post_url" => $this->get_Thread_Link($current_post['threadid'])
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

		// i = case insensitive
		// Notice the most complex come first, that way we don't accidentally
		// replace (for ex) a [/url] that is part of a [url] [/url] statement
		$search_array = array(
						"/\[left\]/i",
						"/\[\/left\]/i",
						"/\[center\]/i",
						"/\[\/center\]/i",
						"/\[right\]/i",
						"/\[\/right\]/i"
						);
		
		$replace_array = array(
						"<div align=\"left\">",
						"</div>",
						"<div align=\"center\">",
						"</div>",
						"<div align=\"right\">",
						"</div>"
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
		$this->DB->query("SELECT avatarid FROM " . $this->DB->prefix . "user WHERE userid='" . $author . "'");

		// assign result to $avatar
		$avatar = $this->DB->value();

		
		// +------------------------------
		//	Detect Avatar and store accordingly
		// +------------------------------
		
		// if forum avatar
		if ($avatar != "0")
		{
			$this->DB->query("SELECT avatarpath FROM " . $this->DB->prefix . "avatar WHERE avatarid='" . $avatar . "'");
			$real_avatar = $this->DB->value();

			$return = $this->CONF['url'] . $real_avatar;
		}

		// if avatar upload / url or if no avatar
		else
		{
			$this->DB->query("SELECT dateline FROM " . $this->DB->prefix . "customavatar WHERE userid='" . $author . "'");

			if ($this->DB->rows() != "0") // if custom avatar
				$return = $this->CONF['url'] . "image.php?u=" . $author . "&#63;dateline=" . $this->DB->value();
			else // else no avatar
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
		$this->DB->query("SELECT * FROM " . $this->DB->prefix . "smilie ORDER BY CHAR_LENGTH(smilietext) DESC");

		// initialize smilie count
		$smilie_count = 0;

		// cycle through smilies, storing into expected format
		while($smilie_data = $this->DB->fetch_array())
		{
			$this->SMILIES[$smilie_count]['text'] = $smilie_data['smilietext'];
			$this->SMILIES[$smilie_count]['image'] = $smilie_data['smiliepath'];
			
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
