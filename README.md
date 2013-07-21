SAK4WP - Swiss Army Knife for WordPress
=======================================

Swiss Army Knife for WordPress is a standalone script which allows you to perform some recovery operations on your WordPress site.
This script is intended to be used for a short time only and then removed in order to prevent security issues.

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


Modules
-------

Limit Login Attempts related features
- Allows you to Unblock yourself or somebody else from Limit Login Attempts plugin
- Added a link to a whois service to see more info about the blocked IP.

Stats Module
- It shows WordPress site stats e.g. number of posts, pages, users, attachments, revisions etc. Numbers are nicely formatted.
- Db version info, db config details parsed from wp-config.php
- Shows if you are running an older or the latest WordPress version. Links to update and to the latest wp are provided. 
- Disk space usage (linux only)

List Page Templates
- This module allows you to see which page templates are use by your pages.
 
.htaccess
- This module allows you to protect WordPress admin area by creating an .htaccess and .htpasswd files.
- Protects wp-login.php too 
Note: This will stop affect WordPress registrations because WP uses wp-login.php for registrations.
You can overcome that if you use a plugin that handles the registrations without relying on wp-login.php file.
- User and Passwords can be auto-generated
- TODO: check a list of sites if they have successfully protected their wp-admin folder
- TODO: cookie remember pwd?

Locate WordPress
- This module searches for local WordPress installations (linux only). It starts from a specified folder and shows the found WordPress installations and their versions.
Useful if you manage multiple WordPress sites and want to make sure all of them are running the latest WordPress version.


Please fork and improve!

Future Ideas
------------
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


Support
-------

Please submit suggestions at github issue tracker: https://github.com/orbisius/sak4wp/issues


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

