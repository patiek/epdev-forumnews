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


class EP_Dev_Forum_News_Admin_Display
{

	var $defaults;
	var $MENU;

	/* ------------------------------------------------------------------ */
	//	Our constructor loads up basic stuff / variables needed.
	/* ------------------------------------------------------------------ */

	function EP_Dev_Forum_News_Admin_Display($title)
	{
		$this->defaults['title_text'] = $title;
		$this->MENU = new EP_Dev_Forum_News_Admin_Menu_Bar();
		$this->load_Default_Menu();
	}


	/* ------------------------------------------------------------------ */
	//	loads default menu
	/* ------------------------------------------------------------------ */
	
	function load_Default_Menu()
	{
		$this->MENU->add("<div align=\"center\">Main Menu</div>", "", 1);
		$this->MENU->add("Main", "index.php", 1);
		$this->MENU->add("Admin Settings", "index.php?page=AdminSettings", 1);
		$this->MENU->add("Script Settings", "index.php?page=NewsSettings", 1);
		$this->MENU->add("News Sources", "index.php?page=ForumSettings", 1);
		$this->MENU->add("Edit Templates", "index.php?page=TemplateSettings", 1);
		$this->MENU->add("Generate Code", "index.php?page=GenerateCode", 1);
		$this->MENU->add("Logout", "index.php?page=goLogout", 1);

		$this->MENU->add("<center>Other</center>", "", 2);
		$this->MENU->add("Troubleshooting", "index.php?page=FAQ", 2);
		$this->MENU->add("Check For Update", "index.php?page=CheckForUpdate", 2);
		$this->MENU->add("Visit EP-Dev.com", "http://www.ep-dev.com", 2);
		$this->MENU->add("Get Support", "http://www.dev-forums.com", 2);
		$this->MENU->add("Contact Author", "mailto: patiek@ep-dev.com", 2);
	}

	
	/* ------------------------------------------------------------------ */
	//	Displays header.
	/* ------------------------------------------------------------------ */
	
	function show_Header($extra = "")
	{
		?>
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
		<HTML>
		<HEAD>
		<TITLE><? 
		
		// strip title of any tags
		// Display title
		echo preg_replace("/<[^>]+>/", "", $this->defaults['title_text']);
		
			?></TITLE>
		<style>
		BODY
		{
			font-family: verdana, sans-serif;
			font-size: 10pt;
		}

		TD
		{
			font-family: verdana, sans-serif;
			font-size: 10pt;
		}
		</style>
		<?

		// Display extra header
		echo $extra;
			
			?>
		</HEAD>

		<BODY style="margin-top: 0px; margin-left: 0px; background:#339BBE;">
		<div style="background-image: url('images/bg-repeat.gif'); background-repeat: repeat-y;"> 
		  <img src="images/top.gif" usemap="#top_map" border="0"> 
		<?
	}

	
	/* ------------------------------------------------------------------ */
	//	Displays footer.
	/* ------------------------------------------------------------------ */
	
	function show_Footer($extra = "")
	{
		// Display $extra first.
		echo $extra;

		?>
		</div>
		<img src="images/bottom.gif">
		<map name="top_map" id="top_map">
		  <area shape="rect" coords="6,19,583,52" href="index.php?page=main" alt="Admin Home">
		  <area shape="rect" coords="317,52,519,70" href="mailto: patiek@ep-dev.com" alt="Send Email">
		  <area shape="rect" coords="317,70,602,88" href="http://www.dev-forums.com" target="_blank" alt="Visit Our Support Forums">
		  <area shape="rect" coords="6,54,234,72" href="http://www.ep-dev.com" target="_blank" alt="Visit us on the web!">
		</map>
		</BODY>
		</HTML>
		<?
	}

	
	/* ------------------------------------------------------------------ */
	//	Displays bulk of page.
	/* ------------------------------------------------------------------ */

	function show_Content($content)
	{
		?>
		<div style="margin-left: 5px; margin-right: 10px; width: 710px;">
		<table>
		<tr>
		<td valign="top">
		<?

			// Display Menu
			$this->MENU->show();

			?>
		</td>
		<td  valign="top">
		<div style="margin-left: 10px;"><div style="font-weight: bold; font-size: 12pt; margin-bottom: 10px;"><? 

		// Display page title
		echo $this->defaults['title_text']; 

		?></div>
		<div><?
			
		// Display content
		echo $content; 
		
		?></div>
		</td>
		</tr>
		</table></div>
		<?
	}

	
	/* ------------------------------------------------------------------ */
	//	Construct form input in table row
	/* ------------------------------------------------------------------ */
	
