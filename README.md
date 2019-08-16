SAK4WP - Swiss Army Knife for WordPress
=======================================

Swiss Army Knife for WordPress is a standalone tool which allows you to perform some recovery operations on your WordPress site.
This tool is intended to be used for a short time only and then removed in order to prevent security issues.
The tool records the current IP address and browser info and will not work with from any other IP or browser.

Installation
------------

Upload !sak4wp.php in the folder where your WordPress installation is.
Then open your browser and navigate to http://yoursite.com/!sak4wp.php

Note: The file name is intentionally prefixed by an exclamation mark (!) to make sure the file appears first in the list of files, even above .htaccess.
That way you should be able to see it in case you forget the file.


Features
--------
- Dashboard - Shows some description about the app.

- Self contained. 
	You just need to upload one file. It outputs JavaScript and CSS files however returns header not modified to make the page load faster.

- Built-in Self Destroy mechanism (must be user triggered) -> the script will try to remove itself. 
	If that is not accessible (due to permissions issues). The user must delete the !sak4wp.php file manually 
	(e.g. by using an FTP client or accessing the file from a control panel).

- When data is shown in tables the currently viewed row is nicely highlited.

- Shows User and Server's IP

- Easy to Use


Modules
-------

Limit Login Attempts related features
- Allows you to Unblock yourself or somebody else from Limit Login Attempts plugin
- Added a link to a whois service to see more info about the blocked IP.

Stats Module
- It shows WordPress site stats e.g. number of posts, pages, users, attachments, revisions etc. Numbers are nicely formatted.
- Db version info, db config details parsed from wp-config.php
- Shows if you are running an older or the latest WordPress version. Links to update and to the latest wp are provided. 
- Shows site's disk space usage (du -sh linux only)
- Shows total server's disk usage with nice coloring on the total disk size, usage and free space (df -h linux only)
- Shows top processes (top -b -n 1 linux only)
- Shows free memory (free -m linux only)
- Lists top 15 larger files (linux only)
- Shows phpinfo

Sice v1.1.5 
- Showing wp base dir.
- Detect if wp caching is enabled
- Check if caching is in within WP base dir.
- Showing load average
- Using Orbisius_WP_SAK_Util::m() to output some simple error messages


List Page Templates
- This module allows you to see which page templates are use by your pages.
 
.htaccess
- This module allows you to protect WordPress admin area by creating an .htaccess and .htpasswd files.
- Protects wp-login.php too 
- User and Passwords can be auto-generated
- TODO: check a list of sites if they have successfully protected their wp-admin folder
- TODO: cookie remember pwd?
- TODO: Update .htaccess files
Note: This will stop affect WordPress registrations because WP uses wp-login.php for registrations.
You can overcome that if you use a plugin that handles the registrations without relying on wp-login.php file.
- offer zip archive
- prefill db account on db dump screen

Locate WordPress
- This module searches for local WordPress installations (linux only). It starts from a specified folder and shows the found WordPress installations and their versions.
Useful if you manage multiple WordPress sites and want to make sure all of them are running the latest WordPress version.
It also allows you to find old/forgotten WordPress installations. If the version cannot be detected it will show 0.0.0
The versions that are older will be shown in red, the up-to-date ones will be shown in green.
TODO: autosuggest?

Plugin Manager
- This module allows you to manage plugins: bulk download, install, and activate plugins. TODO: (de)activate, delete. 
Just enter the plugin's WordPress.org page or a zip file location (web) and the plugin will be downloaded and extracted into wp-content/plugins directory.
The plugin will show which links are skipped and if they were successfully extracted. The file size of the downloaded file is shown.
The plugin directory where the download plugin will be extract is shown. Warning: If the plugin already exists its files will be overridden! 
SAK4WP checks the remote file size before downloading the file. Plugins files larger than the limit (10MB) will be not be downloaded.
Temporary files from the download are deleted later so they don't take too much space in the TMP folder which should be cleaned on reboot anyways.
Plugin list can be retrieved from a text or HTML file.

User Manager
This module allows you to see user account, user meta info, to log in as a user without knowing their password. TODO: Create, delete users.
Administrators accounts are highlighted.

Self Protect
This module allows you to run SAK4WP more securely. The first time you access SAK4WP, it will save your IP and browser info.
If somebody else accesses the file from a different IP or browser, he/she will be stopped.
The module doesn't require any configurations. It is always run before other modules.

PostMeta
The PostMeta module retrieves meta information for a given post/page or multiple posts, pages separated by comma
The meta information also contains post and author info.
The search can also be done using post/page slug.
Video demo: https://www.youtube.com/watch?v=GnrOLxsMhK4&feature=youtu.be

Site Packager
- Export the whole site in .tar or .tar.gz format with a single click
- Can run in background (linux only)
- Shows disk usage.
- Automatically skips some files (logs, cache files etc, previously created archives by SAK4WP)
- TODO: (o) backup only current WordPress site. i.e. wp-* + index.php + .htaccess all else no


Db Export
- This module allows you to dump the database into a nice sql file (gzip compression optional) and provides download links.
- Can run in background (linux only)
- Parse wp-config.php line by line and not using regex to avoid any commented out lines
- Backup only current site's tables.

