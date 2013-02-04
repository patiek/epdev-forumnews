<?php

/* ------------------------------------------------------------------ */
//	Forum Module: Invision Power Board 2.1.x
//		module version: 1.01
//		forum versions: 2.1.x
//		12/4/2005
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Invision_Power_Board_2_1_Access
{
	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $DB;


	function EP_Dev_Forum_News_Invision_Power_Board_2_1_Access(&$DB_access, &$forum_conf, &$error_handle)
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
		// i = case insensitive
		// Notice the most complex come first, that way we don't accidentally
		// replace (for ex) a [/url] that is part of a [url] [/url] statement
		$search_array = array(
						"/\[left\]/i",
						"/\[\/left\]/i",
						"/\[center\]/i",
						"/\[\/center\]/i",
						"/\[right\]/i",
						"/\[\/right\]/i",
						"/\[s\](.*?)\[\/s\]/is",
						"/\[topic=([^\]]*)\](.*?)\[\/topic\]/i",
						"/\[codebox\](.*?)\[\/codebox\]/is"
						);
		
		$replace_array = array(
						"<div align=\"left\">",
						"</div>",
						"<div align=\"center\">",
						"</div>",
						"<div align=\"right\">",
						"</div>",
						"<s>\$1</s>",
						"<a href=\"" . $this->LINKS['thread'] . "\$1\">\$2</a>",
						"<div class='codemain' style='height:200px;white-space:pre;overflow:auto'>\$1</div>"
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
			// +------------------------------
			//	IPB 2.1 uses a radical smilie setup.
			//
			/*	"<!--emo&" . $smilie . "--><img src=\"style_emoticons/<#EMO_DIR#>/" . $smilie_image . 
			//	"\" border='0' style='vertical-align:middle' alt='" . $smilie_image . "' /><!--endemo-->"
			*/
			//	Version 2.1.x changes quotes from single to double.
			// +------------------------------

			// put in url
			$text = str_replace("<img src=\"style_emoticons/<#EMO_DIR#>/" . $current_smilie['image'] . "\"", "<img src=\"" . $this->CONF['url'] . "style_emoticons/" . $current_smilie['set'] . "/" . $current_smilie['image'] . "\"", $text);
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
		$this->DB->query("SELECT avatar_location, avatar_type FROM " . $this->DB->prefix . "member_extra WHERE id='" . $author . "'");

		// assign result to $avatar
		list($avatar, $avatar_type) = $this->DB->fetch_array();

		
		// +------------------------------
		//	Detect Avatar and store accordingly
		// +------------------------------
		
		// if url to avatar
		if ($avatar_type == "url")
		{
			$return = $avatar;
		}

		// if avatar upload
		elseif ($avatar_type == "upload")
		{
			$return = $this->CONF['url'] . "uploads/" . $avatar;
		}

		// if avatar contains period, assume a file
		elseif ($avatar_type == "local")
		{
			$return = $this->CONF['url'] . "style_avatars/" . $avatar;
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
		$this->DB->query("SELECT typed, image, emo_set FROM " . $this->DB->prefix . "emoticons ORDER BY CHAR_LENGTH(typed) DESC");

		// initialize smilie count
		$smilie_count = 0;

		// cycle through smilies, storing into expected format
		while($smilie_data = $this->DB->fetch_array())
		{
			$this->SMILIES[$smilie_count]['text'] = $smilie_data['typed'];
			$this->SMILIES[$smilie_count]['image'] = $smilie_data['image'];
			$this->SMILIES[$smilie_count]['set'] = $smilie_data['emo_set'];
			
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
