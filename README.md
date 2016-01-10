NOTE: This is an old project, kept here for archival purposes. The core of the code is over 10 years old with only minor updates.

EP-Dev Forum News
===============
This PHP script is designed to take forums posts from  one or more forum categories and arrange them in a news or blog format.  Currently it works with vBulletin, Invision Power Board (ipb), Web  Burning Board (wbb), YaBB SE, Simple Machines Forum (SMF), MyBB, phpBB, and RSS/XML sources. It will display a customizable number of posts on a page as well as provide recent headlines from one or multiple forum categories and multiple forum installations. Each post display may contain title, text, comment number,  author, date, avatar, etc. It will automatically detect and display all smilies/emoticons supported by the forum software as well as recognize popular bbcode.

All aspects of the script can be easily edited from within an Admin Control Panel.

DOWNLOAD
===============
Stable Version (recommended): https://github.com/patiek/epdev-forumnews/archive/stable.zip

Development Version: https://github.com/patiek/epdev-forumnews/archive/master.zip

INSTALL
===============
## Quick Installation
1. Upload entire forum-news folder to your website.
2. You must chmod 0666 (read and write all) the files news.php, config/config.php, 
  and config/template.php. Chmod the cache folder to 0777 (read/write/execute all). This 
  can usually be done in your ftp program by right clicking on the file/folder.
3. Visit admin/index.php in your web browser.
4. Since this is your first time logging in, login by typing &quot;admin&quot; 
  into the username field and leaving the password field blank.
5. You will be taken to the change username and password page. Please change 
  your username and password at this time.
6. Login again to the Administration Panel and continue through the steps listed 
  on the main page. The last step should be the Generate Code step, where code 
  is generated for you to place on the page you want to display news and/or headlines 
  on.

## Advanced Installation
IF YOU ARE NOT USING THE ADMINISTRATION PANEL, YOU SHOULD DELETE the admin/ folder!
Before you begin: Chmod cache folder to 0777 if you plan to use cache.
1. Edit config/config.php file with your database settings, urls, and default category ids of your forum that you want the news script to pull news from.
2. Edit the $this->absolute_path in news.php, setting it to your absolute path value.
3. Edit config/template.php and format it to how you want your news to be displayed

4. Insert the following under the <body> tag of any pages you wish for news or headlines to be displayed on:
<?php include_once("/absolute/path/to/forum-news/news.php"); ?>

5. To display news on your page, insert the following:
<?php $forum_obj = new EP_Dev_Forum_News();
$forum_obj->display_News(); ?> (you can also specify non-default ids and news numbers... see examples.php for examples).

6. To display headlines on your page, insert the following:
<?php $forum_obj = new EP_Dev_Forum_News();
$forum_obj->display_News(); ?> (you can also specify non-default ids and news numbers... see examples.php for examples).

The display_News() accepts four arguments:
$forum_obj->display_News(NUMBER TO DISPLAY, FORUM CATEGORY IDS, FORUM IDS, TEMPLATE IDS);

For example: $forum_obj->display_News("10", "2", "0", "2"); would display 10 news items from category id 2 of forum 0 (in config.php) using template 2 (from template.php).

Additionally, any argument can also be left out: $forum_obj->display_News("", "", "", "2"); would display the default number of news items of all default categories of all default forums using template 2.

This is how you would display multiple forums using default categories: $forum_obj->display_News("", "", "2,3", ""); would display forum 1 and forum 2 using their respective default categories with the default template and the default number of news items.

You may also want to display specific category numbers and specific forums. This can be accomplished using the | and the , where | separates forums and , separates category ids. A good example then is $forum_obj->display_News("", "1,2,3|2,3,8,4", "0,2", ""); where we are pulling both forum 0 and forum 2, but for forum 0 we are getting posts from categories 1, 2, and 3 while in forum 2 we are getting posts from categories 2, 3, 8, and 4.

To sum up, I will give you perhaps the most complicated example you will run into:
$forum_obj->display_News("23", "1,2,3|2,3,8,4|8|3,2", "0,2,1,4", "1"); would display 23 news items total. The script will be pulling posts from categories 1, 2, and 3 for forum 0, categories 2, 3, 8, and 4 for forum 2, category 8 for forum 1, and categories 3 and 2 for forum 4. The script will be using template 1 for the output. As you can see, the order of forums or categories does not matter.

The display_Headlines() uses the exact same methods as display_News(). Therefore all the above is applicable to display_Headlines() too! Like all EP-Dev scripts, it is all very feature rich, with the ability to be used by both the novice as well as those who require advanced features. If the above isn't enough customization for you, open up news.php and take a look at the news() function.

What's New in Version 2.0
===============
* Added full cache support. The script can now use output that has been cached
  instead of having to pull all the news from the database every time. This of course
  speeds up the script and saves on many database queries. The cache time can be set
  from within the Admin Panel.<br>
