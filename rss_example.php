<?php
// ------------------------------------------
// EDIT VALUES BELOW:
// ------------------------------------------

$title = "My Site's News!";
$link = "http://www.ep-dev.com";
$description = "Latest script releases.";


header("Content-Type: text/xml");
$xml_head = "<?xml version=\"1.0\"?>
<rss version=\"2.0\">
	<channel>
		<title>{$title}</title>
		<link>{$link}</link>
		<description>{$description}</description>
		<generator>EP-Dev.com Forum News</generator>";
$xml_foot = "	</channel>
</rss>";
// output first part
echo $xml_head;

// ------------------------------------------
// Code from admin panel
// ------------------------------------------
include_once("news.php");
$forum_obj = new EP_Dev_Forum_News();
$forum_obj->display_News("", "", "", "2");



// end rss
echo $xml_foot;