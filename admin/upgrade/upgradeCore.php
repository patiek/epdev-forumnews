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


/* ------------------------------------------------------------------ */
//	Upgrade Core Class
//  Contains upgrade functions related to upgrading configuration of 
//	the script.
/* ------------------------------------------------------------------ */

class UpgradeCore
{
	var $adminPanel;

	function UpgradeCore($adminPanel)
	{
		$this->adminPanel = $adminPanel;
	}


	function navigate($old_version, $new_version)
	{
		switch($_REQUEST['page'])
		{
			// +------------------------------
			//	UPGRADE PROCESS
			// +------------------------------

			case "goUpgrade" :
				$this->upgradeProcess($_REQUEST['type'], $new_version);
				$this->adminPanel->DISPLAY->MENU->blank();
				$this->adminPanel->page_Message("Upgrade Complete", $this->adminPanel->DISPLAY->constructOutput("The upgrade has completed. NOTE: Your absolute path has been reset.<br><br>Please <a href='" . basename($_SERVER['PHP_SELF']) . "'> continue to the admin panel</a>."));
			break;

			
			// +------------------------------
			//	Main Upgrader Page
			// +------------------------------
			
			default : 
			switch($old_version)
			{
				case "2.10" :
				case "2.1" :
				case "2.11" :
					$type = "2.11";
					$this->defaultUpgradePage($old_version, $new_version, $type);
				break;

				case "2.20" :
					$type = "2.20";
					$this->defaultUpgradePage($old_version, $new_version, $type);
				break;
				
				case "2.21" :
					$type = "2.21";
					$this->defaultUpgradePage($old_version, $new_version, $type);
				break;
				
				case "2.22" :
					$type = "2.22";
					$this->defaultUpgradePage($old_version, $new_version, $type);
				break;
				
				case "2.23" :
					$type = "2.23";
					$this->defaultUpgradePage($old_version, $new_version, $type);
				break;

				default : die("No Upgrade Found.");
			}
		}
	}



	/* ------------------------------------------------------------------ */
	//	Default Upgrade Page
	//	Displays generic upgrade page from $old_version to $new_version.
	/* ------------------------------------------------------------------ */
	