* Multiple Forum Support: The script can now pull posts from an infinite number 
  of forums. Additionally, these forums can be of mixed and matched types. This 
  means that it is now possible to pull posts from Invision Power Board and vBulletin 
  and display by date as news.<br>
* Multiple Layout Support: Multiple layouts can now be handled by the script. 
  This means that completely separate layouts (all templates including forum post, 
  headlines, bbcode, ect.) can now be entered in. Combined with multiple forum 
  support, this opens up the awesome possibility of pulling news from multiple 
  forums of different sites and displaying the combined news across an array of 
  sites in that site's layout.<br>
* Added Avatar Support, new BBcode recognition, as well as new forum support!.
* Added basic RSS Support. The script can now pull from rss feeds.

TODO
===============
* Add support for [list] bbcode.
* Add support for post limits per forum and category.
* Add support for fetch restriction by author.

Old Version History
===============
2.24 - October 16, 2006:
FIXED BUG: A bug preventing multi-forum support for non-default forums has been fixed.

2.23 - October 13, 2006:
FIXED BUG: A missing upgrade link in the upgrade core for 2.21 -> 2.22 has been fixed.
FIXED BUG: A bug that was preventing some proper usage of multiple categories has been fixed (thanks ecko! @ hl2grounds.net).
FIXED BUG: A vBulletin 3.x bug that displayed old avatars (outdated) for users has been fixed (thanks ecko! ... again).

2.22 - October 11, 2006:
FIXED BUG: Fixed Invision Power Board Forum access class naming bugs (2.0, 2.1 were affected).

