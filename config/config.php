<?php
// --------------------------------------------
// | The EP-Dev Forum News script        
// |                                           
// | Copyright (c) 2002-2006 EP-Dev.com :           
// | This program is distributed as free       
// | software under the GNU General Public     
// | License as published by the Free Software 
// | Foundation. You may freely redistribute     
// | and/or modify this program.               
// |                                           
// --------------------------------------------

/* ------------------------------------------------------------------ */
//	Configuration class
//	Actually it is more of a structure. Contains configuration for 
//	the script.
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_Config
{
	var $ADMIN;
	var $FORUM;
	var $NEWS;
	var $SCRIPT;

	function EP_Dev_Forum_News_Config()
	{

		$this->ADMIN['enabled'] = true;
		$this->ADMIN['username'] = "admin";
		$this->ADMIN['password'] = "";
		$this->ADMIN['update_check'] = true;


		$this->FORUM[0]['enabled'] = false;
		$this->FORUM[0]['dbtype'] = "mysql";
		$this->FORUM[0]['source'] = "Forum";
		$this->FORUM[0]['username'] = "root";
		$this->FORUM[0]['password'] = "";
		$this->FORUM[0]['name'] = "ipb13";
		$this->FORUM[0]['host'] = "localhost";
		$this->FORUM[0]['prefix'] = "ibf_";
		$this->FORUM[0]['type'] = "Invision_Power_Board_1.3";
		$this->FORUM[0]['url'] = "http://localhost/forums/ipb13/";
		$this->FORUM[0]['default_ids'] = 1;


		$this->FORUM[1]['enabled'] = false;
		$this->FORUM[1]['dbtype'] = "mysql";
		$this->FORUM[1]['source'] = "Forum";
		$this->FORUM[1]['username'] = "root";
		$this->FORUM[1]['password'] = "";
		$this->FORUM[1]['name'] = "forum";
		$this->FORUM[1]['host'] = "localhost";
		$this->FORUM[1]['prefix'] = "";
		$this->FORUM[1]['type'] = "vBulletin_3";
		$this->FORUM[1]['url'] = "http://localhost/forums/vb3/";
		$this->FORUM[1]['default_ids'] = 2;


		$this->FORUM[2]['enabled'] = false;
		$this->FORUM[2]['dbtype'] = "mysql";
		$this->FORUM[2]['source'] = "Forum";
		$this->FORUM[2]['username'] = "root";
		$this->FORUM[2]['password'] = "";
		$this->FORUM[2]['name'] = "smf";
		$this->FORUM[2]['host'] = "localhost";
		$this->FORUM[2]['prefix'] = "smf_";
		$this->FORUM[2]['type'] = "Simple_Machines_Forum_1";
		$this->FORUM[2]['url'] = "http://localhost/forums/smf/";
		$this->FORUM[2]['default_ids'] = "1";


		$this->FORUM[3]['enabled'] = false;
		$this->FORUM[3]['dbtype'] = "mysql";
		$this->FORUM[3]['source'] = "Forum";
		$this->FORUM[3]['username'] = "root";
		$this->FORUM[3]['password'] = "";
		$this->FORUM[3]['name'] = "ipb2";
		$this->FORUM[3]['host'] = "localhost";
		$this->FORUM[3]['prefix'] = "ibf_";
		$this->FORUM[3]['type'] = "Invision_Power_Board_2.0";
		$this->FORUM[3]['url'] = "http://localhost/forums/ipb2/";
		$this->FORUM[3]['default_ids'] = 2;


		$this->FORUM[4]['enabled'] = false;
		$this->FORUM[4]['dbtype'] = "mysql";
		$this->FORUM[4]['source'] = "Forum";
		$this->FORUM[4]['username'] = "root";
		$this->FORUM[4]['password'] = "";
		$this->FORUM[4]['name'] = "phpBB2";
		$this->FORUM[4]['host'] = "localhost";
		$this->FORUM[4]['prefix'] = "phpbb_";
		$this->FORUM[4]['type'] = "phpBB_2";
		$this->FORUM[4]['url'] = "http://localhost/forums/phpBB2/";
		$this->FORUM[4]['default_ids'] = 1;


		$this->FORUM[5]['enabled'] = false;
		$this->FORUM[5]['dbtype'] = "mysql";
		$this->FORUM[5]['source'] = "Forum";
		$this->FORUM[5]['username'] = "root";
		$this->FORUM[5]['password'] = "";
		$this->FORUM[5]['name'] = "yabbse";
		$this->FORUM[5]['host'] = "localhost";
		$this->FORUM[5]['prefix'] = "yabbse_";
		$this->FORUM[5]['type'] = "YaBB_SE_1.5";
		$this->FORUM[5]['url'] = "http://localhost/forums/yabbse/";
		$this->FORUM[5]['default_ids'] = 1;


		$this->FORUM[6]['enabled'] = false;
		$this->FORUM[6]['dbtype'] = "mysql";
		$this->FORUM[6]['source'] = "Forum";
		$this->FORUM[6]['username'] = "root";
		$this->FORUM[6]['password'] = "";
		$this->FORUM[6]['name'] = "wbblite";
		$this->FORUM[6]['host'] = "localhost";
		$this->FORUM[6]['prefix'] = "bb1_";
		$this->FORUM[6]['type'] = "WoltLab_Burning_Board_Lite_1";
		$this->FORUM[6]['url'] = "http://localhost/forums/wbblite/";
		$this->FORUM[6]['default_ids'] = 1;


		$this->FORUM[7]['enabled'] = false;
		$this->FORUM[7]['dbtype'] = "xml";
		$this->FORUM[7]['source'] = "RSS";
		$this->FORUM[7]['username'] = "root";
		$this->FORUM[7]['password'] = "";
		$this->FORUM[7]['name'] = "mybb";
		$this->FORUM[7]['host'] = "http://www.ep-dev.com/rss.php";
		$this->FORUM[7]['prefix'] = "mybb_";
		$this->FORUM[7]['type'] = "RSS";
		$this->FORUM[7]['url'] = "http://localhost/forums/mybb/";
		$this->FORUM[7]['default_ids'] = 1;

		
		$this->NEWS['post_num'] = 10;
		$this->NEWS['headline_num'] = 10;
		$this->NEWS['character_limit'] = 0;
		$this->NEWS['page_break_keyword'] = "[BREAK ARTICLE]";
		$this->NEWS['format_urls'] = true;
		$this->NEWS['bbcode'] = true;
		$this->NEWS['author_image'] = true;
		$this->NEWS['fix_linebreaks'] = true;
		$this->NEWS['recycle_time'] = 5;
		$this->NEWS['failsafe_recycle'] = false;

		$this->SCRIPT['absolute_path'] = "";

		/* ------------------------------
			CHANGING THE VALUES BELOW WILL 
			CAUSE THE ADMIN PANEL TO STOP WORKING
		   ------------------------------ */

		$this->SCRIPT['files']['news'] = $this->SCRIPT['absolute_path'] . "news.php";
		
		$this->SCRIPT['folders']['classes'] = $this->SCRIPT['absolute_path'] . "classes/";
		$this->SCRIPT['folders']['config'] = $this->SCRIPT['absolute_path'] . "config/";

		$this->SCRIPT['folders']['cache'] = $this->SCRIPT['absolute_path'] . "cache/";

		$this->SCRIPT['folders']['access'] = $this->SCRIPT['folders']['classes'] . "access/";
		$this->SCRIPT['files']['xml'] = $this->SCRIPT['folders']['access'] . "xml.php";
		$this->SCRIPT['files']['mysql'] = $this->SCRIPT['folders']['access'] . "mysql.php";
		$this->SCRIPT['files']['display'] = $this->SCRIPT['folders']['classes'] . "display.php";

		$this->SCRIPT['files']['template'] = $this->SCRIPT['folders']['config'] . "template.php";

		$this->SCRIPT['folders']['forums'] = $this->SCRIPT['folders']['classes'] . "sources/";

		$this->SCRIPT['version'] = "2.24";
	}
}
