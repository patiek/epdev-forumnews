<?php

/* ------------------------------------------------------------------ */
//	Source Module: Really Simple Syndication (RSS)
//		module version: 1.03
//		RSS versions: 0.91, 0.92, 2.0
//		4/26/2006
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_RSS_Access
{
	var $SMILIES;
	var $POSTS;
	
	// array to store cached author urls
	var $AUTHOR_IMAGES;
	
	var $CONF;

	var $ERROR;
	var $LINKS;
	var $PARSER;


	function EP_Dev_Forum_News_RSS_Access(&$PARSER, &$forum_conf, &$error_handle)
	{
		/* 
			initialize forum configuration
			WARNING: this should be used only to pull url data,
			where only ->url is valid. Any other config data may not
			exist in future versions. Ideally we would make most of
			the data private, but PHP5 isn't widely supported yet.
		*/
		$this->CONF =& $forum_conf;

		// initialize error handle
		$this->ERROR =& $error_handle;

		// initialize RSS parser
		$this->PARSER =& $PARSER;

		// initialize forum-specific links
		$this->LINKS['author'] = "";
		$this->LINKS['thread'] = "";
	}


	/* ------------------------------------------------------------------ */
	//	Initialize Object
	//  This function is called prior to fetching any news or headlines.	
	//	If something goes wrong, return false on failure. Else return true.
	/* ------------------------------------------------------------------ */
	
	function initialize()
	{
		// nothing to do for RSS
		return true;
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
	//	RETURNS TRUE/FALSE on success/failure
	/* ------------------------------------------------------------------ */
	
	function fetch_Posts($number_to_fetch, $ids_to_fetch, $headlines_only = false)
	{
		// +------------------------------
		//	Fetch RSS Data
		// +------------------------------

		// load data from rss
		$this->PARSER->load();

		// process data
		$this->PARSER->parse();

		$ROOT =& $this->PARSER->getRoot();

		// detect version
		if ($ROOT->getName() == "RSS")
		{
			$feedburner_test = $ROOT->getAttribute("XMLNS:FEEDBURNER");

			if (!empty($feedburner_test))
			{
				$version = "RSS_feedburner";
			}
			else
			{
				switch($ROOT->getAttribute("version"))
				{
					default: $version = "RSS";
				}
			}
		}
		else
		{
			$version = "RDF";
		}


		for($i=0; $i<$ROOT->getNumberChildren() && count($ITEMS) <= $number_to_fetch; $i++)
		{
			$child =& $ROOT->getChild($i+1);
			if ($child->getName() != "ITEM")
			{
				for($j=0; $j<$child->getNumberChildren(); $j++)
				{
					$subChild =& $child->getChild($j+1);
					if ($subChild->getName() == "ITEM")
					{
						$ITEMS[] =& $child->getChild($j+1);
					}
				}
			}
			else
			{
				$ITEMS[] =& $ROOT->getChild($i+1);
			}
		}

		// cycle through results of $post
		for($i=0; $i<=count($ITEMS) && $i < $number_to_fetch; $i++)
		{
			switch($version)
			{
				case "RDF:RDF" :
				case "RDF" :
					$this->parsePost_RDF($ITEMS[$i]);
				break;

				case "RSS_feedburner" :
					$this->parsePost_RSS_feedburner($ITEMS[$i]);
				break;

				default : $this->parsePost_RSS($ITEMS[$i]);
			}
		}

		return true;

	}



	function getNewsData(&$child)
	{
		if (is_object($child))
		{
			$numData = $child->getNumberData();
			$numChildren = $child->getNumberChildren();

			for($i=0; $i<$numData || $i<$numChildren; $i++)
			{
				if ($i < $numData)
				{
					$data = $child->getData($i+1);
					if (!empty($data))
						$newsPost .= $data;
				}

				if ($i < $numChildren)
				{

					$data = $this->getNewsData($child->getChild($i+1));
					$subChild =& $child->getChild($i+1);

					if ($data == "")
					{
						$newsPost .= "<" . $subChild->getName() . $subChild->getAttributesString() . " />";
					}
					else
					{
						$newsPost .= "<" . $subChild->getName() . $subChild->getAttributesString() . ">"
									. $data
									. "</" . $subChild->getName() . ">";
					}
				}
			}
		}
		else
		{
			$newsPost = "";
		}

		return $newsPost;
	}


	function parsePost_RSS($post)
	{
		// attempt to fetch post id
		$post_id = $this->getNewsData($post->getChildByName("GUID"));

		// set post id to current count of array, with negative value.
		// this maintains correct ordering
		if (empty($post_id))
			$post_id = -count($this->POSTS);

		
		// attempt to fetch publishing date
		$date = $this->getNewsData($post->getChildByName("PUBDATE"));

		// try other date format
		if (empty($date))
			$date = $this->getNewsData($post->getChildByName("DC:DATE"));

		// +------------------------------
		//	Store Post Data
		// +------------------------------
		
		// Store into post data
		$this->POSTS[] = array(
								"text" => $this->getNewsData($post->getChildByName("DESCRIPTION")),
								"title" => $this->getNewsData($post->getChildByName("TITLE")),
								"author_name" => $this->getNewsData($post->getChildByName("AUTHOR")),
								"author_id" => $this->getNewsData($post->getChildByName("AUTHOR")),
								"date" => (!empty($date) ? strtotime($date) : time() + ($post_id*60)),
								"reply_num" => "",
								"view_num" => "",
								"post_id" => $post_id,
								"cat_id" => $this->getNewsData($post->getChildByName("CATEGORY")),
								"author_url" => "",
								"post_url" => $this->getNewsData($post->getChildByName("COMMENTS"))
							);
	}


	function parsePost_RDF($post)
	{

		// +------------------------------
		//	Store Post Data
		// +------------------------------
		
		// Store into post data
		$this->POSTS[] = array(
								"text" => $this->getNewsData($post->getChildByName("DESCRIPTION")),
								"title" => $this->getNewsData($post->getChildByName("TITLE")),
								"author_name" => $this->getNewsData($post->getChildByName("DC:CREATOR")),
								"author_id" => $this->getNewsData($post->getChildByName("DC:CREATOR")),
								"date" => strtotime($this->getNewsData($post->getChildByName("DC:DATE"))),
								"reply_num" => "",
								"view_num" => "",
								"post_id" => $this->getNewsData($post->getAttribute("RDF:ABOUT")),
								"cat_id" => $this->getNewsData($post->getChildByName("DC:SUBJECT")),
								"author_url" => "",
								"post_url" => $this->getNewsData($post->getChildByName("LINK"))
							);
	}


	function parsePost_RSS_feedburner($post)
	{
		// attempt to fetch post id
		$post_id = $this->getNewsData($post->getChildByName("GUID"));

		// set post id to current count of array, with negative value.
		// this maintains correct ordering
		if (empty($post_id))
			$post_id = -count($this->POSTS);

		
		// attempt to fetch publishing date
		$date = $this->getNewsData($post->getChildByName("PUBDATE"));

		// try other date format
		if (empty($date))
			$date = $this->getNewsData($post->getChildByName("DC:DATE"));


		// +------------------------------
		//	Store Post Data
		// +------------------------------
		
		// Store into post data
		$this->POSTS[] = array(
								"text" => $this->getNewsData($post->getChildByName("DESCRIPTION")),
								"title" => $this->getNewsData($post->getChildByName("TITLE")),
								"author_name" => $this->getNewsData($post->getChildByName("AUTHOR")),
								"author_id" => $this->getNewsData($post->getChildByName("AUTHOR")),
								"date" => (!empty($date) ? strtotime($date) : time() + ($post_id*60)),
								"reply_num" => "",
								"view_num" => "",
								"post_id" => $post_id,
								"cat_id" => $this->getNewsData($post->getChildByName("CATEGORY")),
								"author_url" => "",
								"post_url" => $this->getNewsData($post->getChildByName("FEEDBURNER:ORIGLINK"))
							);
	}


	/* ------------------------------------------------------------------ */
	//	Parse BB Code
	//  Parses $text for BB code that is specific to this forum.
	/* ------------------------------------------------------------------ */
	
	function parse_BBcode(&$text)
	{
		// Nothing --- This is RSS.
	}


	/* ------------------------------------------------------------------ */
	//	Parse Smilies
	//  Parses $text for forum's smilies
	/* ------------------------------------------------------------------ */
	
	function parse_Smilies(&$text)
	{
		// Nothing --- This is RSS.
	}


	/* ------------------------------------------------------------------ */
	//	Fetch Author Image
	//  Grabs avatar information from DB and stores into $AUTHOR_IMAGES
	/* ------------------------------------------------------------------ */
	
	function fetch_Author_Image($author)
	{
		// Nothing --- This is RSS.

		// store into author images array
		$this->AUTHOR_IMAGES[$author] = null;
	}


	/* ------------------------------------------------------------------ */
	//	Fetch Smilies
	//  Grabs smilies from database and stores into $SMILIES
	/* ------------------------------------------------------------------ */
	
	function fetch_Smilies()
	{
		// Nothing --- This is RSS.
	}


	/* ------------------------------------------------------------------ */
	//	get Author Image
	//  Returns full url to author image
	/* ------------------------------------------------------------------ */
	
	function get_Author_Image($author)
	{
		// Nothing --- This is RSS.
		return null;
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