	function constructTableVariable($name, $description, $var_type, $var_name, $var_value="", $size=null, $helpcode=null, $extra="")
	{
		// table within table
		$row .= "<tr>\n<td>\n<table style='width: 100%;'>\n";

		// Construct name/description/help part
		$row .= "<tr>\n<td align='top'><div><strong>{$name}</strong></div>\n<div>{$description}</div>\n";
		
		if (!empty($helpcode))
			$row .= "<div><a href='?page=FAQ&amp;topic={$helpcode}'>More Information</a></div>\n";

		// close column
		$row .= "</td>\n";

		// Construct variable

		switch($var_type)
		{
			case "text" :
				$row .= "<td align='right'>{$extra}<input type='text' name='{$var_name}' value=\"" . htmlentities($var_value) . "\"" . (!empty($size) ? "size='{$size}'" : "") . " ID='{$var_name}'></td>\n";
			break;

			case "textarea" :
				$row .= "</tr>\n</table>\n</td>\n</tr>\n<tr>\n<td>\n<table>\n<tr>\n";

				$row .= "<td align='center'><div align='left'>{$extra}</div><textarea name='{$var_name}'" 
					. (!empty($size) ? (!empty($size['rows']) ? "rows='" . $size['rows'] . "'" : "")
					. (!empty($size['cols']) ? "cols='" . $size['cols'] . "'" : "") : "")
					." wrap='off' ID='{$var_name}'>" . htmlentities($var_value) . "</textarea></td>\n";
			break;

			case "select" :
				$row .= "<td align='right'>{$extra}<select name='{$var_name}' ID='{$var_name}'>\n";
				foreach($var_value['options'] as $name_of_var => $value_of_var)
				{
					$row .= "<option value='{$value_of_var}'" . ($var_value['selected'] == $name_of_var ? "selected" : "")
					. ">{$name_of_var}</option>\n";
				}
				$row .= "</select></td>\n";
			break;
		}

		// close row
		$row .= "</tr>\n";

		// close table
		$row .= "</table>\n</td>\n</tr>";

		return $row;
	}


	/* ------------------------------------------------------------------ */
	//	Construct Output of particular format
	/* ------------------------------------------------------------------ */
	
	function constructOutput($output, $indent=0)
	{
		return "<div style='margin-left:{$indent}; font-family: verdana, sans-serif; font-size: 10pt;'>" . $output . "</div>";
	}


	/* ------------------------------------------------------------------ */
	//	Construct start of form
	/* ------------------------------------------------------------------ */
	
	function constructStartForm($page, $name="adminpanelForm", $method="POST", $url=null, $preSubmitAction=null)
	{
		if (!empty($preSubmitAction))
			$preSubmitAction = " onSubmit='" . $preSubmitAction . "'";

		if (empty($url))
			$url = basename($_SERVER['PHP_SELF']);

		return "<form name='{$name}' action='{$url}' method='{$method}'{$preSubmitAction}>\n"
				. "<input type='hidden' name='page' value='{$page}'>\n";
	}


	/* ------------------------------------------------------------------ */
	//	Construct end of form with buttons
	/* ------------------------------------------------------------------ */
	
	function constructEndForm($submitButton = "Submit", $resetButton = "")
	{
		if (!empty($submitButton))
			$subBut = "<input type='submit' value='{$submitButton}'>\n";

		if (!empty($resetButton))
			$resBut = "<input type='reset' value='{$resetButton}'>\n";

		return "{$subBut}&nbsp;&nbsp;{$resBut}</form>\n";
	}


	/* ------------------------------------------------------------------ */
	//	Displays page in one easy function so you don't have to call each
	//  function individually. Adds menu if not present.
	/* ------------------------------------------------------------------ */

	function displayPage($content, $title = NULL, $menu = NULL, $header_extra = "", $footer_extra = "")
	{
		// If menu, assign.
		if (!empty($menu))
			$this->MENU = $menu;

		// if title, assign to obj
		if (!empty($title))
			$this->defaults['title_text'] = $title ;

		// Continue to construct page.
		$this->show_Header($header_extra);
		$this->show_Content($content);
		$this->show_Footer($footer_extra);
	}

}



class EP_Dev_Forum_News_Admin_Menu_Bar
{

	/* ------------------------------------------------------------------ */
	//	Our constructor loads up default stuff.
	/* ------------------------------------------------------------------ */

	function EP_Dev_Forum_News_Admin_Menu_Bar()
	{
		// nada
	}

	
	/* ------------------------------------------------------------------ */
	//	Add Item to Menu
	/* ------------------------------------------------------------------ */
	
