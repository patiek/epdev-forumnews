<?php
// --------------------------------------------
// | EP-Dev Forum News        
// |                                           
// | Copyright (c) 2003-2006 Patrick Brown as EP-Dev.com           
// | This program is free software; you can redistribute it and/or modify
// | it under the terms of the GNU General Public License as published by
// | the Free Software Foundation; either version 2 of the License, or
// | (at your option) any later version.              
// | 
// | This program is distributed in the hope that it will be useful,
// | but WITHOUT ANY WARRANTY; without even the implied warranty of
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// | GNU General Public License for more details.
// --------------------------------------------

// set error reporting level
error_reporting(E_ALL ^ E_NOTICE);

// +------------------------------
//	initialize administration panel and navigate
// +------------------------------
$adminPanel = new EP_Dev_Forum_News_Admin();
$adminPanel->navigate($_REQUEST['page']);


/* ------------------------------------------------------------------ */
//	Administration Panel Class
//  Contains / Operates all of the functions of the admin panel through
//	its own functions or other included classes.
/* ------------------------------------------------------------------ */

class EP_Dev_Forum_News_Admin
{
	// configs
	var $CONFIG;
	var $TEMPLATE;

	// error handle
	var $ERROR;

	// panel display
	var $DISPLAY;

	// variable format
	var $FORMAT;

	// user login / logout functions
	var $USER;

	// internal version
	var $internal_version = "2.24";