Addons
Added !sak4wp-theme-troubleshooter.php plugin. For more info see addons/!sak4wp-theme-troubleshooter.php

Troubleshooting
If you see an error e.g. SAK4WP: Error this could be caused because you're accessing the !sak4wp from a different IP address or web browser. Because the tool gives enormous control it only allows connections from the same brower and IP address. If any of these are different you'll see the error message.

if you need to access the SAK4WP you can delete this file: /wp-content/uploads/.ht-sak4wp-yoursite.com


Please fork and improve!

Future Ideas / TODO
--------------------
- Make it not to require WordPress to be present -> e.g. it can download and install WP
- Manage Plugins - Activate/Deactivate/Deploy
	Service to hold different plugin plugin configuration options e.g. a list of faviourite plugins
- Manage Users - add, delete, update, import
- Manage Content - add, delete, update, create sample pages e.g. Home, Services, Contact, About etc.
- Clean DB - removes all content, attachments, menus, plugins -> show checkboxes so the user decides what to delete.
- Download stats (as HTML, TEXT, PDF?)
- Email stats
	This may require some inline CSS in the email because lots of mail providers will remove most of the CSS
- Detect if a WordPress plugin crashed e.g. by setting a custom error function and raising the error reporing level.
	if there is a fatal error coming from /wp-content/plugins/PLUGIN-SLUG/ then deactivate that plugin either by updating 
	the active plugins array or renaming the plugin's folder.
- Use TMP folder to set the time when it was last used. If the app is hasn't been used for more than 2-3 hours -> self destroy
- Automatic updater -> check the latest version at git hub and override itself
- Create/modify child themes
- Create a nice filemanager
- Simple IDE - rich text or simple text to create txt or HTML files
- Use files to setup some encrypted PWD in case the user decides to keep using the script.
- Download any file from WP or ZIP a folder
- Setup a service that will accept ftp details and will upload the !sak4wp on any hosting
- Backup -> Files + Db; Send to Dropbox
- Scan the folders for other WP installations -> manage multiple WP installs from 1 copy of the program or copy/move itself to another folder/site
- DONE: Protect WP admin directory (basic auth)
- Check if WP is setup to use https .. and SAK4WP is not accessed via https -> show a warning or redirect
- Add a favicon
- Add site title to the SAK4WP so when printting content it'll show the tested site
- Log viewer -> scan for *.log and either offer them for download or show last 20 lines
- The WP install scanner to show plugin versions and if they are up-to-date (Thanks Seb for the idea)
- If WP-CLI is available enable commands such as update: core, plugins, themes
- Mass site and db pkg
- Detect and function to remove wp maintenance file
- Detect wrong db and offer to help or don't start in full mode.
- In users module -> search and load users by role, user, email etc.
- Parse wp config...
- Download and unzip remote files from old hosting.
- Generate hash of files and check if all were correctly transferred.
- Module: File Manager -> upload/download files, zip/unzip, (un)tar, check for large files, color large files with red > 1GB, process line per line
list archive contents
- do a scan ... wp core checksum -> check if there files other than images, docs in wp-content/ and uploads.
- show php RC and ini folder for quick inspection.
- load error if exists -> offer for download (>1mb) or visualize

	<?php
	set_time_limit(5*60);
	?>
	<pre>
	<?php
		// > 100K
		//echo `find /doc_root_dir/ -type f -size +100k -exec ls -lh {} \; | awk '{ print $9 ": " $5 }'`;
		
		// > 50MB
		echo `find /doc_root_dir/ -type f -size +50000k -exec ls -lh {} \; | awk '{ print $9 ": " $5 }'`;
	?>

	it seems the syntax differs for differet OSs
	http://www.cyberciti.biz/faq/find-large-files-linux/
	
	download can be limited with this
		
	# BEGIN BackUpWordPress
	# This .htaccess file ensures that other people cannot download your backup files.

	<IfModule mod_rewrite.c>
		RewriteEngine On
		RewriteCond %{QUERY_STRING} !key=26xafasfasfasff09e5
		RewriteRule (.*) - [F]
	</IfModule>

	# END BackUpWordPress


Requirements
------------

- Installed and configured WordPress
- FTP client to upload SAK4WP
- Reminder to remove the SAK4WP after use to prevent being hacked.
- php_curl extension to be available


Support
-------

Help Videos

Check out this YouTube playlist: http://www.youtube.com/playlist?list=PLfGsyhWLtLLiiU_wvXdOUBvBAXEZGrZfw



FAQ
-------

Q1: I am getting SAK4WP: Error
A1: 
If you're getting SAK4WP: Error this means that the tool cannot verify you.
It intentially doesn't provide more info in case the tool is accessed by a hacker.
The reason for the error could be that your IP has changed and/or you're using a different browser etc.

Solution: using (S)FTP go to wp-content/uploads and and delete a file that starts with ".ht-sak4wp-"
This should allow give you access.


Premium Support
---------------

If you want to get premium support for this product contact us to discuss your needs and a possible estimate.

http://orbisius.com/site/contact/


Disclaimer
----------

By using this script you take full responsibility to remove it after the work is complete.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

Author
------

Svetoslav (Slavi) Marinov | twitter: http://twitter.com/orbisius | http://orbisius.com