	function add($text, $url = "", $menu_id = 1)
	{
		// Add on text part to MenuData
		$this->MenuData[$menu_id]['item'][count($this->MenuData[$menu_id]['item'])] = $text;

		// Add on url part to MenuData
		$this->MenuData[$menu_id]['url'][count($this->MenuData[$menu_id]['url'])] = $url;
	}


	/* ------------------------------------------------------------------ */
	//	Remove item from menu
	/* ------------------------------------------------------------------ */
	
	function remove($key = false, $text = false, $url = false, $menu_id = 1)
	{

		// Check if searching by text
		if ($text)
		{
			$key = array_search($text, $this->MenuData[$menu_id]['item']);
		}

		// Check if searching by url
		if ($url)
		{
			$key = array_search($url, $this->MenuData[$menu_id]['url']);
		}

		// remove key
		if ($key !== false)
		{
			unset($this->MenuData[$menu_id]['item'][$key]);
			unset($this->MenuData[$menu_id]['url'][$key]);
		}

	}

	
	/* ------------------------------------------------------------------ */
	//	Remove all menus
	/* ------------------------------------------------------------------ */
	
	function remove_all()
	{
		// Remove all menus
		unset($this->MenuData);
	}


	/* ------------------------------------------------------------------ */
	//	Display Menu
	/* ------------------------------------------------------------------ */
	
	function show($menu_id = 0)
	{
		// Do a loop to display either all ids (if menu_id = 0), or one id ($menu_id).
		for ($i = ($menu_id ? $menu_id : 1); $i < ($menu_id ? ($menu_id + 1) : (count($this->MenuData)+1)); $i++)
		{

			?>
			<div style="width: 151px; background-image: url('images/menu-bg.gif'); background-repeat: repeat-y; font-weight: bold;">
				<img src="images/menu-top.gif">
				<div style="margin-left: 5px;"><?
				
			// Cycle through all entries for this menu.
			for ($j=0; $j < count($this->MenuData[$i]['item']); $j++)
			{
			
				// If a url for item exists, then make item a link
				if (!empty($this->MenuData[$i]['url'][$j]))
				{
					echo "- <a href=\"".$this->MenuData[$i]['url'][$j]."\">".$this->MenuData[$i]['item'][$j]."</a>";
				}

				// Else make it a Menu Header / Category
				else
				{
					echo $this->MenuData[$i]['item'][$j];
				}

				echo "<br>";
			}
		
			?></div>
				<img src="images/menu-bottom.gif"></div><br>
			<?

		}
	}


	/* ------------------------------------------------------------------ */
	//	Create blank menu
	/* ------------------------------------------------------------------ */
	
	function blank($title = "Login")
	{
		// Remove all other menus
		$this->remove_all();

		// Make custom menu
		$this->add($title, "");
	}
}


class EP_Dev_Forum_News_Admin_Error_Handle
{
	/* ------------------------------------------------------------------ */
	//	Stop with $code
	//  Dies with error ouput
	/* ------------------------------------------------------------------ */

	function stop($code)
	{
		$this->kill($this->go($code));
	}


	/* ------------------------------------------------------------------ */
	//	Go $code
	//  Returns textual error based on $code
	/* ------------------------------------------------------------------ */
	
	function go($code, $extra = NULL)
	{
		switch ($code)
		{
			case "mysql_connect_error" : 
				$return = "Error connecting to mysql database with username and password specified.";
			break;

			case "mysql_db_error" : 
				$return = "Error connecting to specified database name.";
			break;

			case "invalid_number" : 
				$return = "ERROR: Invalid number specified for " . $extra;
			break;

			case "invalid_bool" : 
				$return = "ERROR: Invalid value specified for " . $extra;
			break;

			case "bad_permissions" : 
				$return = "ERROR: Could not open or write to file the new settings. Check file permissions section of trouble shooting.";
			break;

			case "bad_login" :
				$return = "Please enter correct username and password!";
			break;

			case "panel_disabled" :
				$return = "The administration panel has been disabled from within the configuration file.";
			break;

			default : $return = "An unknown error occurred!";
		}

		return $return;
	}


	/* ------------------------------------------------------------------ */
	//	Kill with $error
	//  dies with textual $error
	/* ------------------------------------------------------------------ */
	
	function kill($error)
	{
		// new display obj
		$display = new EP_Dev_Forum_News_Admin_Display("ERROR");

		// display page
		$display->displayPage($error, "ERROR -- " . $error);

		die();
	}
}