2.21 - October 7, 2006:
FIXED BUG: Fixed PHP 4.x bug that prevented proper RSS / XML support.
IMPROVED: Improved code in WoltLab_Burning_Board_Lite_1 for thread link (unreachable code).
FIXED BUG: Fixed a bug that caused enabled but inactive forums to be called when forum 0 alone was called (http://www.dev-forums.com/index.php?showtopic=229).
IMPROVED: Improved some logic in error output where script no longer kills PHP on certain errors (such as no posts in category).
ADDED FEATURE: Added support for Invision Power Board (IPB) 2.1.x. The 2.0.x module broke the smilies of 2.1.x.
ADDED FEATURE: The script can now use the cache on file when the data source is down. This will allow websites to continue operating even if, for example, MySQL is offline.
IMPROVED: Modified connection settings of all data sources. MySQL connections are now only established when they are needed.
FIXED BUG: A bug resulting in excessive cache writing when cache is disabled has been fixed.
IMPROVED: The character limit now detects word boundaries and does not cut off in the middle of a word.
IMPROVED: Improved line break fix feature to retain case and be case insensitive.

2.20 - June 29, 2005:
IMPROVED: Added error for trying to use sources that are disabled in the script.
IMPROVED: Added !!SOURCE_ID!! to the recognizable tags for templates. This tag will report the source id, as recognized by the script.
IMPROVED: Modified phpBB 2.x class to allow for custom user prefix setting (you have to manually edit file). This allows for PHP-Nuke users to use the script as nuke overrides phpBB's default user table. Updated FAQ concerning this matter.
IMPROVED: Modified admin panel absolute_path and forum_url restrictions to allow for override of invalid formats (allows for people to use CMS scripts such as PHP-Nuke). A javascript confirm dialog is now invoked instead of an automatic alert (and fail) box.
ADDED FEATURE: Added "Fix Line Breaks" feature in script settings to format <br> into <br />.
IMRPOVED: Modified Invision Power Board 1.3x class to fix line breaks automatically to be valid XML.
IMPROVED: Improved update process with automatic updater incorporated into the admin panel.
IMPROVED: Improved input/output configuration functions of admin panel.
IMPROVED: Added some better visuals to control panel to be more user friendly.
ADDED FEATURE: Script can now pull and use basic RSS / XML feeds from other sites as news or headlines.
ADDED FEATURE: Added ability to use different databases / sources for news. No databases except for MySQL are supported however.
FIXED BUG: Fixed a potential bug in admin/display.php of the administration panel's handling of html entities that could result in being unable to modify variables from the admin panel.
FIXED BUG: Fixed potential problems in parsing bb code URLs and other tags utilizing [tag=][/tag] that contain quotes (ex: [url="http://"]site[/url]) in classes/display.php (http://www.dev-forums.com/index.php?showtopic=145).
FIXED BUG: A bug in the display_Headlines function causing improper handling of headlines has been fixed (http://www.dev-forums.com/index.php?showtopic=132).

2.11 - January 30, 2005:
IMPROVED: Check for Update admin panel page now utilizes the new version of EP-Dev Updater. Links to downloads and information on new versions of the software are now displayed within the admin panel's update page.
ADDED FEATURE: Script now supports My Bulletin Board (MyBB) for its current version of this release (1.0 RC4).

2.10 - January 9, 2005:
ADDED FEATURE: Added true caching support. Cache time can be set form within admin panel. (cache time in minutes)
FIXED BUG: Fixed a major bug that could load incorrect values if admin panel not used. (wrong checks on ids & forums)
ADDED FEATURE: Added example file of RSS code and template code (template 3).
FIXED BUG: Added missing date code link on the admin panel templates page.
FIXED BUG: Fixed a bug that caused post number error to be displayed when post number not set.

2.02 - January 5, 2005:
IMPROVED: Check For Update admin panel page now displays script's version number.
FIXED BUG: Fixed a bug that allowed admin panel to display html tags in header's title.
FIXED BUG: Fixed a bug that prevented the auto-linking of multiple URLs due to regex.
FIXED BUG: Added specific error reporting levels to prevent NOTICE disruptions.

2.01 - January 4, 2005:
FIXED BUG: Fixed broken character limit feature caused by improper argument order. (Thanks to Essie for notifying me).

2.0 - December 29 2004:
ADDED FEATURE: Added ability to automatically check for new script versions on login to the control panel.
IMPROVED: More BBcode supported for many forums.
ADDED FEATURE: Added global support for [php] bbcode.
ADDED FEATURE: Added support for WoltLab Burning Board Lite 1.0.
ADDED FEATURE: Added support for Invision Power Board (IPB) 2.0.
ADDED FEATURE: Added avatar support.
ADDED FEATURE: Added support for Simple Machines Forum (SMF) 1.0.
FIXED BUG: Fixed bug in handling of html special characters in templates resulting in invalid xhtml / html 4.01 code.
IMPROVED: Improved handling of bbcode.
FIXED BUG: Fixed bug in auto-linking that may have allowed for linking of urls that had already been linked.
FIXED BUG: Fixed bug in Invision Board 1.3 class in MySQL select pulling threads improperly.
ADDED FEATURE: Added ability to turn general BB code parsing on and off.
ADDED FEATURE: Multiple templates can now be set, allowing for one script to display data in different templates (aka different layouts).
ADDED FEATURE: Multiple Forums can now be used at one time. News can be pulled from multiple forums into news display, multiple forums can display as different news, ect. all with just one script install.
ADDED FEATURE: In some circumstances posts can now be recycled witihn execution, saving on database queries. Actual caching support is planned to be implemented in the future.
IMPROVED: Complete rewrite of code. Script is now object oriented, allowing for more flexibility and much stronger manipulation.

1.4 - August 2 2004:
FIXED BUG: Fixed bug of vBulletin v2 possibly showing wrong post for given thread on some installations.
IMPROVED: Many files can now be renamed.
IMPROVED: Added much better comment structure to news.php file. Almost everything commented now!
ADDED FEATURE: Option to auto-link posted URLs.
IMPROVED: [highlight] bbcode now recognized in vBulletin.
IMPROVED: Code re-write of post parsing function. More tags recognized in news post layout, comments link layout, and author link layout.
IMPROVED: Combined template configuration into one easy file.
IMRPOVED: Renamed globals to free $TEMPLATES global.
ADDED FEATURE: Added Administration Panel. No more editing files manually! EVERYONE better use it because it sure took a few days of coding.
FIXED BUG: Fixed bug of vBulletin v3 not recognizing database prefix.

1.3 - March 27 2004:
IMPROVED: Improved bbcode logic on all forums to pick up on [code] bbcode. Template for it can be edited in tags.php
FIXED BUG: Fixed a bug with bbcode [quote] tag where TEMPLATES['QUOTES'] (in tags.php) was appending '!!' to the end of the actually quote.
FIXED BUG: Fixed bug in parsing headlines with smilies where smilies would not be run through the smilie_error_fix, even if the value was set to true.
ADDED FEATURE: YaBB SE v1.5.x is now supported!
ADDED FEATURE: Web Burning Board (wbb) v1.2 is now supported!
IMPROVED: Read More links (both cut off link character link & normal cut off link) is now editable from templates/tags.php file.
IMPROVED: Comments link is now fully editable from template/tags.php file.
IMPROVED: Author link is now fully editable from template/tags.php file.

1.2 - March 21 2004:
FIXED BUG: Both Uppercase and Lowercase BB codes now supported for all forum types.
ADDED FEATURE: Invision Board 1.3 is now supported!

1.1 - No Public Release (N/A):
FIXED BUG: Updated smilies to detect https:// to avoid smilie link problems.
FIXED BUG: Smilies now look for http:// instead of http, as a site containing http will end up getting errors.
FIXED BUG: Smilies are now sorted to avoid smilie errors resulting from smilies containing other smilies. You can toggle this feature on / off in config.inc.php, as you may wish to disable the sort to make the script faster.

1.0 - December 7 2003: First Release, everything is new.
