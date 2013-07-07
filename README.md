SAK4WP - Swiss Army Knife for WordPress
======

Swiss Army Knife for WordPress is a standalone script which allows you to perform some recovery operations on your WordPress site.
This script is intended to be used for a short time only and then removed in order to prevent security issues.

Installation
------------

Upload !sak4wp.php in the folder where your WordPress installation is.
Then open your browser and navigate to http://yoursite.com/!sak4wp.php

Note: The file name is intentially prefixed by an exclamation mark (!) to make sure the file appears first in the list of files, even above .htaccess.
That way you should be able to see it in case you forget the file.


Features
--------
- Dashboard - Shows database info, php info, WordPress version
- Built-in Self Destroy mechanism (must be user triggered) -> the script will try to remove itself. 
If that is not accessible (due to permissions issues). The user must delete the !sak4wp.php file manually 
(e.g. by using an FTP client or accessing the file from a control panel).
- The script is self contained. It outputs JavaScript and CSS files however returns header not modified to make the page load faster.

Limit Login Attempts related features
- Allows you to Unblock yourself or somebody else from Limit Login Attempts plugin
- Added a link to a whois service to see more info about the blocked IP.
- ... many more are planned.


Support
--------

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