	function defaultUpgradePage($old_version, $new_version, $type)
	{
		$formURL = basename($_SERVER['PHP_SELF']);

		// default upgrade page.
			$this->adminPanel->DISPLAY->MENU->blank();
			$message = $this->adminPanel->DISPLAY->constructOutput("You are about to begin the process of upgrading from version {$old_version}
			to version {$new_version}. Please follow any on-screen instructions.<br><br>
			<form name='upgradeForm' action='{$formURL}' method='post'>
				<input type='hidden' name='page' value='goUpgrade'>
				<input type='hidden' name='type' value='{$type}'>
				<div align='center'><input type='submit' value='Continue Upgrade'></div>
			</form>
			");

			$this->adminPanel->page_Message("UPGRADE :: From version {$old_version} to version {$new_version}", $message);
	}


	/* ------------------------------------------------------------------ */
	//	Upgrade Process
	//	The part of this script that actually modifies the files (upgrades).
	/* ------------------------------------------------------------------ */
	
	function upgradeProcess($old_version, $new_version)
	{
		$current = $old_version;

		$this->clearAbsolutePath();

		// detect newline type
		$newLine = "
		";
		$newLine = str_replace("\t", "", $newLine);

		while ($current != $new_version)
		{
			switch($current)
			{



				// +------------------------------
				//	Version 2.1x -> 2.20
				// +------------------------------

				case "2.11" :
				// +------------------------------
				//	Add new configuration
				// +------------------------------

				for($i=0; $i<count($this->adminPanel->CONFIG->FORUM); $i++)
				{
					$search["FORUM__{$i}__newConfig"] = "\$this->FORUM[{$i}]['username']";
					$replace["FORUM__{$i}__newConfig"] = "\$this->FORUM[{$i}]['dbtype'] = \"mysql\";{$newLine}";
					$replace["FORUM__{$i}__newConfig"] .= "\t\t\$this->FORUM[{$i}]['source'] = \"Forum\";{$newLine}";
					$replace["FORUM__{$i}__newConfig"] .= "\t\t\$this->FORUM[{$i}]['username']";

					$filenames["../config/config.php"][$i] = "FORUM__{$i}__newConfig";
				}

				// perform search and repalce
				$this->rawReplace($search, $replace, $filenames);

				unset($search);
				unset($replace);
				unset($filenames);


				// replace at $this->SCRIPT['files']['mysql'] variable
				$search['newfilePaths'] = "\$this->SCRIPT['files']['mysql'] = \$this->SCRIPT['folders']['classes']";

				// add new RSS and ACCESS paths as well as modify mysql variable
				$replace['newfilePaths'] = "\$this->SCRIPT['folders']['access'] = \$this->SCRIPT['folders']['classes'] . \"access/\";{$newLine}";
				$replace['newfilePaths'] .= "\t\t\$this->SCRIPT['files']['rss'] = \$this->SCRIPT['folders']['access'] . \"rss.php\";{$newLine}";
				$replace['newfilePaths'] .= "\t\t\$this->SCRIPT['files']['mysql'] = \$this->SCRIPT['folders']['access']";

				$filenames['../config/config.php'][0] = "newfilePaths";

				// new source path
				$search['newsourcePath'] = "\$this->SCRIPT['folders']['forums'] = \$this->SCRIPT['folders']['classes'] . \"forums/\";";

				$replace['newsourcePath'] = "\$this->SCRIPT['folders']['forums'] = \$this->SCRIPT['folders']['classes'] . \"sources/\";";

				$filenames['../config/config.php'][1] = "newsourcePath";


				// new line break feature
				$search['newlineBreak'] = "\$this->NEWS['recycle_time']";
				$replace['newlineBreak'] = "\$this->NEWS['fix_linebreaks'] = true;{$newLine}";
				$replace['newlineBreak'] .= "\t\t\$this->NEWS['recycle_time']";

				$filenames['../config/config.php'][2] = "newlineBreak";


				$this->rawReplace($search, $replace, $filenames);


				// +------------------------------
				//	Modify Version Number to reflect new version
				// +------------------------------
				$this->modifyVersion("2.20");
				$current = "2.20";

				break;




				// +------------------------------
				//	Version 2.20 -> 2.21
				// +------------------------------

				case "2.20" :
				// +------------------------------
				//	Replace old IPB version numbers
				// +------------------------------

				// old 2.0 format
				$search["IPB_New_Version"] = "['type'] = \"Invision_Power_Board_2\";";
				
				// new 2.0 format
				$replace["IPB_New_Version"] = "['type'] = \"Invision_Power_Board_2.0\";";
				
				// config file
				$filenames["../config/config.php"][0] = "IPB_New_Version";
				
				
				// failsafe feature
				$search['failSafe'] = "\$this->NEWS['recycle_time']";
				$replace['failSafe'] = "\$this->NEWS['failsafe_recycle'] = false;{$newLine}";
				$replace['failSafe'] .= "\t\t\$this->NEWS['recycle_time']";

				$filenames["../config/config.php"][1] = "failSafe";

				// perform search and repalce
				$this->rawReplace($search, $replace, $filenames);

				unset($search);
				unset($replace);
				unset($filenames);


				// +------------------------------
				//	Modify Version Number to reflect new version
				// +------------------------------
				$this->modifyVersion("2.21");
				$current = "2.21";

				break;
				
				
				// +------------------------------
				//	Version 2.21 -> 2.22
				// +------------------------------

				case "2.21" :
				
				// only file changes, no config interaction

				// +------------------------------
				//	Modify Version Number to reflect new version
				// +------------------------------
				$this->modifyVersion("2.22");
				$current = "2.22";

				break;
				
				
				// +------------------------------
				//	Version 2.22 -> 2.23
				// +------------------------------

				case "2.22" :
				
				// only file changes, no config interaction

				// +------------------------------
				//	Modify Version Number to reflect new version
				// +------------------------------
				$this->modifyVersion("2.23");
				$current = "2.23";

				break;
				
				
				// +------------------------------
				//	Version 2.23 -> 2.24
				// +------------------------------

				case "2.23" :
				
				// only file changes, no config interaction

				// +------------------------------
				//	Modify Version Number to reflect new version
				// +------------------------------
				$this->modifyVersion("2.24");
				$current = "2.24";

				break;



			}
		}
	}


	/* ------------------------------------------------------------------ */
	//	Raw Replace
	//
	//	Allows for one to replace raw data (not necessarily config data).
	//	$filenames contains array with filename as key and vars as identifiers
	//	of keys in $search and $replace.
	/* ------------------------------------------------------------------ */
	
	function rawReplace($search, $replace, $filenames)
	{

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


	/* ------------------------------------------------------------------ */
	//	Modify Version
	//	Updates configuration file's version to $new_version
	/* ------------------------------------------------------------------ */

	function modifyVersion($new_version)
	{
		$modifyConfigArray = array();

		$modifyConfigArray['adminpanel_filename'] = "../config/config.php:::SCRIPT__version";
		$modifyConfigArray['adminpanel_class'] = "CONFIG";
		$modifyConfigArray['SCRIPT__version'] = $new_version;
		$modifyConfigArray['adminpanel_rules'] = "SCRIPT__version,string";

		$this->adminPanel->ModifyConfig($modifyConfigArray);
	}


	function clearAbsolutePath()
	{
		$modifyConfigArray = array();

		$modifyConfigArray['adminpanel_filename'] = "../config/config.php:::SCRIPT__absolute_path";
		$modifyConfigArray['adminpanel_class'] = "CONFIG";
		$modifyConfigArray['SCRIPT__absolute_path'] = "";
		$modifyConfigArray['adminpanel_rules'] = "SCRIPT__absolute_path,string";

		$this->adminPanel->ModifyConfig($modifyConfigArray);
	}
}
