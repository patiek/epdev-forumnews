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
//	Template class
//	Actually it is more of a structure. Contains template data for 
//	the script.
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Template
{
	var $TEMPLATES;
	function EP_Dev_Forum_News_Template()
	{

$this->TEMPLATES[0]['post'] = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
  <tr>
    <td><strong><font size=\"4\"><a name=\"!!THREADID!!\">!!TITLE!!</a></font></strong></td>
  </tr>
  <tr>
    <td>!!AUTHOR_IMAGE!! !!CONTENT!!</td>
  </tr>
  <tr>
    <td><a href=\"!!NEWS_URL!!\">Comments (!!NUM_COMMENTS!!)</a> - Posted on [DAY], [MONTH] [DAY-NUM] @ [HOUR-12]:[MIN] [AM-PM] by <a href=\"!!AUTHOR_URL!!\">!!AUTHOR_NAME!!</a></td>
  </tr>
</table>
<br>";

$this->TEMPLATES[0]['headlines'] = "· <a href=\"!!NEWS_URL!!\">!!TITLE!!</a><br>";

$this->TEMPLATES[0]['author_image'] = "<img src=\"!!AVATAR_URL!!\" align=\"right\">";

$this->TEMPLATES[0]['quotes'] = "<font color=\"blue\"><i>!!POST_TEXT!!</i></font>";

$this->TEMPLATES[0]['code'] = "<font color=\"blue\">!!POST_TEXT!!</font>";

$this->TEMPLATES[0]['read_more'] = "<a href=\"!!NEWS_URL!!\">[read full story]</a>";

$this->TEMPLATES[0]['read_more_cut_off'] = "...<br><a href=\"!!NEWS_URL!!\">[read full story]</a>";

$this->TEMPLATES[0]['highlight'] = "<font style=\"color: #FF0000; font-weight: bold;\">!!POST_TEXT!!</font>";

$this->TEMPLATES[0]['php'] = "<hr>!!POST_TEXT!!<hr>";


$this->TEMPLATES[1]['post'] = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
  <tr>
    <td><strong><font size=\"4\"><a name=\"!!THREADID!!\">!!TITLE!!</a></font></strong></td>
  </tr>
  <tr>
    <td>!!AUTHOR_IMAGE!! !!CONTENT!!</td>
  </tr>
  <tr>
    <td><a href=\"!!NEWS_URL!!\">Comments (!!NUM_COMMENTS!!)</a> - Posted on [DAY], [MONTH] [DAY-NUM] @ [HOUR-12]:[MIN] [AM-PM] by <a href=\"!!AUTHOR_URL!!\">!!AUTHOR_NAME!!</a></td>
  </tr>
</table>
<br>";

$this->TEMPLATES[1]['headlines'] = "· <a href=\"!!NEWS_URL!!\">!!TITLE!!</a><br>";

$this->TEMPLATES[1]['author_image'] = "<img src=\"!!AVATAR_URL!!\" align=\"right\">";

$this->TEMPLATES[1]['quotes'] = "<font color=\"blue\"><i>!!POST_TEXT!!</i></font>";

$this->TEMPLATES[1]['code'] = "<font color=\"blue\">!!POST_TEXT!!</font>";

$this->TEMPLATES[1]['read_more'] = "<a href=\"!!NEWS_URL!!\">[read full story]</a>";

$this->TEMPLATES[1]['read_more_cut_off'] = "...<br><a href=\"!!NEWS_URL!!\">[read full story]</a>";

$this->TEMPLATES[1]['highlight'] = "<font style=\"color: #FF0000; font-weight: bold;\">!!POST_TEXT!!</font>";

$this->TEMPLATES[1]['php'] = "<hr>!!POST_TEXT!!<hr>";


$this->TEMPLATES[2]['post'] = "<!-- XML TEMPLATE FOR RSS BACKEND -->
<item>
   <title>!!TITLE!!</title>
   <link>!!NEWS_URL!!</link>
   <description>!!CONTENT!!</description>
   <pubDate>[DAY-ABR], [DAY-NUM] [MONTH-ABR] [YEAR-4] [HOUR-24]:[MIN]:[SEC] GMT</pubDate>
</item>
";

$this->TEMPLATES[2]['headlines'] = "· <a href=\"!!NEWS_URL!!\">!!TITLE!!</a><br>";

$this->TEMPLATES[2]['author_image'] = "<img src=\"!!AVATAR_URL!!\" align=\"right\">";

$this->TEMPLATES[2]['quotes'] = "<font color=\"blue\"><i>!!POST_TEXT!!</i></font>";

$this->TEMPLATES[2]['code'] = "<font color=\"blue\">!!POST_TEXT!!</font>";

$this->TEMPLATES[2]['read_more'] = "<a href=\"!!NEWS_URL!!\">[read full story]</a>";

$this->TEMPLATES[2]['read_more_cut_off'] = "...<br><a href=\"!!NEWS_URL!!\">[read full story]</a>";

$this->TEMPLATES[2]['highlight'] = "<font style=\"color: #FF0000; font-weight: bold;\">!!POST_TEXT!!</font>";

$this->TEMPLATES[2]['php'] = "<hr>!!POST_TEXT!!<hr>";

	}

}