	function EP_Dev_Forum_News_Admin()
	{
		// We will be using sessions
		session_start();

		// +------------------------------
		//	Remove effects of magic_quotes
		// +------------------------------
		if (get_magic_quotes_gpc()) {
			$_POST = array_map(array(&$this, 'stripslashes_deep'), $_POST);
			$_GET = array_map(array(&$this, 'stripslashes_deep'), $_GET);
			$_COOKIE = array_map(array(&$this, 'stripslashes_deep'), $_COOKIE);
		}


		// Load config file
		require_once("../config/config.php");

		// initialize configuration
		$this->CONFIG = new EP_Dev_Forum_News_Config();


		// +------------------------------
		//	Load up common required files
		// +------------------------------
		require_once("../config/template.php");
		require_once("display.php");
		require_once("fileio.php");
		require_once("file_format.php");


		// +------------------------------
		//	Initialize variables
		// +------------------------------
		$template_temp = new EP_Dev_Forum_News_Template();
		$this->TEMPLATE = $template_temp;

		$this->ERROR = new EP_Dev_Forum_News_Admin_Error_Handle();
		$this->DISPLAY = new EP_Dev_Forum_News_Admin_Display("EP-Dev Forum News Administration Panel");
		$this->FORMAT = new EP_Dev_Forum_News_Admin_Variable_Format($this->ERROR);
		$this->USER = new EP_Dev_Forum_News_Admin_UserControl($this->CONFIG->ADMIN['username'], $this->CONFIG->ADMIN['password']);


		// +------------------------------
		//	Check if this panel is enabled
		// +------------------------------
		if (!$this->CONFIG->ADMIN['enabled'])
		{
			// error if we are disabled
			$this->ERROR->stop("panel_disabled");
		}


		// +------------------------------
		//	Ensure that files are writable
		// +------------------------------ 
		if (!is_writable("../config/config.php")
			|| !is_writable("../config/template.php")
			|| !is_writable("../news.php")
			|| !is_writable("../cache/"))
		{
			$this->DISPLAY->MENU->blank();
			$message = $this->DISPLAY->constructOutput("It has been detected that not all of the files that need to be writable are writable.<br><br>
			Please ensure the following files are chmod 0666 (read & write all):<br>
			forum-news/news.php<br>
			forum-news/config/config.php<br>
			forum-news/config/template.php<br><br>
			In addition, the cache folder needs to be chmod 0777 (read/write/execute all):<br>
			forum-news/cache/<br><br>
			NOTE: You can usually chmod files by right clicking on the file in your ftp program and selecting \"Change file permissions\" or \"CHMOD\".<br><br>
			Once you have changed these files to be writable, please refresh this page.
			");

			$this->page_Message("ERROR: NOT ALL REQUIRED FILES WRITABLE", $message);
			die();
		}


		// +------------------------------
		//	Check if in process of upgrading and load upgrader if so.
		// +------------------------------
		if ($this->CONFIG->SCRIPT['version'] != $this->internal_version)
		{
			require_once("upgrade/upgradeCore.php");
			$UPGRADER = new UpgradeCore($this);
			$UPGRADER->navigate($this->CONFIG->SCRIPT['version'], $this->internal_version);
			die();
		}
	}


	/* ------------------------------------------------------------------ */
	//	Strip slashes from $value
	//	Equivilent to stripslashes(), but it operates recursively
	/* ------------------------------------------------------------------ */
	
	function stripslashes_deep($value)
	{
       $value = is_array($value) ?
                   array_map(array(&$this, 'stripslashes_deep'), $value) :
                   stripslashes($value);

       return $value;
	}


	/* ------------------------------------------------------------------ */
	//	Navigate to $page
	//  Calls, based on $page, the correct page method.
	/* ------------------------------------------------------------------ */
	
	function navigate($page = null)
	{
		// +------------------------------
		//	Call method based on $page
		// +------------------------------
		switch($page)
		{
			// +------------------------------
			//	Non-restricted (Public) Pages
			// +------------------------------

			case "FAQ" :
				if (!$this->USER->check())
					$this->DISPLAY->MENU->blank();
				$this->page_FAQ();
			break;

			case "goLogin" :
				$this->USER->login($_POST['username'], $_POST['password']);
				$this->navigate();
			break;

			default: 
				
				// +------------------------------
				//	User Authentication
				// +------------------------------
				if(!$this->USER->check()) // if not valid user
				{
					// show login page
					$this->DISPLAY->MENU->blank();

					if ($this->USER->defaultConfig())
						$this->page_Login($this->CONFIG->ADMIN['username'], $this->CONFIG->ADMIN['password']);
					else
						$this->page_Login();
				}


				// +------------------------------
				//	Restricted (Requires authentication) Pages
				// +------------------------------

				else
				{
					// +------------------------------
					//	Auto Check for update (if enabled)
					// +------------------------------
					if ($this->CONFIG->ADMIN['update_check'] && !$this->USER->getValue("checked_for_update"))
					{
						$update_info = $this->CheckUpdate();
						if ($update_info['version_available'])
						{
							$this->USER->setValue("checked_for_update", true);

							$this->page_CheckForUpdate();

							break;
						}
					}


					// +------------------------------
					//	Force Username & Password Change if still default
					// +------------------------------
					if ($this->USER->defaultConfig() && $page != "goModifyConfig")
						$page = "AdminSettings";


					/* A fancy (or sloppy, depending on how you look at it)
					embedded switch statement */

					switch($page)
					{
						case "goLogout" :
							$this->USER->logout();
							$this->navigate();
						break;

						case "goModifyConfig" :
							$this->ModifyConfig($_POST); // only POST vars at the moment.
							$this->page_Message("Settings Updated", "Your settings have been updated.");
						break;

						case "AdminSettings" :
							$this->page_AdminSettings();
						break;

						case "ForumSettings" :
							$this->page_ForumSettings();
						break;

						case "NewsSettings" :
							$this->page_NewsSettings();
						break;

						case "TemplateSettings" :
							$this->page_EditTemplates();
						break;

						case "GenerateCode" :
							$this->page_GenerateCode();
						break;

						case "CheckForUpdate" :
							$this->page_CheckForUpdate();
						break;

						default :
							$this->page_Main();
					}
				}
		}
	}


	/* ------------------------------------------------------------------ */
	//	Login Page
	//	If specified, will fill in username and password input with given
	//	parameters.
	/* ------------------------------------------------------------------ */
	
	function page_Login($default_username="", $default_password="")
	{
		// start form
		$content .= $this->DISPLAY->constructStartForm("goLogin", "ADMIN_login_form");

		if (!empty($default_username))
			$content .= $this->DISPLAY->constructOutput("Click Login to begin!");

		$content .= "<br>";

		$content .= "<table>\n";
		
		$content .= "<tr>\n<td align='center'>" . $this->DISPLAY->constructOutput("Username: <input type='text' name='username' value='{$default_username}'>") . "</td>\n</tr>\n";
		$content .= "<tr>\n<td align='center'>" . $this->DISPLAY->constructOutput("Password: <input type='password' name='password' value='{$default_password}'>") . "</td>\n</tr>\n";

		$content .= "</table>\n";

		$content .= $this->DISPLAY->constructOutput("<a href='index.php?page=FAQ&amp;topic=1'>Forgot your password?</a>");

		$content .= "<br>";

		// end form
		$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("Login") . "</div>";

		if (!empty($default_username))
			$content .= "<br>" . $this->DISPLAY->constructOutput("Default username is \"admin\" and default password is blank (empty).", 20);

		$this->DISPLAY->displayPage($content, "EP-Dev Forum News Login");
	}


	/* ------------------------------------------------------------------ */
	//	Script Update Page
	//	Checks to see if script is up-to-date.
	/* ------------------------------------------------------------------ */
	
	function page_CheckForUpdate()
	{
		$update_info = $this->CheckUpdate();
		if ($update_info['version_available'])
		{
			$message = $this->DISPLAY->constructOutput("A new update, version {$update_info['version_current']}, is available for download from <a href='{$update_info['download_url']}' target='_blank'>EP-Dev.com</a>. Details can be found below:<br>");
			
			if ($update_info['recommend'])
			{
				$message .= $this->DISPLAY->constructOutput("<font color='red'>Warning: The version of the script you are running, {$update_info['version_user']}, is at least two versions old. Please update as soon as possible.</font>");
			}

			if (!empty($update_info['download_url']))
			{
				$message .= "<br>";

				$message .= $this->DISPLAY->constructOutput("You can download the new version from <a href='{$update_info['download_url']}' target='_blank'>EP-Dev.com</a>.");
			}

			if (count($update_info['version_recent']) != 0)
			{
				$message .= "<br><strong>Bug Fixes and Features in new version:</strong>";
			}

			for($i=0; $i<count($update_info['version_recent']); $i++)
			{
				$message .= $this->DISPLAY->constructOutput("<i>Version {$update_info['version_recent'][$i]['number']}</i><br>{$update_info['version_recent'][$i]['description']}") . "<br>";
			}
		}
		else
		{
			$message = $this->DISPLAY->constructOutput("No new updates are available. Your script is up-to-date.");
		}

		$message .= "<br>\n" . $this->DISPLAY->constructOutput("Your current script version is " . $this->CONFIG->SCRIPT['version']);

		$this->DISPLAY->displayPage($message, ($update_info['version_available'] ? "<font color='red'>Update Available!</font>" : "No Update Available"));
	}


	/* ------------------------------------------------------------------ */
	//	Check Update
	//	Returns script info pulled from EP-Dev.com server.
	//	returns array:	string ['ver'] = version number from server.
	//					boolean ['new'] = script status (true = up-to-date)
	/* ------------------------------------------------------------------ */
	
	function CheckUpdate()
	{
		// +------------------------------
		//	Connect to EP-Dev and check for Update.
		// +------------------------------

		$referrer = "http://" . $_SERVER['HTTP_HOST'] . str_replace("forum-news/admin/index.php" , "", $_SERVER['PHP_SELF']);
		
		$cur_version = $this->CONFIG->SCRIPT['version'];
		$file = @file_get_contents("http://www.ep-dev.com/update/check.php?name=ep-dev-forum-news&version={$cur_version}&referrer={$referrer}");
		if ($file === false)
		{
			$version_info['success'] = false;
			$version_info['version_available'] = false;
		}
		else
		{
			$version_info = unserialize($file);
			$version_info['success'] = true;
			$version_info['version_available'] = ($version_info['version_current'] != $cur_version);
		}

		return $version_info;
	}


	/* ------------------------------------------------------------------ */
	//	FAQ Page
	//	If set, $_GET['topic'] will force single topic display.
	//	Else, all will be displayed in FAQ manner.
	/* ------------------------------------------------------------------ */
	
	function page_FAQ()
	{
		// get proper FAQ code, 0 = display all (default)
		$faq_code = (isset($_GET['topic']) ? $_GET['topic'] : 0);

		// +------------------------------
		//	Format questions / answers
		// +------------------------------

		$questions[] = "I forgot my username and password to the admin section. How can I change them manually?";
		$answers[] = "Open up config/config.php and edit the <font color='#0000FF'>\$this->ADMIN['username']</font> and <font color='#0000FF'>\$this->ADMIN['password']</font> values.";

		$questions[] = "How do I make my headline links go to the news on my page instead of to my forum posts?";
		$answers[] = "Go to <font color='#0000FF'>Edit Templates</font>. Edit the <font color='#FF0000'>Headlines</font> template.
						Change the !!NEWS_URL!! to !!THREADID!!.<br>\nNow edit the <font color='#FF0000'>Post</font> template.
						Modify !!TITLE!! to reflect an anchor link, such as <a name=\"!!THREADID!!\">!!TITLE!!</a>.";


		$questions[] = "How do I display a specific image for each category id?";
		$answers[] = "This is possible by using the <font color='#0000FF'>Edit Templates</font> link. You must rename your images to numbers that reflect their corresponding category ids. For example, if you wanted an image for a category id of 3, then you would have to rename your category image to 3.gif or 3.jpg and place the following somewhere in your <font color='#FF0000'>Post</font> template (NOTE: this is just an example, you will have to ensure you put in the real url to your category images):<br>
			<textarea style='width: 500px; height: 75px;'><img src=\"http://www.mysite.com/cat_images/!!CATEGORYID!!.gif\"></textarea>";

		
		$questions[] = "My pages are not php pages, but I still want to use this script! How can I use this script without renaming my pages to \".php\"?";
		$answers[] = "If you are using apache web server (and it is likely that you are), then you can put the following into a file named \".htaccess\". This will parse all your .html and .htm pages as php pages:
			<textarea style='width: 500px; height: 75px;'>AddHandler application/x-httpd-php .php .html .htm</textarea>";

		$questions[] = "Where are all of my forums at on the Generate Code page?";
		$answers[] = "If you do not see all of your forums on the <font color='#0000FF'>Generate Code</font> page then you may have some of them disabled. Go to the <font color='#0000FF'>News Sources</font> page and ensure that they are enabled. Disabled forums will not show up on the <font color='#0000FF'>Generate Code</font> page.";


		$questions[] = "What does the Cache feature do?";
		$answers[] = "The cache feature saves time and database queries by saving the news output into files (found in the cache folder). Everytime the script executes it will look for these files and display them if they are not too old, as designated by the Cache Time setting on the <font color='#0000FF'>Script Settings</font> page.<br><br>In short, the script will only pull updated news every so many minutes instead of pulling and processing it every time a visitor visits your page (real-time). The caching is invisible to your visitors except in the fact that it loads your page faster.";


		$questions[] = "Does EP-Dev Forum News include RSS support?";
		$answers[] = "As of version 2.10, the script includes an RSS example. The rss_example.php gives a simple RSS output and can be used as a template by developers. The rss_example.php file utilizes the pre-installed template (template 3) that comes by default with the EP-Dev Forum News script.";


		$questions[] = "How can I configure the script to work with PHP-Nuke's forums?";
		$answers[] = "PHP-Nuke uses a slightly modified version of the phpBB forum software. In order to configure PHP-Nuke, go to the <font color='#0000FF'>News Sources</font> page and configure the script to use phpBB and PHP-Nuke's unusual forum URL (you will have to click \"YES\" when the script prompts you to confirm the forum URL).<br><br>Now manually open classes/sources/phpBB_2.php in an editor (or notepad) and find the <font color='#0000FF'>var \$customPrefix = \"\";</font> near the top of the file. Modify this variable to reflect your PHP-Nuke's users table. Thus, to get the script to work with the default users table that PHP-Nuke installs you would modify it to look like:<br><font color='#0000FF'>var \$customPrefix = \"nuke_users\";</font>";

		
		// +------------------------------
		//	Display all or single answers / questions
		// +------------------------------
		// If no FAQ code, display all
		if ($faq_code == 0)
		{
			$content .= "<ol>\n";
			
			for($i=0; $i<count($questions); $i++)
				$content .= "<li>" . $this->DISPLAY->constructOutput("<a href='#FAQ{$i}'>{$questions[$i]}</a>") ."</li>\n";

			$content .= "</ol>\n<hr>\n";

			for($i=0; $i<count($questions); $i++)
			{
				$content .= $this->DISPLAY->constructOutput("<strong><a name='FAQ{$i}'>" . ($i+1) . "</a>. {$questions[$i]}</strong>");
				$content .= $this->DISPLAY->constructOutput($answers[$i], 15);
				$content .= "<br>\n<br>\n";
			}
		}

		// Display single if FAQ code specified
		else
		{
			$content .= $this->DISPLAY->constructOutput("<strong><a name='FAQ{$i}'>" . ($faq_code) . "</a>. {$questions[$faq_code-1]}</strong>");
			$content .= $this->DISPLAY->constructOutput($answers[$faq_code-1], 15);
			$content .= "<br>\n<br>\n";
		}

		// display page
		$this->DISPLAY->displayPage($content, "Troubleshooting");
	}


	/* ------------------------------------------------------------------ */
	//	Message Page
	//	Displays generic page with $title as title and $message as content
	/* ------------------------------------------------------------------ */
	
	function page_Message($title, $message)
	{
		$content = $this->DISPLAY->constructOutput($message);

		// display page
		$this->DISPLAY->displayPage($content, $title);
	}


	/* ------------------------------------------------------------------ */
	//	Welcome Page
	/* ------------------------------------------------------------------ */
	
	function page_Main()
	{
		$message = "<a href='index.php?page=AdminSettings'>Admin Settings</a>";
		$message2 = "Manage this script's preferences such as username and password access.";
		$content .= $this->DISPLAY->constructOutput($message);
		$content .= $this->DISPLAY->constructOutput($message2, 15);
		
		$content .= "<br>";
		$message = "<a href='index.php?page=NewsSettings'>Script Settings</a>";
		$message2 = "The Script Settings page allows you to modify specific global settings of the script. The settings include the script's absolute path as well as general news settings.";
		$content .= $this->DISPLAY->constructOutput($message);
		$content .= $this->DISPLAY->constructOutput($message2, 15);

		$content .= "<br>";
		$message = "<a href='index.php?page=ForumSettings'>News Sources</a>";
		$message2 = "The News Sources page allows you to modify specific settings for your forums or news sources (such as RSS feeds), including database and URL information.";
		$content .= $this->DISPLAY->constructOutput($message);
		$content .= $this->DISPLAY->constructOutput($message2, 15);

		$content .= "<br>";
		$message = "<a href='index.php?page=TemplateSettings'>Template Settings</a>";
		$message2 = "The Template Settings page allows you to modify the appearance of your news using the EP-Dev Forum News template system.";
		$content .= $this->DISPLAY->constructOutput($message);
		$content .= $this->DISPLAY->constructOutput($message2, 15);

		$content .= "<br>";
		$message = "<a href='index.php?page=GenerateCode'>Generate Code</a>";
		$message2 = "This is the page that enables you to include the news 
		script on your site. This page will generate the code that you must place onto your 
		website in order to display news and headlines. The page allows you to generate code 
		based on default settings as well as code based on custom settings that you designate on the 
		generate code page.";
		$content .= $this->DISPLAY->constructOutput($message);
		$content .= $this->DISPLAY->constructOutput($message2, 15);


		$this->DISPLAY->displayPage($content);
	}


	/* ------------------------------------------------------------------ */
	//	Admin Settings Page
	//	Contains all administration panel related settings.
	/* ------------------------------------------------------------------ */
	
	function page_AdminSettings()
	{
		// If still default config, notify that config must be changed
		if ($this->USER->defaultConfig())
		{
			$content .= $this->DISPLAY->constructOutput("<font color='red'>You must change your username and password before you are allowed to continue.</font>");
		}


		$content .= $this->DISPLAY->constructOutput("The settings below concern the administration panel.");

		// +------------------------------
		//	Construct form with table of inputs
		// +------------------------------
		
		// start form
		$content .= $this->DISPLAY->constructStartForm("goModifyConfig", "configADMIN_form");

		$content .= "<input type='hidden' name='adminpanel_filename' value='../config/config.php:::ADMIN__username,ADMIN__password,ADMIN__update_check'>\n";

		$content .=  "<input type='hidden' name='adminpanel_class' value='CONFIG'>\n";

		$content .= "<table>";

		// Username input
		$name = "Username";
		$description = "Your desired username to this script's administration panel.";
		$varType = "text";
		$varName = "ADMIN__username";
		$varValue = $this->CONFIG->ADMIN['username'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		// Password input
		$name = "Password";
		$description = "Your desired password to this script's administration panel.";
		$varType = "text";
		$varName = "ADMIN__password";
		$varValue = $this->CONFIG->ADMIN['password'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);


		// Automatically check for updates?
		$name = "Check for Updates on Login";
		$description = "If enabled, the script will automatically check EP-Dev.com for important updates and notify you when a new version of this script is released. It is recommended that you leave this enabled.";
		$varType = "select";
		$varName = "ADMIN__update_check";
		$varOptions = array(
						"Enabled" => "true",
						"Disabled" => "false"
						);
		$varSelected = ($this->CONFIG->ADMIN['update_check'] ? "Enabled" : "Disabled");
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);


		$content .= "</table>";

		// end form
		$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("Save Config") . "</div>";

		// display page
		$this->DISPLAY->displayPage($content, "Admin Panel Settings");
	}


	/* ------------------------------------------------------------------ */
	//	News Settings Page
	//	Contains all news related settings.
	/* ------------------------------------------------------------------ */
	
	function page_NewsSettings()
	{
		$content .= $this->DISPLAY->constructOutput("The values below concern the settings of the EP-Dev Forum News script.");


		$abs_path = ereg_replace("[\\/]+admin[\\/]+$", "/", dirname($_SERVER['SCRIPT_FILENAME']) . "/");


		// +------------------------------
		//	Javascript: Ensure trailing slash on abs path
		// +------------------------------
		$javascript .= "<script LANGUAGE=\"Javascript\" type=\"text/javascript\">
			function check_AbsPath()
			{
				if (

					(document.getElementById('SCRIPT__absolute_path').value.charAt (
						document.getElementById('SCRIPT__absolute_path').value.length -1
							) != '/')

					&& (document.getElementById('SCRIPT__absolute_path').value.substr (
						document.getElementById('SCRIPT__absolute_path').value.length -2, document.getElementById('SCRIPT__absolute_path').value.length
						) != \"\\\\\\\\\")

					)
				{
					var confirmPath = window.confirm(\"WARNING! The last character of absolute path SHOULD BE a '/' or '\\\\\\\\'. Do you want to continue anyway (the script may not work correctly)?\");
					document.getElementById('SCRIPT__absolute_path').focus();
					document.getElementById('SCRIPT__absolute_path').select();
					return confirmPath;
				}

				return true;
			}
			
			function load_AbsPath()
			{
				document.getElementById('SCRIPT__absolute_path').value = \"{$abs_path}\";
			}
		</script>
		";

		// +------------------------------
		//	Construct form with table of inputs
		// +------------------------------
		
		// start form
		$content .= $this->DISPLAY->constructStartForm("goModifyConfig", "configNEWS_form", "POST", null, "return check_AbsPath();");

		$content .= "<input type='hidden' name='adminpanel_filename' value='../config/config.php:::NEWS__recycle_time,NEWS__failsafe_recycle,NEWS__post_num,NEWS__headline_num,NEWS__character_limit,NEWS__page_break_keyword,NEWS__format_urls,NEWS__bbcode,NEWS__author_image,NEWS__fix_linebreaks,SCRIPT__absolute_path:::../news.php:::absolute_path'>\n";

		// we need to copy the value of absolute path so that it can also be written for news.php (under a different variable)
		$content .= "<input type='hidden' name='adminpanel_copy' value='SCRIPT__absolute_path:::absolute_path'>\n";

		$content .=  "<input type='hidden' name='adminpanel_class' value='CONFIG'>\n";

		$content .= "<table>";
		
		$name = "Absolute Path";
		$description = "The absolute path to the folder of this script. (<a href=\"javascript:load_AbsPath();\">auto-detect</a>) <br><font color='blue' style=\"font-size:10px;\">Example:<br>/home/user/pubic_html/forum-news/</font><br>\n";
		$varType = "text";
		$varName = "SCRIPT__absolute_path";
		$varValue = $this->CONFIG->SCRIPT['absolute_path'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, "40");

		$name = "Cache Time";
		$description = "Cache time (in minutes). If enabled, the script will only update the news output every x minutes. By caching your output, the script will speed up significantly. Recommended minute setting: 5. Set to 0 to disable and to display news in real-time.";
		$help_code = 6;
		$varType = "text";
		$varName = "NEWS__recycle_time";
		$varValue = $this->CONFIG->NEWS['recycle_time'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, "3", $help_code);
		
		$name = "Failsafe Cache Display";
		$description = "If enabled, the script will attempt to use the cache when the script cannot connect to a news source. Thus, problems such as temporary mysql downtime will not cause your news to go offline.";
		$varType = "select";
		$varName = "NEWS__failsafe_recycle";
		$varOptions = array(
						"Enabled" => "true",
						"Disabled" => "false"
						);
		$varSelected = ($this->CONFIG->NEWS['failsafe_recycle'] ? "Enabled" : "Disabled");
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$name = "News Number";
		$description = "The default number of news items (forum posts) to display on the page.";
		$varType = "text";
		$varName = "NEWS__post_num";
		$varValue = $this->CONFIG->NEWS['post_num'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, "3");

		$name = "Headline Number";
		$description = "The default number of headlines to display on the page.";
		$varType = "text";
		$varName = "NEWS__headline_num";
		$varValue = $this->CONFIG->NEWS['headline_num'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, "3");

		$name = "Character Limit";
		$description = "The limit to impose on all news posts. When news post reaches this length, the script will insert a read more link. Set to 0 to disable.";
		$varType = "text";
		$varName = "NEWS__character_limit";
		$varValue = $this->CONFIG->NEWS['character_limit'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, "7");

		$name = "Page Break Keyword";
		$description = "The script will break the article and insert a read more link when it encounters this keyword.";
		$varType = "text";
		$varName = "NEWS__page_break_keyword";
		$varValue = $this->CONFIG->NEWS['page_break_keyword'];
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$name = "Auto-Link URLs";
		$description = "If enabled, the script will automatically convert URLs to clickable hyperlinks.";
		$varType = "select";
		$varName = "NEWS__format_urls";
		$varOptions = array(
						"Enabled" => "true",
						"Disabled" => "false"
						);
		$varSelected = ($this->CONFIG->NEWS['format_urls'] ? "Enabled" : "Disabled");
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$name = "Parse BB Code";
		$description = "If enabled, the script will recognize popular bbcode such as [b]some text[/b].";
		$varType = "select";
		$varName = "NEWS__bbcode";
		$varOptions = array(
						"Enabled" => "true",
						"Disabled" => "false"
						);
		$varSelected = ($this->CONFIG->NEWS['bbcode'] ? "Enabled" : "Disabled");
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$name = "Avatars";
		$description = "If enabled, the script will recognize the author image template, otherwise it simple remove it.";
		$varType = "select";
		$varName = "NEWS__author_image";
		$varOptions = array(
						"Enabled" => "true",
						"Disabled" => "false"
						);
		$varSelected = ($this->CONFIG->NEWS['author_image'] ? "Enabled" : "Disabled");
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);


		$name = "Fix Line Breaks";
		$description = "If enabled, the script will search and replace line break tags with correct formatted tags. It will replace &lt;br&gt; tags with &lt;br /&gt; in order to ensure XML compatibility. Most newer forum versions do this automatically, in which case you can disable this feature.";
		$varType = "select";
		$varName = "NEWS__fix_linebreaks";
		$varOptions = array(
						"Enabled" => "true",
						"Disabled" => "false"
						);
		$varSelected = ($this->CONFIG->NEWS['fix_linebreaks'] ? "Enabled" : "Disabled");
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);


		$content .= "</table>";

		// end form
		$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("Save Config") . "</div>";

		// display page
		$this->DISPLAY->displayPage($content, "News Script Settings", null, $javascript);
	}


	/* ------------------------------------------------------------------ */
	//	Forum Settings Page
	//	Contains all forum-specific settings.
	/* ------------------------------------------------------------------ */
	
	function page_ForumSettings()
	{
		// get working forum id
		$workingForum = (isset($_REQUEST['config_fid']) ? $_REQUEST['config_fid'] : 0);

		// get source type
		if (isset($_POST['config_stype']))
		{
			$this->CONFIG->FORUM[$workingForum]['host'] = "";
			$sourceType = $_POST['config_stype'];
		}
		else
		{
			$sourceType = $this->CONFIG->FORUM[$workingForum]['source'];
		}


		// +------------------------------
		//	Javascript: Ensure trailing slash on forum URL
		// +------------------------------
		$javascript .= "<script LANGUAGE=\"Javascript\" type=\"text/javascript\">
			function check_ForumURL() {
			if (
				(document.getElementById('FORUM__{$workingForum}__url').value.charAt (
					document.getElementById('FORUM__{$workingForum}__url').value.length -1
						) != '/')
				)
			{
				var confirmURL = window.confirm(\"WARNING! The last character of forum URL SHOULD BE a '/'. Do you want to continue anyway (the script may not work correctly)?\");
				document.getElementById('FORUM__{$workingForum}__url').focus();
				document.getElementById('FORUM__{$workingForum}__url').select();
				return confirmURL;
			}

			return true;
		}
		</script>
		";


		// start form
		$content .= $this->DISPLAY->constructStartForm("ForumSettings", "forumSelect_form", "POST");

		$content .= "<div><table><tr><td>";

		$content .= "<table width='100%'>";

		$name = "News Source Select";
		$description = "Please select the forum you want to edit and click go. The script comes with the ability to combine news from " . count($this->CONFIG->FORUM) . " different forums or sites.";
		$varType = "select";
		$varName = "config_fid";
		foreach($this->CONFIG->FORUM as $fid => $fidValues)
			$varOptions['News Source '.($fid+1)] = $fid;
		$varSelected = "News Source " . ($workingForum + 1);
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$content .= "</table>";

		$content .= "</td><td>";
		
		// end form
		$content .= "<td align=\"center\"><div style=\"margin-top: 35px;\">" . $this->DISPLAY->constructEndForm("GO") . "</div></td></tr></table></div><br>";



		// start form
		$content .= $this->DISPLAY->constructStartForm("ForumSettings", "forumSelect_form", "POST");
		$content .= "<input type='hidden' name='config_fid' value='{$workingForum}'>";

		$content .= "<div><table><tr><td>";

		$content .= "<table width='100%'>";

		$name = "Change Source Type";
		$description = "The script can fetch news and headlines from multiple types of sources, including both forums and RSS feeds. To change the source type for this particular source, select the source type from the dropdown box and click \"Change\".";
		$varType = "select";
		$varName = "config_stype";

		// +------------------------------
		//	Pull all the forum types from classes folder
		// +------------------------------
		unset($varOptions);
		$handle = opendir('../classes/sources');
		while (false !== ($file = readdir($handle)))
		{
			if(eregi("^[a-zA-Z0-9_-]*$", $file))
			{
				//$varOptions[str_replace("_", " ", substr($file, 0, -4))] = substr($file, 0, -4);
				$varOptions[$file] = $file;
			}
		}
		closedir($handle);

		$varSelected = $sourceType;

		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);

		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$content .= "</table>";

		$content .= "</td><td>";
		
		// end form
		$content .= "<td align=\"center\"><div style=\"margin-top: 35px;\">" . $this->DISPLAY->constructEndForm("Change") . "</div></td></tr></table></div><br>";


		$content .= $this->DISPLAY->constructOutput("<strong>News Source " . ($workingForum+1) . " Settings:</strong>");


		if ($sourceType == "Forum")
		{
			// start form
			$content .= $this->DISPLAY->constructStartForm("goModifyConfig", "configForum_form", "POST", null, "return check_ForumURL();");

			$content .= "<input type='hidden' name='adminpanel_filename' value='../config/config.php:::FORUM__{$workingForum}__enabled,FORUM__{$workingForum}__username,FORUM__{$workingForum}__password,FORUM__{$workingForum}__name,FORUM__{$workingForum}__host,FORUM__{$workingForum}__prefix,FORUM__{$workingForum}__type,FORUM__{$workingForum}__url,FORUM__{$workingForum}__default_ids,FORUM__{$workingForum}__dbtype,FORUM__{$workingForum}__source'>\n";

			$content .=  "<input type='hidden' name='adminpanel_class' value='CONFIG'>\n";
			$content .=  "<input type='hidden' name='FORUM__{$workingForum}__dbtype' value='mysql'>\n";
			$content .=  "<input type='hidden' name='FORUM__{$workingForum}__source' value='Forum'>\n";

			$content .= "<table>";


			$name = "Enable Forum";
			$description = "Select if you want to enable this forum.";
			$varType = "select";
			$varName = "FORUM__{$workingForum}__enabled";
			$varOptions = array(
							"Enabled" => "true",
							"Disabled" => "false"
							);
			$varSelected = ($this->CONFIG->FORUM[$workingForum]['enabled'] ? "Enabled" : "Disabled");
			$varValue = array(
							"options" => $varOptions,
							"selected" => $varSelected
							);
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);


			$name = "DB Username";
			$description = "The username of the forum's database.";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__username";
			$varValue = $this->CONFIG->FORUM[$workingForum]['username'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$name = "DB Password";
			$description = "The password of the forum's database.";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__password";
			$varValue = $this->CONFIG->FORUM[$workingForum]['password'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$name = "DB Name";
			$description = "The name of the forum's database.";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__name";
			$varValue = $this->CONFIG->FORUM[$workingForum]['name'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$name = "DB Host";
			$description = "The host of the forum's database.";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__host";
			$varValue = $this->CONFIG->FORUM[$workingForum]['host'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$name = "DB Prefix";
			$description = "The prefix of the tables of the forum.<br>Some popular prefixes are:<br><font color='blue'>(blank) = vBulletin default<br>
												phpbb_ = phpBB default<br>
												ibf_ = Invision default<br>
												bbl_ = WBB default<br>
												yabbse_ = YaBB default</font>";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__prefix";
			$varValue = $this->CONFIG->FORUM[$workingForum]['prefix'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$name = "Forum Type";
			$description = "Select the type of forum & version from the drop down list. If your forum version isn't supported, try selecting an earlier version.";
			$varType = "select";
			$varName = "FORUM__{$workingForum}__type";

			// +------------------------------
			//	Pull all the forum types from classes folder
			// +------------------------------
			unset($varOptions);
			$handle = opendir('../classes/sources/Forum');
			while ($file = readdir($handle))
			{
				if(eregi("(.*).php", $file))
				{
					$varOptions[str_replace("_", " ", substr($file, 0, -4))] = substr($file, 0, -4);

					if ($this->CONFIG->FORUM[$workingForum]['type'] == substr($file, 0, -4))
						$varSelected = str_replace("_", " ", substr($file, 0, -4));
				}
			}
			$varValue = array(
							"options" => $varOptions,
							"selected" => $varSelected
							);
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$name = "Forum URL";
			$description = "The URL to the forum with trailing slash.<br><font color='blue'>Example: http://www.site.com/forum/</font>";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__url";
			$varValue = $this->CONFIG->FORUM[$workingForum]['url'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, 45);

			$name = "Default Category Ids";
			$description = "The default category/forum ids of this forum that you want to pull posts from to be displayed as news.<br>Example: 1, 2, 3";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__default_ids";
			$varValue = $this->CONFIG->FORUM[$workingForum]['default_ids'];
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

			$content .= "</table>";

			// end form
			$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("Save Config") . "</div>";
		}
		else if ($sourceType == "RSS")
		{
			// start form
			$content .= $this->DISPLAY->constructStartForm("goModifyConfig", "configForum_form", "POST");

			$content .= "<input type='hidden' name='adminpanel_filename' value='../config/config.php:::FORUM__{$workingForum}__enabled,FORUM__{$workingForum}__host,FORUM__{$workingForum}__type,FORUM__{$workingForum}__dbtype,FORUM__{$workingForum}__source'>\n";

			$content .=  "<input type='hidden' name='adminpanel_class' value='CONFIG'>\n";
			$content .=  "<input type='hidden' name='FORUM__{$workingForum}__type' value='RSS'>\n";
			$content .=  "<input type='hidden' name='FORUM__{$workingForum}__dbtype' value='xml'>\n";
			$content .=  "<input type='hidden' name='FORUM__{$workingForum}__source' value='RSS'>\n";

			$content .= "<table>";


			$name = "Enable RSS Feed";
			$description = "Select if you want to enable this feed.";
			$varType = "select";
			$varName = "FORUM__{$workingForum}__enabled";
			$varOptions = array(
							"Enabled" => "true",
							"Disabled" => "false"
							);
			$varSelected = ($this->CONFIG->FORUM[$workingForum]['enabled'] ? "Enabled" : "Disabled");
			$varValue = array(
							"options" => $varOptions,
							"selected" => $varSelected
							);
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);


			$name = "RSS Feed URL";
			$description = "The url of the rss feed that you want to pull news from.<font color='blue'><br>example: http://www.somesite.com/rss.xml</font>";
			$varType = "text";
			$varName = "FORUM__{$workingForum}__host";
			$varValue = $this->CONFIG->FORUM[$workingForum]['host'];
			$varSize = 40;
			$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize);

			$content .= "</table>";

			// end form
			$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("Save Config") . "</div>";
		}

		// display page
		$this->DISPLAY->displayPage($content, "News Source " . ($workingForum+1) . " Settings", null, $javascript);
	}


	/* ------------------------------------------------------------------ */
	//	Template Edit Page
	//	Contains all templates for easy editing.
	/* ------------------------------------------------------------------ */
	
	function page_EditTemplates()
	{
		// get working template id
		$workingTemplate = (isset($_GET['config_tid']) ? $_GET['config_tid'] : 0);


		// Array of general valid codes
		$valid_codes['general'] = array (
							"!!TITLE!!" => "The post's title.",
							"!!CATEGORYID!!" => "The post's category id.",
							"!!VIEWS!!" => "The number of views of this post.",
							"!!THREADID!!" => "The thread id of this post.",
							"!!NUM_COMMENTS!!" => "The number of comments of this post.",
							"!!AUTHOR_NAME!!" => "The post's author's name.",
							"!!AUTHOR_ID!!" => "The author's id of the post.",
							"!!NEWS_URL!!" => "The URL to the post in the forum.",
							"!!AUTHOR_URL!!" => "The URL to the author's profile.",
							"!!SOURCE_ID!!" => "The ID number of this post's source in the EP-Dev Forum News script."
						);

		$valid_codes['post'] = $valid_codes['general'];
		$valid_codes['post']['!!CONTENT!!'] = "The text of the post.";
		$valid_codes['post']['!!AUTHOR_IMAGE!!'] = "Will be replaced by the author image template if author has image (avatar).";

		$valid_codes['tags'] = $valid_codes['general'];
		$valid_codes['tags']['!!POST_TEXT!!'] = "The text between the tags.";

		$valid_codes['authorimage'] = $valid_codes['general'];
		$valid_codes['authorimage']['!!AVATAR_URL!!'] = "The URL to the author's image.";

		$valid_codes['dates'] = array (
							"[YEAR-4]" => "The four year representation of the date. EX: 2005",
							"[YEAR-2]" => "The two year representation of the date. EX: 05",
							"[MONTH-NUM]" => "The month number. EX: 2 (for February)",
							"[MONTH]" => "The name of the month. EX: February",
							"[MONTH-ABR]" => "The abreviated name of the month. EX: Feb",
							"[DAY-NUM]" => "The day number of the month. EX: 5",
							"[DAY]" => "The day of the month. EX: Wednesday",
							"[DAY-ABR]" => "The abreviated day of the month. EX: Wed",
							"[HOUR-24]" => "The hour on 24 hour time. EX: 14 for 2 PM",
							"[HOUR-12]" => "The hour on 12 hour time. EX: 2 for 2 PM",
							"[MIN]" => "The minutes.",
							"[SEC]" => "The seconds.",
							"[AM-PM]" => "The designation of AM or PM."
						);

		$javascript = "<script LANGUAGE=\"Javascript\" type=\"text/javascript\">\n";
		
		// +------------------------------
		//	Cycle through codes and generate
		//	javascript functions to display them
		// +------------------------------
		
		foreach($valid_codes as $code_type => $code_array)
		{
			$javascript .= "function show_{$code_type}(win_size_w, win_size_h, var_name) {\n"
						. "win_{$code_type} = window.open('', 'tags_{$code_type}_window', 'toolbar=no,status=no,width=' + win_size_w +',height=' + win_size_h + ',resizable=no');\n";
			
			$javascript .= "windowContent = \"" . $this->DISPLAY->constructOutput("<strong>Valid \" + var_name + \" Tags</strong>") . "\";\n";

			$javascript .= "windowContent += \"<table style=\\\"width: \" + win_size_w + \";\\\">\";\n";

			foreach($code_array as $code => $description)
			{
				$javascript .= "windowContent += \"<tr><td>" . addslashes($this->DISPLAY->constructOutput($code)) . "</td>"
							. "<td>" . addslashes($this->DISPLAY->constructOutput($description)) . "</td></tr>\";\n";
			}

			$javascript .= "windowContent += \"</table>\";\n";

			$javascript .= "win_{$code_type}.document.write(windowContent);\n}\n\n";
		}

		$javascript .= "</script>\n";


		// start form
		$content .= $this->DISPLAY->constructStartForm("TemplateSettings", "templateSelect_form", "GET");

		$content .= "<table width='90%'>";

		$name = "Template Select";
		$description = "Please select the template you want to edit and click go.";
		$varType = "select";
		$varName = "config_tid";
		foreach($this->TEMPLATE->TEMPLATES as $tid => $tidValues)
			$varOptions['Template '.($tid+1)] = $tid;
		$varSelected = "Template " . ($workingTemplate + 1);
		$varValue = array(
						"options" => $varOptions,
						"selected" => $varSelected
						);
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue);

		$content .= "</table>";
		
		// end form
		$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("GO") . "</div>";



		$content .= $this->DISPLAY->constructOutput("<strong>Template " . ($workingTemplate+1) . " Settings:</strong>");

		// start form
		$content .= $this->DISPLAY->constructStartForm("goModifyConfig", "configTemplate_form");

		$content .= "<input type='hidden' name='adminpanel_filename' value='../config/template.php:::TEMPLATES__{$workingTemplate}__headlines,TEMPLATES__{$workingTemplate}__quotes,TEMPLATES__{$workingTemplate}__code,TEMPLATES__{$workingTemplate}__highlight,TEMPLATES__{$workingTemplate}__php,TEMPLATES__{$workingTemplate}__read_more,TEMPLATES__{$workingTemplate}__read_more_cut_off,TEMPLATES__{$workingTemplate}__author_image,TEMPLATES__{$workingTemplate}__post'>\n";

		$content .=  "<input type='hidden' name='adminpanel_class' value='TEMPLATE'>\n";

		$content .= "<table>";


		$name = "Headlines";
		$description = "This is the template that will be used to display headlines.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__headlines";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['headlines'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_general(350, 350, 'Headline');\">View Valid Headline Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Quotes";
		$description = "This is the template that will be used to display quotes in the [quote][/quote] bbcode.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__quotes";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['quotes'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_tags(365, 350, 'Quote');\">View Valid Quote Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Code";
		$description = "This is the template that will be used to display code in the [code][/code] bbcode.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__code";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['code'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_tags(365, 350, 'Code');\">View Valid Code Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Highlight";
		$description = "This is the template that will be used to display highlighted text in the [highlight][/highlight] bbcode.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__highlight";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['highlight'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_tags(365, 350, 'Highlight');\">View Valid Highlight Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "PHP";
		$description = "This is the template that will be used to display php in the [php][/php] bbcode.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__php";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['php'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_tags(365, 350, 'PHP');\">View Valid PHP Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Read More Link";
		$description = "This is the format of link that results when the post has the break article tag in it.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__read_more";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['read_more'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_tags(365, 350, 'Read More Link');\">View Valid \"Read More Link\" Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Cut Off Link";
		$description = "This is the format of link that results when a post goes over the maximum character limit.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__read_more_cut_off";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['read_more_cut_off'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_tags(365, 350, 'Cut Off Link');\">View Valid \"Cut Off Link\" Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Author Image (Avatar)";
		$description = "This is the template of the author image that will replace !!AUTHOR_IMAGE!! in the post template if an avatar for the forum member is available.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__author_image";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['author_image'];
		$varSize = array(
					"rows" => 4, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_authorimage(365, 350, 'Author Image');\">View Valid Author Image Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);


		$name = "Post";
		$description = "This is the template that will be used to display the news post.";
		$varType = "textarea";
		$varName = "TEMPLATES__{$workingTemplate}__post";
		$varValue = $this->TEMPLATE->TEMPLATES[$workingTemplate]['post'];
		$varSize = array(
					"rows" => 15, 
					"cols" => 60
					);
		$varExtra = "<a href=\"javascript: show_post(380, 375, 'Post');\">View Valid Post Tags</a>&nbsp;&nbsp;&nbsp;<a href=\"javascript: show_dates(385, 405, 'Post');\">View Valid Date Tags</a>";
		$content .= $this->DISPLAY->constructTableVariable($name, $description, $varType, $varName, $varValue, $varSize, null, $varExtra);

		$content .= "</table>";

		// end form
		$content .= "<div align='center'>" . $this->DISPLAY->constructEndForm("Save Config") . "</div>";
		
		// display page
		$this->DISPLAY->displayPage($content, "Template " . ($workingTemplate+1) . " Settings", null, $javascript);
	}


	/* ------------------------------------------------------------------ */
	//	Generate Code Page
	//	Allows for the generation of code to call the Forum News script.
	/* ------------------------------------------------------------------ */
	
	function page_GenerateCode()
	{

		$content .= "<form name='form_generatecode'>";

		$content .= "<table>\n<tr>\n";

		$content .= "<td>" . $this->DISPLAY->constructOutput("Select Source(s) <font size='1'>(<a href='index.php?page=FAQ&amp;topic=5'>help?</a>)</font>") . "</td>";
		$content .= "<td>" . $this->DISPLAY->constructOutput("Category IDs") . "</td>";

		$content .= "</tr>";

		// +------------------------------
		//	Cycle through forums
		// +------------------------------
		for ($i=0; $i<count($this->CONFIG->FORUM); $i++)
		{
			// Only enabled forums
			if ($this->CONFIG->FORUM[$i]['enabled'])
			{
				// forum count
				$f_count++;
				$content .= "<tr>\n";
				$content .= "<td>"
						. $this->DISPLAY->constructOutput("<input type='checkbox' name='forum_{$i}' ID='forum_{$i}'". ($f_count==1 ? " CHECKED" : "") . ">" . " Source " . ($i+1)
						. " " . str_replace("_", " ", "(" . $this->CONFIG->FORUM[$i]['type'] . ")"))
						. "</td>\n";

				$content .= "<td>"
						. $this->DISPLAY->constructOutput("<input type='text' name='forum_{$i}_ids' value='{$this->CONFIG->FORUM[$i]['default_ids']}' size='15' ID='forum_{$i}_ids'>"
						. " <input type='checkbox' name='forum_{$i}_default_ids' onClick=\"document.getElementById('forum_{$i}_ids').value='{$this->CONFIG->FORUM[$i]['default_ids']}'; document.getElementById('forum_{$i}_ids').disabled=(document.getElementById('forum_{$i}_default_ids').checked)\" ID='forum_{$i}_default_ids'> "
						."Default")
						. "</td>\n";

				$content .= "</tr>\n";

				// disable / enable category id box based on checkbox value
				$forum_enable .= "document.getElementById('forum_{$i}_ids').disabled = false; ";

				// The java function will be used later when generating code
				$java_func .= "if (document.getElementById('forum_{$i}').checked)
				{
					catIDS += (catIDS.length > 0 ? \"|\" + document.getElementById('forum_{$i}_ids').value : document.getElementById('forum_{$i}_ids').value);
					forIDS += (forIDS.length > 0 ? \",{$i}\" : \"{$i}\");
				}\n";
			}
		}

		$content .= "</table>\n";

		$content .= "<br>";

		$content .= "<table width='100%'>\n<tr>\n<td>";

		$template = "<select name='template' ID='template_id'>";

		for($i=0; $i<count($this->TEMPLATE->TEMPLATES); $i++)
			$template .= "<option value='{$i}'>" . $this->DISPLAY->constructOutput("Template " . ($i+1)) . "</option>\n";

		$template .= "</select>\n";

		$content .= $this->DISPLAY->constructOutput("Select Template: " . $template);

		$content .= "<br>";

		$content .= $this->DISPLAY->constructOutput("Select number of posts to display: <input type='text' name='postnum' value='' size='4' ID='news_num'>");

		$content .= "<br>";

		$content .= $this->DISPLAY->constructOutput("Select Type: <select name='type' ID='code_type'>\n<option value='News'>News</option>\n<option value='Headlines'>Headlines</option>\n</select>\n");

		$content .= "</td><td>";

		$content .= "<input type='button' value='Generate Code' onClick=\"Generate_Code();\">";
		$content .= "<br><br>";
		$content .= "<input type='reset' value='Reset Values' onClick=\"" . $forum_enable . "\">";

		$content .= "</td></tr></table>";

		$content .= "<br>";

		$content .= $this->DISPLAY->constructOutput("<strong>Generated Code:</strong>");

		$content .= $this->DISPLAY->constructOutput("<font size='1' color='blue'>Copy and paste the following into a PHP page (.php):</font>", 15);

		$content .= $this->DISPLAY->constructOutput("<textarea ID='generated_code' name='post' style='width: 500px; height: 100px;'></textarea>", 15);

		$content .= "</form>";


		// +------------------------------
		//	The function that takes all of 
		//	the specified information and 
		//	shoves it into a textbox in a
		//	specific format.
		// +------------------------------
		$javascript = "<script LANGUAGE=\"Javascript\" type=\"text/javascript\">
			function Generate_Code()
			{
				var genCode = \"\";
				var forIDS = \"\";
				var catIDS = \"\";

				var newsNum = document.getElementById('news_num').value;
				var tempID = document.getElementById('template_id').value;

				var codeType = document.getElementById('code_type').value;

				newsNum = removeSpaces(newsNum);
				tempID = removeSpaces(tempID);

				{$java_func}

				forIDS = removeSpaces(forIDS);
				catIDS = removeSpaces(catIDS);

				genCode = \"<" . "?php include_once(\\\"{$this->CONFIG->SCRIPT['files']['news']}\\\");\\n\";
				genCode += \"\$forum_obj = new EP_Dev_Forum_News();\\n\";
				genCode += \"\$forum_obj->display_\" + codeType + \"(\" + \"\\\"\" + newsNum + \"\\\", \" + \"\\\"\" + catIDS + \"\\\", \" + \"\\\"\" + forIDS + \"\\\", \" + \"\\\"\" + tempID + \"\\\"\" + \"); ?" . ">\";

				document.getElementById('generated_code').value = genCode;

				document.getElementById('generated_code').focus();
				document.getElementById('generated_code').select();
			}

			// removes the spaces from value
			function removeSpaces(value)
			{
				for(var i=0; i<value.length; i++)
				{
					if (value.charAt(i) == \" \")
					{
						value = value.substr(0, i) + value.substr(i+1, value.length-1);
						i--;
					}
				}

				return value;
			}
				</script>";

		$this->DISPLAY->displayPage($content, "Generate Code", null, $javascript);

	}


	/* ------------------------------------------------------------------ */
	//	Add Configuration of $data
	//	A function that is used to add $data to configuration files.
	//	Fairly complex as it must do quite a bit.
	/* ------------------------------------------------------------------ */
	
	function AddConfig($data)
	{
		// +------------------------------
		//	Convert triple underscores to singular periods
		// +------------------------------
		foreach($data as $key => $value)
		{
			$data_new[str_replace("___", ".", $key)] = $value;
		}

		// assign new data to old variable
		$data = $data_new;

		// remove temp variable
		unset($data_new);


		// +------------------------------
		//	Pull out special data
		//	Store special (not-to-be-posted) data into is own array
		// +------------------------------

		foreach ($data as $key => $value)
		{
			if (ereg("^adminpanel_(.*)", $key, $args))
			{
				$data_extra[$args[1]] = $value;
				unset($data[$key]);
			}
		}

		// also get rid of page
		unset($data['page']);


		// +------------------------------
		//	Custom Rule Sets
		//	Get Custom Rule Sets
		// +------------------------------
		$customRules = array();
		if (isset($data_extra['rules']))
		{
			$ruleset = explode(":::", $data_extra['rules']);

			foreach($ruleset as $current_rule)
			{
				$current_set = explode(",", $current_rule);
				$customRules[$current_set[0]] = $current_set[1];
			}
		}


		// +------------------------------
		//	Grab file names
		//
		//	Filenames are in adminpanel_filename in the format of:
		//	filename:::var1,var2,var3:::filename2:::var4,var5,var6
		//	where var1, 2, and 3 will be written to filename
		//	and var4, 5, and 6 will be written to filename2
		// +------------------------------

		// adminpanel_FILENAME
		$filenames_array = explode(":::", $data_extra['filename']);
		

		// Store filenames into array such as $array['filename'] = array(var1, var2, ...);
		for($i=0; $i<count($filenames_array); $i+=2)
			$filenames[$filenames_array[$i]] = explode(",", $filenames_array[$i+1]);


		
		// +------------------------------
		//	Build search / replace arrays
		//
		//	Cycle through the data and build the search and replace arrays.
		// +------------------------------
		
		foreach($data as $key => $value)
		{
			// make sure these are empty
			unset($key_data);
			unset($keys);
			unset($keys_string);

			/* keys are stored as ARRAY_TYPE__KEY ...
				Example: $data['ADMIN__username'] = "value";
				-or-
				$data['BUYMODE__CONFIG__currency_string'] = "value"; where ARRAY_TYPE__KEY1__KEY2
			*/
			$key_data = explode("__", $key);

			// Store keys into an array
			$keys = array();
			for($i=1; $i<count($key_data); $i++)
			{
				$keys[] = $key_data[$i];

				// keep keys string as well for search array
				// note: We detect if numeric key or not
				$keys_string .= (!is_numeric($key_data[$i]) ? "['".$key_data[$i]."']" : "[".$key_data[$i]."]");
			}


			if (!isset($customRules[$key]))
			{
				// store formatted replace value into replace array.
				$replace[$key] = $this->FORMAT->convertDataToString($value, $keys, $key_data[0]);
			}
			else
			{
				// store formatted replace value into replace array.
				$replace[$key] = $this->FORMAT->convertDataToString($value, $keys, $key_data[0], $customRules[$key]);
			}
		}


		// detect newline type
		$newLine = "
		";
		$newLine = str_replace("\t", "", $newLine);


		// +------------------------------
		//	Perform Search & Replace
		// +------------------------------
		foreach($filenames as $file => $vars)
		{
			// create new fileObj
			$fileObj = new EP_Dev_Forum_News_Admin_File_IO($file, $this->ERROR);

			// Open file
			$fileObj->open();

			// read contents
			$fileContent = $fileObj->read();

			// perform search & replace
			foreach($vars as $cur_var)
			{
				$newConfig .= "\t\t" . $replace[$cur_var] . $newLine;
			}

			$newConfig .= $newLine . $newLine . "\t\t{$data_extra['replace_string']}";

			$fileContent = str_replace("\t\t{$data_extra['replace_string']}", $newConfig, $fileContent);

			// write data back to file
			$fileObj->writeNew($fileContent);

			// close file object
			$fileObj->close();

			// get rid of variable
			unset($fileObj);
		}
	}



	/* ------------------------------------------------------------------ */
	//	Remove Configuration of $data
	//	A function that is used to remove configuration from files.
	/* ------------------------------------------------------------------ */
	
	function RemoveConfig($data)
	{
		// +------------------------------
		//	Convert triple underscores to singular periods
		// +------------------------------
		foreach($data as $key => $value)
		{
			$data_new[str_replace("___", ".", $key)] = $value;
		}

		// assign new data to old variable
		$data = $data_new;

		// remove temp variable
		unset($data_new);


		// +------------------------------
		//	Pull out special data
		//	Store special (not-to-be-posted) data into is own array
		// +------------------------------

		foreach ($data as $key => $value)
		{
			if (ereg("^adminpanel_(.*)", $key, $args))
			{
				$data_extra[$args[1]] = $value;
				unset($data[$key]);
			}
		}

		// also get rid of page
		unset($data['page']);



		// +------------------------------
		//	Custom Rule Sets
		//	Get Custom Rule Sets
		// +------------------------------
		$customRules = array();
		if (isset($data_extra['rules']))
		{
			$ruleset = explode(":::", $data_extra['rules']);

			foreach($ruleset as $current_rule)
			{
				$current_set = explode(",", $current_rule);
				$customRules[$current_set[0]] = $current_set[1];
			}
		}



		// +------------------------------
		//	Grab file names
		//
		//	Filenames are in adminpanel_filename in the format of:
		//	filename:::var1,var2,var3:::filename2:::var4,var5,var6
		//	where var1, 2, and 3 will be written to filename
		//	and var4, 5, and 6 will be written to filename2
		// +------------------------------

		// adminpanel_FILENAME
		$filenames_array = explode(":::", $data_extra['filename']);
		

		// Store filenames into array such as $array['filename'][block_number] = array(var1, var2, ...);
		for($i=0; $i<count($filenames_array); $i+=2)
			$filenames[$filenames_array[$i]][] = explode(",", $filenames_array[$i+1]);

		//var_dump($filenames);
		//die();


		foreach($data as $key => $value)
		{
			// make sure these are empty
			unset($key_data);
			unset($keys);
			unset($keys_string);

			/* keys are stored as ARRAY_TYPE__KEY ...
				Example: $data['ADMIN__username'] = "value";
				-or-
				$data['BUYMODE__CONFIG__currency_string'] = "value"; where ARRAY_TYPE__KEY1__KEY2
			*/
			$key_data = explode("__", $key);


			// Store keys into an array
			$keys = array();
			for($i=1; $i<count($key_data); $i++)
			{
				$keys[] = $key_data[$i];

				// keep keys string as well for search array
				// note: We detect if numeric key or not
				$keys_string .= (!is_numeric($key_data[$i]) ? "['".$key_data[$i]."']" : "[".$key_data[$i]."]");
			}

			$current_mainclass = $data_extra['class']; 

			eval("\$removeValues[\$key] = \$this->{$current_mainclass}->{$key_data[0]}{$keys_string};");

			if (!isset($customRules[$key]))
			{
				// store formatted search value into remove array.
				$remove_data[$key] .= quotemeta($this->FORMAT->convertVarToString($removeValues[$key], $keys, $key_data[0]));
			}
			else
			{
				// store formatted search value into remove array.
				$remove_data[$key] .= quotemeta($this->FORMAT->convertVarToString($removeValues[$key], $keys, $key_data[0], $customRules[$key]));
			}
		}

		
		// cycle through filenames
		foreach($filenames as $filename => $block_data)
		{
			// +------------------------------
			//	Perform Search & Replace
			// +------------------------------
			// create new fileObj
			$fileObj = new EP_Dev_Forum_News_Admin_File_IO($filename, $this->ERROR);

			// Open file
			$fileObj->open();

			// read contents
			$fileContent = $fileObj->read();

			// cycle through keys of current removal block
			foreach($block_data as $remove_data_keys)
			{
				unset($remove_string);

				foreach($remove_data_keys as $current_key)
				{
					$remove_string .= $remove_data[$current_key] . "\s*";
				}

				// perform removal
				$fileContent = preg_replace("/" . $remove_string . "/", $data_extra['replace_string'], $fileContent);
			}

			// write data back to file
			$fileObj->writeNew($fileContent);

			// close file object
			$fileObj->close();

			// get rid of variable
			unset($fileObj);
		}
	}



	/* ------------------------------------------------------------------ */
	//	Modify config files with new $data
	//	A relatively complex function that makes specific calls to classes
	//	in order to eventually modify filenames (specified within $data)
	//	with the new information (also specified within $data).
	/* ------------------------------------------------------------------ */
	
	function ModifyConfig($data)
	{
		// +------------------------------
		//	Convert triple underscores to singular periods
		// +------------------------------
		foreach($data as $key => $value)
		{
			$data_new[str_replace("___", ".", $key)] = $value;
		}

		// assign new data to old variable
		$data = $data_new;

		// remove temp variable
		unset($data_new);


		// +------------------------------
		//	Pull out special data
		//	Store special (not-to-be-posted) data into is own array
		// +------------------------------

		foreach ($data as $key => $value)
		{
			if (ereg("^adminpanel_(.*)", $key, $args))
			{
				$data_extra[$args[1]] = $value;
				unset($data[$key]);
			}
		}

		// also get rid of page
		unset($data['page']);


		// +------------------------------
		//	Custom Rule Sets
		//	Get Custom Rule Sets
		// +------------------------------
		$customRules = array();
		if (isset($data_extra['rules']))
		{
			$ruleset = explode(":::", $data_extra['rules']);

			foreach($ruleset as $current_rule)
			{
				$current_set = explode(",", $current_rule);
				$customRules[$current_set[0]] = $current_set[1];
			}
		}


		// +------------------------------
		//	Grab file names
		//
		//	Filenames are in adminpanel_filename in the format of:
		//	filename:::var1,var2,var3:::filename2:::var4,var5,var6
		//	where var1, 2, and 3 will be written to filename
		//	and var4, 5, and 6 will be written to filename2
		// +------------------------------

		// adminpanel_FILENAME
		$filenames_array = explode(":::", $data_extra['filename']);
		

		// Store filenames into array such as $array['filename'] = array(var1, var2, ...);
		for($i=0; $i<count($filenames_array); $i+=2)
			$filenames[$filenames_array[$i]] = explode(",", $filenames_array[$i+1]);


		// store special keys of arrays
		if (isset($data_extra['arrays']))
			$varArrayKeys = implode(",", $data_extra['arrays']);
		else
			$varArrayKeys = array();


		// perform the copy action
		// ORIGINAL_NAME:NEW_NAME,ORIGINAL_NAME2:NEW_NAME2
		if (isset($data_extra['copy']))
		{
			$need_renamed = explode(",", $data_extra['copy']);

			foreach($need_renamed as $rename)
			{
				$rename_arry = explode(":::", $rename);

				// create new entry in data with old data
				$data[$rename_arry[1]] = $data[$rename_arry[0]];

				// place into array of new_key => old_key;
				// This will be recognized later so that old_key's value is used.
				$varCopy[$rename_arry[1]] = $rename_arry[0];
			}
		}


		
		// +------------------------------
		//	Build search / replace arrays
		//
		//	Cycle through the data and build the search and replace arrays.
		// +------------------------------
		
		foreach($data as $key => $value)
		{
			// make sure these are empty
			unset($key_data);
			unset($keys);
			unset($keys_string);

			/* keys are stored as ARRAY_TYPE__KEY ...
				Example: $data['ADMIN__username'] = "value";
				-or-
				$data['BUYMODE__CONFIG__currency_string'] = "value"; where ARRAY_TYPE__KEY1__KEY2
			*/
			$key_data = explode("__", $key);

			// Store keys into an array
			$keys = array();
			for($i=1; $i<count($key_data); $i++)
			{
				$keys[] = $key_data[$i];

				// keep keys string as well for search array
				// note: We detect if numeric key or not
				$keys_string .= (!is_numeric($key_data[$i]) ? "['".$key_data[$i]."']" : "[".$key_data[$i]."]");
			}

			// Take care of array variables
			if (in_array($key, $varArrayKeys))
			{
				// strip out all but numbers and commas
				$new_value = ereg_replace("[^0-9,]", "", $value);

				// turn into array
				unset($value);

				// turn into array
				$value = explode(",", $value);
			}

			// store formatted replace value into replace array.
			if (!isset($customRules[$key]))
			{
				$replace[$key] = $this->FORMAT->convertDataToString($value, $keys, $key_data[0]);
			}
			else
			{
				$replace[$key] = $this->FORMAT->convertDataToString($value, $keys, $key_data[0], $customRules[$key]);
			}

			// Detect if copy is in place
			if (isset($varCopy[$key]))
			{
				// store earlier found data of key in varCopy[] for $key
				eval("\$searchValues[\$key] = \$searchValues[\$varCopy[\$key]];");
			}
			else
			{
				// Allow for multiple main class setups
				if (!empty($data_extra['class2']))
				{
					$mainkeyclass = explode(":", $data_extra['class2']);

					if (strstr($mainkeyclass[1], $key) !== false)
						$current_mainclass = $mainkeyclass[0];
					else
						$current_mainclass = $data_extra['class'];
				}
				else
				{
					$current_mainclass = $data_extra['class']; 
				}

				// store current data for variable into $searchValues
				eval("\$searchValues[\$key] = \$this->{$current_mainclass}->{$key_data[0]}{$keys_string};");
			}

			// store formatted search value into search array
			// detect custom ruleset
			if (!isset($customRules[$key]))
			{
				$search[$key] = $this->FORMAT->convertVarToString($searchValues[$key], $keys, $key_data[0]);
			}
			else
			{
				$search[$key] = $this->FORMAT->convertVarToString($searchValues[$key], $keys, $key_data[0], $customRules[$key]);
			}
		}

		// +------------------------------
		//	Perform Search & Replace
		// +------------------------------
		foreach($filenames as $file => $vars)
		{
			// create new fileObj
			$fileObj = new EP_Dev_Forum_News_Admin_File_IO($file, $this->ERROR);

			// Open file
			$fileObj->open();

			// read contents
			$fileContent = $fileObj->read();

			// perform search & replace
			foreach($vars as $cur_var)
			{
				//echo "SEARCH: <pre>" . $search[$cur_var] . "</pre><br><br>" . "REPLACE: <pre>" . $replace[$cur_var] . "</pre><br><br><br><br><br>";
				$fileContent = str_replace($search[$cur_var], $replace[$cur_var], $fileContent);
			}

			// write data back to file
			$fileObj->writeNew($fileContent);

			// close file object
			$fileObj->close();

			// get rid of variable
			unset($fileObj);
		}
	}
}


/* ------------------------------------------------------------------ */
//	User Interaction Class
//	Contains all functions that interact with specific user.
/* ------------------------------------------------------------------ */

class EP_Dev_Forum_News_Admin_UserControl
{
	var $username;
	var $password;
	var $key_prefix;

	function EP_Dev_Forum_News_Admin_UserControl($config_username, $config_password)
	{
		$this->username = $config_username;
		$this->password = $config_password;

		$this->key_prefix = "Forum_News_Admin_";
	}


	/* ------------------------------------------------------------------ */
	//	Login based on $username and $password
	//	Sets new session based on $username and $password
	/* ------------------------------------------------------------------ */
	
	function login($username, $password)
	{
		if ($this->check($username, $password))
		{
			$this->setValue("username", $username);
			$this->setValue("password", $password);
		}
	}


	/* ------------------------------------------------------------------ */
	//	Logout
	//	Destroys all session data relating to admin panel.
	/* ------------------------------------------------------------------ */
	
	function logout()
	{
		foreach(array_keys($_SESSION) as $key)
		{
			if (substr($key, 0, strlen($this->key_prefix)) == $this->key_prefix)
				unset($_SESSION[$key]);
		}
	}


	/* ------------------------------------------------------------------ */
	//	check
	//	boolean return of correct username & password.
	//	If parameters specified, they are used. Otherwise, session values
	//	are used.
	//	return true = good login, false = bad login
	/* ------------------------------------------------------------------ */
	
	function check($username = null, $password = null)
	{
		if ($username===null)
			$username = $this->getValue("username");

		if ($password===null)
			$password = $this->getValue("password");
		
		return ($username == $this->username && $password == $this->password);
	}


	/* ------------------------------------------------------------------ */
	//	Get session value of $key
	//	Returns session value of $key
	/* ------------------------------------------------------------------ */
	
	function getValue($key)
	{
		return $_SESSION[$this->key_prefix . $key];
	}


	/* ------------------------------------------------------------------ */
	//	Set session value of $key to $value
	/* ------------------------------------------------------------------ */
	
	function setValue($key, $value)
	{
		$_SESSION[$this->key_prefix . $key] = $value;
	}


	// Checks if username and password are still default
	function defaultConfig()
	{
		return ($this->username == "admin" && $this->password == "");
	}

}