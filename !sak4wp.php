<?php

/**
Swiss Army Knife for WordPress - a standalone script that allows you fix your WordPress installation and make some admin stuff.
You must remove it after the work is complete to avoid security issues.

License: GPL (v2 or later)
Author: Svetoslav Marinov (SLAVI)
Author Site: http://orbisius.com
Product Site: http://sak4wp.com
Copyright: All Rights Reserved.

Disclaimer: By using this script you take full responsibility to remove it.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
DISCLAIMER OF WARRANTY

The Software is provided "AS IS" and "WITH ALL FAULTS," without warranty of any kind, including without limitation the warranties of merchantability,
fitness for a particular purpose and non-infringement. The Licensor makes no warranty that the Software is free of defects or is suitable for any
particular purpose. In no event shall the Licensor be responsible for loss or damages arising from the installation or use of the Software,
including but not limited to any indirect, punitive, special, incidental or consequential damages of any character including, without limitation,
damages for loss of goodwill, work stoppage, computer failure or malfunction, or any and all other commercial damages or losses.
The entire risk as to the quality and performance of the Software is borne by you. Should the Software prove defective, you and not the
Licensor assume the entire cost of any service and repair.

*/

define('ORBISIUS_WP_SAK_APP_SHORT_NAME', 'SAK4WP');
define('ORBISIUS_WP_SAK_APP_NAME', 'Swiss Army Knife for WordPress');
define('ORBISIUS_WP_SAK_APP_URL', 'http://sak4wp.com');
define('ORBISIUS_WP_SAK_APP_VER', '1.0.3');
define('ORBISIUS_WP_SAK_APP_SCRIPT', basename(__FILE__));
define('ORBISIUS_WP_SAK_HOST', str_replace('www.', '', $_SERVER['HTTP_HOST']));

// this stops WP Super Cache and W3 Total Cache from caching
define( 'WP_CACHE', false );

/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

try {
    $ctrl = Orbisius_WP_SAK_Controller::getInstance();
    $ctrl->init();

    $ctrl->preRun();
    
    // This WP load may fail which we'll check()
    include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wp-load.php');

    $ctrl->check();
    $ctrl->run();
    $ctrl->postRun();
} catch (Exception $e) {
    
}

/**
 * 
 */
class Orbisius_WP_SAK_Controller_Module {
    protected $description = '';
    private $params = array();

    public function init($params = null) {
        if (!is_null($params)) {
            $this->params = $params;
        }
    }

    public function handleAction() {
        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, json_encode( array('status' => 0, 'message' => "Invalid Action") ) );
    }

    /**
     * Descripton about what the module does
     */
    public function getInfo() {
        return $this->description;
    }

    /**
     * Descripton about what the module does
     */
    public function run() {
        trigger_error("Module doesn't implement run() method.", E_USER_ERROR);
    }
}

/**
 * Self_Protect Module - Make sure that only one user can access SAK.
 */
class Orbisius_WP_SAK_Controller_Module_Self_Protect extends Orbisius_WP_SAK_Controller_Module {
	private $first_run_file;
	
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
		$this->first_run_file = Orbisius_WP_SAK_Util::getWPUploadsDir() . '.ht-sak4wp-' . ORBISIUS_WP_SAK_HOST;
		
        $this->description = <<<EOF
<h4>Self Protect</h4>
<p>
This module allows you to run SAK4WP more securely. The first time you access SAK4WP, it will save your IP and browser info.
If somebody else accesses the file from a different IP or browser, he/she will be stopped.
The module doesn't require any configurations. It is always run before other modules.
</p>
EOF;
    }

    /**
     * Creates a first run file and stores IP + browser info
     */
    public function run() {
        $buff = '';
		
		$this->checkFirstRun();
		
        return $buff;
    }  
	
    /**
     * Checks if the script is access from a different IP or browser.
	 * It dies with an error
     */
    public function checkFirstRun() {
		// Creates a (host specific) file which stops people from accessing SAK4WP as the same time as you.
        $first_run_file = $this->first_run_file;

		$ip = Orbisius_WP_SAK_Util::getIP();
		$ua = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
        
		if (file_exists($first_run_file)) {
			$data = Orbisius_WP_SAK_Util_File::read($first_run_file, Orbisius_WP_SAK_Util_File::UNSERIALIZE);
			
			// If any of the recorded info is different, we don't want to deal with that person.
			if (empty($data) || $data['ip'] != $ip || $data['ua'] != $ua) {			
				$ctrl = Orbisius_WP_SAK_Controller::getInstance();
				$ctrl->doExit('Error');
			}
		} else {
			$creation_time = time();
		
			$data['ua'] = $ua;
			$data['ip'] = $ip;
			$data['creation_time'] = $creation_time;
			$data['hash'] = sha1($ua . $ip . $creation_time . ORBISIUS_WP_SAK_HOST);
			
			Orbisius_WP_SAK_Util_File::write($first_run_file, $data, Orbisius_WP_SAK_Util_File::SERIALIZE);
		}
		
        return true;
    }
	
	/**
	* Removes the first run file. This file prevents other users from accessing the file.
	* Even the browser has to match.
	*/
	public function clean() {
		unlink($this->first_run_file);
	}
}

/**
 * Example Module - Handles ...
 */
class Orbisius_WP_SAK_Controller_Module_Example extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {     
        $this->description = <<<EOF
<h4>Example</h4>
<p>This module allows you to ...
</p>
EOF;
    }

    /**
     * 
     */
    public function run() {
        $buff = '';

        if (!empty($_REQUEST['cmd'])) {
            if ($_REQUEST['cmd'] == 'command') {
                $text = empty($_REQUEST['text']) ? substr(sha1(mt_rand(100, 1000) . time()), 0, 6) : trim($_REQUEST['text']);
				
                $this->doSomething($text);

                $buff .= "<br/>";
            }
        }
		
        // Let's show delete button if any if the files exists.
        if ($ht_files_exist) {
            $buff .= "<p><br/><a href='?page=mod_htaccess&cmd=delete_htaccess' class='app-module-self-destroy-button'
                    onclick='return confirm('Are you sure?', '');'>delete .htaccess & .htpasswd files.</a></p>\n";
        }
        
        return $buff;
    }
    
    /**
     * Sample Method
     */
    public function doSomething($text) {
        
        return $text;
    }
}


/**
 * Example Module - Handles ...
 */
class Orbisius_WP_SAK_Controller_Module_User_Manager extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions.
	 * If user ID is present it will call method so the method can execute before any content is out.
     */
    public function __construct() {
		if (!empty($_REQUEST['user_id'])) {
			$this->loginAs();
		}
	
        $this->description = <<<EOF
<h4>User Manager</h4>
<p>This module allows you to see user account, user meta info, to log in as a user without knowing their password. TODO: Create, delete users.
Administrator accounts are highlighted.</p>
EOF;

		$current_user = wp_get_current_user();
					
		if (!empty($current_user->ID)) {
			$this->description .= "<span class='app_logged_in app-simple-alert-success'>Currently Logged User: $current_user->display_name 
				[$current_user->user_email] (ID: $current_user->ID)</span>";
		} else {
			$this->description .= "<span class='app_not_logged_in'>Currently Logged User: (none)</span>";
		}
		
		$this->description .= "<br/>";		
    }

    /**
     * Lists users and their meta info.
	 * The user info is dumped on the screen. TODO: make it look pretty.
	 * This listing also allows the user of SAK4WP to login as given user.
     */
    public function run() {
        $buff = '';

		$data = get_users();
		$records = array();

		$highlight_admins = array();
		
        foreach ($data as $idx => $user_obj) {
			$rec = (array) $user_obj->data;
			
			// Let's remove those fields because the table can't fit more than 5
			unset($rec['user_url']);
			unset($rec['user_pass']);
			unset($rec['user_status']);
			unset($rec['user_activation_key']);
			
			// This allows us to swich the user to a different one.
			$rec['user_login'] .= " (<a href='?page=mod_user_manager&user_id=$user_obj->ID'>Login</a>)";
					
            $user_meta = get_user_meta($user_obj->ID);

            $rec['ID'] .= " (<a href='javascript:void(0);' class='toggle_info_trigger'>show/hide meta</a>)\n" .
                '<pre class="toggle_info app_hide">' . var_export($user_meta, 1) . "</pre>\n";
						
			$records[] = $rec;
			
			if (user_can($user_obj->ID, 'manage_options' )) {
				$highlight_admins[] = $idx;
			}
        }
        
        $buff .= "<p class='results'></p>\n";

		$ctrl = Orbisius_WP_SAK_Controller::getInstance();
		$buff .= $ctrl->renderTable('Users: ' . count($data), '', $records, $highlight_admins);
		
        return $buff;
    }

    /**
     * This method auto logins in the user with certain user ID (int)
	 * After the user is logged in this will redirect again to the User Manager's page.
     */
    public function loginAs() {
		$user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
		wp_set_auth_cookie( $user_id, false, is_ssl() );
		
		wp_redirect('?page=mod_user_manager&logged_in_as=' . $user_id);
		exit;
    }
}

/**
 * Plugin_Manager Module - Allows you to manage plugins: bulk install, de/activate, delete
 */
class Orbisius_WP_SAK_Controller_Module_Plugin_Manager extends Orbisius_WP_SAK_Controller_Module {
	private $target_dir = ''; // plugins directory i.e. wp-content/plugins/
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
		$this->target_dir = WP_PLUGIN_DIR;
		$warning = Orbisius_WP_SAK_Util::msg("If the plugin already exists its files will be overridden!", 0);
		 
        $this->description = <<<EOF
<h4>Plugin Manager</h4>
<p>This module allows you to manage plugins: bulk download, install, and activate plugins. TODO: (de)activate, delete. Just enter the plugin's WordPress.org page 
    or a zip file location (web). Enter multiple links each on a new line. Warning: If the plugin already exists its files will be overridden!
<br/> Plugins will be extracted in: <strong>$this->target_dir</strong> <br/>

$warning
</p>
EOF;
		
    }

    /**
     * 
     */
    public function run() {
        $buff = '';

		$download_list_url = empty($_REQUEST['download_list_url']) ? '' : trim($_REQUEST['download_list_url']);	
		$download_list_url_esc = esc_attr($download_list_url);		

		$download_list_buff = empty($_REQUEST['download_list_buff']) ? '' : trim($_REQUEST['download_list_buff']);
		$download_list_buff_esc = esc_attr($download_list_buff);		

		$buff .= "<br/><h4>Plugin List from Text/HTML File</h4>\n";
		$buff .= "<p>Download Plugin list from a text file (e.g. from the public folder of your dropbox account, on your site etc.).</p>\n";
		$buff .= "<form method='post' id='mod_plugin_manager_download_list_form'>\n";
		$buff .= "<input type='text' name='download_list_url' id='download_list_url' class='app_full_width' value='$download_list_url_esc' />\n";
		$buff .= "<input type='submit' name='submit' class='app-btn-primary' value='Download Plugin List' />\n";
		$buff .= "</form>\n";
		$buff .= "<p class='download_list_results'></p>\n";
		
		$buff .= "<br/><h4>Plugin Page Links</h4>\n";
		$buff .= "<p>You can enter direct links to .zip files and/or plugin pages on WordPress.org only</p>\n";
		$buff .= "<form method='post' id='mod_plugin_manager_download_plugins_form'>\n";
		$buff .= "<textarea name='download_list_buff' id='download_list_buff' class='app_full_width' rows='8'>$download_list_buff_esc</textarea>\n";
		$buff .= "<input type='checkbox' id='activate_plugins' name='activate_plugins' class='app-btn-primary' value='1' /> 
                <label for='activate_plugins'>Activate plugin(s) after installation</label><br/>\n";
		$buff .= "<input type='submit' name='submit' class='app-btn-primary' value='Download & Extract' />\n";
		$buff .= "</form>\n";
		
        //$buff .= "<p><br/><a href='?page=mod_locate_wp&cmd=search' class='app-btn-primary mod_search_for_wordpress'>Search</a></p>\n";
        $buff .= "<p class='results'></p>\n";
        
        return $buff;
    }
    
	/**
     * This is called via ajax and downloads some plugins and extracts the zip files' contents into plugins folder.
     * The result is JSON
     */
    public function downloadAction() {	
        $msg = '';
        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $params = $ctrl->getParams();

        $download_list_buff = empty($params['download_list_buff']) ? '' : trim($params['download_list_buff']);
        $activate_plugins = empty($params['activate_plugins']) ? 0 : 1;

		$locations = preg_split('#[\r\n]+#si', $download_list_buff); // let's split things by new lines.
		$locations = array_map('trim', $locations); // no spaces
		$locations = array_unique($locations); // only unique links
		$locations = array_filter($locations); // skip empty lines		
		
        if (!empty($locations)) {
			$plugins_dir = $this->target_dir;
			$req_cnt = 0;
			$limit = 10 * 1024 * 1024; // 10MB
			$limit_fmt = Orbisius_WP_SAK_Util::formatFileSize($limit);
		
			foreach ($locations as $link) {
				if (empty($link)) {
					continue;
				}				
				
				// If the link points to a plugin hosted by wordpress.org go and find the download link.
				// otherwise we'll skip them because SAK4WP can find other random ZIP files or bad plugins
				// which could break WP. There could be some www
				if (preg_match('#https?://[w\.]*wordpress.org/(?:extend/)?plugins/#si', $link) 
						&& !preg_match('#\.zip$#si', $link)) {
					// let's load WP page
					$result = Orbisius_WP_SAK_Util::makeHttpRequest($link);				

					$org_link_esc = esc_attr($link);

					if (empty($result['debug']['http_code']) || $result['debug']['http_code'] != 200) {
						$result_html .= Orbisius_WP_SAK_Util::msg("Couldn't Download Link: HTTP Code: " . $result['debug']['http_code'], 2);
					} elseif (empty($result['error'])) {
						$body_buff = $result['buffer'];
						
						// the download link may contain alphanumeric + some versioning.
						// e.g. http://downloads.wordpress.org/plugin/orbisius-cyberstore.1.1.7.zip
						if (preg_match('#(https?://downloads.wordpress.org/plugin/(?:[\w-.]+).zip)#si', $body_buff, $matches)) {
							$link = $matches[1];
							$result_html .= Orbisius_WP_SAK_Util::msg("Found Download Link: [$org_link_esc => $link]", 2);
						}
					} else {
						$result_html .= Orbisius_WP_SAK_Util::msg("Couldn't Find Plugin Download Link: [$link]", 2);
					}
					
					// let's give the server a chance to relax for a sec.
					if (++$req_cnt % 5 == 0) {
						sleep(1);
					}
				}
				
				$link_esc = esc_attr($link);
			
				// skip links not ending in .zip for now.
				if (!preg_match('#\.zip$#si', $link)) {									
					$result_html .= Orbisius_WP_SAK_Util::msg("Skipping link: [$link_esc]. Reason: doesn't end in .zip");
					
					continue;
				} else {
					$dl_status = null;
					$extract_status = null;
					
					$result_html .= Orbisius_WP_SAK_Util::msg("Processing link: [$link_esc]", 2);
					
					// Let's do a quick check
					$remote_file_size = Orbisius_WP_SAK_Util::getRemoteFileSize($link);					
					
					// The max plugin size is 10MB					
					if ($remote_file_size > $limit) {
						$file_size_fmt = Orbisius_WP_SAK_Util::formatFileSize($remote_file_size);
						$result_html .= Orbisius_WP_SAK_Util::msg("Download Failed: [$link_esc, $file_size_fmt]." 
							. " Plugin file is bigger than $limit_fmt.", 0);
					} else {			
						$dl_status = Orbisius_WP_SAK_Util::downloadFile($link);

						if (empty($dl_status['status']) 
								|| empty($dl_status['debug']['http_code']) 
								|| $dl_status['debug']['http_code'] != 200) {
							$result_html .= Orbisius_WP_SAK_Util::msg("Download Failed: [$link_esc]. 
								Request Info: (<a href='javascript:void(0);' class='toggle_info_trigger'>show/hide</a>)
							<pre class='toggle_info app_hide'>" 
								. var_export( $dl_status, 1) . "</pre>", 0);				
						} else {
							$file = $dl_status['file'];
							$file_size = filesize($file);
							$file_size_fmt = Orbisius_WP_SAK_Util::formatFileSize($file_size);
							$result_html .= Orbisius_WP_SAK_Util::msg("Download OK: [$link_esc, $file_size_fmt]", 1);

                            // Here we're assuming that the plugin will be in a folder in the zip file.
							$extract_status = Orbisius_WP_SAK_Util::extractArchiveFile($file, $plugins_dir);
							
							if (!empty($extract_status['status'])) {
								$result_html .= Orbisius_WP_SAK_Util::msg("Plugin Extracting OK: [$link_esc] in plugins/{$extract_status['plugin_folder']}", 1);

                                if ($activate_plugins) {
                                    $act_status = Orbisius_WP_SAK_Util::doPluginAction($extract_status['main_plugin_file'], 1);

                                    if (!empty($act_status['status'])) {
                                        $result_html .= Orbisius_WP_SAK_Util::msg("The plugin was successfully activated.", 1);
                                    } else {
                                        $result_html .= Orbisius_WP_SAK_Util::msg("Plugin couldn't be activated. Error: " . $act_status['error'], 0);
                                    }
                                }
							} else {
								$result_html .= Orbisius_WP_SAK_Util::msg("Plugin Extracting Failed: [$link_esc]", 0);
							}
							
							// Let's remove the temporary file so it doesn't take too much space in the TMP folder.
							// Note: if we start using downloadFile with 2nd parameter e.g. target dir
							// this unlink idea may not be a good one.
							unlink($file);
						}
					}
					
					$result_html .= '<br/>';
				}
			}

            $status = 1;
        } else {
			$status = 1;
            $result_html = Orbisius_WP_SAK_Util::msg('No links have been entered or the entered ones do not end in .zip.', 2);
        }
		
        $result_status = array('status' => $status, 'message' => $msg, 'results' => $result_html, );   		
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $result_status);
    }

	/**
     * This is called via ajax and downloads some plugins and extracts the zip files' contents into plugins folder.
     * The result is JSON
     */
    public function get_download_listAction() {	
        $msg = $result_html = '';
        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $params = $ctrl->getParams();

        $download_list_url = empty($params['download_list_url']) ? '' : $params['download_list_url'];
		
        if (!empty($download_list_url)) {
			$result = Orbisius_WP_SAK_Util::makeHttpRequest($download_list_url);				

			$org_link_esc = esc_attr($link);
			
			if (empty($result['error'])) {
				$plugin_list = array();
				$body_buff = $result['buffer'];
				
				// Do we have links to WP plugins?
				if (preg_match_all('#https?://[w\.]*wordpress.org/(?:extend/)?plugins/[\w-]+/?#si', $body_buff, $matches)) {
					$plugin_list += $matches[0];
				}
				
				// How about .zip files? direct downloads
				if (preg_match_all('#https?://[^\s]+\.zip#si', $body_buff, $matches)) {
					$plugin_list += $matches[0];
				}
				
				$plugin_list = array_unique($plugin_list);
				
				$result_html .= "Found link(s)<br/>";
                
                $result_html .= "(<a href='javascript:void(0);' class='toggle_info_trigger'>show/hide retrieved content</a>) \n";
				$result_html .= "<pre class='toggle_info app_hide'>";
				$result_html .= esc_html($body_buff);
				$result_html .= "</pre>";
                
				$result_html .= "<br/><textarea rows='5' cols='40' id='download_list_download_links' class='app_full_width'>";
				$result_html .= join("\n", $plugin_list);
				$result_html .= "</textarea>";
				
				$result_html .= <<<HTML_EOF
				<button name='add_to_download' onclick='Sak4wp.Util.appendData("#download_list_download_links", "#download_list_buff");'
					id='add_to_download' class='app-btn-secondary' >Add to Download</button>
HTML_EOF;
				
			} else {
				$result_html .= Orbisius_WP_SAK_Util::msg("Couldn't Find Plugin Download Link: [$link]", 2);
			}
			
            $status = 1;
        } else {
			$status = 1;
            $result_html = Orbisius_WP_SAK_Util::msg('No link has been entered.', 2);
        }
		
        $result_status = array('status' => $status, 'message' => $msg, 'results' => $result_html, );   		
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $result_status);
    }
}

/**
 * Locate_WordPress Module - Searches for local WordPress installations in different folders and shows their versions.
 */
class Orbisius_WP_SAK_Controller_Module_Locate_WordPress extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>Locate WordPress</h4>
<p>Searches for local WordPress installations in different folders and shows their versions. 
Useful if you manage multiple WordPress sites and want to make sure all of them are running the latest WordPress.
</p>
EOF;
    }

    /**
     * 
     */
    public function run() {
        $buff = '';

		$start_folder = empty($_REQUEST['start_folder']) ? dirname(__FILE__) : trim($_REQUEST['start_folder']);
		$start_folder_esc = esc_attr($start_folder);

		$buff .= "<br/><form method='post' id='mod_locate_wordpress_form'>\n";
		$buff .= "<input type='hidden' name='cmd' value='search' />\n";
		$buff .= "Start Folder:<br/><input type='text' name='start_folder' id='start_folder' value='$start_folder_esc' class='app_full_width' />\n";
		$buff .= "<input type='submit' name='submit' class='app-btn-primary' value='Search' />\n";
		$buff .= "</form>\n";
		
        //$buff .= "<p><br/><a href='?page=mod_locate_wp&cmd=search' class='app-btn-primary mod_search_for_wordpress'>Search</a></p>\n";
        $buff .= "<p class='results'></p>\n";
        
        return $buff;
    }
    
	/**
     * This is called via ajax and searches for WP by finding wp-includes folder starting from start_folder.
	 * Needs starting folder.
     * The result is JSON
     */
    public function searchAction() {
        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $params = $ctrl->getParams();

        $start_folder = empty($params['start_folder']) ? dirname(__FILE__) : $params['start_folder'];

        $s = 0;
        $msg = '';

        $status = array('status' => 0, 'message' => '', 'results' => '', 'start_folder' => $start_folder);
        
		// this searches for folders that contain wp-includes and that's where we'll read version.php
		$cmd = "find $start_folder -type d -name \"wp-includes\" 2>/dev/null";
		
        if (!empty($start_folder)) {
			$latest_wp_version = Orbisius_WP_SAK_Util::getLatestWordPressVersion();
            $file_search_buffer = `$cmd`;
		
			if (!empty($file_search_buffer)) {
				$lines = preg_split('#[\r\n]+#si', $file_search_buffer);
				
				foreach ($lines as $line) {
					if (empty($line)) {
						continue;
					}
					
					$ver_file = $line . '/version.php'; // we just need to append the file to the abs dir path.
					$ver_buff = Orbisius_WP_SAK_Util_File::read($ver_file); // will be faster if we read first 120 bytes?
										
					$version = '0.0.0'; // defaut
					
					// parse version which is like this $wp_version = '3.5.2';
					if (preg_match('#wp_version\s*=\s*[\'"]([^\'"]+)#si', $ver_buff, $matches)) {
						$version = $matches[1];
						
						// is it the latest?; no -> let's warn them
						if (version_compare($version, $latest_wp_version, '<')) {
							$version = Orbisius_WP_SAK_Util::msg("$version (upgrade)", 0, 1);
						} else {
							$version = Orbisius_WP_SAK_Util::msg("$version", 1, 1);
						}
					}
					
					// by doing the dirname we'll go up 1 level above wp-includes -> wp root dir
					$data[dirname($line)] = $version;		
				}

				$result_html = $ctrl->renderKeyValueTable('WordPress Installations. Latest WordPress Version: ' . $latest_wp_version, $data, array(
						'table_css' => 'app-table-long-first-col',
						'header' => array('Location', 'WordPress Version'),
					)
				);
			}
						
            $status['status'] = 1;
        } else {
            $status['message'] = 'Error';
        }
		
		$status['results'] = empty($result_html) ? Orbisius_WP_SAK_Util::msg("Nothing found", 2) : $result_html;

        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $status);
    }
}

/**
 * This module handles lists page templates.
 */
class Orbisius_WP_SAK_Controller_Module_Htaccess extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->htaccess_dir = ABSPATH . 'wp-admin/';
        
        $this->description = <<<EOF
<h4>.htaccess</h4>
<p>This module allows you to create an .htaccess and .htpasswd files for the WordPress admin area.
    It will also add a snippet in the main .htaccess file so wp-login.php is protected too.
</p>
EOF;
    }

    private $htaccess_dir = null;

    /**
     * 
     */
    public function run() {
        $buff = '';

        $htaccess_file = $this->htaccess_dir . '.htaccess';
        $root_htaccess_file = ABSPATH . '.htaccess';
        $htpasswd_file = $this->htaccess_dir . '.htpasswd';
        $admin_url = admin_url('/');

        $buff .= "<br/>Create Account<br/><form method='post'>\n";
        $buff .= "<input type='hidden' name='cmd' value='create_htaccess' />\n";
        $buff .= "User: <input type='text' name='user' value='' />\n";
        $buff .= "Pass: <input type='text' name='pass' value='' />\n";
        $buff .= "<input type='submit' name='submit' class='app-btn-primary' value='create' />\n";
        $buff .= "</form>\n<p>Note: If the files already exist the new data will be appended.
            If you leave either box empty random user/pass will be generated.</p>";

        if (!empty($_REQUEST['cmd'])) {
            if ($_REQUEST['cmd'] == 'create_htaccess') {
                $user = empty($_REQUEST['user']) ? substr(sha1(mt_rand(100, 1000) . time()), 0, 6) : trim($_REQUEST['user']);
                $pass = empty($_REQUEST['pass']) ? substr(sha1(time() . mt_rand(100, 1000)), 0, 10) : trim($_REQUEST['pass']);
				
                $this->createHtaccessFile($user, $pass);

                $buff .= "<br/>Copy the following login info for your records<br/><pre>\nAdmin URL: $admin_url\nUser: $user\nPassword: $pass\n</pre>";
            } elseif ($_REQUEST['cmd'] == 'delete_htaccess') {
                $this->deleteHtaccessFile();
            }
        }

        $ht_files_exist = 0;

        if (file_exists($root_htaccess_file)) {
            $ht_files_exist++;

            $buff .= "<span class='app-simple-alert-success'>.htaccess [$root_htaccess_file] already exists (Read Only Data)</span>\n";
            $buff .= '<textarea class="app-code-textarea" readonly="readonly">';
            $buff .= Orbisius_WP_SAK_Util_File::read($root_htaccess_file);
            $buff .= '</textarea>';
        }

        if (file_exists($htaccess_file)) {
            $ht_files_exist++;

            $buff .= "<span class='app-simple-alert-success'>.htaccess [$htaccess_file] already exists (Read Only Data)</span>\n";
            $buff .= '<textarea class="app-code-textarea" readonly="readonly">';
            $buff .= Orbisius_WP_SAK_Util_File::read($htaccess_file);
            $buff .= '</textarea>';

            if (file_exists($htpasswd_file)) {
                $ht_files_exist++;
            
                $buff .= "<span class='app-simple-alert-success'>File [$htpasswd_file] already exists (Read Only Data)</span>\n";
                $buff .= '<textarea class="app-code-textarea" readonly="readonly">';
                $buff .= Orbisius_WP_SAK_Util_File::read($htpasswd_file);
                $buff .= '</textarea>';
            }
        } else {
            $buff .= "<span class='app-simple-alert-error'>File [$htaccess_file] doesn't exist</span>\n";
        }

        // Let's show delete button if any if the files exists.
        if ($ht_files_exist) {
            $buff .= "<p><br/><a href='?page=mod_htaccess&cmd=delete_htaccess' class='app-module-self-destroy-button'
                    onclick='return confirm('Are you sure?', '');'>delete .htaccess & .htpasswd files.</a></p>\n";
        }
        
        return $buff;
    }

    /**
     * Deletes pwd and htaccess file.
     * @return bool returns true if both 
     */
    public function deleteHtaccessFile() {
        $htaccess_file = $this->htaccess_dir . '.htaccess';
        $htpasswd_file = $this->htaccess_dir . '.htpasswd';

        $status1 = @unlink($htaccess_file);
        $status2 = @unlink($htpasswd_file);

        return $status1 && $status2;
    }
    
    /**
     * Creates/appends .htaccess and .htpasswd files in wp-admin folder.
     * It will check if the password or basic auth rules are aleady added.
     *
     * @param string $user
     * @param string $pwd
     * @return bool returns true if both
     */
    public function createHtaccessFile($user, $pwd = '') {
        $status1 = $status2 = true;
        $htpasswd_file = $this->htaccess_dir . '.htpasswd';
        $htaccess_file = $this->htaccess_dir . '.htaccess';
        $htaccess_root_dir_file = ABSPATH . '.htaccess'; // in the www
        $host = ORBISIUS_WP_SAK_HOST;
        $host = preg_quote($host);

        $htpasswd_buff = $user . ':' . $this->generatePassword($pwd) . "\n";

        $htaccess_buff = <<<BUFF

######## SAK4WP_START ########
# Protect all files except admin-ajax.php which is used by plugins to send ajax calls.
<FilesMatch "!admin-ajax.php">
	AuthUserFile $htpasswd_file

	AuthType Basic
	AuthName "Protected Area"
	AuthGroupFile None
	Require valid-user
</FilesMatch>
######## SAK4WP_END ########

BUFF;

        $htaccess_buff_wp_login = <<<BUFF

######## SAK4WP_PROTECT_LOGIN_START ########
<FilesMatch "wp-login.php">
	AuthUserFile $htpasswd_file

	AuthType Basic
	AuthName "Protected Area"
	AuthGroupFile None
	Require valid-user
</FilesMatch>

<IfModule mod_rewrite.c>
	RewriteEngine on
	RewriteCond %{REQUEST_METHOD} POST
	RewriteCond %{HTTP_REFERER} !^https?://(.*)?$host [NC]
	RewriteCond %{REQUEST_URI} ^/wp-login\.php(.*)$ [OR]
	RewriteCond %{REQUEST_URI} ^/wp-admin$
	RewriteRule ^(.*)$ - [R=403,L]
</IfModule>

######## SAK4WP_PROTECT_LOGIN_START_END ########

BUFF;

        // Creates password file
        $current_htpasswd_buff = is_file($htpasswd_file) ? Orbisius_WP_SAK_Util_File::read($htpasswd_file) : '';

        // we will only add the info if the htaccess doesn't exist there yet
        if (empty($current_htpasswd_buff) || (stripos($current_htpasswd_buff, $htpasswd_buff) === false)) {
            $status2 = Orbisius_WP_SAK_Util_File::write($htpasswd_file, $htpasswd_buff, Orbisius_WP_SAK_Util_File::FILE_APPEND);
        }

        $current_htaccess_buff = is_file($htaccess_file) ? Orbisius_WP_SAK_Util_File::read($htaccess_file) : '';

        // we will only add the info if the htaccess doesn't exist there yet
        if (empty($current_htaccess_buff) || (stripos($current_htaccess_buff, 'SAK4WP_START') === false)) {
            $status1 = Orbisius_WP_SAK_Util_File::write($htaccess_file, $htaccess_buff, Orbisius_WP_SAK_Util_File::FILE_APPEND);
        }

        // Restricts access to wp-login.php
        $current_htaccess_rootdir_buff = is_file($htaccess_root_dir_file) ? Orbisius_WP_SAK_Util_File::read($htaccess_root_dir_file) : '';

        // we will only add the info if the htaccess doesn't exist there yet
        if (empty($current_htaccess_rootdir_buff) || (stripos($current_htaccess_rootdir_buff, 'SAK4WP_PROTECT_LOGIN_START') === false)) {
            $status3 = Orbisius_WP_SAK_Util_File::write($htaccess_root_dir_file, $htaccess_buff_wp_login, Orbisius_WP_SAK_Util_File::FILE_APPEND);
        }

        return $status1 && $status2;
    }

    /**
     * Generates a password that will be used in htaccess
     * @see http://www.htaccesstools.com/articles/create-password-for-htpasswd-file-using-php/
     */
    public function generatePassword($plain_text_pwd) {
        $password = crypt($plain_text_pwd, base64_encode($plain_text_pwd));
        
        return $password;
    }
}

/**
 * This module handles lists page templates.
 */
class Orbisius_WP_SAK_Controller_Module_List_Page_Templates extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>List Page Templates</h4>
<p>This module allows you to see which page templates are use by your pages.
</p>
EOF;
    }

    /**
     *
     */
    public function run() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT ID, post_title, post_name, post_author, post_parent, meta_key, meta_value, post_date FROM $wpdb->posts p
                LEFT JOIN $wpdb->term_relationships r ON (p.ID = r.object_id)
                LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id)
                WHERE pm.meta_key = '_wp_page_template'
                ORDER BY post_parent
                LIMIT 500
            " // p.*,pm.*
        );

        $buff = '';

        if (empty($results)) {
            $buff .= "No results.";
        } else {
            $ctrl = Orbisius_WP_SAK_Controller::getInstance();
            
            // make title clickable
            foreach ($results as $idx => $row_obj) {
               $link = get_permalink($row_obj->ID);
               $results[$idx]->post_title = "<a href='$link' target='_blank'>$row_obj->post_title</a>";
            }

            $buff .= $ctrl->renderTable('', '', $results);
        }

        return $buff;
    }
}
	
/**
 * This module handles actions related to Limit Login Attempts plugin.
 */
class Orbisius_WP_SAK_Controller_Module_Limit_Login_Attempts_Unblocker extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Gets the IP address that are blocked.
	 * Limit Login Attempts saves them in WP db's options under: 'limit_login_lockouts' name
     */
    public function __construct() {
        $lockouts = get_option('limit_login_lockouts');
        $retries = get_option('limit_login_retries');
		
        $this->lockouts = $lockouts;
        $this->retries = $retries;

		// ::SNOTE: fake IPs for testing
		/*$this->lockouts['111'] = time();
		$this->retries['111'] = 1;*/

		$this->ip = Orbisius_WP_SAK_Util::getIP();
		$this->description = <<<EOF
<h4>Limit Login Attempts Unblocker</h4>
<p> This section allows you to unblock yourself or other IP address that were blocked by
    <a href="http://wordpress.org/plugins/limit-login-attempts/" target="_blank" title="new/tab">Limit Login Attempts</a> plugin.
</p>
EOF;
    }

	/**
     * Checks if the current IP is blocked and lists all the blocked IPs in a table.
     */
    public function run() {
		$buff = '';
		$my_ip = $this->ip;

		if ($this->isBlocked($my_ip)) {
			$buff .= "<div class='app-alert-error'>Your IP [$my_ip] address is blocked.
				Scroll down to the yellow row and click on Unblock link.</div>";
		} else {
			$buff .= "<div class='app-alert-success'>Your IP [$my_ip] address is NOT blocked.</div>";
		}

		$buff .= "<br/>";
		$buff .= $this->getBlockedAsHTML();
		
		return $buff;
	}
	
    /**
     * Handles IP unblocking. It searches the IP in the list and then
     * updates the array and saves it in the db.
     * The result is JSON
     */
    public function unblockIPAction() {
        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $params = $ctrl->getParams();
        $lockouts = $this->lockouts;
        $retries = $this->retries;

        $ip = empty($params['ip']) ? '' : $params['ip'];

        $s = 0;
        $msg = '';

        $status = array('status' => 0, 'message' => '');
        
        if (!empty($lockouts[$ip])) {
            unset($lockouts[$ip]);
            unset($retries[$ip]);

            update_option('limit_login_lockouts', $lockouts);
            update_option('limit_login_retries', $retries);

            $status['status'] = 1;
        } else {
            $status['message'] = 'IP address: [' . esc_attr($ip) . '] not found.';
            //$status['data'] = $lockouts; // let's not send the data.
        }

        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, json_encode($status));
    }

    /**
     * Checks if the current user or a specific IP is banned.
     * @param string $ip
     * @return bool
     */
    public function isBlocked($ip = '') {
       $ip = empty($ip) ? Orbisius_WP_SAK_Util::getIP() : $ip;
       
       $lockouts = $this->lockouts;
       $banned = !empty($lockouts) && !empty($lockouts[$ip]);

       return $banned;
    }

    /**
     * Renders all the IPs in a nice table.
	 *
     * @param string $ip
     * @return string
     */
    public function getBlockedAsHTML() {
       $buff = '';
       $lockouts = $this->lockouts;

       $my_ip = $this->ip;

       if (!empty($lockouts)) {
            $cnt = 0;
            $data = $highlight_rows = array();

            foreach ($lockouts as $ip => $ts) {
                $you = '';

                if ($my_ip == $ip) {
                    $you = '<span class="app-simple-alert-success">&larr; (you)</span>';
                    $highlight_rows[] = $cnt;
                }
                
                $t = date('r', $ts);
                $ip_who_is_link = "<a href='http://who.is/whois-ip/ip-address/$ip/' target='_blank' data-ip='$ip' title='view ip info'>$ip</a> $you";
                $when_blocked = $t;
                $action = "<a href='javascript:void(0);' class='mod_limit_login_attempts_blocked_ip' data-ip='$ip'>Unblock</a>";

                $data[] = array(
                    'IP' => $ip_who_is_link,
                    'When Blocked' => $when_blocked,
                    'Action' => $action,
                );
                
                $cnt++;
            }
            
            $ctrl = Orbisius_WP_SAK_Controller::getInstance();
            $buff .= $ctrl->renderTable('Blocked IPs', '', $data, $highlight_rows);
       }

       return $buff;
    }
}

class Orbisius_WP_SAK_Controller_Module_Stats extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>Stats</h4>
<p>This module allows you to see a lot of stats for your WordPress site.
</p>
EOF;
    }    

    /**
     * 
     */
    public function run() {
        global $wpdb;
        global $wp_version;

        $buff = '';

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        
        $cfg = $this->read_wp_config();
        $cfg['db_version'] = $wpdb->get_var("SELECT VERSION()");
        $buff .= $ctrl->renderKeyValueTable('Database Info', $cfg);

        $data = array();
        $latest_wp_version = Orbisius_WP_SAK_Util::getLatestWordPressVersion();
        $data['PHP Version'] = phpversion();
        $wp_version_label = $wp_version;

        if (version_compare($wp_version, $latest_wp_version, '<')) {
            $upgrade_link = admin_url('update-core.php');
            $wp_version_label .= " (<span class='app-simple-alert-error'><a href='$upgrade_link' target='_blank'>upgrade</a> or download
                the latest <a href='http://wordpress.org/latest.zip' target='_blank'>WordPress</a> version ASAP</span>)";
        } else {
            $wp_version_label .= " (<span class='app-simple-alert-success'>Cool. You're running the latest WordPress)</span>";
        }

        $data['WordPress Version'] = $wp_version_label;
        $data['Latest WordPress Version'] = $latest_wp_version;

        $data['Operating System'] = PHP_OS;
        $data['Max Upload File Size Limit'] = $this->get_max_upload_size() . 'MB';
        $data['Memory Limit'] = $this->get_memory_limit() . 'MB';

        $dir = dirname(__FILE__); // that's where the sak is installed.
        $disk_usage = `du -sh $dir`;
        $disk_usage = trim($disk_usage);
        $disk_usage = empty($disk_usage) ? 'N/A' : $disk_usage;
        $data['Disk Space Usage'] = $disk_usage;

        $buff .= $ctrl->renderKeyValueTable('System Info', $data);

        $data = array();
        $data['User(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users");
        $data['User Meta Row(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta");
        $data['Comment(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments");
        $data['Comment Meta Rows(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->commentmeta");
        $data['Posts'] = $wpdb->get_var("SELECT COUNT(ID) as rev_cnt FROM $wpdb->posts p WHERE p.post_type = 'post'");
        $data['Pages'] = $wpdb->get_var("SELECT COUNT(ID) as rev_cnt FROM $wpdb->posts p WHERE p.post_type = 'page'");
        $data['Attachments'] = $wpdb->get_var("SELECT COUNT(ID) as rev_cnt FROM $wpdb->posts p WHERE p.post_type = 'attachment'");
        $data['Options Row(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options");
        $data['Link(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->links");
        $data['Terms(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->terms");
        $data['Terms Taxonomy Row(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy");
        $data['Terms Relationship(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships");
        $data['Meta Data Row(s)'] = $posts_cnt = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta pm");
        $data['Revisions'] = $wpdb->get_var(
            "SELECT COUNT(*) as rev_cnt FROM $wpdb->posts p
	LEFT JOIN $wpdb->term_relationships r ON (p.ID = r.object_id)
	LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id)
	WHERE p.post_type = 'revision'
                "
        );
        
        $buff .= $ctrl->renderKeyValueTable('WordPress Site Stats', $data);
		
		ob_start();
		phpinfo();
		$php_info = ob_get_contents();
		ob_get_clean();
		
		// clear HTML buff around content and reduce heading tags to h4
		$php_info = preg_replace('#.*?<body[^>]*>#si', '', $php_info);
		$php_info = preg_replace('#</body>.*#si', '', $php_info);
		$php_info = preg_replace('#<h\d#si', '<h4', $php_info);
		$php_info = preg_replace('#</\d#si', '</h4', $php_info);
		
		$php_info = '<h4>PHP Info</h4>' 
			. " (<a href='javascript:void(0);' class='toggle_info_trigger'>show/hide</a>)\n"
			. " <div class='toggle_info app_hide'>" . $php_info . '</div>';
				
		$buff .= $php_info;
		
		return $buff;
    }

    /**
     * checks several variables and returns the lowest (in MB).
     * @see http://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
     * @return int
     */
    public static function get_max_upload_size() {
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));

        $upload_mb = min($max_upload, $max_post, $memory_limit);

        return $upload_mb;
    }

    /**
     * Returns memory limit based on the CFG in MB
     * 
     * @return int
     */
    public static function get_memory_limit() {
        $memory_limit = (int)(ini_get('memory_limit'));
        return $memory_limit;
    }
    
    /**
     * Reads wordpress's config file and returns db data in an array.
     *
     * @param void $wp_config_file
     * @return array; array(4) { ["db_name"]=> string(10) "aaaaaa" ["db_user"]=> string(8) "aaaa" ["db_host"]=> string(9) "localhost" ["db_prefix"]=> string(9) "wp_fresh_" }
     */
    public function read_wp_config($wp_config_file = '') {
        if (empty($wp_config_file)) {
            $wp_config_file = ABSPATH . '/wp-config.php';
        }

        $data = array();
        $wp_config_buff = Orbisius_WP_SAK_Util_File::read($wp_config_file);

        /*if (empty($wp_config_buff)) {
            throw new Exception("There was an error with the mibew zip package.");
        }*/

        // define('DB_NAME', 'default_db');
        if (preg_match('#define\s*\(\s*[\'"]DB_NAME[\'"]\s*,\s*[\'"]\s*(.*?)\s*[\'"]#si', $wp_config_buff, $matches)) {
            $data['db_name'] = $matches[1];
        }

        // DB_USER
        if (preg_match('#define\s*\(\s*[\'"]DB_USER[\'"]\s*,\s*[\'"]\s*(.*?)\s*[\'"]#si', $wp_config_buff, $matches)) {
            $data['db_user'] = $matches[1];
        }

        // DB_PASSWORD
        if (preg_match('#define\s*\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*[\'"]\s*(.*?)\s*[\'"]#si', $wp_config_buff, $matches)) {
            $data['db_pass'] = $matches[1];
        }

        // DB_HOST
        if (preg_match('#define\s*\(\s*[\'"]DB_HOST[\'"]\s*,\s*[\'"]\s*(.*?)\s*[\'"]#si', $wp_config_buff, $matches)) {
            $data['db_host'] = $matches[1];
        }

        // wp prefix; $table_prefix  = 'default_db_wp_';
        if (preg_match('#table_prefix\s*=\s*[\'"]\s*(.*?)\s*[\'"]#si', $wp_config_buff, $matches)) {
            $data['db_prefix'] = $matches[1];
        }

        return $data;
    }
}

/**
* Cool file functions. Support file locking, (de)serialization etc.
*/
class Orbisius_WP_SAK_Util_File {
    // options for read/write methods.
    const SERIALIZE = 2;
    const UNSERIALIZE = 4;
    const FILE_APPEND = 8;
	
    /**
     * @desc write function using flock
     *
     * @param string $vars
     * @param string $buffer
     * @param int $append
     * @return bool
     */
    public static function write($file, $buffer = '', $option = null) {
        $buff = false;
        $tries = 0;
        $handle = '';

        $write_mod = 'wb';

        if (!is_null($option)) {
            if ($option & self::SERIALIZE) {
                $buffer = serialize($buffer);
            }

            if ($option & self::FILE_APPEND) {
                $write_mod = 'ab';
            }
        }

        if (($handle = @fopen($file, $write_mod))
                && flock($handle, LOCK_EX)) {
            // lock obtained
            if (fwrite($handle, $buffer) !== false) {
                @fclose($handle);
                return true;
            }
        }

        return false;
    }

    /**
     * @desc read function using flock
     *
     * @param string $vars
     * @param string $buffer
     * @param int $option whether to unserialize the data
     * @return mixed : string/data struct
     */
    public static function read($file, $option = null) {
        $buff = false;
        $read_mod = "rb";
        $handle = false;

        if (($handle = @fopen($file, $read_mod))
                && (flock($handle, LOCK_EX))) { //  | LOCK_NB - let's block; we want everything saved
            $buff = @fread($handle, filesize($file));
            @fclose($handle);
        }

        if ($option == self::UNSERIALIZE) {
            $buff = unserialize($buff);
        }

        return $buff;
    }
}

/**
* Cool functions that do not belong to a class and can be called individually.
*/
class Orbisius_WP_SAK_Util {
	public static $curl_options = array(
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0',
        CURLOPT_AUTOREFERER => true,
        CURLOPT_COOKIEFILE => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 400,
	);	

    /**
     * Gets IP. This may require checking some $_SERVER variables ... if the user is using a proxy.
     * @return string
     */
    public static function getIP() {
        $ip = $_SERVER['REMOTE_ADDR'];

        return $ip;
    }
	
    /**
     * Gets Server IP from env.
     * @return string
     */
    public static function getServerIP() {
        $ip = $_SERVER['SERVER_ADDR'];

        return $ip;
    }

    /**
     * proto str formatFileSize( int $size )
     *
     * @param string
     * @return string 1 KB/ MB
     */
    public static function formatFileSize($size) {
    	$size_suff = 'Bytes';

        if ($size > 1024 ) {
            $size /= 1024;
            $size_suff = 'KB';
        }

        if ( $size > 1024 ) {
            $size /= 1024;
            $size_suff = 'MB';
        }

        if ( $size > 1024 ) {
            $size /= 1024;
            $size_suff = 'GB';
        }

        if ( $size > 1024 ) {
            $size /= 1024;
            $size_suff = 'TB';
        }

        $size = number_format($size, 2);
		$size = preg_replace('#\.00$#', '', $size);

        return $size . " $size_suff";
    }	
	
	/**
     * a simple status message, no formatting except color.
	 * status is 0, 1 or 2
     */
    function msg($msg, $status = 0, $use_simple_css = 0) {
        $inline_css = '';
        
		if ($status ==  1) {
			$cls = 'app-alert-success';
		} elseif ($status == 2) {
			$cls = 'app-alert-notice';
		} else {
			$cls = 'app-alert-error';
		}		

		// use a simple CSS e.g. a nice span to alert, not just huge divs
		if ($use_simple_css) {
			$cls = str_replace('alert', 'simple-alert', $cls);
		}
		
        $str = "<div class='$cls' $inline_css>$msg</div>";
		
        return $str;
    }
	
	/**
	* Since we are downloading files, it is a good idea to be smart about it.
	* For example it doesn't make sense to download 10MB plugin and stress the server.
	* @see http://stackoverflow.com/questions/2602612/php-remote-file-size-without-downloading-file
	*/
	public static function getRemoteFileSize($url) {
		 $ch = curl_init($url);
		 
		 curl_setopt_array($ch, self::$curl_options);
		 
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		 curl_setopt($ch, CURLOPT_HEADER, true);
		 curl_setopt($ch, CURLOPT_NOBODY, true);
		 curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		 $data = curl_exec($ch);
		 $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

		 curl_close($ch);
		 
		 return $size;
	}
	
	/**
	* Downloads a file from a given url. The file is saved in a tmp folder and the location is returned
	* in the record
	*/	
	public static function downloadFile($url, $target_dir = '') {
		$status = 0;
		$error = $file = $debug = '';

		// let's allow the script to run longer in case we download lots of files.
		$old_time_limit = ini_get('max_execution_time');
		set_time_limit(600);

		try {
			if (!empty($target_dir)) { // trailingslash
				$target_dir = rtrim($target_dir, '/');
				$target_dir .= '/';
			}
			
			if (($ch = curl_init($url)) == false) {
				throw new Exception("curl_init error for url [$url].");
			}

			curl_setopt_array($ch, self::$curl_options);
		   
			$file = tempnam(self::getTempDir(), '!sak4wp_');	   
			
			$fp = fopen($file, "wb");
			
			if (empty($fp)) {
				throw new Exception("fopen error for file [$url]");
			}
			
			curl_setopt($ch, CURLOPT_FILE, $fp);       
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			
			if (curl_exec($ch) === false) {
				unlink($file);				
		
				throw new Exception("curl_exec error $file. Curl Error: " . curl_error($ch));
			} elseif (!empty($target_dir) && is_dir($target_dir)) { // ::SNOTE: this is not tested yet!
				$eurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				
				if (preg_match('#^.*/(.+)$#', $eurl, $match)) {
					if (rename($file, $target_dir . $match[1]) || copy($file, $target_dir . $match[1])) {
						$file = $target_dir . $match[1];
					}
				}
			}
		    
			$debug = curl_getinfo($ch);
		   
		    fclose($fp);
			curl_close($ch);
			
			$status = 1;
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
		
		$data = array(
			'status' => $status,
			'error' => $error,
			'file' => $file,
			'debug' => $debug,
		);		
		
		set_time_limit($old_time_limit);
		
		return $data;
	}

    /**
     * Activates or Deactivates a plugin.
     *
     * @param string $plugin_file - plugin's folder e.g. wp-content/plugins/like-gate/like-gate.php
     * @param int $action - 0 - for deactivate, 1 - activation
     * @return WP_Error in case of an error or true
     */
    static public function doPluginAction($plugin_file = '', $action = 1) {
        $error = '';
        $status = 0;

        require_once(ABSPATH . '/wp-admin/includes/plugin.php');

        if (empty($plugin_file)) {
            $data = array(
                'status' => $status,
                'error' => 'Plugin file not specified.',
            );

            return $data;
        }

        $result = empty($action)
                    ? deactivate_plugins($plugin_file)
                    : activate_plugin($plugin_file);

        if (is_wp_error($result)) {
            $error = $status->get_error_message();
        } else {
            $status = 1;
        }

        $data = array(
            'status' => $status,
            'error' => $error,
        );

		return $data;
    }

    /**
     * Reads a file partially e.g. the first NN bytes.
     *
     * @param string $file
     * @param int $len_bytes how much bytes to read
     * @param int $seek_bytes should we start from the start?
     * @return string
     */
    static function readFilePartially($file, $len_bytes = 512, $seek_bytes = 0) {
        $buff = '';
        
        $file_handle = fopen($file, 'rb');

        if (!empty($file_handle)) {
            if ($seek_bytes > 0) {
                fseek($file_handle, $seek_bytes);
            }

            $buff = fread($file_handle, $len_bytes);
            fclose($file_handle);
        }

        return $buff;
    }

    /**
     * This plugin scans the files in a folder and tries to get plugin data.
     * The real plugin file will have Name, Description variables set.
     * If the file doesn't have that info WP will prefill the data with empty values.
     *
     * @param string $folder - plugin's folder e.g. wp-content/plugins/like-gate/
     * @return string wp-content/plugins/like-gate/like-gate.php or false if not found.
     */
    static public function findMainPluginFile($folder = '') {
        $folder = trailingslashit($folder);
        $files_arr = glob($folder . '*.php'); // list only php files.

        foreach ($files_arr	as $file) {
            $buff = self::readFilePartially($file);

            // Did we find the plugin? If yes, it'll have Name filled in.
            if (stripos($buff, 'Plugin Name') !== false) {
                return $file;
            }
        }

        return false;
    }
	
	/**
	 * Extracts a which was saved in a tmp folder. We're expecting the zip file to contain a folder first
     * and then some contents
     * @param string $archive_file a file in the tmp folder
     * @param string $target_directory usually wp-content/plugins/
     * @see http://www.phpconcept.net/pclzip/user-guide/54
     * @see http://core.trac.wordpress.org/browser/tags/3.6/wp-admin/includes/file.php#L0
	*/	
	static public function extractArchiveFile($archive_file, $target_directory) {
		$status = 0;
		$error = $plugin_folder = $main_plugin_file = '';
		
		// Requires WP to be loaded.
		include_once(ABSPATH . 'wp-admin/includes/file.php');
        include_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
		
		if (function_exists('WP_Filesystem')) {
			WP_Filesystem();
			
            $archive = new PclZip($archive_file);
            $list = $archive->listContent(); // this contains all of the files and directories

            /*
            array(2) {
              [0]=>
              array(10) {
                ["filename"]=>
                string(7) "addons/"
                ["stored_filename"]=>
                string(7) "addons/"
                ["size"]=>
                int(0)
                ["compressed_size"]=>
                int(0)
                ["mtime"]=>
                int(1377115594)
                ["comment"]=>
                string(0) ""
                ["folder"]=>
                bool(true)
                ["index"]=>
                int(0)
                ["status"]=>
                string(2) "ok"
                ["crc"]=>
                int(0)
              }
              [1]=>
              array(10) {
                ["filename"]=>
                string(39) "addons/!sak4wp-theme-troubleshooter.php"
                ["stored_filename"]=>
                string(39) "addons/!sak4wp-theme-troubleshooter.php"
                ["size"]=>
                int(2900)
                ["compressed_size"]=>
                int(1112)
                ["mtime"]=>
                int(1377116198)
                ["comment"]=>
                string(0) ""
                ["folder"]=>
                bool(false)
                ["index"]=>
                int(1)
                ["status"]=>
                string(2) "ok"
                ["crc"]=>
                int(-1530906934)
              }
            }
            */
            
            // the first element should be the folder. e.g. like-gate.zip -> like-gate/ folder
            // listContent returns an array and folder key should be true.
            foreach ($list as $file_or_dir_rec) {
                if (empty($file_or_dir_rec['filename'])
                        || preg_match('#^(\.|__)#si', $file_or_dir_rec['filename'])) { // skip hidden or MAC files
                    continue;
                }

                // We want to check if there is a folder at the root level (index=0).
                if (!empty($file_or_dir_rec['folder']) && empty($file_or_dir_rec['index'])) {
                    $plugin_folder = $file_or_dir_rec['filename'];
                    break;
                }
            }

            if (!empty($plugin_folder)) {
                $status = unzip_file($archive_file, $target_directory);
            } else {
                $status = new WP_Error('100', "Cannot find plugin folder in the zip archive.");
            }
			
			if (is_wp_error($status)) {
				$error = $status->get_error_message();
			} else {
				$status = 1;
                $main_plugin_file = self::findMainPluginFile( trailingslashit( trailingslashit($target_directory) . $plugin_folder) );
			}
		} else {
			$error = 'WP_Filesystem is not loaded.';
		}
		
		$data = array(
            'status' => $status,
            'error' => $error,
            'plugin_folder' => $plugin_folder,
            'main_plugin_file' => $main_plugin_file,
        );
		
		return $data;
	}

	/**
     * Returns WordPress' uploads folder. Local path. The directory includes a trailing slash
     * We may need to save some hidden files there which weren't a good match for the temp directory.
     * Since this code is run before the WP load, we might need to guess it?
	 * 
     * @see http://codex.wordpress.org/Function_Reference/wp_upload_dir
     * @return string
     */
    public static function getWPUploadsDir() {
		if (function_exists('wp_upload_dir')) {
            $upload_dir = wp_upload_dir();
            $dir = $upload_dir['basedir'];
            $dir = rtrim($dir, '/') . '/';
		} else {
            $dir = dirname(__FILE__) . '/wp-content/uploads/';
        }

        // As a last resource we'll use temp dir.
        // which is not a cool thing because anybody can search the temp folder
        // and find out who is using SAK4WP
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new Exception("Cannot find WordPress' upload_dir or it is not wriable.");
        }
		
		return $dir;
	}

	/**
     * Tries to get the temp directory for php.
     * It checks if this function exists: sys_get_temp_dir (since php 5.2).
     * Otherwise it checks the ENV variables TMP, TEMP, and TMPDIR
     * 
     * @see http://php.net/manual/en/function.sys-get-temp-dir.php
     * @return string
     */
    public static function getTempDir() {
        $dir = '/tmp';

        if (function_exists('sys_get_temp_dir')) {
            $dir = sys_get_temp_dir();
        } else {
            if ($temp = getenv('TMP')) {
                $dir = $temp;
            } elseif ($temp = getenv('TEMP')) {
                $dir = $temp;
            } elseif ($temp = getenv('TMPDIR')) {
                $dir = $temp;
            } else {
                $temp = tempnam(__FILE__, '');

                if (file_exists($temp)) {
                    unlink($temp);
                    $dir = dirname($temp);
                }
            }
        }

        return $dir;
    }    
	
	/**
    * Parses the WP.org website to get the latest WP version.
    * It uses the temp directory to store the version
    *
    * @param void
    * @return string e.g. 3.5.1 or defaults to 0.0.0 in case of an error
    */
    public static function getLatestWordPressVersion() {
       $url = 'http://wordpress.org/download/';
       $ver = $default_ver = '0.0.0'; // default
       $ver_file = rtrim(self::getTempDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wp-ver.txt'; // C:\Windows\TEMP\wp-ver.txt 

       // we will check every 4h for a WP version
       if (!file_exists($ver_file) || (time() - filemtime($ver_file) > 4 * 3600)) {
           $result = Orbisius_WP_SAK_Util::makeHttpRequest($url);

           if (empty($result['error'])) {
               $body_buff = $result['buffer'];

               // look for a link that points to latest.zip"
               // <a class="button download-button" href="/latest.zip" onClick="recordOutboundLink(this, 'Download', 'latest.zip');return false;">
               // <strong>Download&nbsp;WordPress&nbsp;3.5.1</strong></span></a>
               if (preg_match('#(<a.*?latest\.zip.*?</a>)#si', $body_buff, $matches)) {
                   $dl_link = $matches[1];
                   $dl_link = strip_tags($dl_link);

                   if (preg_match('#(\d+\.\d+(?:\.\d+)?[\w]*)#si', $dl_link, $ver_matches)) { // 1.2.3 or 1.2.3b
                       $ver = $ver_matches[1];
                       Orbisius_WP_SAK_Util_File::write($ver_file, $ver);
                   }
               }
           }
       } else {
           $ver = Orbisius_WP_SAK_Util_File::read($ver_file);

           // Did somebody change the version file from the tmp?
           // and inserted some bad JS?
           if (!preg_match('#^[\.\d]+$#', $ver)) {
               $ver = $default_ver;
           }
       }

       return $ver;
    }
	
    /**
    * Makes a request to a given URL. Headers are requested too.
    *
    * @param string
    * @return array ; use $data['buffer'] to get the contents and $data['headers'] to get an array of headers.
    * @see http://stackoverflow.com/questions/28395/passing-post-values-with-curl
    */
    public static function makeHttpRequest($url, $params = array()) {
        if (!function_exists('curl_init')) {
            throw new Exception("Cannot find cURL php extension or it's not loaded.");
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSLVERSION,3);

        if (defined('CURLOPT_CERTINFO')) {
            curl_setopt($ch, CURLOPT_CERTINFO, 1);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, empty($params['user_agent']) ? 'Mozilla/4.0 (compatible; MSIE 1.0; unknown)' : $params['user_agent']);

        if (!empty($params)) {
            if (isset($params['__req']) && strtolower($params['__req']) == 'get') {
                unset($params['__req']);
                $url .= '?' . http_build_query($params);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        $buffer  = curl_exec($ch);
        $status = !empty($buffer);
        $error = empty($buffer) ? curl_error($ch) : '';
        $error_no = empty($buffer) ? curl_errno($ch) : '';

        $data = array(
            'status' => $status,
            'error' => $error,
            'error_no' => $error_no,
            'debug' => curl_getinfo($ch),
        );

        curl_close($ch);

        $data['debug']['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['debug']['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        if (empty($error)) {
            $m = array();
            // the buffer contains headers and the document body
            $arr = preg_match('#(.*?)\r?\n\r?\n(.*)#si', $buffer, $m);

            $headers_buff = $m[1];
            $body_buff = trim($m[2]);

            $data['buffer'] = $body_buff;
            $data['raw_buffer'] = $buffer;

            $header_lines = preg_split('#[\r]\n#si', $headers_buff);

            // Parse header lines and put them in array
            foreach ($header_lines as $line) {
                $key = $val = '';
                @list ($key, $val) = preg_split('#\s*:\s*#si', $line);

                // parse status field: HTTP/1.1 200 OK
                if (empty($val) && preg_match('#HTTP/1.\d\s(\d+)#si', $key, $matches)) {
                    $key = 'Status';
                    $val = $matches[1];
                }

                $data['headers'][$key] = $val;
            }
        }

        return $data;
    }
}

class Orbisius_WP_SAK_Controller {
    private function __construct() {
    }

    public function __destruct() {
      // This is just here to remind you that the
      // destructor must be public even in the case
      // of a singleton.
    }

    public function __clone() {
       trigger_error('Cloning instances of this class is forbidden.', E_USER_ERROR);
    }

    public function __wakeup() {
       trigger_error('Unserializing instances of this class is forbidden.', E_USER_ERROR);
    }

    /**
     * Does some cleanup, outputs some text (if any) and exists.
     */
	public function doExit($msg = '', $title = '') {
        unset($this->params);
		
        if (!empty($msg)) {
            $app_name = ORBISIUS_WP_SAK_APP_SHORT_NAME;
            echo "<h3 style='color:red;'>$app_name: $msg</h3>";
        }

        exit;
    }

    /**
     * Performs some checks after wp-load
     */
	public function check() {
        if (!defined('ABSPATH')) {
            die('ABSPATH is not defined. This script must be installed at the same level as your WordPress Installation.');
        }
        
        // Let's make sure we are protected all the time.
		$mod_obj = new Orbisius_WP_SAK_Controller_Module_Self_Protect();
		$mod_obj->run();
    }

    static private $_instance = null;

    public static function getInstance() {
        if (is_null(self::$_instance)) {
           self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * Setup params.
     */
	public function init() {
		$params = $_REQUEST;
        $params = array_map('trim', $params);
		$this->params = $params;
	}	

    /**
     * Executes some quick and light actions that do not require WP to be loaded.
     * E.g. outputting JS, CSS etc. as well as self destroy
     */
	public function preRun() {
        $params = $this->params;
        
        if (isset($params['css'])) {
            $this->outputStyles($params['css']);
        } elseif (isset($params['js'])) {
            $this->outputScripts($params['js']);
        } elseif (isset($params['img'])) {
            if ($params['img'] == 'icon_user') {
                $img_buff = self::app_icon_user;
            } else {
                $img_buff = $params['img'] == 'glyphicons-halflings-white'
                    ? self::bootstrap_glyphicons_halflings_white
                    : self::bootstrap_glyphicons_halflings;
            }
            
            $img_buff = base64_decode($img_buff);
            $this->sendHeader(self::HEADER_IMAGE_PNG, $img_buff);
        } elseif (isset($params['destroy'])) {
			$mod_obj = new Orbisius_WP_SAK_Controller_Module_Self_Protect();
			$mod_obj->clean();
			
            if (!unlink(__FILE__)) {
				$this->doExit('Cannot self destroy. Please delete <strong>' . __FILE__ . '</strong> manually.');
            }

            // This should be a test. If the user is seeing the script
            // that means it is is still alive.
            $this->redirect(ORBISIUS_WP_SAK_APP_SCRIPT);
        }
    }
    
    /**
     * Executes tasks that require WP to loaded
     */
	public function redirect($url, $status = 302) {
        header('Location: ' . $url);
        $this->doExit();
    }

    /**
     * Executes tasks that require WP to be loaded.
     */
	public function run() {
        $params = $this->params;
        
		if (!empty($params['module']) && !empty($params['action'])) {
            $action = $params['action'];
            $module = $params['module'];

            // e.g. Orbisius_WP_SAK_Controller_Module_Limit_Login_Attempts_Unblocker
            $module_class = 'Orbisius_WP_SAK_Controller_Module_' . $module;

            $obj_action_name = $action . 'Action';

            // if the module name doesn't exist OR the class -> it's an error.
            if (!class_exists($module_class)
                    || (($obj = new $module_class()) && !method_exists($obj, $obj_action_name))) {
                $status['status'] = 0;
                $status['message'] = 'Internal Error.';
                $this->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $status);
            }
            
            $obj->init();
            $obj->$obj_action_name();
        }

        $this->displayHeader();
        $this->displayFooter();
	}

	public function getParams() {
		return $this->params;
	}

    /**
     * WP should be loaded by now.
     */
	public function postRun() {
		
	}

    /**
     * Returns HTML content which is shown in the centre of the page.
     * 
     * @param string $page
     * @return string
     */
	public function getPageContent($page = '') {
        if (empty($page) && !empty($_REQUEST['page'])) {
            $page = $_REQUEST['page'];
        }

        $script = ORBISIUS_WP_SAK_APP_SCRIPT;
        $app_name = ORBISIUS_WP_SAK_APP_NAME;
         
		switch ($page) {
            case 'mod_self_protect':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Self_Protect();
                $descr = $mod_obj->getInfo();
                //$descr .= $mod_obj->run();

                break;                
			case 'mod_user_manager':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_User_Manager();
                $descr = $mod_obj->getInfo();
                $descr .= $mod_obj->run();

                break;                 
			case 'mod_plugin_manager':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Plugin_Manager();
                $descr = $mod_obj->getInfo();
                $descr .= $mod_obj->run();

                break;
			case 'mod_locate_wp':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Locate_WordPress();
                $descr = $mod_obj->getInfo();
                $descr .= $mod_obj->run();

                break;            
		case 'mod_htaccess':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Htaccess();
                $descr = $mod_obj->getInfo();
                $descr .= $mod_obj->run();

                break;
            case 'mod_stats':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Stats();
                $descr = $mod_obj->getInfo();
                $descr .= $mod_obj->run();

                break;
            case 'mod_list_page_templates':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_List_Page_Templates();
                $descr = $mod_obj->getInfo();
                $descr .= $mod_obj->run();

                break;
            case 'mod_unblock':
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Limit_Login_Attempts_Unblocker();
				$descr = $mod_obj->getInfo();
				$descr .= $mod_obj->run();

                break;
            
            case '':
            case 'home':
                $descr = <<<BUFF_EOF
                <p>$app_name is a standalone script which allows you to see some stats for your wordpress site and also perform some
                    recovery operations on your WordPress site.
                        This script is intended to be used for short time only and then removed in order to prevent security issues.
                   </p>
                <p> When you are done click on the <a href='$script?destroy' class='app-module-self-destroy-button' 
                    title="This will remove this script. If you see the same script that means the self destruction didn't happen.
                        Please remove the file manually by connecting using an FTP client."
                onclick="return confirm('This will remove this script. If you see the same script that means the self
                    destruction didn\'t happen. Please confirm self destroy operation.', '');">Self Destroy</a>

   button and the script will attempt to delete itself (if it has the necessary permissions).
                   </p>
BUFF_EOF;

                break;
            
            case 'help':
                    $app_url = ORBISIUS_WP_SAK_APP_URL;
        $ver = "<strong>Always remove this file when the work is complete!</strong>
                | Powered by <a href='$app_url' target='_blank'>$app_name</a> (v" . ORBISIUS_WP_SAK_APP_VER . ')';

                $descr = <<<BUFF_EOF
<h4>Support</h4>
<p>We provide support via <a href="https://github.com/orbisius/sak4wp/issues" target="_blank">github's issue tracker</a>.</p>

<br/>
<h4>Home Page</h4>
<p><a href="$app_url" target="_blank">$app_url</a></p>

<br/>
<h4>Project Page on GitHub Page</h4>
<p><a href="https://github.com/orbisius/sak4wp/" target="_blank">https://github.com/orbisius/sak4wp/</a></p>

<br/>
<h4>Suggestions</h4>
<p>If you have a suggestion submit a ticket at <a href="https://github.com/orbisius/sak4wp/issues" target="_blank">github's issue tracker</a> too.</p>

<br/>
<h4>Security</h4>
<p>
    <strong>If you've found a security bug please <a href="http://orbisius.com/site/contact/" target="_blank">Contact us</a> right away!</strong>
</p>

BUFF_EOF;

                break;

            case 'about':
                $descr = <<<BUFF_EOF
<h4>About</h4>
<p>$app_name was created by Svetoslav Marinov (SLAVI), <a href="http://orbisius.com" target="_blank">http://orbisius.com</a>.</p>

<h4>Need Help?</h4>
<p>Please check <a href="?page=help">Help</a> page for more info.</p>


<h4>Credits</h4>
<ul>
    <li>WordPress team</li>
    <li>Icons by FamFamFam</li>
    <li>Thanks for myself :) for my passing for sharing my work.</li>
 </ul>
BUFF_EOF;

                break;
            
            case 'donate':
                $descr = <<<BUFF_EOF
<h4>Donate</h4>
<p>Thank you for considering a donation to this project. We appraciate every contribution.</p>
<p>By donating you will speed up the development of the project.</p>

<br/>

<h4>Where to send the money?</h4>
<p>Please send it via PayPal to <strong>billing@orbisius.com</strong>.</p>

BUFF_EOF;

            case 'account':
                $descr = <<<BUFF_EOF
<h4>Account</h4>
<p>
    How would you like to be able to store all your plugin lists on the cloud?
    Tweet about SAK4WP to get a free account (when available).
</p>


BUFF_EOF;

                break;

            default:
                $descr = <<<BUFF_EOF
            <h4>Error</h4>
            <p>Invalid page.</p>
BUFF_EOF;
                break;
        }

        return $descr;
	}
	
	public function displayHeader() {
        $script = ORBISIUS_WP_SAK_APP_SCRIPT;
		$app_name = ORBISIUS_WP_SAK_APP_NAME;		
        $app_short_name = ORBISIUS_WP_SAK_APP_SHORT_NAME;
		$app_url = ORBISIUS_WP_SAK_APP_URL;
        $year = date('Y');
        $ver = "<strong>Always remove this file when the work is complete!</strong>
                | Powered by <a href='$app_url' target='_blank' title='$app_name'>$app_short_name</a> (v" . ORBISIUS_WP_SAK_APP_VER . ')';

        $host = ORBISIUS_WP_SAK_HOST;
        $site_url = site_url('/');
        $admin_url = admin_url('/');
		$ip = Orbisius_WP_SAK_Util::getIP();
		$server_ip = Orbisius_WP_SAK_Util::getServerIP();
        
        $page_content = $this->getPageContent();

		$buff = <<<BUFF_EOF
<!DOCTYPE html>
<html>
    <head>
        <title>$app_name</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="?css=1" type="text/css" rel="stylesheet" />
        <link href="?css=cust" type="text/css" rel="stylesheet" />
        <link href="?css=2" type="text/css" rel="stylesheet" />
<!-- 
<link href="http://marketplace.com.clients.com/landing/1/bootstrap/css/bootstrap.css" rel="stylesheet" /> -->
        <meta name="author" content="Orbisius.com" />
    </head>
    <body>		

   <div class="container main_container">
      <div class="masthead">
        <h3 class="muted">$app_name

		  <span class="social_links">
				  <a href="https://twitter.com/Orbisius" class=
				  "twitter-follow-button" data-show-count="false">Follow @Orbisius</a>
				  <script type="text/javascript">
				  //<![CDATA[
				  !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
				  //]]>
				  </script>
		  </span>
		</h3>

        <p>Running on: <strong>{$host}</strong> | Your IP: $ip | Server IP: $server_ip
			| <a href='$site_url' target='_blank'>Public Area</a>
			| <a href='$admin_url' target='_blank'>Admin Area</a>
			
			<span class='app-align-right'>
				  <img src='?img=icon_user' alt='account' /> <a href="$script?page=account">Account</a>
				| <a href="$script?page=help">Help</a>
				| <a href="$script?page=donate">Donate</a></li>
				| <a href="$script?page=about">About</a>
			</span>
		</p>

        <ul class="nav">
            <li class="active"><a href="$script">Dashboard</a></li>
            <li class="active">Modules:
				  <a href="$script?page=mod_stats" title="Lists WordPress Site Stats.">Stats</a>
				| <a href="$script?page=mod_unblock" title="Unblocks your IP from Limit Login Attempts ban list">Unblock</a>
				| <a href="$script?page=mod_list_page_templates" title="Lists Page Templates">Page Templates</a>
				| <a href="$script?page=mod_htaccess" title="Lists Page Templates">.htaccess</a>
				| <a href="$script?page=mod_locate_wp" title="Searches for WordPress Installations starting for a given folder">Locate WordPress</a>
				| <a href="$script?page=mod_plugin_manager" title="Searches and installs plugins">Plugin Manager</a>
				| <a href="$script?page=mod_user_manager" title="User Manager">User Manager</a>
				<!--| <a href="$script?page=mod_self_protect" title="Self Protect">Self Protect</a>	-->
			</li>

            <li class='right'><a href='$script?destroy' class='app-module-self-destroy-button' title="This will remove this script.
                If you see the same script that means the self destruction didn't happen. Please remove the file manually by connecting using an FTP client."
                onclick="return confirm('This will remove this script. If you see the same script that means the self destruction didn\'t happen.
                    Please confirm self destroy operation.', '');">Self Destroy</a>

                <a href='#' class='app-question-box' title="The script will attempt to remove itself. After than it will try to redirect to
                    itself. So if you see the same script that means the self destruction didn't finish successfully.
                        In that case remove the file manually by using an FTP client.">?</a>
            </li>            
        </ul>

        <!--<div class="navbar">
          <div class="navbar-inner">
            <div class="container">
              <ul class="nav">
                <li class="active"><a href="$script">Dashboard</a></li>
                <li><a href="$script?page=help">Help</a></li>
                <li><a href="$script?page=about">About</a></li>
                <li><a href="$script?page=donate">Donate</a></li>
              </ul>
            </div>
          </div>
        </div> --> <!-- /.navbar -->

      <br />
                
      <div class="row-fluid">
	    <div class="span12">
            <p>$page_content</p>
        </div>
      </div>

      <hr />

      <div class="footer">
            &copy; Orbisius.com $year
            <a href="https://twitter.com/Orbisius" class="twitter-follow-button"
				  data-show-count="false">Follow @Orbisius</a> <script type="text/javascript">
					//<![CDATA[
				  !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
				  //]]>
                </script>
            <div class='app_ver'>$ver</div>
      </div>
    </div> <!-- /container -->
BUFF_EOF;
		echo $buff;
	}
	
	public function displayFooter() {
        $script = ORBISIUS_WP_SAK_APP_SCRIPT;
        
		$buff = <<<BUFF_EOF
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
        <script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-1.8.2.min.js"></script>
        <script src="?js=1"></script>

        <script>		
            var wpsak_json_cfg = { ajax_url : '$script' };
			
			var Sak4wp = {
				Util : {
					// This method adds data from one box to anther. The target box's content is preserved.
					appendData : function (src, target) {
                        // we're adding a new line before and after the found links. The new lines will be later removed.
						$(target).val($(target).val() + String.fromCharCode(13) + $(src).val() + String.fromCharCode(13)); // new line
					},
					
					/*
					* Some buttons expose more content
					*/
					setupToggleButtons : function () {						
						$('.toggle_info_trigger').on('click', function() {
							if ($(this).siblings('.toggle_info').length) {
								$(this).siblings('.toggle_info').toggle();
							} else if ($(this).closest('.toggle_info').length) {
								$(this).closest('.toggle_info').toggle();
							}
						});
					}
				}
			};
			
            jQuery(document).ready(function($) {
				// let's select the first input box
				$('form').find('input[type=text],textarea,select').filter(':visible:first').focus();
				
				// always re-initialize the toggle buttons after an Ajax request.
				$( document ).ajaxComplete(function( event,request, settings ) {
				    Sak4wp.Util.setupToggleButtons();
				});
				
				Sak4wp.Util.setupToggleButtons();
				
				// Plugin_Manager : Download links
                $('#mod_plugin_manager_download_list_form').submit(function() {
					$('.app-ajax-message').remove();
					var form = $(this);
					var container = '.download_list_results';
					
                    $(container).empty().append("<div class='app-ajax-message app-alert-notice'>loading ...</div>");
				
					$.ajax({
                        type : "post",
                        dataType : "json",
                        url : wpsak_json_cfg.ajax_url, // contains all the necessary params
                        data : $(form).serialize() + '&module=Plugin_Manager&action=get_download_list',
                        success : function(json) {
                           $('.app-ajax-message').remove();
                
                           if (json.status) {
                              $(container).html(json.results);							  
                           } else {
                              $(container).append("<span class='app-ajax-message app-alert-error'>There was an error. Error: "
                                  + json.message + "</span>");
                           }
                        },
                        error : function(jqXHR, text_status, error_thrown) {
                            $('.app-ajax-message').remove();
                
                            alert("There was an error. " + text_status + ' ' + error_thrown);
                        },
                        
                    }); // ajax

                    return false;
				}); // Plugin_Manager
				
				// Plugin_Manager
                $('#mod_plugin_manager_download_plugins_form').submit(function() {
					$('.app-ajax-message').remove();
					var form = $(this);
					var container = '.results';
					
                    $(container).empty().append("<div class='app-ajax-message app-alert-notice'>loading ...</div>");
				
					$.ajax({
                        type : "post",
                        dataType : "json",
                        url : wpsak_json_cfg.ajax_url, // contains all the necessary params
                        data : $(form).serialize() + '&module=Plugin_Manager&action=download',
                        success : function(json) {
                           $('.app-ajax-message').remove();
                
                           if (json.status) {
                              $(container).html(json.results);
                           } else {
                              $(container).append("<span class='app-ajax-message app-alert-error'>There was an error. Error: "
                                  + json.message + "</span>");
                           }
                        },
                        error : function(jqXHR, text_status, error_thrown) {
                            $('.app-ajax-message').remove();
                
                            alert("There was an error. " + text_status + ' ' + error_thrown);
                        },
                        
                    }); // ajax

                    return false;
				}); // Plugin_Manager
				
				// search for wp
                $('#mod_locate_wordpress_form').submit(function() {
					$('.app-ajax-message').remove();
					var form = $(this);
					var container = '.results';
					
                    $(container).empty().append("<div class='app-ajax-message app-alert-notice'>loading ...</div>");
				
					$.ajax({
                        type : "post",
                        dataType : "json",
                        url : wpsak_json_cfg.ajax_url, // contains all the necessary params
                        data : $(form).serialize() + '&module=Locate_WordPress&action=search',
                        success : function(json) {
                           $('.app-ajax-message').remove();
                
                           if (json.status) {
                              $(container).html(json.results);
                           } else {
                              $(container).append("<span class='app-ajax-message app-alert-error'>There was an error. Error: "
                                  + json.message + "</span>");
                           }
                        },
                        error : function(jqXHR, text_status, error_thrown) {
                            $('.app-ajax-message').remove();
                
                            alert("There was an error. " + text_status + ' ' + error_thrown);
                        },
                        
                    }); // ajax

                    return false;
				}); // search4wp
				
                // unblock IP ajax
                $('.mod_limit_login_attempts_blocked_ip').click(function() {
                    var ip = $(this).data('ip');
                    var container = $(this).closest('td');
                
                    if (!confirm('Are you sure you want to unblock : ' + ip + '?', '')) {
                        return false;
                    }

                    //var parent_container = $(this).closest('tr'); // in case we want to remove the unblocked row

                    $('.app-ajax-message').remove();
                    $(container).append("<span class='app-ajax-message app-alert-notice'>loading ...</span>");

                    $.ajax({
                        type : "post",
                        dataType : "json",
                        url : wpsak_json_cfg.ajax_url, // contains all the necessary params
                        data : { module : 'Limit_Login_Attempts_Unblocker', action : 'unblockIP', ip : ip },
                        success : function(json) {
                           $('.app-ajax-message').remove();
                
                           if (json.status) {
                              //$(parent_container).slideUp('slow').remove();
                              $(container).append("<span class='app-ajax-message app-alert-success'>Unblocked!</span>");
                           } else {
                              $(container).append("<span class='app-ajax-message app-alert-error'>There was an error. Error: "
                                  + json.message + "</span>");
                           }
                        },
                        error : function(jqXHR, text_status, error_thrown) {
                            $('.app-ajax-message').remove();
                
                            alert("There was an error. " + text_status + ' ' + error_thrown);
                        },
                        
                    }); // ajax

                    return false;
                }); // click on .mod_limit_login_attempts_blocked_ip
            }); // ready
        </script>
	</body>
</html>
BUFF_EOF;
		echo $buff;
	}

    /**
     * Renders a nice stats table. Expects that the data is rows of associative array.
     * @param string $title - the text that will be shown above the table.
     * @param array $data
     * @param array $highlight_rows - some rows may need to be highlighted to stand out (diff bg color)
     * @return string HTML table
     */
    public function renderTable($title = '', $description = '', $data = array(), $highlight_rows = array()) {
        $buff = '';

        if (!empty($title)) {
            $buff .= "<h4>$title</h4>\n";
        }

        if (!empty($description)) {
            $buff .= "<p>$description</p>\n";
        }

        $buff .= "<table width='100%' class='app-table' cellpadding='2' cellspacing='0'>\n";

        foreach ($data as $idx => $row_obj) {
           // conv. to array if necessary
           $row_arr = is_object($row_obj) ? (array) $row_obj : $row_obj;

           // let's put header col. we'll output the keys in a tr
           if ($idx == 0) {
               $buff .= "\t<tr class='app-table-header-row'>\n";

               foreach (array_keys($row_arr) as $key) {
                   $buff .= "\t\t<td>$key</td>\n";
               }

               $buff .= "\t</tr>\n";
           }

           // Do we need to highlight the current row?
           $extra_cls = (!empty($highlight_rows) && in_array($idx, $highlight_rows))
                    ? ' app-table-hightlist-row ' : '';

           $cls = $idx % 2 != 0 ? 'app-table-row-odd' : '';
           $cls .= $extra_cls;
           $buff .= "\t<tr class='$cls app-table-data-row-centered'>\n";

           foreach ($row_arr as $key => $value) {
               $buff .= "\t\t<td>$value</td>\n";
           }

           $buff .= "\t</tr>\n";
           
           // let's put header col. we'll output the keys in a tr
           if ($idx == count($data) - 1) {
               $buff .= "\t<tr class='app-table-header-row'>\n";

               foreach (array_keys($row_arr) as $key) {
                   $buff .= "\t\t<td>$key</td>\n";
               }

               $buff .= "\t</tr>\n";
           }
        }

        $buff .= "</table>\n";
        
        return $buff;
    }

    /**
     * Renders a nice stats table. Expects that the data is key value pairs.
     * @param string $title - the text that will be shown above the table.
     * @param array $data
     * @return string HTML table
     */
    public function renderKeyValueTable($title, $data = array(), $options = array()) {
        $buff = '';

        if (!empty($title)) {
            $buff .= "<h4>$title</h4>\n";
        }

		// this is a nice way to add extra CSS
		$table_css = empty($options['table_css']) ? '' : $options['table_css'];
		
		$cnt = 0;
        $buff .= "<table class='app-table $table_css' cellpadding='2' cellspacing='0'>\n";        
		
		if (!empty($options['header'])) {		
		   $buff .= "\t<tr class='app-table-header-row'>\n";

		   foreach ($options['header'] as $label) {
			   $buff .= "\t\t<td>$label</td>\n";
		   }

		   $buff .= "\t</tr>\n";
		}

        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                $value = number_format($value, 2);
                $value = preg_replace('#\.00$#', '', $value);
            }

            $cnt++;
            $cls = $cnt % 2 != 0 ? ' app-table-row-odd ' : '';
            $cls .= ' app-table-data-row ';
            $buff .= "<tr class='$cls'>\n";
            $buff .= "<td>$key</td>\n"; //  class='data_cell_long'  class='data_cell_short'
            $buff .= "<td>$value</td>\n";
            $buff .= "</tr>\n";
        }

        $buff .= "</table>\n";

        return $buff;
    }

    const HEADER_CSS = 'css';
    const HEADER_JS = 'js';
    const HEADER_JSON = 'json';
    const HEADER_IMAGE_PNG = 'png';

    /*
     * B v.2.3
     * $f = 'C:/projects/clients/marketplace.com/htdocs/landing/1/bootstrap/img/glyphicons-halflings-white.png';
        $encoded_data = base64_encode(file_get_contents($f));
        die($encoded_data);
     */
    const bootstrap_glyphicons_halflings = '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';
    const bootstrap_glyphicons_halflings_white = 'iVBORw0KGgoAAAANSUhEUgAAAdUAAACfCAMAAACY07N7AAAC2VBMVEX///8AAAAAAAD5+fn///8AAAD////9/f1tbW0AAAD///////////8AAAAAAAD////w8PD+/v729vYAAAD8/PwAAAAAAAD////////a2toAAADCwsL09PT////////09PT39/f///8AAAAAAACzs7P9/f0AAADi4uKwsLD////////7+/vn5+f+/v7///8AAADt7e0AAADPz88AAAD9/f329vbt7e37+/vn5+f6+vrh4eGSkpL+/v7+/v7BwcGYmJh0dHTh4eHQ0NAAAADz8/O7u7uhoaGAgID9/f3U1NRiYmL////V1dX4+Pjc3Nz6+vr7+/vp6en7+/v9/f39/f3R0dHy8vL8/Pz4+Pjr6+v8/Py2trbGxsbl5eXu7u719fX9/f1lZWVnZ2fw8PC2trbg4OD39/f6+vrp6enl5eX6+vr4+PjLy8v///+EhITx8fF4eHj39/fd3d35+fnIyMjS0tLs7Oz6+vre3t7i4uLm5ubz8/Obm5uoqKilpaXc3Nzu7u7////x8fHJycnw8PD////////e3t7Gxsa8vLzr6+vW1tbQ0NDi4uL5+fn09PTi4uLs7Oz19fW0tLT////9/f37+/v8/Pz6+vrm5uYAAADk5OT8/Pz39/ewsLCZmZn9/f3s7Oz8/PzBwcHp6en////a2trw8PDw8PD19fXx8fH+/v74+Pj+/v6Ojo7i4uL7+/v5+fnc3Nz////y8vL6+vqfn5/t7e339/f29vbo6Ojz8/P6+vr19fX19fWmpqbLy8v6+vr4+PjT09Pr6+v6+vrr6+uqqqrz8/Pt7e2ioqLPz8/a2trW1taioqLr6+vi4uL5+flVVVXNzc3////W1tbj4+Ph4eHq6ur8/Pz////29vb7+/vz8/P09PTMzMz////////5+fn19fX////y8vL9/f0AAADZ2dn8/Pz7+/v8/Pzp6em/v7/7+/vq6urp6en+/v7////4ck/mAAAA8nRSTlMAGgDUzwIP8SMQ759fCgUvqfDGFeIYA78fbxNTt98/hsV/BhdD4Q1rRI+vwo3ATxJTD18IoKWasozTETbQ4D40IX5hC6dAMR7RXydvEsRuotKLkZCATYahkzOxQlFqmbZwJiUhFWy1wyJYcXI7gB2XIEFbgjxg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';

    // used in accounts
    const app_icon_user = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ3SURBVDjLpZNtSNNRFIcNKunF1rZWBMJqKaSiX9RP1dClsjldA42slW0q5oxZiuHrlqllLayoaJa2jbm1Lc3QUZpKFmmaTMsaRp+kMgjBheSmTL2//kqMBJlFHx44XM7vOfdyuH4A/P6HFQ9zo7cpa/mM6RvCrVDzaVDy6C5JJKv6rwSnIhlFd0R0Up/GwF2KWyl01CTSkM/dQoQRzAurCjRCGnRUUE2FaoSL0HExiYVzsQwcj6RNrSqo4W5Gh6Yc4+1qDDTkIy+GhYK4nTgdz0H2PrrHUJzs71NQn86enPn+CVN9GnzruoYR63mMPbkC59gQzDl7pt7rc9f7FNyUhPY6Bx9gwt4E9zszhWWpdg6ZcS8j3O7zCTuEpnXB+3MNZkUUZu0NmHE8XsL91oSWwiiEc3MeseLrN6woYCWa/Zl8ozyQ3w3Hl2lYy0SwlCUvsVi/Gv2JwITnYPDun2Hy6jYuEzAF1jUBCVYpO6kXo+NuGMeBAgcgfwNkvgBOPgUqXgKvP7rBFvRhE1crp8Vq1noFYSlacVyqGk0D86gbART9BDk9BFnPCNJbCY5aCFL1Cyhtp0RWAp74MsKSrkq9guHyvfMTtmLc1togpZoyqYmyNoITzVTYRJCiXYBIQ3CwFqi83o3JDhX6C0M8XsGIMoQ4OyuRlq1DdZcLkmbgGDX1iIEKNxAcbgTEOqC4ZRaJ6Ub86K7CYFEo8Qo+GBQlQyXBczLZpbloaQ9k1NUz/kD2myBBKxRZpa5hVcQslalatoUxizxAVVrN3CW21bFj9F858Q9dnIRmDyeuybM71uxmH9BNBB1q6zybV7H9s1Ue4PM3/gu/AEbfqfWy2twsAAAAAElFTkSuQmCC';
    
    /**
     * Outputs the necessary header based on the type.
	 * The $buffer can be an array -> will be JSON encoded.
     * @param string $type
     * @param string/array $buffer
     */
	public function sendHeader($type = null, $buffer = '') {
		if ($type == self::HEADER_CSS) {
			header("Content-type: text/css");
		} elseif ($type == self::HEADER_JS) {
			header("Content-type: text/javascript", true);
		} elseif ($type == self::HEADER_JSON) {
			header("Content-type: application/json", true);
		} elseif ($type == self::HEADER_IMAGE_PNG) {
			header("Content-type: image/png");
		}

        //header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        //header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        // header('Cache-Control: public');
        
        if (!empty($buffer)) {
			$buffer = is_scalar($buffer) ? $buffer : json_encode($buffer);
			
            if ($type != self::HEADER_JSON) { // don't bother caching JSON replies
                $etag = md5($buffer);
                header("ETag: \"$etag\"");

                $etag_header = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"\'') : false;
                header('Cache-Control: public');

                if ($etag_header == $etag) { // http://css-tricks.com/snippets/php/intelligent-php-cache-control/
                    header("HTTP/1.1 304 Not Modified");
                    $buffer = '';
                } else {
                    header("Content-Length: " . strlen($buffer));
                }
            } else {
                header("Content-Length: " . strlen($buffer));
            }

            echo $buffer;
            $this->doExit();
        }
	}

    /**
     * $f = 'C:\projects\clients\marketplace.com\htdocs\landing\1\bootstrap\js\bootstrap.min.js';
        $encoded_data = base64_encode(file_get_contents($f));
        die($encoded_data);
     * @param type $file_id
     */
	public function outputScripts($file_id = '') {
        // newdoc format is available since php 5.3. It allows $vars to be in the buffer that's why we need to base64 encode
		$buff[1] = <<<BUFF_EOF
LyohCiogQm9vdHN0cmFwLmpzIGJ5IEBmYXQgJiBAbWRvCiogQ29weXJpZ2h0IDIwMTIgVHdpdHRlciwgSW5jLgoqIGh0dHA6Ly93d3cuYXBhY2hlLm9yZy9saWNlbnNlcy9MSUNFTlNFLTIuMC50eHQKKi8KIWZ1bmN0aW9uKGUpeyJ1c2Ugc3RyaWN0IjtlKGZ1bmN0aW9uKCl7ZS5zdXBwb3J0LnRyYW5zaXRpb249ZnVuY3Rpb24oKXt2YXIgZT1mdW5jdGlvbigpe3ZhciBlPWRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoImJvb3RzdHJhcCIpLHQ9e1dlYmtpdFRyYW5zaXRpb246IndlYmtpdFRyYW5zaXRpb25FbmQiLE1velRyYW5zaXRpb246InRyYW5zaXRpb25lbmQiLE9UcmFuc2l0aW9uOiJvVHJhbnNpdGlvbkVuZCBvdHJhbnNpdGlvbmVuZCIsdHJhbnNpdGlvbjoidHJhbnNpdGlvbmVuZCJ9LG47Zm9yKG4gaW4gdClpZihlLnN0eWxlW25dIT09dW5kZWZpbmVkKXJldHVybiB0W25dfSgpO3JldHVybiBlJiZ7ZW5kOmV9fSgpfSl9KHdpbmRvdy5qUXVlcnkpLCFmdW5jdGlvbihlKXsidXNlIHN0cmljdCI7dmFyIHQ9J1tkYXRhLWRpc21pc3M9ImFsZXJ0Il0nLG49ZnVuY3Rpb24obil7ZShuKS5vbigiY2xpY2siLHQsdGhpcy5jbG9zZSl9O24ucHJvdG90eXBlLmNsb3NlPWZ1bmN0aW9uKHQpe2Z1bmN0aW9uIHMoKXtpLnRyaWdnZXIoImNsb3NlZCIpLnJlbW92ZSgpfXZhciBuPWUodGhpcykscj1uLmF0dHIoImRhdGEtdGFyZ2V0IiksaTtyfHwocj1uLmF0dHIoImhyZWYiKSxyPXImJnIucmVwbGFjZSgvLiooPz0jW15cc10qJCkvLCIiKSksaT1lKHIpLHQmJnQucHJldmVudERlZmF1bHQoKSxpLmxlbmd0aHx8KGk9bi5oYXNDbGFzcygiYWxlcnQiKT9uOm4ucGFyZW50KCkpLGkudHJpZ2dlcih0PWUuRXZlbnQoImNsb3NlIikpO2lmKHQuaXNEZWZhdWx0UHJldmVudGVkKCkpcmV0dXJuO2kucmVtb3ZlQ2xhc3MoImluIiksZS5zdXBwb3J0LnRyYW5zaXRpb24mJmkuaGFzQ2xhc3MoImZhZGUiKT9pLm9uKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCxzKTpzKCl9O3ZhciByPWUuZm4uYWxlcnQ7ZS5mbi5hbGVydD1mdW5jdGlvbih0KXtyZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uKCl7dmFyIHI9ZSh0aGlzKSxpPXIuZGF0YSgiYWxlcnQiKTtpfHxyLmRhdGEoImFsZXJ0IixpPW5ldyBuKHRoaXMpKSx0eXBlb2YgdD09InN0cmluZyImJmlbdF0uY2FsbChyKX0pfSxlLmZuLmFsZXJ0LkNvbnN0cnVjdG9yPW4sZS5mbi5hbGVydC5ub0NvbmZsaWN0PWZ1bmN0aW9uKCl7cmV0dXJuIGUuZm4uYWxlcnQ9cix0aGlzfSxlKGRvY3VtZW50KS5vbigiY2xpY2suYWxlcnQuZGF0YS1hcGkiLHQsbi5wcm90b3R5cGUuY2xvc2UpfSh3aW5kb3cualF1ZXJ5KSwhZnVuY3Rpb24oZSl7InVzZSBzdHJpY3QiO3ZhciB0PWZ1bmN0aW9uKHQsbil7dGhpcy4kZWxlbWVudD1lKHQpLHRoaXMub3B0aW9ucz1lLmV4dGVuZCh7fSxlLmZuLmJ1dHRvbi5kZWZhdWx0cyxuKX07dC5wcm90b3R5cGUuc2V0U3RhdGU9ZnVuY3Rpb24oZSl7dmFyIHQ9ImRpc2FibGVkIixuPXRoaXMuJGVsZW1lbnQscj1uLmRhdGEoKSxpPW4uaXMoImlucHV0Iik/InZhbCI6Imh0bWwiO2UrPSJUZXh0IixyLnJlc2V0VGV4dHx8bi5kYXRhKCJyZXNldFRleHQiLG5baV0oKSksbltpXShyW2VdfHx0aGlzLm9wdGlvbnNbZV0pLHNldFRpbWVvdXQoZnVuY3Rpb24oKXtlPT0ibG9hZGluZ1RleHQiP24uYWRkQ2xhc3ModCkuYXR0cih0LHQpOm4ucmVtb3ZlQ2xhc3ModCkucmVtb3ZlQXR0cih0KX0sMCl9LHQucHJvdG90eXBlLnRvZ2dsZT1mdW5jdGlvbigpe3ZhciBlPXRoaXMuJGVsZW1lbnQuY2xvc2VzdCgnW2RhdGEtdG9nZ2xlPSJidXR0b25zLXJhZGlvIl0nKTtlJiZlLmZpbmQoIi5hY3RpdmUiKS5yZW1vdmVDbGFzcygiYWN0aXZlIiksdGhpcy4kZWxlbWVudC50b2dnbGVDbGFzcygiYWN0aXZlIil9O3ZhciBuPWUuZm4uYnV0dG9uO2UuZm4uYnV0dG9uPWZ1bmN0aW9uKG4pe3JldHVybiB0aGlzLmVhY2goZnVuY3Rpb24oKXt2YXIgcj1lKHRoaXMpLGk9ci5kYXRhKCJidXR0b24iKSxzPXR5cGVvZiBuPT0ib2JqZWN0IiYmbjtpfHxyLmRhdGEoImJ1dHRvbiIsaT1uZXcgdCh0aGlzLHMpKSxuPT0idG9nZ2xlIj9pLnRvZ2dsZSgpOm4mJmkuc2V0U3RhdGUobil9KX0sZS5mbi5idXR0b24uZGVmYXVsdHM9e2xvYWRpbmdUZXh0OiJsb2FkaW5nLi4uIn0sZS5mbi5idXR0b24uQ29uc3RydWN0b3I9dCxlLmZuLmJ1dHRvbi5ub0NvbmZsaWN0PWZ1bmN0aW9uKCl7cmV0dXJuIGUuZm4uYnV0dG9uPW4sdGhpc30sZShkb2N1bWVudCkub24oImNsaWNrLmJ1dHRvbi5kYXRhLWFwaSIsIltkYXRhLXRvZ2dsZV49YnV0dG9uXSIsZnVuY3Rpb24odCl7dmFyIG49ZSh0LnRhcmdldCk7bi5oYXNDbGFzcygiYnRuIil8fChuPW4uY2xvc2VzdCgiLmJ0biIpKSxuLmJ1dHRvbigidG9nZ2xlIil9KX0od2luZG93LmpRdWVyeSksIWZ1bmN0aW9uKGUpeyJ1c2Ugc3RyaWN0Ijt2YXIgdD1mdW5jdGlvbih0LG4pe3RoaXMuJGVsZW1lbnQ9ZSh0KSx0aGlzLiRpbmRpY2F0b3JzPXRoaXMuJGVsZW1lbnQuZmluZCgiLmNhcm91c2VsLWluZGljYXRvcnMiKSx0aGlzLm9wdGlvbnM9bix0aGlzLm9wdGlvbnMucGF1c2U9PSJob3ZlciImJnRoaXMuJGVsZW1lbnQub24oIm1vdXNlZW50ZXIiLGUucHJveHkodGhpcy5wYXVzZSx0aGlzKSkub24oIm1vdXNlbGVhdmUiLGUucHJveHkodGhpcy5jeWNsZSx0aGlzKSl9O3QucHJvdG90eXBlPXtjeWNsZTpmdW5jdGlvbih0KXtyZXR1cm4gdHx8KHRoaXMucGF1c2VkPSExKSx0aGlzLmludGVydmFsJiZjbGVhckludGVydmFsKHRoaXMuaW50ZXJ2YWwpLHRoaXMub3B0aW9ucy5pbnRlcnZhbCYmIXRoaXMucGF1c2VkJiYodGhpcy5pbnRlcnZhbD1zZXRJbnRlcnZhbChlLnByb3h5KHRoaXMubmV4dCx0aGlzKSx0aGlzLm9wdGlvbnMuaW50ZXJ2YWwpKSx0aGlzfSxnZXRBY3RpdmVJbmRleDpmdW5jdGlvbigpe3JldHVybiB0aGlzLiRhY3RpdmU9dGhpcy4kZWxlbWVudC5maW5kKCIuaXRlbS5hY3RpdmUiKSx0aGlzLiRpdGVtcz10aGlzLiRhY3RpdmUucGFyZW50KCkuY2hpbGRyZW4oKSx0aGlzLiRpdGVtcy5pbmRleCh0aGlzLiRhY3RpdmUpfSx0bzpmdW5jdGlvbih0KXt2YXIgbj10aGlzLmdldEFjdGl2ZUluZGV4KCkscj10aGlzO2lmKHQ+dGhpcy4kaXRlbXMubGVuZ3RoLTF8fHQ8MClyZXR1cm47cmV0dXJuIHRoaXMuc2xpZGluZz90aGlzLiRlbGVtZW50Lm9uZSgic2xpZCIsZnVuY3Rpb24oKXtyLnRvKHQpfSk6bj09dD90aGlzLnBhdXNlKCkuY3ljbGUoKTp0aGlzLnNsaWRlKHQ+bj8ibmV4dCI6InByZXYiLGUodGhpcy4kaXRlbXNbdF0pKX0scGF1c2U6ZnVuY3Rpb24odCl7cmV0dXJuIHR8fCh0aGlzLnBhdXNlZD0hMCksdGhpcy4kZWxlbWVudC5maW5kKCIubmV4dCwgLnByZXYiKS5sZW5ndGgmJmUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCYmKHRoaXMuJGVsZW1lbnQudHJpZ2dlcihlLnN1cHBvcnQudHJhbnNpdGlvbi5lbmQpLHRoaXMuY3ljbGUoITApKSxjbGVhckludGVydmFsKHRoaXMuaW50ZXJ2YWwpLHRoaXMuaW50ZXJ2YWw9bnVsbCx0aGlzfSxuZXh0OmZ1bmN0aW9uKCl7aWYodGhpcy5zbGlkaW5nKXJldHVybjtyZXR1cm4gdGhpcy5zbGlkZSgibmV4dCIpfSxwcmV2OmZ1bmN0aW9uKCl7aWYodGhpcy5zbGlkaW5nKXJldHVybjtyZXR1cm4gdGhpcy5zbGlkZSgicHJldiIpfSxzbGlkZTpmdW5jdGlvbih0LG4pe3ZhciByPXRoaXMuJGVsZW1lbnQuZmluZCgiLml0ZW0uYWN0aXZlIiksaT1ufHxyW3RdKCkscz10aGlzLmludGVydmFsLG89dD09Im5leHQiPyJsZWZ0IjoicmlnaHQiLHU9dD09Im5leHQiPyJmaXJzdCI6Imxhc3QiLGE9dGhpcyxmO3RoaXMuc2xpZGluZz0hMCxzJiZ0aGlzLnBhdXNlKCksaT1pLmxlbmd0aD9pOnRoaXMuJGVsZW1lbnQuZmluZCgiLml0ZW0iKVt1XSgpLGY9ZS5FdmVudCgic2xpZGUiLHtyZWxhdGVkVGFyZ2V0OmlbMF0sZGlyZWN0aW9uOm99KTtpZihpLmhhc0NsYXNzKCJhY3RpdmUiKSlyZXR1cm47dGhpcy4kaW5kaWNhdG9ycy5sZW5ndGgmJih0aGlzLiRpbmRpY2F0b3JzLmZpbmQoIi5hY3RpdmUiKS5yZW1vdmVDbGFzcygiYWN0aXZlIiksdGhpcy4kZWxlbWVudC5vbmUoInNsaWQiLGZ1bmN0aW9uKCl7dmFyIHQ9ZShhLiRpbmRpY2F0b3JzLmNoaWxkcmVuKClbYS5nZXRBY3RpdmVJbmRleCgpXSk7dCYmdC5hZGRDbGFzcygiYWN0aXZlIil9KSk7aWYoZS5zdXBwb3J0LnRyYW5zaXRpb24mJnRoaXMuJGVsZW1lbnQuaGFzQ2xhc3MoInNsaWRlIikpe3RoaXMuJGVsZW1lbnQudHJpZ2dlcihmKTtpZihmLmlzRGVmYXVsdFByZXZlbnRlZCgpKXJldHVybjtpLmFkZENsYXNzKHQpLGlbMF0ub2Zmc2V0V2lkdGgsci5hZGRDbGFzcyhvKSxpLmFkZENsYXNzKG8pLHRoaXMuJGVsZW1lbnQub25lKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCxmdW5jdGlvbigpe2kucmVtb3ZlQ2xhc3MoW3Qsb10uam9pbigiICIpKS5hZGRDbGFzcygiYWN0aXZlIiksci5yZW1vdmVDbGFzcyhbImFjdGl2ZSIsb10uam9pbigiICIpKSxhLnNsaWRpbmc9ITEsc2V0VGltZW91dChmdW5jdGlvbigpe2EuJGVsZW1lbnQudHJpZ2dlcigic2xpZCIpfSwwKX0pfWVsc2V7dGhpcy4kZWxlbWVudC50cmlnZ2VyKGYpO2lmKGYuaXNEZWZhdWx0UHJldmVudGVkKCkpcmV0dXJuO3IucmVtb3ZlQ2xhc3MoImFjdGl2ZSIpLGkuYWRkQ2xhc3MoImFjdGl2ZSIpLHRoaXMuc2xpZGluZz0hMSx0aGlzLiRlbGVtZW50LnRyaWdnZXIoInNsaWQiKX1yZXR1cm4gcyYmdGhpcy5jeWNsZSgpLHRoaXN9fTt2YXIgbj1lLmZuLmNhcm91c2VsO2UuZm4uY2Fyb3VzZWw9ZnVuY3Rpb24obil7cmV0dXJuIHRoaXMuZWFjaChmdW5jdGlvbigpe3ZhciByPWUodGhpcyksaT1yLmRhdGEoImNhcm91c2VsIikscz1lLmV4dGVuZCh7fSxlLmZuLmNhcm91c2VsLmRlZmF1bHRzLHR5cGVvZiBuPT0ib2JqZWN0IiYmbiksbz10eXBlb2Ygbj09InN0cmluZyI/bjpzLnNsaWRlO2l8fHIuZGF0YSgiY2Fyb3VzZWwiLGk9bmV3IHQodGhpcyxzKSksdHlwZW9mIG49PSJudW1iZXIiP2kudG8obik6bz9pW29dKCk6cy5pbnRlcnZhbCYmaS5wYXVzZSgpLmN5Y2xlKCl9KX0sZS5mbi5jYXJvdXNlbC5kZWZhdWx0cz17aW50ZXJ2YWw6NWUzLHBhdXNlOiJob3ZlciJ9LGUuZm4uY2Fyb3VzZWwuQ29uc3RydWN0b3I9dCxlLmZuLmNhcm91c2VsLm5vQ29uZmxpY3Q9ZnVuY3Rpb24oKXtyZXR1cm4gZS5mbi5jYXJvdXNlbD1uLHRoaXN9LGUoZG9jdW1lbnQpLm9uKCJjbGljay5jYXJvdXNlbC5kYXRhLWFwaSIsIltkYXRhLXNsaWRlXSwgW2RhdGEtc2xpZGUtdG9dIixmdW5jdGlvbih0KXt2YXIgbj1lKHRoaXMpLHIsaT1lKG4uYXR0cigiZGF0YS10YXJnZXQiKXx8KHI9bi5hdHRyKCJocmVmIikpJiZyLnJlcGxhY2UoLy4qKD89I1teXHNdKyQpLywiIikpLHM9ZS5leHRlbmQoe30saS5kYXRhKCksbi5kYXRhKCkpLG87aS5jYXJvdXNlbChzKSwobz1uLmF0dHIoImRhdGEtc2xpZGUtdG8iKSkmJmkuZGF0YSgiY2Fyb3VzZWwiKS5wYXVzZSgpLnRvKG8pLmN5Y2xlKCksdC5wcmV2ZW50RGVmYXVsdCgpfSl9KHdpbmRvdy5qUXVlcnkpLCFmdW5jdGlvbihlKXsidXNlIHN0cmljdCI7dmFyIHQ9ZnVuY3Rpb24odCxuKXt0aGlzLiRlbGVtZW50PWUodCksdGhpcy5vcHRpb25zPWUuZXh0ZW5kKHt9LGUuZm4uY29sbGFwc2UuZGVmYXVsdHMsbiksdGhpcy5vcHRpb25zLnBhcmVudCYmKHRoaXMuJHBhcmVudD1lKHRoaXMub3B0aW9ucy5wYXJlbnQpKSx0aGlzLm9wdGlvbnMudG9nZ2xlJiZ0aGlzLnRvZ2dsZSgpfTt0LnByb3RvdHlwZT17Y29uc3RydWN0b3I6dCxkaW1lbnNpb246ZnVuY3Rpb24oKXt2YXIgZT10aGlzLiRlbGVtZW50Lmhhc0NsYXNzKCJ3aWR0aCIpO3JldHVybiBlPyJ3aWR0aCI6ImhlaWdodCJ9LHNob3c6ZnVuY3Rpb24oKXt2YXIgdCxuLHIsaTtpZih0aGlzLnRyYW5zaXRpb25pbmd8fHRoaXMuJGVsZW1lbnQuaGFzQ2xhc3MoImluIikpcmV0dXJuO3Q9dGhpcy5kaW1lbnNpb24oKSxuPWUuY2FtZWxDYXNlKFsic2Nyb2xsIix0XS5qb2luKCItIikpLHI9dGhpcy4kcGFyZW50JiZ0aGlzLiRwYXJlbnQuZmluZCgiPiAuYWNjb3JkaW9uLWdyb3VwID4gLmluIik7aWYociYmci5sZW5ndGgpe2k9ci5kYXRhKCJjb2xsYXBzZSIpO2lmKGkmJmkudHJhbnNpdGlvbmluZylyZXR1cm47ci5jb2xsYXBzZSgiaGlkZSIpLGl8fHIuZGF0YSgiY29sbGFwc2UiLG51bGwpfXRoaXMuJGVsZW1lbnRbdF0oMCksdGhpcy50cmFuc2l0aW9uKCJhZGRDbGFzcyIsZS5FdmVudCgic2hvdyIpLCJzaG93biIpLGUuc3VwcG9ydC50cmFuc2l0aW9uJiZ0aGlzLiRlbGVtZW50W3RdKHRoaXMuJGVsZW1lbnRbMF1bbl0pfSxoaWRlOmZ1bmN0aW9uKCl7dmFyIHQ7aWYodGhpcy50cmFuc2l0aW9uaW5nfHwhdGhpcy4kZWxlbWVudC5oYXNDbGFzcygiaW4iKSlyZXR1cm47dD10aGlzLmRpbWVuc2lvbigpLHRoaXMucmVzZXQodGhpcy4kZWxlbWVudFt0XSgpKSx0aGlzLnRyYW5zaXRpb24oInJlbW92ZUNsYXNzIixlLkV2ZW50KCJoaWRlIiksImhpZGRlbiIpLHRoaXMuJGVsZW1lbnRbdF0oMCl9LHJlc2V0OmZ1bmN0aW9uKGUpe3ZhciB0PXRoaXMuZGltZW5zaW9uKCk7cmV0dXJuIHRoaXMuJGVsZW1lbnQucmVtb3ZlQ2xhc3MoImNvbGxhcHNlIilbdF0oZXx8ImF1dG8iKVswXS5vZmZzZXRXaWR0aCx0aGlzLiRlbGVtZW50W2UhPT1udWxsPyJhZGRDbGFzcyI6InJlbW92ZUNsYXNzIl0oImNvbGxhcHNlIiksdGhpc30sdHJhbnNpdGlvbjpmdW5jdGlvbih0LG4scil7dmFyIGk9dGhpcyxzPWZ1bmN0aW9uKCl7bi50eXBlPT0ic2hvdyImJmkucmVzZXQoKSxpLnRyYW5zaXRpb25pbmc9MCxpLiRlbGVtZW50LnRyaWdnZXIocil9O3RoaXMuJGVsZW1lbnQudHJpZ2dlcihuKTtpZihuLmlzRGVmYXVsdFByZXZlbnRlZCgpKXJldHVybjt0aGlzLnRyYW5zaXRpb25pbmc9MSx0aGlzLiRlbGVtZW50W3RdKCJpbiIpLGUuc3VwcG9ydC50cmFuc2l0aW9uJiZ0aGlzLiRlbGVtZW50Lmhhc0NsYXNzKCJjb2xsYXBzZSIpP3RoaXMuJGVsZW1lbnQub25lKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCxzKTpzKCl9LHRvZ2dsZTpmdW5jdGlvbigpe3RoaXNbdGhpcy4kZWxlbWVudC5oYXNDbGFzcygiaW4iKT8iaGlkZSI6InNob3ciXSgpfX07dmFyIG49ZS5mbi5jb2xsYXBzZTtlLmZuLmNvbGxhcHNlPWZ1bmN0aW9uKG4pe3JldHVybiB0aGlzLmVhY2goZnVuY3Rpb24oKXt2YXIgcj1lKHRoaXMpLGk9ci5kYXRhKCJjb2xsYXBzZSIpLHM9ZS5leHRlbmQoe30sZS5mbi5jb2xsYXBzZS5kZWZhdWx0cyxyLmRhdGEoKSx0eXBlb2Ygbj09Im9iamVjdCImJm4pO2l8fHIuZGF0YSgiY29sbGFwc2UiLGk9bmV3IHQodGhpcyxzKSksdHlwZW9mIG49PSJzdHJpbmciJiZpW25dKCl9KX0sZS5mbi5jb2xsYXBzZS5kZWZhdWx0cz17dG9nZ2xlOiEwfSxlLmZuLmNvbGxhcHNlLkNvbnN0cnVjdG9yPXQsZS5mbi5jb2xsYXBzZS5ub0NvbmZsaWN0PWZ1bmN0aW9uKCl7cmV0dXJuIGUuZm4uY29sbGFwc2U9bix0aGlzfSxlKGRvY3VtZW50KS5vbigiY2xpY2suY29sbGFwc2UuZGF0YS1hcGkiLCJbZGF0YS10b2dnbGU9Y29sbGFwc2VdIixmdW5jdGlvbih0KXt2YXIgbj1lKHRoaXMpLHIsaT1uLmF0dHIoImRhdGEtdGFyZ2V0Iil8fHQucHJldmVudERlZmF1bHQoKXx8KHI9bi5hdHRyKCJocmVmIikpJiZyLnJlcGxhY2UoLy4qKD89I1teXHNdKyQpLywiIikscz1lKGkpLmRhdGEoImNvbGxhcHNlIik/InRvZ2dsZSI6bi5kYXRhKCk7bltlKGkpLmhhc0NsYXNzKCJpbiIpPyJhZGRDbGFzcyI6InJlbW92ZUNsYXNzIl0oImNvbGxhcHNlZCIpLGUoaSkuY29sbGFwc2Uocyl9KX0od2luZG93LmpRdWVyeSksIWZ1bmN0aW9uKGUpeyJ1c2Ugc3RyaWN0IjtmdW5jdGlvbiByKCl7ZSgiLmRyb3Bkb3duLWJhY2tkcm9wIikucmVtb3ZlKCksZSh0KS5lYWNoKGZ1bmN0aW9uKCl7aShlKHRoaXMpKS5yZW1vdmVDbGFzcygib3BlbiIpfSl9ZnVuY3Rpb24gaSh0KXt2YXIgbj10LmF0dHIoImRhdGEtdGFyZ2V0IikscjtufHwobj10LmF0dHIoImhyZWYiKSxuPW4mJi8jLy50ZXN0KG4pJiZuLnJlcGxhY2UoLy4qKD89I1teXHNdKiQpLywiIikpLHI9biYmZShuKTtpZighcnx8IXIubGVuZ3RoKXI9dC5wYXJlbnQoKTtyZXR1cm4gcn12YXIgdD0iW2RhdGEtdG9nZ2xlPWRyb3Bkb3duXSIsbj1mdW5jdGlvbih0KXt2YXIgbj1lKHQpLm9uKCJjbGljay5kcm9wZG93bi5kYXRhLWFwaSIsdGhpcy50b2dnbGUpO2UoImh0bWwiKS5vbigiY2xpY2suZHJvcGRvd24uZGF0YS1hcGkiLGZ1bmN0aW9uKCl7bi5wYXJlbnQoKS5yZW1vdmVDbGFzcygib3BlbiIpfSl9O24ucHJvdG90eXBlPXtjb25zdHJ1Y3RvcjpuLHRvZ2dsZTpmdW5jdGlvbih0KXt2YXIgbj1lKHRoaXMpLHMsbztpZihuLmlzKCIuZGlzYWJsZWQsIDpkaXNhYmxlZCIpKXJldHVybjtyZXR1cm4gcz1pKG4pLG89cy5oYXNDbGFzcygib3BlbiIpLHIoKSxvfHwoIm9udG91Y2hzdGFydCJpbiBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQmJmUoJzxkaXYgY2xhc3M9ImRyb3Bkb3duLWJhY2tkcm9wIi8+JykuaW5zZXJ0QmVmb3JlKGUodGhpcykpLm9uKCJjbGljayIscikscy50b2dnbGVDbGFzcygib3BlbiIpKSxuLmZvY3VzKCksITF9LGtleWRvd246ZnVuY3Rpb24obil7dmFyIHIscyxvLHUsYSxmO2lmKCEvKDM4fDQwfDI3KS8udGVzdChuLmtleUNvZGUpKXJldHVybjtyPWUodGhpcyksbi5wcmV2ZW50RGVmYXVsdCgpLG4uc3RvcFByb3BhZ2F0aW9uKCk7aWYoci5pcygiLmRpc2FibGVkLCA6ZGlzYWJsZWQiKSlyZXR1cm47dT1pKHIpLGE9dS5oYXNDbGFzcygib3BlbiIpO2lmKCFhfHxhJiZuLmtleUNvZGU9PTI3KXJldHVybiBuLndoaWNoPT0yNyYmdS5maW5kKHQpLmZvY3VzKCksci5jbGljaygpO3M9ZSgiW3JvbGU9bWVudV0gbGk6bm90KC5kaXZpZGVyKTp2aXNpYmxlIGEiLHUpO2lmKCFzLmxlbmd0aClyZXR1cm47Zj1zLmluZGV4KHMuZmlsdGVyKCI6Zm9jdXMiKSksbi5rZXlDb2RlPT0zOCYmZj4wJiZmLS0sbi5rZXlDb2RlPT00MCYmZjxzLmxlbmd0aC0xJiZmKyssfmZ8fChmPTApLHMuZXEoZikuZm9jdXMoKX19O3ZhciBzPWUuZm4uZHJvcGRvd247ZS5mbi5kcm9wZG93bj1mdW5jdGlvbih0KXtyZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uKCl7dmFyIHI9ZSh0aGlzKSxpPXIuZGF0YSgiZHJvcGRvd24iKTtpfHxyLmRhdGEoImRyb3Bkb3duIixpPW5ldyBuKHRoaXMpKSx0eXBlb2YgdD09InN0cmluZyImJmlbdF0uY2FsbChyKX0pfSxlLmZuLmRyb3Bkb3duLkNvbnN0cnVjdG9yPW4sZS5mbi5kcm9wZG93bi5ub0NvbmZsaWN0PWZ1bmN0aW9uKCl7cmV0dXJuIGUuZm4uZHJvcGRvd249cyx0aGlzfSxlKGRvY3VtZW50KS5vbigiY2xpY2suZHJvcGRvd24uZGF0YS1hcGkiLHIpLm9uKCJjbGljay5kcm9wZG93bi5kYXRhLWFwaSIsIi5kcm9wZG93biBmb3JtIixmdW5jdGlvbihlKXtlLnN0b3BQcm9wYWdhdGlvbigpfSkub24oImNsaWNrLmRyb3Bkb3duLmRhdGEtYXBpIix0LG4ucHJvdG90eXBlLnRvZ2dsZSkub24oImtleWRvd24uZHJvcGRvd24uZGF0YS1hcGkiLHQrIiwgW3JvbGU9bWVudV0iLG4ucHJvdG90eXBlLmtleWRvd24pfSh3aW5kb3cualF1ZXJ5KSwhZnVuY3Rpb24oZSl7InVzZSBzdHJpY3QiO3ZhciB0PWZ1bmN0aW9uKHQsbil7dGhpcy5vcHRpb25zPW4sdGhpcy4kZWxlbWVudD1lKHQpLmRlbGVnYXRlKCdbZGF0YS1kaXNtaXNzPSJtb2RhbCJdJywiY2xpY2suZGlzbWlzcy5tb2RhbCIsZS5wcm94eSh0aGlzLmhpZGUsdGhpcykpLHRoaXMub3B0aW9ucy5yZW1vdGUmJnRoaXMuJGVsZW1lbnQuZmluZCgiLm1vZGFsLWJvZHkiKS5sb2FkKHRoaXMub3B0aW9ucy5yZW1vdGUpfTt0LnByb3RvdHlwZT17Y29uc3RydWN0b3I6dCx0b2dnbGU6ZnVuY3Rpb24oKXtyZXR1cm4gdGhpc1t0aGlzLmlzU2hvd24/ImhpZGUiOiJzaG93Il0oKX0sc2hvdzpmdW5jdGlvbigpe3ZhciB0PXRoaXMsbj1lLkV2ZW50KCJzaG93Iik7dGhpcy4kZWxlbWVudC50cmlnZ2VyKG4pO2lmKHRoaXMuaXNTaG93bnx8bi5pc0RlZmF1bHRQcmV2ZW50ZWQoKSlyZXR1cm47dGhpcy5pc1Nob3duPSEwLHRoaXMuZXNjYXBlKCksdGhpcy5iYWNrZHJvcChmdW5jdGlvbigpe3ZhciBuPWUuc3VwcG9ydC50cmFuc2l0aW9uJiZ0LiRlbGVtZW50Lmhhc0NsYXNzKCJmYWRlIik7dC4kZWxlbWVudC5wYXJlbnQoKS5sZW5ndGh8fHQuJGVsZW1lbnQuYXBwZW5kVG8oZG9jdW1lbnQuYm9keSksdC4kZWxlbWVudC5zaG93KCksbiYmdC4kZWxlbWVudFswXS5vZmZzZXRXaWR0aCx0LiRlbGVtZW50LmFkZENsYXNzKCJpbiIpLmF0dHIoImFyaWEtaGlkZGVuIiwhMSksdC5lbmZvcmNlRm9jdXMoKSxuP3QuJGVsZW1lbnQub25lKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCxmdW5jdGlvbigpe3QuJGVsZW1lbnQuZm9jdXMoKS50cmlnZ2VyKCJzaG93biIpfSk6dC4kZWxlbWVudC5mb2N1cygpLnRyaWdnZXIoInNob3duIil9KX0saGlkZTpmdW5jdGlvbih0KXt0JiZ0LnByZXZlbnREZWZhdWx0KCk7dmFyIG49dGhpczt0PWUuRXZlbnQoImhpZGUiKSx0aGlzLiRlbGVtZW50LnRyaWdnZXIodCk7aWYoIXRoaXMuaXNTaG93bnx8dC5pc0RlZmF1bHRQcmV2ZW50ZWQoKSlyZXR1cm47dGhpcy5pc1Nob3duPSExLHRoaXMuZXNjYXBlKCksZShkb2N1bWVudCkub2ZmKCJmb2N1c2luLm1vZGFsIiksdGhpcy4kZWxlbWVudC5yZW1vdmVDbGFzcygiaW4iKS5hdHRyKCJhcmlhLWhpZGRlbiIsITApLGUuc3VwcG9ydC50cmFuc2l0aW9uJiZ0aGlzLiRlbGVtZW50Lmhhc0NsYXNzKCJmYWRlIik/dGhpcy5oaWRlV2l0aFRyYW5zaXRpb24oKTp0aGlzLmhpZGVNb2RhbCgpfSxlbmZvcmNlRm9jdXM6ZnVuY3Rpb24oKXt2YXIgdD10aGlzO2UoZG9jdW1lbnQpLm9uKCJmb2N1c2luLm1vZGFsIixmdW5jdGlvbihlKXt0LiRlbGVtZW50WzBdIT09ZS50YXJnZXQmJiF0LiRlbGVtZW50LmhhcyhlLnRhcmdldCkubGVuZ3RoJiZ0LiRlbGVtZW50LmZvY3VzKCl9KX0sZXNjYXBlOmZ1bmN0aW9uKCl7dmFyIGU9dGhpczt0aGlzLmlzU2hvd24mJnRoaXMub3B0aW9ucy5rZXlib2FyZD90aGlzLiRlbGVtZW50Lm9uKCJrZXl1cC5kaXNtaXNzLm1vZGFsIixmdW5jdGlvbih0KXt0LndoaWNoPT0yNyYmZS5oaWRlKCl9KTp0aGlzLmlzU2hvd258fHRoaXMuJGVsZW1lbnQub2ZmKCJrZXl1cC5kaXNtaXNzLm1vZGFsIil9LGhpZGVXaXRoVHJhbnNpdGlvbjpmdW5jdGlvbigpe3ZhciB0PXRoaXMsbj1zZXRUaW1lb3V0KGZ1bmN0aW9uKCl7dC4kZWxlbWVudC5vZmYoZS5zdXBwb3J0LnRyYW5zaXRpb24uZW5kKSx0LmhpZGVNb2RhbCgpfSw1MDApO3RoaXMuJGVsZW1lbnQub25lKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCxmdW5jdGlvbigpe2NsZWFyVGltZW91dChuKSx0LmhpZGVNb2RhbCgpfSl9LGhpZGVNb2RhbDpmdW5jdGlvbigpe3ZhciBlPXRoaXM7dGhpcy4kZWxlbWVudC5oaWRlKCksdGhpcy5iYWNrZHJvcChmdW5jdGlvbigpe2UucmVtb3ZlQmFja2Ryb3AoKSxlLiRlbGVtZW50LnRyaWdnZXIoImhpZGRlbiIpfSl9LHJlbW92ZUJhY2tkcm9wOmZ1bmN0aW9uKCl7dGhpcy4kYmFja2Ryb3AmJnRoaXMuJGJhY2tkcm9wLnJlbW92ZSgpLHRoaXMuJGJhY2tkcm9wPW51bGx9LGJhY2tkcm9wOmZ1bmN0aW9uKHQpe3ZhciBuPXRoaXMscj10aGlzLiRlbGVtZW50Lmhhc0NsYXNzKCJmYWRlIik/ImZhZGUiOiIiO2lmKHRoaXMuaXNTaG93biYmdGhpcy5vcHRpb25zLmJhY2tkcm9wKXt2YXIgaT1lLnN1cHBvcnQudHJhbnNpdGlvbiYmcjt0aGlzLiRiYWNrZHJvcD1lKCc8ZGl2IGNsYXNzPSJtb2RhbC1iYWNrZHJvcCAnK3IrJyIgLz4nKS5hcHBlbmRUbyhkb2N1bWVudC5ib2R5KSx0aGlzLiRiYWNrZHJvcC5jbGljayh0aGlzLm9wdGlvbnMuYmFja2Ryb3A9PSJzdGF0aWMiP2UucHJveHkodGhpcy4kZWxlbWVudFswXS5mb2N1cyx0aGlzLiRlbGVtZW50WzBdKTplLnByb3h5KHRoaXMuaGlkZSx0aGlzKSksaSYmdGhpcy4kYmFja2Ryb3BbMF0ub2Zmc2V0V2lkdGgsdGhpcy4kYmFja2Ryb3AuYWRkQ2xhc3MoImluIik7aWYoIXQpcmV0dXJuO2k/dGhpcy4kYmFja2Ryb3Aub25lKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCx0KTp0KCl9ZWxzZSF0aGlzLmlzU2hvd24mJnRoaXMuJGJhY2tkcm9wPyh0aGlzLiRiYWNrZHJvcC5yZW1vdmVDbGFzcygiaW4iKSxlLnN1cHBvcnQudHJhbnNpdGlvbiYmdGhpcy4kZWxlbWVudC5oYXNDbGFzcygiZmFkZSIpP3RoaXMuJGJhY2tkcm9wLm9uZShlLnN1cHBvcnQudHJhbnNpdGlvbi5lbmQsdCk6dCgpKTp0JiZ0KCl9fTt2YXIgbj1lLmZuLm1vZGFsO2UuZm4ubW9kYWw9ZnVuY3Rpb24obil7cmV0dXJuIHRoaXMuZWFjaChmdW5jdGlvbigpe3ZhciByPWUodGhpcyksaT1yLmRhdGEoIm1vZGFsIikscz1lLmV4dGVuZCh7fSxlLmZuLm1vZGFsLmRlZmF1bHRzLHIuZGF0YSgpLHR5cGVvZiBuPT0ib2JqZWN0IiYmbik7aXx8ci5kYXRhKCJtb2RhbCIsaT1uZXcgdCh0aGlzLHMpKSx0eXBlb2Ygbj09InN0cmluZyI/aVtuXSgpOnMuc2hvdyYmaS5zaG93KCl9KX0sZS5mbi5tb2RhbC5kZWZhdWx0cz17YmFja2Ryb3A6ITAsa2V5Ym9hcmQ6ITAsc2hvdzohMH0sZS5mbi5tb2RhbC5Db25zdHJ1Y3Rvcj10LGUuZm4ubW9kYWwubm9Db25mbGljdD1mdW5jdGlvbigpe3JldHVybiBlLmZuLm1vZGFsPW4sdGhpc30sZShkb2N1bWVudCkub24oImNsaWNrLm1vZGFsLmRhdGEtYXBpIiwnW2RhdGEtdG9nZ2xlPSJtb2RhbCJdJyxmdW5jdGlvbih0KXt2YXIgbj1lKHRoaXMpLHI9bi5hdHRyKCJocmVmIiksaT1lKG4uYXR0cigiZGF0YS10YXJnZXQiKXx8ciYmci5yZXBsYWNlKC8uKig/PSNbXlxzXSskKS8sIiIpKSxzPWkuZGF0YSgibW9kYWwiKT8idG9nZ2xlIjplLmV4dGVuZCh7cmVtb3RlOiEvIy8udGVzdChyKSYmcn0saS5kYXRhKCksbi5kYXRhKCkpO3QucHJldmVudERlZmF1bHQoKSxpLm1vZGFsKHMpLm9uZSgiaGlkZSIsZnVuY3Rpb24oKXtuLmZvY3VzKCl9KX0pfSh3aW5kb3cualF1ZXJ5KSwhZnVuY3Rpb24oZSl7InVzZSBzdHJpY3QiO3ZhciB0PWZ1bmN0aW9uKGUsdCl7dGhpcy5pbml0KCJ0b29sdGlwIixlLHQpfTt0LnByb3RvdHlwZT17Y29uc3RydWN0b3I6dCxpbml0OmZ1bmN0aW9uKHQsbixyKXt2YXIgaSxzLG8sdSxhO3RoaXMudHlwZT10LHRoaXMuJGVsZW1lbnQ9ZShuKSx0aGlzLm9wdGlvbnM9dGhpcy5nZXRPcHRpb25zKHIpLHRoaXMuZW5hYmxlZD0hMCxvPXRoaXMub3B0aW9ucy50cmlnZ2VyLnNwbGl0KCIgIik7Zm9yKGE9by5sZW5ndGg7YS0tOyl1PW9bYV0sdT09ImNsaWNrIj90aGlzLiRlbGVtZW50Lm9uKCJjbGljay4iK3RoaXMudHlwZSx0aGlzLm9wdGlvbnMuc2VsZWN0b3IsZS5wcm94eSh0aGlzLnRvZ2dsZSx0aGlzKSk6dSE9Im1hbnVhbCImJihpPXU9PSJob3ZlciI/Im1vdXNlZW50ZXIiOiJmb2N1cyIscz11PT0iaG92ZXIiPyJtb3VzZWxlYXZlIjoiYmx1ciIsdGhpcy4kZWxlbWVudC5vbihpKyIuIit0aGlzLnR5cGUsdGhpcy5vcHRpb25zLnNlbGVjdG9yLGUucHJveHkodGhpcy5lbnRlcix0aGlzKSksdGhpcy4kZWxlbWVudC5vbihzKyIuIit0aGlzLnR5cGUsdGhpcy5vcHRpb25zLnNlbGVjdG9yLGUucHJveHkodGhpcy5sZWF2ZSx0aGlzKSkpO3RoaXMub3B0aW9ucy5zZWxlY3Rvcj90aGlzLl9vcHRpb25zPWUuZXh0ZW5kKHt9LHRoaXMub3B0aW9ucyx7dHJpZ2dlcjoibWFudWFsIixzZWxlY3RvcjoiIn0pOnRoaXMuZml4VGl0bGUoKX0sZ2V0T3B0aW9uczpmdW5jdGlvbih0KXtyZXR1cm4gdD1lLmV4dGVuZCh7fSxlLmZuW3RoaXMudHlwZV0uZGVmYXVsdHMsdGhpcy4kZWxlbWVudC5kYXRhKCksdCksdC5kZWxheSYmdHlwZW9mIHQuZGVsYXk9PSJudW1iZXIiJiYodC5kZWxheT17c2hvdzp0LmRlbGF5LGhpZGU6dC5kZWxheX0pLHR9LGVudGVyOmZ1bmN0aW9uKHQpe3ZhciBuPWUuZm5bdGhpcy50eXBlXS5kZWZhdWx0cyxyPXt9LGk7dGhpcy5fb3B0aW9ucyYmZS5lYWNoKHRoaXMuX29wdGlvbnMsZnVuY3Rpb24oZSx0KXtuW2VdIT10JiYocltlXT10KX0sdGhpcyksaT1lKHQuY3VycmVudFRhcmdldClbdGhpcy50eXBlXShyKS5kYXRhKHRoaXMudHlwZSk7aWYoIWkub3B0aW9ucy5kZWxheXx8IWkub3B0aW9ucy5kZWxheS5zaG93KXJldHVybiBpLnNob3coKTtjbGVhclRpbWVvdXQodGhpcy50aW1lb3V0KSxpLmhvdmVyU3RhdGU9ImluIix0aGlzLnRpbWVvdXQ9c2V0VGltZW91dChmdW5jdGlvbigpe2kuaG92ZXJTdGF0ZT09ImluIiYmaS5zaG93KCl9LGkub3B0aW9ucy5kZWxheS5zaG93KX0sbGVhdmU6ZnVuY3Rpb24odCl7dmFyIG49ZSh0LmN1cnJlbnRUYXJnZXQpW3RoaXMudHlwZV0odGhpcy5fb3B0aW9ucykuZGF0YSh0aGlzLnR5cGUpO3RoaXMudGltZW91dCYmY2xlYXJUaW1lb3V0KHRoaXMudGltZW91dCk7aWYoIW4ub3B0aW9ucy5kZWxheXx8IW4ub3B0aW9ucy5kZWxheS5oaWRlKXJldHVybiBuLmhpZGUoKTtuLmhvdmVyU3RhdGU9Im91dCIsdGhpcy50aW1lb3V0PXNldFRpbWVvdXQoZnVuY3Rpb24oKXtuLmhvdmVyU3RhdGU9PSJvdXQiJiZuLmhpZGUoKX0sbi5vcHRpb25zLmRlbGF5LmhpZGUpfSxzaG93OmZ1bmN0aW9uKCl7dmFyIHQsbixyLGkscyxvLHU9ZS5FdmVudCgic2hvdyIpO2lmKHRoaXMuaGFzQ29udGVudCgpJiZ0aGlzLmVuYWJsZWQpe3RoaXMuJGVsZW1lbnQudHJpZ2dlcih1KTtpZih1LmlzRGVmYXVsdFByZXZlbnRlZCgpKXJldHVybjt0PXRoaXMudGlwKCksdGhpcy5zZXRDb250ZW50KCksdGhpcy5vcHRpb25zLmFuaW1hdGlvbiYmdC5hZGRDbGFzcygiZmFkZSIpLHM9dHlwZW9mIHRoaXMub3B0aW9ucy5wbGFjZW1lbnQ9PSJmdW5jdGlvbiI/dGhpcy5vcHRpb25zLnBsYWNlbWVudC5jYWxsKHRoaXMsdFswXSx0aGlzLiRlbGVtZW50WzBdKTp0aGlzLm9wdGlvbnMucGxhY2VtZW50LHQuZGV0YWNoKCkuY3NzKHt0b3A6MCxsZWZ0OjAsZGlzcGxheToiYmxvY2sifSksdGhpcy5vcHRpb25zLmNvbnRhaW5lcj90LmFwcGVuZFRvKHRoaXMub3B0aW9ucy5jb250YWluZXIpOnQuaW5zZXJ0QWZ0ZXIodGhpcy4kZWxlbWVudCksbj10aGlzLmdldFBvc2l0aW9uKCkscj10WzBdLm9mZnNldFdpZHRoLGk9dFswXS5vZmZzZXRIZWlnaHQ7c3dpdGNoKHMpe2Nhc2UiYm90dG9tIjpvPXt0b3A6bi50b3Arbi5oZWlnaHQsbGVmdDpuLmxlZnQrbi53aWR0aC8yLXIvMn07YnJlYWs7Y2FzZSJ0b3AiOm89e3RvcDpuLnRvcC1pLGxlZnQ6bi5sZWZ0K24ud2lkdGgvMi1yLzJ9O2JyZWFrO2Nhc2UibGVmdCI6bz17dG9wOm4udG9wK24uaGVpZ2h0LzItaS8yLGxlZnQ6bi5sZWZ0LXJ9O2JyZWFrO2Nhc2UicmlnaHQiOm89e3RvcDpuLnRvcCtuLmhlaWdodC8yLWkvMixsZWZ0Om4ubGVmdCtuLndpZHRofX10aGlzLmFwcGx5UGxhY2VtZW50KG8scyksdGhpcy4kZWxlbWVudC50cmlnZ2VyKCJzaG93biIpfX0sYXBwbHlQbGFjZW1lbnQ6ZnVuY3Rpb24oZSx0KXt2YXIgbj10aGlzLnRpcCgpLHI9blswXS5vZmZzZXRXaWR0aCxpPW5bMF0ub2Zmc2V0SGVpZ2h0LHMsbyx1LGE7bi5vZmZzZXQoZSkuYWRkQ2xhc3ModCkuYWRkQ2xhc3MoImluIikscz1uWzBdLm9mZnNldFdpZHRoLG89blswXS5vZmZzZXRIZWlnaHQsdD09InRvcCImJm8hPWkmJihlLnRvcD1lLnRvcCtpLW8sYT0hMCksdD09ImJvdHRvbSJ8fHQ9PSJ0b3AiPyh1PTAsZS5sZWZ0PDAmJih1PWUubGVmdCotMixlLmxlZnQ9MCxuLm9mZnNldChlKSxzPW5bMF0ub2Zmc2V0V2lkdGgsbz1uWzBdLm9mZnNldEhlaWdodCksdGhpcy5yZXBsYWNlQXJyb3codS1yK3MscywibGVmdCIpKTp0aGlzLnJlcGxhY2VBcnJvdyhvLWksbywidG9wIiksYSYmbi5vZmZzZXQoZSl9LHJlcGxhY2VBcnJvdzpmdW5jdGlvbihlLHQsbil7dGhpcy5hcnJvdygpLmNzcyhuLGU/NTAqKDEtZS90KSsiJSI6IiIpfSxzZXRDb250ZW50OmZ1bmN0aW9uKCl7dmFyIGU9dGhpcy50aXAoKSx0PXRoaXMuZ2V0VGl0bGUoKTtlLmZpbmQoIi50b29sdGlwLWlubmVyIilbdGhpcy5vcHRpb25zLmh0bWw/Imh0bWwiOiJ0ZXh0Il0odCksZS5yZW1vdmVDbGFzcygiZmFkZSBpbiB0b3AgYm90dG9tIGxlZnQgcmlnaHQiKX0saGlkZTpmdW5jdGlvbigpe2Z1bmN0aW9uIGkoKXt2YXIgdD1zZXRUaW1lb3V0KGZ1bmN0aW9uKCl7bi5vZmYoZS5zdXBwb3J0LnRyYW5zaXRpb24uZW5kKS5kZXRhY2goKX0sNTAwKTtuLm9uZShlLnN1cHBvcnQudHJhbnNpdGlvbi5lbmQsZnVuY3Rpb24oKXtjbGVhclRpbWVvdXQodCksbi5kZXRhY2goKX0pfXZhciB0PXRoaXMsbj10aGlzLnRpcCgpLHI9ZS5FdmVudCgiaGlkZSIpO3RoaXMuJGVsZW1lbnQudHJpZ2dlcihyKTtpZihyLmlzRGVmYXVsdFByZXZlbnRlZCgpKXJldHVybjtyZXR1cm4gbi5yZW1vdmVDbGFzcygiaW4iKSxlLnN1cHBvcnQudHJhbnNpdGlvbiYmdGhpcy4kdGlwLmhhc0NsYXNzKCJmYWRlIik/aSgpOm4uZGV0YWNoKCksdGhpcy4kZWxlbWVudC50cmlnZ2VyKCJoaWRkZW4iKSx0aGlzfSxmaXhUaXRsZTpmdW5jdGlvbigpe3ZhciBlPXRoaXMuJGVsZW1lbnQ7KGUuYXR0cigidGl0bGUiKXx8dHlwZW9mIGUuYXR0cigiZGF0YS1vcmlnaW5hbC10aXRsZSIpIT0ic3RyaW5nIikmJmUuYXR0cigiZGF0YS1vcmlnaW5hbC10aXRsZSIsZS5hdHRyKCJ0aXRsZSIpfHwiIikuYXR0cigidGl0bGUiLCIiKX0saGFzQ29udGVudDpmdW5jdGlvbigpe3JldHVybiB0aGlzLmdldFRpdGxlKCl9LGdldFBvc2l0aW9uOmZ1bmN0aW9uKCl7dmFyIHQ9dGhpcy4kZWxlbWVudFswXTtyZXR1cm4gZS5leHRlbmQoe30sdHlwZW9mIHQuZ2V0Qm91bmRpbmdDbGllbnRSZWN0PT0iZnVuY3Rpb24iP3QuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCk6e3dpZHRoOnQub2Zmc2V0V2lkdGgsaGVpZ2h0OnQub2Zmc2V0SGVpZ2h0fSx0aGlzLiRlbGVtZW50Lm9mZnNldCgpKX0sZ2V0VGl0bGU6ZnVuY3Rpb24oKXt2YXIgZSx0PXRoaXMuJGVsZW1lbnQsbj10aGlzLm9wdGlvbnM7cmV0dXJuIGU9dC5hdHRyKCJkYXRhLW9yaWdpbmFsLXRpdGxlIil8fCh0eXBlb2Ygbi50aXRsZT09ImZ1bmN0aW9uIj9uLnRpdGxlLmNhbGwodFswXSk6bi50aXRsZSksZX0sdGlwOmZ1bmN0aW9uKCl7cmV0dXJuIHRoaXMuJHRpcD10aGlzLiR0aXB8fGUodGhpcy5vcHRpb25zLnRlbXBsYXRlKX0sYXJyb3c6ZnVuY3Rpb24oKXtyZXR1cm4gdGhpcy4kYXJyb3c9dGhpcy4kYXJyb3d8fHRoaXMudGlwKCkuZmluZCgiLnRvb2x0aXAtYXJyb3ciKX0sdmFsaWRhdGU6ZnVuY3Rpb24oKXt0aGlzLiRlbGVtZW50WzBdLnBhcmVudE5vZGV8fCh0aGlzLmhpZGUoKSx0aGlzLiRlbGVtZW50PW51bGwsdGhpcy5vcHRpb25zPW51bGwpfSxlbmFibGU6ZnVuY3Rpb24oKXt0aGlzLmVuYWJsZWQ9ITB9LGRpc2FibGU6ZnVuY3Rpb24oKXt0aGlzLmVuYWJsZWQ9ITF9LHRvZ2dsZUVuYWJsZWQ6ZnVuY3Rpb24oKXt0aGlzLmVuYWJsZWQ9IXRoaXMuZW5hYmxlZH0sdG9nZ2xlOmZ1bmN0aW9uKHQpe3ZhciBuPXQ/ZSh0LmN1cnJlbnRUYXJnZXQpW3RoaXMudHlwZV0odGhpcy5fb3B0aW9ucykuZGF0YSh0aGlzLnR5cGUpOnRoaXM7bi50aXAoKS5oYXNDbGFzcygiaW4iKT9uLmhpZGUoKTpuLnNob3coKX0sZGVzdHJveTpmdW5jdGlvbigpe3RoaXMuaGlkZSgpLiRlbGVtZW50Lm9mZigiLiIrdGhpcy50eXBlKS5yZW1vdmVEYXRhKHRoaXMudHlwZSl9fTt2YXIgbj1lLmZuLnRvb2x0aXA7ZS5mbi50b29sdGlwPWZ1bmN0aW9uKG4pe3JldHVybiB0aGlzLmVhY2goZnVuY3Rpb24oKXt2YXIgcj1lKHRoaXMpLGk9ci5kYXRhKCJ0b29sdGlwIikscz10eXBlb2Ygbj09Im9iamVjdCImJm47aXx8ci5kYXRhKCJ0b29sdGlwIixpPW5ldyB0KHRoaXMscykpLHR5cGVvZiBuPT0ic3RyaW5nIiYmaVtuXSgpfSl9LGUuZm4udG9vbHRpcC5Db25zdHJ1Y3Rvcj10LGUuZm4udG9vbHRpcC5kZWZhdWx0cz17YW5pbWF0aW9uOiEwLHBsYWNlbWVudDoidG9wIixzZWxlY3RvcjohMSx0ZW1wbGF0ZTonPGRpdiBjbGFzcz0idG9vbHRpcCI+PGRpdiBjbGFzcz0idG9vbHRpcC1hcnJvdyI+PC9kaXY+PGRpdiBjbGFzcz0idG9vbHRpcC1pbm5lciI+PC9kaXY+PC9kaXY+Jyx0cmlnZ2VyOiJob3ZlciBmb2N1cyIsdGl0bGU6IiIsZGVsYXk6MCxodG1sOiExLGNvbnRhaW5lcjohMX0sZS5mbi50b29sdGlwLm5vQ29uZmxpY3Q9ZnVuY3Rpb24oKXtyZXR1cm4gZS5mbi50b29sdGlwPW4sdGhpc319KHdpbmRvdy5qUXVlcnkpLCFmdW5jdGlvbihlKXsidXNlIHN0cmljdCI7dmFyIHQ9ZnVuY3Rpb24oZSx0KXt0aGlzLmluaXQoInBvcG92ZXIiLGUsdCl9O3QucHJvdG90eXBlPWUuZXh0ZW5kKHt9LGUuZm4udG9vbHRpcC5Db25zdHJ1Y3Rvci5wcm90b3R5cGUse2NvbnN0cnVjdG9yOnQsc2V0Q29udGVudDpmdW5jdGlvbigpe3ZhciBlPXRoaXMudGlwKCksdD10aGlzLmdldFRpdGxlKCksbj10aGlzLmdldENvbnRlbnQoKTtlLmZpbmQoIi5wb3BvdmVyLXRpdGxlIilbdGhpcy5vcHRpb25zLmh0bWw/Imh0bWwiOiJ0ZXh0Il0odCksZS5maW5kKCIucG9wb3Zlci1jb250ZW50IilbdGhpcy5vcHRpb25zLmh0bWw/Imh0bWwiOiJ0ZXh0Il0obiksZS5yZW1vdmVDbGFzcygiZmFkZSB0b3AgYm90dG9tIGxlZnQgcmlnaHQgaW4iKX0saGFzQ29udGVudDpmdW5jdGlvbigpe3JldHVybiB0aGlzLmdldFRpdGxlKCl8fHRoaXMuZ2V0Q29udGVudCgpfSxnZXRDb250ZW50OmZ1bmN0aW9uKCl7dmFyIGUsdD10aGlzLiRlbGVtZW50LG49dGhpcy5vcHRpb25zO3JldHVybiBlPSh0eXBlb2Ygbi5jb250ZW50PT0iZnVuY3Rpb24iP24uY29udGVudC5jYWxsKHRbMF0pOm4uY29udGVudCl8fHQuYXR0cigiZGF0YS1jb250ZW50IiksZX0sdGlwOmZ1bmN0aW9uKCl7cmV0dXJuIHRoaXMuJHRpcHx8KHRoaXMuJHRpcD1lKHRoaXMub3B0aW9ucy50ZW1wbGF0ZSkpLHRoaXMuJHRpcH0sZGVzdHJveTpmdW5jdGlvbigpe3RoaXMuaGlkZSgpLiRlbGVtZW50Lm9mZigiLiIrdGhpcy50eXBlKS5yZW1vdmVEYXRhKHRoaXMudHlwZSl9fSk7dmFyIG49ZS5mbi5wb3BvdmVyO2UuZm4ucG9wb3Zlcj1mdW5jdGlvbihuKXtyZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uKCl7dmFyIHI9ZSh0aGlzKSxpPXIuZGF0YSgicG9wb3ZlciIpLHM9dHlwZW9mIG49PSJvYmplY3QiJiZuO2l8fHIuZGF0YSgicG9wb3ZlciIsaT1uZXcgdCh0aGlzLHMpKSx0eXBlb2Ygbj09InN0cmluZyImJmlbbl0oKX0pfSxlLmZuLnBvcG92ZXIuQ29uc3RydWN0b3I9dCxlLmZuLnBvcG92ZXIuZGVmYXVsdHM9ZS5leHRlbmQoe30sZS5mbi50b29sdGlwLmRlZmF1bHRzLHtwbGFjZW1lbnQ6InJpZ2h0Iix0cmlnZ2VyOiJjbGljayIsY29udGVudDoiIix0ZW1wbGF0ZTonPGRpdiBjbGFzcz0icG9wb3ZlciI+PGRpdiBjbGFzcz0iYXJyb3ciPjwvZGl2PjxoMyBjbGFzcz0icG9wb3Zlci10aXRsZSI+PC9oMz48ZGl2IGNsYXNzPSJwb3BvdmVyLWNvbnRlbnQiPjwvZGl2PjwvZGl2Pid9KSxlLmZuLnBvcG92ZXIubm9Db25mbGljdD1mdW5jdGlvbigpe3JldHVybiBlLmZuLnBvcG92ZXI9bix0aGlzfX0od2luZG93LmpRdWVyeSksIWZ1bmN0aW9uKGUpeyJ1c2Ugc3RyaWN0IjtmdW5jdGlvbiB0KHQsbil7dmFyIHI9ZS5wcm94eSh0aGlzLnByb2Nlc3MsdGhpcyksaT1lKHQpLmlzKCJib2R5Iik/ZSh3aW5kb3cpOmUodCksczt0aGlzLm9wdGlvbnM9ZS5leHRlbmQoe30sZS5mbi5zY3JvbGxzcHkuZGVmYXVsdHMsbiksdGhpcy4kc2Nyb2xsRWxlbWVudD1pLm9uKCJzY3JvbGwuc2Nyb2xsLXNweS5kYXRhLWFwaSIsciksdGhpcy5zZWxlY3Rvcj0odGhpcy5vcHRpb25zLnRhcmdldHx8KHM9ZSh0KS5hdHRyKCJocmVmIikpJiZzLnJlcGxhY2UoLy4qKD89I1teXHNdKyQpLywiIil8fCIiKSsiIC5uYXYgbGkgPiBhIix0aGlzLiRib2R5PWUoImJvZHkiKSx0aGlzLnJlZnJlc2goKSx0aGlzLnByb2Nlc3MoKX10LnByb3RvdHlwZT17Y29uc3RydWN0b3I6dCxyZWZyZXNoOmZ1bmN0aW9uKCl7dmFyIHQ9dGhpcyxuO3RoaXMub2Zmc2V0cz1lKFtdKSx0aGlzLnRhcmdldHM9ZShbXSksbj10aGlzLiRib2R5LmZpbmQodGhpcy5zZWxlY3RvcikubWFwKGZ1bmN0aW9uKCl7dmFyIG49ZSh0aGlzKSxyPW4uZGF0YSgidGFyZ2V0Iil8fG4uYXR0cigiaHJlZiIpLGk9L14jXHcvLnRlc3QocikmJmUocik7cmV0dXJuIGkmJmkubGVuZ3RoJiZbW2kucG9zaXRpb24oKS50b3ArKCFlLmlzV2luZG93KHQuJHNjcm9sbEVsZW1lbnQuZ2V0KDApKSYmdC4kc2Nyb2xsRWxlbWVudC5zY3JvbGxUb3AoKSkscl1dfHxudWxsfSkuc29ydChmdW5jdGlvbihlLHQpe3JldHVybiBlWzBdLXRbMF19KS5lYWNoKGZ1bmN0aW9uKCl7dC5vZmZzZXRzLnB1c2godGhpc1swXSksdC50YXJnZXRzLnB1c2godGhpc1sxXSl9KX0scHJvY2VzczpmdW5jdGlvbigpe3ZhciBlPXRoaXMuJHNjcm9sbEVsZW1lbnQuc2Nyb2xsVG9wKCkrdGhpcy5vcHRpb25zLm9mZnNldCx0PXRoaXMuJHNjcm9sbEVsZW1lbnRbMF0uc2Nyb2xsSGVpZ2h0fHx0aGlzLiRib2R5WzBdLnNjcm9sbEhlaWdodCxuPXQtdGhpcy4kc2Nyb2xsRWxlbWVudC5oZWlnaHQoKSxyPXRoaXMub2Zmc2V0cyxpPXRoaXMudGFyZ2V0cyxzPXRoaXMuYWN0aXZlVGFyZ2V0LG87aWYoZT49bilyZXR1cm4gcyE9KG89aS5sYXN0KClbMF0pJiZ0aGlzLmFjdGl2YXRlKG8pO2ZvcihvPXIubGVuZ3RoO28tLTspcyE9aVtvXSYmZT49cltvXSYmKCFyW28rMV18fGU8PXJbbysxXSkmJnRoaXMuYWN0aXZhdGUoaVtvXSl9LGFjdGl2YXRlOmZ1bmN0aW9uKHQpe3ZhciBuLHI7dGhpcy5hY3RpdmVUYXJnZXQ9dCxlKHRoaXMuc2VsZWN0b3IpLnBhcmVudCgiLmFjdGl2ZSIpLnJlbW92ZUNsYXNzKCJhY3RpdmUiKSxyPXRoaXMuc2VsZWN0b3IrJ1tkYXRhLXRhcmdldD0iJyt0KyciXSwnK3RoaXMuc2VsZWN0b3IrJ1tocmVmPSInK3QrJyJdJyxuPWUocikucGFyZW50KCJsaSIpLmFkZENsYXNzKCJhY3RpdmUiKSxuLnBhcmVudCgiLmRyb3Bkb3duLW1lbnUiKS5sZW5ndGgmJihuPW4uY2xvc2VzdCgibGkuZHJvcGRvd24iKS5hZGRDbGFzcygiYWN0aXZlIikpLG4udHJpZ2dlcigiYWN0aXZhdGUiKX19O3ZhciBuPWUuZm4uc2Nyb2xsc3B5O2UuZm4uc2Nyb2xsc3B5PWZ1bmN0aW9uKG4pe3JldHVybiB0aGlzLmVhY2goZnVuY3Rpb24oKXt2YXIgcj1lKHRoaXMpLGk9ci5kYXRhKCJzY3JvbGxzcHkiKSxzPXR5cGVvZiBuPT0ib2JqZWN0IiYmbjtpfHxyLmRhdGEoInNjcm9sbHNweSIsaT1uZXcgdCh0aGlzLHMpKSx0eXBlb2Ygbj09InN0cmluZyImJmlbbl0oKX0pfSxlLmZuLnNjcm9sbHNweS5Db25zdHJ1Y3Rvcj10LGUuZm4uc2Nyb2xsc3B5LmRlZmF1bHRzPXtvZmZzZXQ6MTB9LGUuZm4uc2Nyb2xsc3B5Lm5vQ29uZmxpY3Q9ZnVuY3Rpb24oKXtyZXR1cm4gZS5mbi5zY3JvbGxzcHk9bix0aGlzfSxlKHdpbmRvdykub24oImxvYWQiLGZ1bmN0aW9uKCl7ZSgnW2RhdGEtc3B5PSJzY3JvbGwiXScpLmVhY2goZnVuY3Rpb24oKXt2YXIgdD1lKHRoaXMpO3Quc2Nyb2xsc3B5KHQuZGF0YSgpKX0pfSl9KHdpbmRvdy5qUXVlcnkpLCFmdW5jdGlvbihlKXsidXNlIHN0cmljdCI7dmFyIHQ9ZnVuY3Rpb24odCl7dGhpcy5lbGVtZW50PWUodCl9O3QucHJvdG90eXBlPXtjb25zdHJ1Y3Rvcjp0LHNob3c6ZnVuY3Rpb24oKXt2YXIgdD10aGlzLmVsZW1lbnQsbj10LmNsb3Nlc3QoInVsOm5vdCguZHJvcGRvd24tbWVudSkiKSxyPXQuYXR0cigiZGF0YS10YXJnZXQiKSxpLHMsbztyfHwocj10LmF0dHIoImhyZWYiKSxyPXImJnIucmVwbGFjZSgvLiooPz0jW15cc10qJCkvLCIiKSk7aWYodC5wYXJlbnQoImxpIikuaGFzQ2xhc3MoImFjdGl2ZSIpKXJldHVybjtpPW4uZmluZCgiLmFjdGl2ZTpsYXN0IGEiKVswXSxvPWUuRXZlbnQoInNob3ciLHtyZWxhdGVkVGFyZ2V0Oml9KSx0LnRyaWdnZXIobyk7aWYoby5pc0RlZmF1bHRQcmV2ZW50ZWQoKSlyZXR1cm47cz1lKHIpLHRoaXMuYWN0aXZhdGUodC5wYXJlbnQoImxpIiksbiksdGhpcy5hY3RpdmF0ZShzLHMucGFyZW50KCksZnVuY3Rpb24oKXt0LnRyaWdnZXIoe3R5cGU6InNob3duIixyZWxhdGVkVGFyZ2V0Oml9KX0pfSxhY3RpdmF0ZTpmdW5jdGlvbih0LG4scil7ZnVuY3Rpb24gbygpe2kucmVtb3ZlQ2xhc3MoImFjdGl2ZSIpLmZpbmQoIj4gLmRyb3Bkb3duLW1lbnUgPiAuYWN0aXZlIikucmVtb3ZlQ2xhc3MoImFjdGl2ZSIpLHQuYWRkQ2xhc3MoImFjdGl2ZSIpLHM/KHRbMF0ub2Zmc2V0V2lkdGgsdC5hZGRDbGFzcygiaW4iKSk6dC5yZW1vdmVDbGFzcygiZmFkZSIpLHQucGFyZW50KCIuZHJvcGRvd24tbWVudSIpJiZ0LmNsb3Nlc3QoImxpLmRyb3Bkb3duIikuYWRkQ2xhc3MoImFjdGl2ZSIpLHImJnIoKX12YXIgaT1uLmZpbmQoIj4gLmFjdGl2ZSIpLHM9ciYmZS5zdXBwb3J0LnRyYW5zaXRpb24mJmkuaGFzQ2xhc3MoImZhZGUiKTtzP2kub25lKGUuc3VwcG9ydC50cmFuc2l0aW9uLmVuZCxvKTpvKCksaS5yZW1vdmVDbGFzcygiaW4iKX19O3ZhciBuPWUuZm4udGFiO2UuZm4udGFiPWZ1bmN0aW9uKG4pe3JldHVybiB0aGlzLmVhY2goZnVuY3Rpb24oKXt2YXIgcj1lKHRoaXMpLGk9ci5kYXRhKCJ0YWIiKTtpfHxyLmRhdGEoInRhYiIsaT1uZXcgdCh0aGlzKSksdHlwZW9mIG49PSJzdHJpbmciJiZpW25dKCl9KX0sZS5mbi50YWIuQ29uc3RydWN0b3I9dCxlLmZuLnRhYi5ub0NvbmZsaWN0PWZ1bmN0aW9uKCl7cmV0dXJuIGUuZm4udGFiPW4sdGhpc30sZShkb2N1bWVudCkub24oImNsaWNrLnRhYi5kYXRhLWFwaSIsJ1tkYXRhLXRvZ2dsZT0idGFiIl0sIFtkYXRhLXRvZ2dsZT0icGlsbCJdJyxmdW5jdGlvbih0KXt0LnByZXZlbnREZWZhdWx0KCksZSh0aGlzKS50YWIoInNob3ciKX0pfSh3aW5kb3cualF1ZXJ5KSwhZnVuY3Rpb24oZSl7InVzZSBzdHJpY3QiO3ZhciB0PWZ1bmN0aW9uKHQsbil7dGhpcy4kZWxlbWVudD1lKHQpLHRoaXMub3B0aW9ucz1lLmV4dGVuZCh7fSxlLmZuLnR5cGVhaGVhZC5kZWZhdWx0cyxuKSx0aGlzLm1hdGNoZXI9dGhpcy5vcHRpb25zLm1hdGNoZXJ8fHRoaXMubWF0Y2hlcix0aGlzLnNvcnRlcj10aGlzLm9wdGlvbnMuc29ydGVyfHx0aGlzLnNvcnRlcix0aGlzLmhpZ2hsaWdodGVyPXRoaXMub3B0aW9ucy5oaWdobGlnaHRlcnx8dGhpcy5oaWdobGlnaHRlcix0aGlzLnVwZGF0ZXI9dGhpcy5vcHRpb25zLnVwZGF0ZXJ8fHRoaXMudXBkYXRlcix0aGlzLnNvdXJjZT10aGlzLm9wdGlvbnMuc291cmNlLHRoaXMuJG1lbnU9ZSh0aGlzLm9wdGlvbnMubWVudSksdGhpcy5zaG93bj0hMSx0aGlzLmxpc3RlbigpfTt0LnByb3RvdHlwZT17Y29uc3RydWN0b3I6dCxzZWxlY3Q6ZnVuY3Rpb24oKXt2YXIgZT10aGlzLiRtZW51LmZpbmQoIi5hY3RpdmUiKS5hdHRyKCJkYXRhLXZhbHVlIik7cmV0dXJuIHRoaXMuJGVsZW1lbnQudmFsKHRoaXMudXBkYXRlcihlKSkuY2hhbmdlKCksdGhpcy5oaWRlKCl9LHVwZGF0ZXI6ZnVuY3Rpb24oZSl7cmV0dXJuIGV9LHNob3c6ZnVuY3Rpb24oKXt2YXIgdD1lLmV4dGVuZCh7fSx0aGlzLiRlbGVtZW50LnBvc2l0aW9uKCkse2hlaWdodDp0aGlzLiRlbGVtZW50WzBdLm9mZnNldEhlaWdodH0pO3JldHVybiB0aGlzLiRtZW51Lmluc2VydEFmdGVyKHRoaXMuJGVsZW1lbnQpLmNzcyh7dG9wOnQudG9wK3QuaGVpZ2h0LGxlZnQ6dC5sZWZ0fSkuc2hvdygpLHRoaXMuc2hvd249ITAsdGhpc30saGlkZTpmdW5jdGlvbigpe3JldHVybiB0aGlzLiRtZW51LmhpZGUoKSx0aGlzLnNob3duPSExLHRoaXN9LGxvb2t1cDpmdW5jdGlvbih0KXt2YXIgbjtyZXR1cm4gdGhpcy5xdWVyeT10aGlzLiRlbGVtZW50LnZhbCgpLCF0aGlzLnF1ZXJ5fHx0aGlzLnF1ZXJ5Lmxlbmd0aDx0aGlzLm9wdGlvbnMubWluTGVuZ3RoP3RoaXMuc2hvd24/dGhpcy5oaWRlKCk6dGhpczoobj1lLmlzRnVuY3Rpb24odGhpcy5zb3VyY2UpP3RoaXMuc291cmNlKHRoaXMucXVlcnksZS5wcm94eSh0aGlzLnByb2Nlc3MsdGhpcykpOnRoaXMuc291cmNlLG4/dGhpcy5wcm9jZXNzKG4pOnRoaXMpfSxwcm9jZXNzOmZ1bmN0aW9uKHQpe3ZhciBuPXRoaXM7cmV0dXJuIHQ9ZS5ncmVwKHQsZnVuY3Rpb24oZSl7cmV0dXJuIG4ubWF0Y2hlcihlKX0pLHQ9dGhpcy5zb3J0ZXIodCksdC5sZW5ndGg/dGhpcy5yZW5kZXIodC5zbGljZSgwLHRoaXMub3B0aW9ucy5pdGVtcykpLnNob3coKTp0aGlzLnNob3duP3RoaXMuaGlkZSgpOnRoaXN9LG1hdGNoZXI6ZnVuY3Rpb24oZSl7cmV0dXJufmUudG9Mb3dlckNhc2UoKS5pbmRleE9mKHRoaXMucXVlcnkudG9Mb3dlckNhc2UoKSl9LHNvcnRlcjpmdW5jdGlvbihlKXt2YXIgdD1bXSxuPVtdLHI9W10saTt3aGlsZShpPWUuc2hpZnQoKSlpLnRvTG93ZXJDYXNlKCkuaW5kZXhPZih0aGlzLnF1ZXJ5LnRvTG93ZXJDYXNlKCkpP35pLmluZGV4T2YodGhpcy5xdWVyeSk/bi5wdXNoKGkpOnIucHVzaChpKTp0LnB1c2goaSk7cmV0dXJuIHQuY29uY2F0KG4scil9LGhpZ2hsaWdodGVyOmZ1bmN0aW9uKGUpe3ZhciB0PXRoaXMucXVlcnkucmVwbGFjZSgvW1wtXFtcXXt9KCkqKz8uLFxcXF4kfCNcc10vZywiXFwkJiIpO3JldHVybiBlLnJlcGxhY2UobmV3IFJlZ0V4cCgiKCIrdCsiKSIsImlnIiksZnVuY3Rpb24oZSx0KXtyZXR1cm4iPHN0cm9uZz4iK3QrIjwvc3Ryb25nPiJ9KX0scmVuZGVyOmZ1bmN0aW9uKHQpe3ZhciBuPXRoaXM7cmV0dXJuIHQ9ZSh0KS5tYXAoZnVuY3Rpb24odCxyKXtyZXR1cm4gdD1lKG4ub3B0aW9ucy5pdGVtKS5hdHRyKCJkYXRhLXZhbHVlIixyKSx0LmZpbmQoImEiKS5odG1sKG4uaGlnaGxpZ2h0ZXIocikpLHRbMF19KSx0LmZpcnN0KCkuYWRkQ2xhc3MoImFjdGl2ZSIpLHRoaXMuJG1lbnUuaHRtbCh0KSx0aGlzfSxuZXh0OmZ1bmN0aW9uKHQpe3ZhciBuPXRoaXMuJG1lbnUuZmluZCgiLmFjdGl2ZSIpLnJlbW92ZUNsYXNzKCJhY3RpdmUiKSxyPW4ubmV4dCgpO3IubGVuZ3RofHwocj1lKHRoaXMuJG1lbnUuZmluZCgibGkiKVswXSkpLHIuYWRkQ2xhc3MoImFjdGl2ZSIpfSxwcmV2OmZ1bmN0aW9uKGUpe3ZhciB0PXRoaXMuJG1lbnUuZmluZCgiLmFjdGl2ZSIpLnJlbW92ZUNsYXNzKCJhY3RpdmUiKSxuPXQucHJldigpO24ubGVuZ3RofHwobj10aGlzLiRtZW51LmZpbmQoImxpIikubGFzdCgpKSxuLmFkZENsYXNzKCJhY3RpdmUiKX0sbGlzdGVuOmZ1bmN0aW9uKCl7dGhpcy4kZWxlbWVudC5vbigiZm9jdXMiLGUucHJveHkodGhpcy5mb2N1cyx0aGlzKSkub24oImJsdXIiLGUucHJveHkodGhpcy5ibHVyLHRoaXMpKS5vbigia2V5cHJlc3MiLGUucHJveHkodGhpcy5rZXlwcmVzcyx0aGlzKSkub24oImtleXVwIixlLnByb3h5KHRoaXMua2V5dXAsdGhpcykpLHRoaXMuZXZlbnRTdXBwb3J0ZWQoImtleWRvd24iKSYmdGhpcy4kZWxlbWVudC5vbigia2V5ZG93biIsZS5wcm94eSh0aGlzLmtleWRvd24sdGhpcykpLHRoaXMuJG1lbnUub24oImNsaWNrIixlLnByb3h5KHRoaXMuY2xpY2ssdGhpcykpLm9uKCJtb3VzZWVudGVyIiwibGkiLGUucHJveHkodGhpcy5tb3VzZWVudGVyLHRoaXMpKS5vbigibW91c2VsZWF2ZSIsImxpIixlLnByb3h5KHRoaXMubW91c2VsZWF2ZSx0aGlzKSl9LGV2ZW50U3VwcG9ydGVkOmZ1bmN0aW9uKGUpe3ZhciB0PWUgaW4gdGhpcy4kZWxlbWVudDtyZXR1cm4gdHx8KHRoaXMuJGVsZW1lbnQuc2V0QXR0cmlidXRlKGUsInJldHVybjsiKSx0PXR5cGVvZiB0aGlzLiRlbGVtZW50W2VdPT0iZnVuY3Rpb24iKSx0fSxtb3ZlOmZ1bmN0aW9uKGUpe2lmKCF0aGlzLnNob3duKXJldHVybjtzd2l0Y2goZS5rZXlDb2RlKXtjYXNlIDk6Y2FzZSAxMzpjYXNlIDI3OmUucHJldmVudERlZmF1bHQoKTticmVhaztjYXNlIDM4OmUucHJldmVudERlZmF1bHQoKSx0aGlzLnByZXYoKTticmVhaztjYXNlIDQwOmUucHJldmVudERlZmF1bHQoKSx0aGlzLm5leHQoKX1lLnN0b3BQcm9wYWdhdGlvbigpfSxrZXlkb3duOmZ1bmN0aW9uKHQpe3RoaXMuc3VwcHJlc3NLZXlQcmVzc1JlcGVhdD1+ZS5pbkFycmF5KHQua2V5Q29kZSxbNDAsMzgsOSwxMywyN10pLHRoaXMubW92ZSh0KX0sa2V5cHJlc3M6ZnVuY3Rpb24oZSl7aWYodGhpcy5zdXBwcmVzc0tleVByZXNzUmVwZWF0KXJldHVybjt0aGlzLm1vdmUoZSl9LGtleXVwOmZ1bmN0aW9uKGUpe3N3aXRjaChlLmtleUNvZGUpe2Nhc2UgNDA6Y2FzZSAzODpjYXNlIDE2OmNhc2UgMTc6Y2FzZSAxODpicmVhaztjYXNlIDk6Y2FzZSAxMzppZighdGhpcy5zaG93bilyZXR1cm47dGhpcy5zZWxlY3QoKTticmVhaztjYXNlIDI3OmlmKCF0aGlzLnNob3duKXJldHVybjt0aGlzLmhpZGUoKTticmVhaztkZWZhdWx0OnRoaXMubG9va3VwKCl9ZS5zdG9wUHJvcGFnYXRpb24oKSxlLnByZXZlbnREZWZhdWx0KCl9LGZvY3VzOmZ1bmN0aW9uKGUpe3RoaXMuZm9jdXNlZD0hMH0sYmx1cjpmdW5jdGlvbihlKXt0aGlzLmZvY3VzZWQ9ITEsIXRoaXMubW91c2Vkb3ZlciYmdGhpcy5zaG93biYmdGhpcy5oaWRlKCl9LGNsaWNrOmZ1bmN0aW9uKGUpe2Uuc3RvcFByb3BhZ2F0aW9uKCksZS5wcmV2ZW50RGVmYXVsdCgpLHRoaXMuc2VsZWN0KCksdGhpcy4kZWxlbWVudC5mb2N1cygpfSxtb3VzZWVudGVyOmZ1bmN0aW9uKHQpe3RoaXMubW91c2Vkb3Zlcj0hMCx0aGlzLiRtZW51LmZpbmQoIi5hY3RpdmUiKS5yZW1vdmVDbGFzcygiYWN0aXZlIiksZSh0LmN1cnJlbnRUYXJnZXQpLmFkZENsYXNzKCJhY3RpdmUiKX0sbW91c2VsZWF2ZTpmdW5jdGlvbihlKXt0aGlzLm1vdXNlZG92ZXI9ITEsIXRoaXMuZm9jdXNlZCYmdGhpcy5zaG93biYmdGhpcy5oaWRlKCl9fTt2YXIgbj1lLmZuLnR5cGVhaGVhZDtlLmZuLnR5cGVhaGVhZD1mdW5jdGlvbihuKXtyZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uKCl7dmFyIHI9ZSh0aGlzKSxpPXIuZGF0YSgidHlwZWFoZWFkIikscz10eXBlb2Ygbj09Im9iamVjdCImJm47aXx8ci5kYXRhKCJ0eXBlYWhlYWQiLGk9bmV3IHQodGhpcyxzKSksdHlwZW9mIG49PSJzdHJpbmciJiZpW25dKCl9KX0sZS5mbi50eXBlYWhlYWQuZGVmYXVsdHM9e3NvdXJjZTpbXSxpdGVtczo4LG1lbnU6Jzx1bCBjbGFzcz0idHlwZWFoZWFkIGRyb3Bkb3duLW1lbnUiPjwvdWw+JyxpdGVtOic8bGk+PGEgaHJlZj0iIyI+PC9hPjwvbGk+JyxtaW5MZW5ndGg6MX0sZS5mbi50eXBlYWhlYWQuQ29uc3RydWN0b3I9dCxlLmZuLnR5cGVhaGVhZC5ub0NvbmZsaWN0PWZ1bmN0aW9uKCl7cmV0dXJuIGUuZm4udHlwZWFoZWFkPW4sdGhpc30sZShkb2N1bWVudCkub24oImZvY3VzLnR5cGVhaGVhZC5kYXRhLWFwaSIsJ1tkYXRhLXByb3ZpZGU9InR5cGVhaGVhZCJdJyxmdW5jdGlvbih0KXt2YXIgbj1lKHRoaXMpO2lmKG4uZGF0YSgidHlwZWFoZWFkIikpcmV0dXJuO24udHlwZWFoZWFkKG4uZGF0YSgpKX0pfSh3aW5kb3cualF1ZXJ5KSwhZnVuY3Rpb24oZSl7InVzZSBzdHJpY3QiO3ZhciB0PWZ1bmN0aW9uKHQsbil7dGhpcy5vcHRpb25zPWUuZXh0ZW5kKHt9LGUuZm4uYWZmaXguZGVmYXVsdHMsbiksdGhpcy4kd2luZG93PWUod2luZG93KS5vbigic2Nyb2xsLmFmZml4LmRhdGEtYXBpIixlLnByb3h5KHRoaXMuY2hlY2tQb3NpdGlvbix0aGlzKSkub24oImNsaWNrLmFmZml4LmRhdGEtYXBpIixlLnByb3h5KGZ1bmN0aW9uKCl7c2V0VGltZW91dChlLnByb3h5KHRoaXMuY2hlY2tQb3NpdGlvbix0aGlzKSwxKX0sdGhpcykpLHRoaXMuJGVsZW1lbnQ9ZSh0KSx0aGlzLmNoZWNrUG9zaXRpb24oKX07dC5wcm90b3R5cGUuY2hlY2tQb3NpdGlvbj1mdW5jdGlvbigpe2lmKCF0aGlzLiRlbGVtZW50LmlzKCI6dmlzaWJsZSIpKXJldHVybjt2YXIgdD1lKGRvY3VtZW50KS5oZWlnaHQoKSxuPXRoaXMuJHdpbmRvdy5zY3JvbGxUb3AoKSxyPXRoaXMuJGVsZW1lbnQub2Zmc2V0KCksaT10aGlzLm9wdGlvbnMub2Zmc2V0LHM9aS5ib3R0b20sbz1pLnRvcCx1PSJhZmZpeCBhZmZpeC10b3AgYWZmaXgtYm90dG9tIixhO3R5cGVvZiBpIT0ib2JqZWN0IiYmKHM9bz1pKSx0eXBlb2Ygbz09ImZ1bmN0aW9uIiYmKG89aS50b3AoKSksdHlwZW9mIHM9PSJmdW5jdGlvbiImJihzPWkuYm90dG9tKCkpLGE9dGhpcy51bnBpbiE9bnVsbCYmbit0aGlzLnVucGluPD1yLnRvcD8hMTpzIT1udWxsJiZyLnRvcCt0aGlzLiRlbGVtZW50LmhlaWdodCgpPj10LXM/ImJvdHRvbSI6byE9bnVsbCYmbjw9bz8idG9wIjohMTtpZih0aGlzLmFmZml4ZWQ9PT1hKXJldHVybjt0aGlzLmFmZml4ZWQ9YSx0aGlzLnVucGluPWE9PSJib3R0b20iP3IudG9wLW46bnVsbCx0aGlzLiRlbGVtZW50LnJlbW92ZUNsYXNzKHUpLmFkZENsYXNzKCJhZmZpeCIrKGE/Ii0iK2E6IiIpKX07dmFyIG49ZS5mbi5hZmZpeDtlLmZuLmFmZml4PWZ1bmN0aW9uKG4pe3JldHVybiB0aGlzLmVhY2goZnVuY3Rpb24oKXt2YXIgcj1lKHRoaXMpLGk9ci5kYXRhKCJhZmZpeCIpLHM9dHlwZW9mIG49PSJvYmplY3QiJiZuO2l8fHIuZGF0YSgiYWZmaXgiLGk9bmV3IHQodGhpcyxzKSksdHlwZW9mIG49PSJzdHJpbmciJiZpW25dKCl9KX0sZS5mbi5hZmZpeC5Db25zdHJ1Y3Rvcj10LGUuZm4uYWZmaXguZGVmYXVsdHM9e29mZnNldDowfSxlLmZuLmFmZml4Lm5vQ29uZmxpY3Q9ZnVuY3Rpb24oKXtyZXR1cm4gZS5mbi5hZmZpeD1uLHRoaXN9LGUod2luZG93KS5vbigibG9hZCIsZnVuY3Rpb24oKXtlKCdbZGF0YS1zcHk9ImFmZml4Il0nKS5lYWNoKGZ1bmN0aW9uKCl7dmFyIHQ9ZSh0aGlzKSxuPXQuZGF0YSgpO24ub2Zmc2V0PW4ub2Zmc2V0fHx7fSxuLm9mZnNldEJvdHRvbSYmKG4ub2Zmc2V0LmJvdHRvbT1uLm9mZnNldEJvdHRvbSksbi5vZmZzZXRUb3AmJihuLm9mZnNldC50b3A9bi5vZmZzZXRUb3ApLHQuYWZmaXgobil9KX0pfSh3aW5kb3cualF1ZXJ5KTs=
BUFF_EOF;

        $buff[1] = base64_decode($buff[1]);

        $buffer = empty($buff[$file_id]) ? '' : $buff[$file_id];

        $file = basename(__FILE__);

        // we'll modify the CSS so it points to us and we'll return the image (availalbe from a constant)
        $buffer = str_replace('../img/glyphicons-halflings.png', $file . '?img=glyphicons-halflings', $buffer);
        $buffer = str_replace('../img/glyphicons-halflings-white.png', $file . '?img=glyphicons-halflings-white', $buffer);

//file_put_contents($file_id . '.js.txt', $buffer);

        $this->sendHeader(self::HEADER_JS, $buffer);
	}

	public function outputStyles($file_id = '') {
		$buff[1] = <<<BUFF_EOF
/*!
 * Bootstrap v2.3.2
 *
 * Copyright 2012 Twitter, Inc
 * Licensed under the Apache License v2.0
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Designed and built with all the love in the world @twitter by @mdo and @fat.
 */.clearfix{*zoom:1}.clearfix:before,.clearfix:after{display:table;line-height:0;content:""}.clearfix:after{clear:both}.hide-text{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.input-block-level{display:block;width:100%;min-height:30px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}article,aside,details,figcaption,figure,footer,header,hgroup,nav,section{display:block}audio,canvas,video{display:inline-block;*display:inline;*zoom:1}audio:not([controls]){display:none}html{font-size:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}a:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}a:hover,a:active{outline:0}sub,sup{position:relative;font-size:75%;line-height:0;vertical-align:baseline}sup{top:-0.5em}sub{bottom:-0.25em}img{width:auto\9;height:auto;max-width:100%;vertical-align:middle;border:0;-ms-interpolation-mode:bicubic}#map_canvas img,.google-maps img{max-width:none}button,input,select,textarea{margin:0;font-size:100%;vertical-align:middle}button,input{*overflow:visible;line-height:normal}button::-moz-focus-inner,input::-moz-focus-inner{padding:0;border:0}button,html input[type="button"],input[type="reset"],input[type="submit"]{cursor:pointer;-webkit-appearance:button}label,select,button,input[type="button"],input[type="reset"],input[type="submit"],input[type="radio"],input[type="checkbox"]{cursor:pointer}input[type="search"]{-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;-webkit-appearance:textfield}input[type="search"]::-webkit-search-decoration,input[type="search"]::-webkit-search-cancel-button{-webkit-appearance:none}textarea{overflow:auto;vertical-align:top}@media print{*{color:#000!important;text-shadow:none!important;background:transparent!important;box-shadow:none!important}a,a:visited{text-decoration:underline}a[href]:after{content:" (" attr(href) ")"}abbr[title]:after{content:" (" attr(title) ")"}.ir a:after,a[href^="javascript:"]:after,a[href^="#"]:after{content:""}pre,blockquote{border:1px solid #999;page-break-inside:avoid}thead{display:table-header-group}tr,img{page-break-inside:avoid}img{max-width:100%!important}@page{margin:.5cm}p,h2,h3{orphans:3;widows:3}h2,h3{page-break-after:avoid}}body{margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:20px;color:#333;background-color:#fff}a{color:#08c;text-decoration:none}a:hover,a:focus{color:#005580;text-decoration:underline}.img-rounded{-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.img-polaroid{padding:4px;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.1);-moz-box-shadow:0 1px 3px rgba(0,0,0,0.1);box-shadow:0 1px 3px rgba(0,0,0,0.1)}.img-circle{-webkit-border-radius:500px;-moz-border-radius:500px;border-radius:500px}.row{margin-left:-20px;*zoom:1}.row:before,.row:after{display:table;line-height:0;content:""}.row:after{clear:both}[class*="span"]{float:left;min-height:1px;margin-left:20px}.container,.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:940px}.span12{width:940px}.span11{width:860px}.span10{width:780px}.span9{width:700px}.span8{width:620px}.span7{width:540px}.span6{width:460px}.span5{width:380px}.span4{width:300px}.span3{width:220px}.span2{width:140px}.span1{width:60px}.offset12{margin-left:980px}.offset11{margin-left:900px}.offset10{margin-left:820px}.offset9{margin-left:740px}.offset8{margin-left:660px}.offset7{margin-left:580px}.offset6{margin-left:500px}.offset5{margin-left:420px}.offset4{margin-left:340px}.offset3{margin-left:260px}.offset2{margin-left:180px}.offset1{margin-left:100px}.row-fluid{width:100%;*zoom:1}.row-fluid:before,.row-fluid:after{display:table;line-height:0;content:""}.row-fluid:after{clear:both}.row-fluid [class*="span"]{display:block;float:left;width:100%;min-height:30px;margin-left:2.127659574468085%;*margin-left:2.074468085106383%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="span"]:first-child{margin-left:0}.row-fluid .controls-row [class*="span"]+[class*="span"]{margin-left:2.127659574468085%}.row-fluid .span12{width:100%;*width:99.94680851063829%}.row-fluid .span11{width:91.48936170212765%;*width:91.43617021276594%}.row-fluid .span10{width:82.97872340425532%;*width:82.92553191489361%}.row-fluid .span9{width:74.46808510638297%;*width:74.41489361702126%}.row-fluid .span8{width:65.95744680851064%;*width:65.90425531914893%}.row-fluid .span7{width:57.44680851063829%;*width:57.39361702127659%}.row-fluid .span6{width:48.93617021276595%;*width:48.88297872340425%}.row-fluid .span5{width:40.42553191489362%;*width:40.37234042553192%}.row-fluid .span4{width:31.914893617021278%;*width:31.861702127659576%}.row-fluid .span3{width:23.404255319148934%;*width:23.351063829787233%}.row-fluid .span2{width:14.893617021276595%;*width:14.840425531914894%}.row-fluid .span1{width:6.382978723404255%;*width:6.329787234042553%}.row-fluid .offset12{margin-left:104.25531914893617%;*margin-left:104.14893617021275%}.row-fluid .offset12:first-child{margin-left:102.12765957446808%;*margin-left:102.02127659574467%}.row-fluid .offset11{margin-left:95.74468085106382%;*margin-left:95.6382978723404%}.row-fluid .offset11:first-child{margin-left:93.61702127659574%;*margin-left:93.51063829787232%}.row-fluid .offset10{margin-left:87.23404255319149%;*margin-left:87.12765957446807%}.row-fluid .offset10:first-child{margin-left:85.1063829787234%;*margin-left:84.99999999999999%}.row-fluid .offset9{margin-left:78.72340425531914%;*margin-left:78.61702127659572%}.row-fluid .offset9:first-child{margin-left:76.59574468085106%;*margin-left:76.48936170212764%}.row-fluid .offset8{margin-left:70.2127659574468%;*margin-left:70.10638297872339%}.row-fluid .offset8:first-child{margin-left:68.08510638297872%;*margin-left:67.9787234042553%}.row-fluid .offset7{margin-left:61.70212765957446%;*margin-left:61.59574468085106%}.row-fluid .offset7:first-child{margin-left:59.574468085106375%;*margin-left:59.46808510638297%}.row-fluid .offset6{margin-left:53.191489361702125%;*margin-left:53.085106382978715%}.row-fluid .offset6:first-child{margin-left:51.063829787234035%;*margin-left:50.95744680851063%}.row-fluid .offset5{margin-left:44.68085106382979%;*margin-left:44.57446808510638%}.row-fluid .offset5:first-child{margin-left:42.5531914893617%;*margin-left:42.4468085106383%}.row-fluid .offset4{margin-left:36.170212765957444%;*margin-left:36.06382978723405%}.row-fluid .offset4:first-child{margin-left:34.04255319148936%;*margin-left:33.93617021276596%}.row-fluid .offset3{margin-left:27.659574468085104%;*margin-left:27.5531914893617%}.row-fluid .offset3:first-child{margin-left:25.53191489361702%;*margin-left:25.425531914893618%}.row-fluid .offset2{margin-left:19.148936170212764%;*margin-left:19.04255319148936%}.row-fluid .offset2:first-child{margin-left:17.02127659574468%;*margin-left:16.914893617021278%}.row-fluid .offset1{margin-left:10.638297872340425%;*margin-left:10.53191489361702%}.row-fluid .offset1:first-child{margin-left:8.51063829787234%;*margin-left:8.404255319148938%}[class*="span"].hide,.row-fluid [class*="span"].hide{display:none}[class*="span"].pull-right,.row-fluid [class*="span"].pull-right{float:right}.container{margin-right:auto;margin-left:auto;*zoom:1}.container:before,.container:after{display:table;line-height:0;content:""}.container:after{clear:both}.container-fluid{padding-right:20px;padding-left:20px;*zoom:1}.container-fluid:before,.container-fluid:after{display:table;line-height:0;content:""}.container-fluid:after{clear:both}p{margin:0 0 10px}.lead{margin-bottom:20px;font-size:21px;font-weight:200;line-height:30px}small{font-size:85%}strong{font-weight:bold}em{font-style:italic}cite{font-style:normal}.muted{color:#999}a.muted:hover,a.muted:focus{color:#808080}.text-warning{color:#c09853}a.text-warning:hover,a.text-warning:focus{color:#a47e3c}.text-error{color:#b94a48}a.text-error:hover,a.text-error:focus{color:#953b39}.text-info{color:#3a87ad}a.text-info:hover,a.text-info:focus{color:#2d6987}.text-success{color:#468847}a.text-success:hover,a.text-success:focus{color:#356635}.text-left{text-align:left}.text-right{text-align:right}.text-center{text-align:center}h1,h2,h3,h4,h5,h6{margin:10px 0;font-family:inherit;font-weight:bold;line-height:20px;color:inherit;text-rendering:optimizelegibility}h1 small,h2 small,h3 small,h4 small,h5 small,h6 small{font-weight:normal;line-height:1;color:#999}h1,h2,h3{line-height:40px}h1{font-size:38.5px}h2{font-size:31.5px}h3{font-size:24.5px}h4{font-size:17.5px}h5{font-size:14px}h6{font-size:11.9px}h1 small{font-size:24.5px}h2 small{font-size:17.5px}h3 small{font-size:14px}h4 small{font-size:14px}.page-header{padding-bottom:9px;margin:20px 0 30px;border-bottom:1px solid #eee}ul,ol{padding:0;margin:0 0 10px 25px}ul ul,ul ol,ol ol,ol ul{margin-bottom:0}li{line-height:20px}ul.unstyled,ol.unstyled{margin-left:0;list-style:none}ul.inline,ol.inline{margin-left:0;list-style:none}ul.inline>li,ol.inline>li{display:inline-block;*display:inline;padding-right:5px;padding-left:5px;*zoom:1}dl{margin-bottom:20px}dt,dd{line-height:20px}dt{font-weight:bold}dd{margin-left:10px}.dl-horizontal{*zoom:1}.dl-horizontal:before,.dl-horizontal:after{display:table;line-height:0;content:""}.dl-horizontal:after{clear:both}.dl-horizontal dt{float:left;width:160px;overflow:hidden;clear:left;text-align:right;text-overflow:ellipsis;white-space:nowrap}.dl-horizontal dd{margin-left:180px}hr{margin:20px 0;border:0;border-top:1px solid #eee;border-bottom:1px solid #fff}abbr[title],abbr[data-original-title]{cursor:help;border-bottom:1px dotted #999}abbr.initialism{font-size:90%;text-transform:uppercase}blockquote{padding:0 0 0 15px;margin:0 0 20px;border-left:5px solid #eee}blockquote p{margin-bottom:0;font-size:17.5px;font-weight:300;line-height:1.25}blockquote small{display:block;line-height:20px;color:#999}blockquote small:before{content:'\2014 \00A0'}blockquote.pull-right{float:right;padding-right:15px;padding-left:0;border-right:5px solid #eee;border-left:0}blockquote.pull-right p,blockquote.pull-right small{text-align:right}blockquote.pull-right small:before{content:''}blockquote.pull-right small:after{content:'\00A0 \2014'}q:before,q:after,blockquote:before,blockquote:after{content:""}address{display:block;margin-bottom:20px;font-style:normal;line-height:20px}code,pre{padding:0 3px 2px;font-family:Monaco,Menlo,Consolas,"Courier New",monospace;font-size:12px;color:#333;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}code{padding:2px 4px;color:#d14;white-space:nowrap;background-color:#f7f7f9;border:1px solid #e1e1e8}pre{display:block;padding:9.5px;margin:0 0 10px;font-size:13px;line-height:20px;word-break:break-all;word-wrap:break-word;white-space:pre;white-space:pre-wrap;background-color:#f5f5f5;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.15);-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}pre.prettyprint{margin-bottom:20px}pre code{padding:0;color:inherit;white-space:pre;white-space:pre-wrap;background-color:transparent;border:0}.pre-scrollable{max-height:340px;overflow-y:scroll}form{margin:0 0 20px}fieldset{padding:0;margin:0;border:0}legend{display:block;width:100%;padding:0;margin-bottom:20px;font-size:21px;line-height:40px;color:#333;border:0;border-bottom:1px solid #e5e5e5}legend small{font-size:15px;color:#999}label,input,button,select,textarea{font-size:14px;font-weight:normal;line-height:20px}input,button,select,textarea{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif}label{display:block;margin-bottom:5px}select,textarea,input[type="text"],input[type="password"],input[type="datetime"],input[type="datetime-local"],input[type="date"],input[type="month"],input[type="time"],input[type="week"],input[type="number"],input[type="email"],input[type="url"],input[type="search"],input[type="tel"],input[type="color"],.uneditable-input{display:inline-block;height:20px;padding:4px 6px;margin-bottom:10px;font-size:14px;line-height:20px;color:#555;vertical-align:middle;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}input,textarea,.uneditable-input{width:206px}textarea{height:auto}textarea,input[type="text"],input[type="password"],input[type="datetime"],input[type="datetime-local"],input[type="date"],input[type="month"],input[type="time"],input[type="week"],input[type="number"],input[type="email"],input[type="url"],input[type="search"],input[type="tel"],input[type="color"],.uneditable-input{background-color:#fff;border:1px solid #ccc;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-webkit-transition:border linear .2s,box-shadow linear .2s;-moz-transition:border linear .2s,box-shadow linear .2s;-o-transition:border linear .2s,box-shadow linear .2s;transition:border linear .2s,box-shadow linear .2s}textarea:focus,input[type="text"]:focus,input[type="password"]:focus,input[type="datetime"]:focus,input[type="datetime-local"]:focus,input[type="date"]:focus,input[type="month"]:focus,input[type="time"]:focus,input[type="week"]:focus,input[type="number"]:focus,input[type="email"]:focus,input[type="url"]:focus,input[type="search"]:focus,input[type="tel"]:focus,input[type="color"]:focus,.uneditable-input:focus{border-color:rgba(82,168,236,0.8);outline:0;outline:thin dotted \9;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6)}input[type="radio"],input[type="checkbox"]{margin:4px 0 0;margin-top:1px \9;*margin-top:0;line-height:normal}input[type="file"],input[type="image"],input[type="submit"],input[type="reset"],input[type="button"],input[type="radio"],input[type="checkbox"]{width:auto}select,input[type="file"]{height:30px;*margin-top:4px;line-height:30px}select{width:220px;background-color:#fff;border:1px solid #ccc}select[multiple],select[size]{height:auto}select:focus,input[type="file"]:focus,input[type="radio"]:focus,input[type="checkbox"]:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}.uneditable-input,.uneditable-textarea{color:#999;cursor:not-allowed;background-color:#fcfcfc;border-color:#ccc;-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.025);-moz-box-shadow:inset 0 1px 2px rgba(0,0,0,0.025);box-shadow:inset 0 1px 2px rgba(0,0,0,0.025)}.uneditable-input{overflow:hidden;white-space:nowrap}.uneditable-textarea{width:auto;height:auto}input:-moz-placeholder,textarea:-moz-placeholder{color:#999}input:-ms-input-placeholder,textarea:-ms-input-placeholder{color:#999}input::-webkit-input-placeholder,textarea::-webkit-input-placeholder{color:#999}.radio,.checkbox{min-height:20px;padding-left:20px}.radio input[type="radio"],.checkbox input[type="checkbox"]{float:left;margin-left:-20px}.controls>.radio:first-child,.controls>.checkbox:first-child{padding-top:5px}.radio.inline,.checkbox.inline{display:inline-block;padding-top:5px;margin-bottom:0;vertical-align:middle}.radio.inline+.radio.inline,.checkbox.inline+.checkbox.inline{margin-left:10px}.input-mini{width:60px}.input-small{width:90px}.input-medium{width:150px}.input-large{width:210px}.input-xlarge{width:270px}.input-xxlarge{width:530px}input[class*="span"],select[class*="span"],textarea[class*="span"],.uneditable-input[class*="span"],.row-fluid input[class*="span"],.row-fluid select[class*="span"],.row-fluid textarea[class*="span"],.row-fluid .uneditable-input[class*="span"]{float:none;margin-left:0}.input-append input[class*="span"],.input-append .uneditable-input[class*="span"],.input-prepend input[class*="span"],.input-prepend .uneditable-input[class*="span"],.row-fluid input[class*="span"],.row-fluid select[class*="span"],.row-fluid textarea[class*="span"],.row-fluid .uneditable-input[class*="span"],.row-fluid .input-prepend [class*="span"],.row-fluid .input-append [class*="span"]{display:inline-block}input,textarea,.uneditable-input{margin-left:0}.controls-row [class*="span"]+[class*="span"]{margin-left:20px}input.span12,textarea.span12,.uneditable-input.span12{width:926px}input.span11,textarea.span11,.uneditable-input.span11{width:846px}input.span10,textarea.span10,.uneditable-input.span10{width:766px}input.span9,textarea.span9,.uneditable-input.span9{width:686px}input.span8,textarea.span8,.uneditable-input.span8{width:606px}input.span7,textarea.span7,.uneditable-input.span7{width:526px}input.span6,textarea.span6,.uneditable-input.span6{width:446px}input.span5,textarea.span5,.uneditable-input.span5{width:366px}input.span4,textarea.span4,.uneditable-input.span4{width:286px}input.span3,textarea.span3,.uneditable-input.span3{width:206px}input.span2,textarea.span2,.uneditable-input.span2{width:126px}input.span1,textarea.span1,.uneditable-input.span1{width:46px}.controls-row{*zoom:1}.controls-row:before,.controls-row:after{display:table;line-height:0;content:""}.controls-row:after{clear:both}.controls-row [class*="span"],.row-fluid .controls-row [class*="span"]{float:left}.controls-row .checkbox[class*="span"],.controls-row .radio[class*="span"]{padding-top:5px}input[disabled],select[disabled],textarea[disabled],input[readonly],select[readonly],textarea[readonly]{cursor:not-allowed;background-color:#eee}input[type="radio"][disabled],input[type="checkbox"][disabled],input[type="radio"][readonly],input[type="checkbox"][readonly]{background-color:transparent}.control-group.warning .control-label,.control-group.warning .help-block,.control-group.warning .help-inline{color:#c09853}.control-group.warning .checkbox,.control-group.warning .radio,.control-group.warning input,.control-group.warning select,.control-group.warning textarea{color:#c09853}.control-group.warning input,.control-group.warning select,.control-group.warning textarea{border-color:#c09853;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.warning input:focus,.control-group.warning select:focus,.control-group.warning textarea:focus{border-color:#a47e3c;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e}.control-group.warning .input-prepend .add-on,.control-group.warning .input-append .add-on{color:#c09853;background-color:#fcf8e3;border-color:#c09853}.control-group.error .control-label,.control-group.error .help-block,.control-group.error .help-inline{color:#b94a48}.control-group.error .checkbox,.control-group.error .radio,.control-group.error input,.control-group.error select,.control-group.error textarea{color:#b94a48}.control-group.error input,.control-group.error select,.control-group.error textarea{border-color:#b94a48;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.error input:focus,.control-group.error select:focus,.control-group.error textarea:focus{border-color:#953b39;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392}.control-group.error .input-prepend .add-on,.control-group.error .input-append .add-on{color:#b94a48;background-color:#f2dede;border-color:#b94a48}.control-group.success .control-label,.control-group.success .help-block,.control-group.success .help-inline{color:#468847}.control-group.success .checkbox,.control-group.success .radio,.control-group.success input,.control-group.success select,.control-group.success textarea{color:#468847}.control-group.success input,.control-group.success select,.control-group.success textarea{border-color:#468847;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.success input:focus,.control-group.success select:focus,.control-group.success textarea:focus{border-color:#356635;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b}.control-group.success .input-prepend .add-on,.control-group.success .input-append .add-on{color:#468847;background-color:#dff0d8;border-color:#468847}.control-group.info .control-label,.control-group.info .help-block,.control-group.info .help-inline{color:#3a87ad}.control-group.info .checkbox,.control-group.info .radio,.control-group.info input,.control-group.info select,.control-group.info textarea{color:#3a87ad}.control-group.info input,.control-group.info select,.control-group.info textarea{border-color:#3a87ad;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.info input:focus,.control-group.info select:focus,.control-group.info textarea:focus{border-color:#2d6987;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7ab5d3;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7ab5d3;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7ab5d3}.control-group.info .input-prepend .add-on,.control-group.info .input-append .add-on{color:#3a87ad;background-color:#d9edf7;border-color:#3a87ad}input:focus:invalid,textarea:focus:invalid,select:focus:invalid{color:#b94a48;border-color:#ee5f5b}input:focus:invalid:focus,textarea:focus:invalid:focus,select:focus:invalid:focus{border-color:#e9322d;-webkit-box-shadow:0 0 6px #f8b9b7;-moz-box-shadow:0 0 6px #f8b9b7;box-shadow:0 0 6px #f8b9b7}.form-actions{padding:19px 20px 20px;margin-top:20px;margin-bottom:20px;background-color:#f5f5f5;border-top:1px solid #e5e5e5;*zoom:1}.form-actions:before,.form-actions:after{display:table;line-height:0;content:""}.form-actions:after{clear:both}.help-block,.help-inline{color:#595959}.help-block{display:block;margin-bottom:10px}.help-inline{display:inline-block;*display:inline;padding-left:5px;vertical-align:middle;*zoom:1}.input-append,.input-prepend{display:inline-block;margin-bottom:10px;font-size:0;white-space:nowrap;vertical-align:middle}.input-append input,.input-prepend input,.input-append select,.input-prepend select,.input-append .uneditable-input,.input-prepend .uneditable-input,.input-append .dropdown-menu,.input-prepend .dropdown-menu,.input-append .popover,.input-prepend .popover{font-size:14px}.input-append input,.input-prepend input,.input-append select,.input-prepend select,.input-append .uneditable-input,.input-prepend .uneditable-input{position:relative;margin-bottom:0;*margin-left:0;vertical-align:top;-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-append input:focus,.input-prepend input:focus,.input-append select:focus,.input-prepend select:focus,.input-append .uneditable-input:focus,.input-prepend .uneditable-input:focus{z-index:2}.input-append .add-on,.input-prepend .add-on{display:inline-block;width:auto;height:20px;min-width:16px;padding:4px 5px;font-size:14px;font-weight:normal;line-height:20px;text-align:center;text-shadow:0 1px 0 #fff;background-color:#eee;border:1px solid #ccc}.input-append .add-on,.input-prepend .add-on,.input-append .btn,.input-prepend .btn,.input-append .btn-group>.dropdown-toggle,.input-prepend .btn-group>.dropdown-toggle{vertical-align:top;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.input-append .active,.input-prepend .active{background-color:#a9dba9;border-color:#46a546}.input-prepend .add-on,.input-prepend .btn{margin-right:-1px}.input-prepend .add-on:first-child,.input-prepend .btn:first-child{-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.input-append input,.input-append select,.input-append .uneditable-input{-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.input-append input+.btn-group .btn:last-child,.input-append select+.btn-group .btn:last-child,.input-append .uneditable-input+.btn-group .btn:last-child{-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-append .add-on,.input-append .btn,.input-append .btn-group{margin-left:-1px}.input-append .add-on:last-child,.input-append .btn:last-child,.input-append .btn-group:last-child>.dropdown-toggle{-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-prepend.input-append input,.input-prepend.input-append select,.input-prepend.input-append .uneditable-input{-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.input-prepend.input-append input+.btn-group .btn,.input-prepend.input-append select+.btn-group .btn,.input-prepend.input-append .uneditable-input+.btn-group .btn{-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-prepend.input-append .add-on:first-child,.input-prepend.input-append .btn:first-child{margin-right:-1px;-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.input-prepend.input-append .add-on:last-child,.input-prepend.input-append .btn:last-child{margin-left:-1px;-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-prepend.input-append .btn-group:first-child{margin-left:0}input.search-query{padding-right:14px;padding-right:4px \9;padding-left:14px;padding-left:4px \9;margin-bottom:0;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}.form-search .input-append .search-query,.form-search .input-prepend .search-query{-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.form-search .input-append .search-query{-webkit-border-radius:14px 0 0 14px;-moz-border-radius:14px 0 0 14px;border-radius:14px 0 0 14px}.form-search .input-append .btn{-webkit-border-radius:0 14px 14px 0;-moz-border-radius:0 14px 14px 0;border-radius:0 14px 14px 0}.form-search .input-prepend .search-query{-webkit-border-radius:0 14px 14px 0;-moz-border-radius:0 14px 14px 0;border-radius:0 14px 14px 0}.form-search .input-prepend .btn{-webkit-border-radius:14px 0 0 14px;-moz-border-radius:14px 0 0 14px;border-radius:14px 0 0 14px}.form-search input,.form-inline input,.form-horizontal input,.form-search textarea,.form-inline textarea,.form-horizontal textarea,.form-search select,.form-inline select,.form-horizontal select,.form-search .help-inline,.form-inline .help-inline,.form-horizontal .help-inline,.form-search .uneditable-input,.form-inline .uneditable-input,.form-horizontal .uneditable-input,.form-search .input-prepend,.form-inline .input-prepend,.form-horizontal .input-prepend,.form-search .input-append,.form-inline .input-append,.form-horizontal .input-append{display:inline-block;*display:inline;margin-bottom:0;vertical-align:middle;*zoom:1}.form-search .hide,.form-inline .hide,.form-horizontal .hide{display:none}.form-search label,.form-inline label,.form-search .btn-group,.form-inline .btn-group{display:inline-block}.form-search .input-append,.form-inline .input-append,.form-search .input-prepend,.form-inline .input-prepend{margin-bottom:0}.form-search .radio,.form-search .checkbox,.form-inline .radio,.form-inline .checkbox{padding-left:0;margin-bottom:0;vertical-align:middle}.form-search .radio input[type="radio"],.form-search .checkbox input[type="checkbox"],.form-inline .radio input[type="radio"],.form-inline .checkbox input[type="checkbox"]{float:left;margin-right:3px;margin-left:0}.control-group{margin-bottom:10px}legend+.control-group{margin-top:20px;-webkit-margin-top-collapse:separate}.form-horizontal .control-group{margin-bottom:20px;*zoom:1}.form-horizontal .control-group:before,.form-horizontal .control-group:after{display:table;line-height:0;content:""}.form-horizontal .control-group:after{clear:both}.form-horizontal .control-label{float:left;width:160px;padding-top:5px;text-align:right}.form-horizontal .controls{*display:inline-block;*padding-left:20px;margin-left:180px;*margin-left:0}.form-horizontal .controls:first-child{*padding-left:180px}.form-horizontal .help-block{margin-bottom:0}.form-horizontal input+.help-block,.form-horizontal select+.help-block,.form-horizontal textarea+.help-block,.form-horizontal .uneditable-input+.help-block,.form-horizontal .input-prepend+.help-block,.form-horizontal .input-append+.help-block{margin-top:10px}.form-horizontal .form-actions{padding-left:180px}table{max-width:100%;background-color:transparent;border-collapse:collapse;border-spacing:0}.table{width:100%;margin-bottom:20px}.table th,.table td{padding:8px;line-height:20px;text-align:left;vertical-align:top;border-top:1px solid #ddd}.table th{font-weight:bold}.table thead th{vertical-align:bottom}.table caption+thead tr:first-child th,.table caption+thead tr:first-child td,.table colgroup+thead tr:first-child th,.table colgroup+thead tr:first-child td,.table thead:first-child tr:first-child th,.table thead:first-child tr:first-child td{border-top:0}.table tbody+tbody{border-top:2px solid #ddd}.table .table{background-color:#fff}.table-condensed th,.table-condensed td{padding:4px 5px}.table-bordered{border:1px solid #ddd;border-collapse:separate;*border-collapse:collapse;border-left:0;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.table-bordered th,.table-bordered td{border-left:1px solid #ddd}.table-bordered caption+thead tr:first-child th,.table-bordered caption+tbody tr:first-child th,.table-bordered caption+tbody tr:first-child td,.table-bordered colgroup+thead tr:first-child th,.table-bordered colgroup+tbody tr:first-child th,.table-bordered colgroup+tbody tr:first-child td,.table-bordered thead:first-child tr:first-child th,.table-bordered tbody:first-child tr:first-child th,.table-bordered tbody:first-child tr:first-child td{border-top:0}.table-bordered thead:first-child tr:first-child>th:first-child,.table-bordered tbody:first-child tr:first-child>td:first-child,.table-bordered tbody:first-child tr:first-child>th:first-child{-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topleft:4px}.table-bordered thead:first-child tr:first-child>th:last-child,.table-bordered tbody:first-child tr:first-child>td:last-child,.table-bordered tbody:first-child tr:first-child>th:last-child{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-moz-border-radius-topright:4px}.table-bordered thead:last-child tr:last-child>th:first-child,.table-bordered tbody:last-child tr:last-child>td:first-child,.table-bordered tbody:last-child tr:last-child>th:first-child,.table-bordered tfoot:last-child tr:last-child>td:first-child,.table-bordered tfoot:last-child tr:last-child>th:first-child{-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-moz-border-radius-bottomleft:4px}.table-bordered thead:last-child tr:last-child>th:last-child,.table-bordered tbody:last-child tr:last-child>td:last-child,.table-bordered tbody:last-child tr:last-child>th:last-child,.table-bordered tfoot:last-child tr:last-child>td:last-child,.table-bordered tfoot:last-child tr:last-child>th:last-child{-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-moz-border-radius-bottomright:4px}.table-bordered tfoot+tbody:last-child tr:last-child td:first-child{-webkit-border-bottom-left-radius:0;border-bottom-left-radius:0;-moz-border-radius-bottomleft:0}.table-bordered tfoot+tbody:last-child tr:last-child td:last-child{-webkit-border-bottom-right-radius:0;border-bottom-right-radius:0;-moz-border-radius-bottomright:0}.table-bordered caption+thead tr:first-child th:first-child,.table-bordered caption+tbody tr:first-child td:first-child,.table-bordered colgroup+thead tr:first-child th:first-child,.table-bordered colgroup+tbody tr:first-child td:first-child{-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topleft:4px}.table-bordered caption+thead tr:first-child th:last-child,.table-bordered caption+tbody tr:first-child td:last-child,.table-bordered colgroup+thead tr:first-child th:last-child,.table-bordered colgroup+tbody tr:first-child td:last-child{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-moz-border-radius-topright:4px}.table-striped tbody>tr:nth-child(odd)>td,.table-striped tbody>tr:nth-child(odd)>th{background-color:#f9f9f9}.table-hover tbody tr:hover>td,.table-hover tbody tr:hover>th{background-color:#f5f5f5}table td[class*="span"],table th[class*="span"],.row-fluid table td[class*="span"],.row-fluid table th[class*="span"]{display:table-cell;float:none;margin-left:0}.table td.span1,.table th.span1{float:none;width:44px;margin-left:0}.table td.span2,.table th.span2{float:none;width:124px;margin-left:0}.table td.span3,.table th.span3{float:none;width:204px;margin-left:0}.table td.span4,.table th.span4{float:none;width:284px;margin-left:0}.table td.span5,.table th.span5{float:none;width:364px;margin-left:0}.table td.span6,.table th.span6{float:none;width:444px;margin-left:0}.table td.span7,.table th.span7{float:none;width:524px;margin-left:0}.table td.span8,.table th.span8{float:none;width:604px;margin-left:0}.table td.span9,.table th.span9{float:none;width:684px;margin-left:0}.table td.span10,.table th.span10{float:none;width:764px;margin-left:0}.table td.span11,.table th.span11{float:none;width:844px;margin-left:0}.table td.span12,.table th.span12{float:none;width:924px;margin-left:0}.table tbody tr.success>td{background-color:#dff0d8}.table tbody tr.error>td{background-color:#f2dede}.table tbody tr.warning>td{background-color:#fcf8e3}.table tbody tr.info>td{background-color:#d9edf7}.table-hover tbody tr.success:hover>td{background-color:#d0e9c6}.table-hover tbody tr.error:hover>td{background-color:#ebcccc}.table-hover tbody tr.warning:hover>td{background-color:#faf2cc}.table-hover tbody tr.info:hover>td{background-color:#c4e3f3}[class^="icon-"],[class*=" icon-"]{display:inline-block;width:14px;height:14px;margin-top:1px;*margin-right:.3em;line-height:14px;vertical-align:text-top;background-image:url("../img/glyphicons-halflings.png");background-position:14px 14px;background-repeat:no-repeat}.icon-white,.nav-pills>.active>a>[class^="icon-"],.nav-pills>.active>a>[class*=" icon-"],.nav-list>.active>a>[class^="icon-"],.nav-list>.active>a>[class*=" icon-"],.navbar-inverse .nav>.active>a>[class^="icon-"],.navbar-inverse .nav>.active>a>[class*=" icon-"],.dropdown-menu>li>a:hover>[class^="icon-"],.dropdown-menu>li>a:focus>[class^="icon-"],.dropdown-menu>li>a:hover>[class*=" icon-"],.dropdown-menu>li>a:focus>[class*=" icon-"],.dropdown-menu>.active>a>[class^="icon-"],.dropdown-menu>.active>a>[class*=" icon-"],.dropdown-submenu:hover>a>[class^="icon-"],.dropdown-submenu:focus>a>[class^="icon-"],.dropdown-submenu:hover>a>[class*=" icon-"],.dropdown-submenu:focus>a>[class*=" icon-"]{background-image:url("../img/glyphicons-halflings-white.png")}.icon-glass{background-position:0 0}.icon-music{background-position:-24px 0}.icon-search{background-position:-48px 0}.icon-envelope{background-position:-72px 0}.icon-heart{background-position:-96px 0}.icon-star{background-position:-120px 0}.icon-star-empty{background-position:-144px 0}.icon-user{background-position:-168px 0}.icon-film{background-position:-192px 0}.icon-th-large{background-position:-216px 0}.icon-th{background-position:-240px 0}.icon-th-list{background-position:-264px 0}.icon-ok{background-position:-288px 0}.icon-remove{background-position:-312px 0}.icon-zoom-in{background-position:-336px 0}.icon-zoom-out{background-position:-360px 0}.icon-off{background-position:-384px 0}.icon-signal{background-position:-408px 0}.icon-cog{background-position:-432px 0}.icon-trash{background-position:-456px 0}.icon-home{background-position:0 -24px}.icon-file{background-position:-24px -24px}.icon-time{background-position:-48px -24px}.icon-road{background-position:-72px -24px}.icon-download-alt{background-position:-96px -24px}.icon-download{background-position:-120px -24px}.icon-upload{background-position:-144px -24px}.icon-inbox{background-position:-168px -24px}.icon-play-circle{background-position:-192px -24px}.icon-repeat{background-position:-216px -24px}.icon-refresh{background-position:-240px -24px}.icon-list-alt{background-position:-264px -24px}.icon-lock{background-position:-287px -24px}.icon-flag{background-position:-312px -24px}.icon-headphones{background-position:-336px -24px}.icon-volume-off{background-position:-360px -24px}.icon-volume-down{background-position:-384px -24px}.icon-volume-up{background-position:-408px -24px}.icon-qrcode{background-position:-432px -24px}.icon-barcode{background-position:-456px -24px}.icon-tag{background-position:0 -48px}.icon-tags{background-position:-25px -48px}.icon-book{background-position:-48px -48px}.icon-bookmark{background-position:-72px -48px}.icon-print{background-position:-96px -48px}.icon-camera{background-position:-120px -48px}.icon-font{background-position:-144px -48px}.icon-bold{background-position:-167px -48px}.icon-italic{background-position:-192px -48px}.icon-text-height{background-position:-216px -48px}.icon-text-width{background-position:-240px -48px}.icon-align-left{background-position:-264px -48px}.icon-align-center{background-position:-288px -48px}.icon-align-right{background-position:-312px -48px}.icon-align-justify{background-position:-336px -48px}.icon-list{background-position:-360px -48px}.icon-indent-left{background-position:-384px -48px}.icon-indent-right{background-position:-408px -48px}.icon-facetime-video{background-position:-432px -48px}.icon-picture{background-position:-456px -48px}.icon-pencil{background-position:0 -72px}.icon-map-marker{background-position:-24px -72px}.icon-adjust{background-position:-48px -72px}.icon-tint{background-position:-72px -72px}.icon-edit{background-position:-96px -72px}.icon-share{background-position:-120px -72px}.icon-check{background-position:-144px -72px}.icon-move{background-position:-168px -72px}.icon-step-backward{background-position:-192px -72px}.icon-fast-backward{background-position:-216px -72px}.icon-backward{background-position:-240px -72px}.icon-play{background-position:-264px -72px}.icon-pause{background-position:-288px -72px}.icon-stop{background-position:-312px -72px}.icon-forward{background-position:-336px -72px}.icon-fast-forward{background-position:-360px -72px}.icon-step-forward{background-position:-384px -72px}.icon-eject{background-position:-408px -72px}.icon-chevron-left{background-position:-432px -72px}.icon-chevron-right{background-position:-456px -72px}.icon-plus-sign{background-position:0 -96px}.icon-minus-sign{background-position:-24px -96px}.icon-remove-sign{background-position:-48px -96px}.icon-ok-sign{background-position:-72px -96px}.icon-question-sign{background-position:-96px -96px}.icon-info-sign{background-position:-120px -96px}.icon-screenshot{background-position:-144px -96px}.icon-remove-circle{background-position:-168px -96px}.icon-ok-circle{background-position:-192px -96px}.icon-ban-circle{background-position:-216px -96px}.icon-arrow-left{background-position:-240px -96px}.icon-arrow-right{background-position:-264px -96px}.icon-arrow-up{background-position:-289px -96px}.icon-arrow-down{background-position:-312px -96px}.icon-share-alt{background-position:-336px -96px}.icon-resize-full{background-position:-360px -96px}.icon-resize-small{background-position:-384px -96px}.icon-plus{background-position:-408px -96px}.icon-minus{background-position:-433px -96px}.icon-asterisk{background-position:-456px -96px}.icon-exclamation-sign{background-position:0 -120px}.icon-gift{background-position:-24px -120px}.icon-leaf{background-position:-48px -120px}.icon-fire{background-position:-72px -120px}.icon-eye-open{background-position:-96px -120px}.icon-eye-close{background-position:-120px -120px}.icon-warning-sign{background-position:-144px -120px}.icon-plane{background-position:-168px -120px}.icon-calendar{background-position:-192px -120px}.icon-random{width:16px;background-position:-216px -120px}.icon-comment{background-position:-240px -120px}.icon-magnet{background-position:-264px -120px}.icon-chevron-up{background-position:-288px -120px}.icon-chevron-down{background-position:-313px -119px}.icon-retweet{background-position:-336px -120px}.icon-shopping-cart{background-position:-360px -120px}.icon-folder-close{width:16px;background-position:-384px -120px}.icon-folder-open{width:16px;background-position:-408px -120px}.icon-resize-vertical{background-position:-432px -119px}.icon-resize-horizontal{background-position:-456px -118px}.icon-hdd{background-position:0 -144px}.icon-bullhorn{background-position:-24px -144px}.icon-bell{background-position:-48px -144px}.icon-certificate{background-position:-72px -144px}.icon-thumbs-up{background-position:-96px -144px}.icon-thumbs-down{background-position:-120px -144px}.icon-hand-right{background-position:-144px -144px}.icon-hand-left{background-position:-168px -144px}.icon-hand-up{background-position:-192px -144px}.icon-hand-down{background-position:-216px -144px}.icon-circle-arrow-right{background-position:-240px -144px}.icon-circle-arrow-left{background-position:-264px -144px}.icon-circle-arrow-up{background-position:-288px -144px}.icon-circle-arrow-down{background-position:-312px -144px}.icon-globe{background-position:-336px -144px}.icon-wrench{background-position:-360px -144px}.icon-tasks{background-position:-384px -144px}.icon-filter{background-position:-408px -144px}.icon-briefcase{background-position:-432px -144px}.icon-fullscreen{background-position:-456px -144px}.dropup,.dropdown{position:relative}.dropdown-toggle{*margin-bottom:-3px}.dropdown-toggle:active,.open .dropdown-toggle{outline:0}.caret{display:inline-block;width:0;height:0;vertical-align:top;border-top:4px solid #000;border-right:4px solid transparent;border-left:4px solid transparent;content:""}.dropdown .caret{margin-top:8px;margin-left:2px}.dropdown-menu{position:absolute;top:100%;left:0;z-index:1000;display:none;float:left;min-width:160px;padding:5px 0;margin:2px 0 0;list-style:none;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);*border-right-width:2px;*border-bottom-width:2px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;-webkit-box-shadow:0 5px 10px rgba(0,0,0,0.2);-moz-box-shadow:0 5px 10px rgba(0,0,0,0.2);box-shadow:0 5px 10px rgba(0,0,0,0.2);-webkit-background-clip:padding-box;-moz-background-clip:padding;background-clip:padding-box}.dropdown-menu.pull-right{right:0;left:auto}.dropdown-menu .divider{*width:100%;height:1px;margin:9px 1px;*margin:-5px 0 5px;overflow:hidden;background-color:#e5e5e5;border-bottom:1px solid #fff}.dropdown-menu>li>a{display:block;padding:3px 20px;clear:both;font-weight:normal;line-height:20px;color:#333;white-space:nowrap}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus,.dropdown-submenu:hover>a,.dropdown-submenu:focus>a{color:#fff;text-decoration:none;background-color:#0081c2;background-image:-moz-linear-gradient(top,#08c,#0077b3);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#0077b3));background-image:-webkit-linear-gradient(top,#08c,#0077b3);background-image:-o-linear-gradient(top,#08c,#0077b3);background-image:linear-gradient(to bottom,#08c,#0077b3);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0077b3',GradientType=0)}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{color:#fff;text-decoration:none;background-color:#0081c2;background-image:-moz-linear-gradient(top,#08c,#0077b3);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#0077b3));background-image:-webkit-linear-gradient(top,#08c,#0077b3);background-image:-o-linear-gradient(top,#08c,#0077b3);background-image:linear-gradient(to bottom,#08c,#0077b3);background-repeat:repeat-x;outline:0;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0077b3',GradientType=0)}.dropdown-menu>.disabled>a,.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{color:#999}.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{text-decoration:none;cursor:default;background-color:transparent;background-image:none;filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.open{*z-index:1000}.open>.dropdown-menu{display:block}.dropdown-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:990}.pull-right>.dropdown-menu{right:0;left:auto}.dropup .caret,.navbar-fixed-bottom .dropdown .caret{border-top:0;border-bottom:4px solid #000;content:""}.dropup .dropdown-menu,.navbar-fixed-bottom .dropdown .dropdown-menu{top:auto;bottom:100%;margin-bottom:1px}.dropdown-submenu{position:relative}.dropdown-submenu>.dropdown-menu{top:0;left:100%;margin-top:-6px;margin-left:-1px;-webkit-border-radius:0 6px 6px 6px;-moz-border-radius:0 6px 6px 6px;border-radius:0 6px 6px 6px}.dropdown-submenu:hover>.dropdown-menu{display:block}.dropup .dropdown-submenu>.dropdown-menu{top:auto;bottom:0;margin-top:0;margin-bottom:-2px;-webkit-border-radius:5px 5px 5px 0;-moz-border-radius:5px 5px 5px 0;border-radius:5px 5px 5px 0}.dropdown-submenu>a:after{display:block;float:right;width:0;height:0;margin-top:5px;margin-right:-10px;border-color:transparent;border-left-color:#ccc;border-style:solid;border-width:5px 0 5px 5px;content:" "}.dropdown-submenu:hover>a:after{border-left-color:#fff}.dropdown-submenu.pull-left{float:none}.dropdown-submenu.pull-left>.dropdown-menu{left:-100%;margin-left:10px;-webkit-border-radius:6px 0 6px 6px;-moz-border-radius:6px 0 6px 6px;border-radius:6px 0 6px 6px}.dropdown .dropdown-menu .nav-header{padding-right:20px;padding-left:20px}.typeahead{z-index:1051;margin-top:2px;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.well{min-height:20px;padding:19px;margin-bottom:20px;background-color:#f5f5f5;border:1px solid #e3e3e3;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);box-shadow:inset 0 1px 1px rgba(0,0,0,0.05)}.well blockquote{border-color:#ddd;border-color:rgba(0,0,0,0.15)}.well-large{padding:24px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.well-small{padding:9px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.fade{opacity:0;-webkit-transition:opacity .15s linear;-moz-transition:opacity .15s linear;-o-transition:opacity .15s linear;transition:opacity .15s linear}.fade.in{opacity:1}.collapse{position:relative;height:0;overflow:hidden;-webkit-transition:height .35s ease;-moz-transition:height .35s ease;-o-transition:height .35s ease;transition:height .35s ease}.collapse.in{height:auto}.close{float:right;font-size:20px;font-weight:bold;line-height:20px;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20)}.close:hover,.close:focus{color:#000;text-decoration:none;cursor:pointer;opacity:.4;filter:alpha(opacity=40)}button.close{padding:0;cursor:pointer;background:transparent;border:0;-webkit-appearance:none}.btn{display:inline-block;*display:inline;padding:4px 12px;margin-bottom:0;*margin-left:.3em;font-size:14px;line-height:20px;color:#333;text-align:center;text-shadow:0 1px 1px rgba(255,255,255,0.75);vertical-align:middle;cursor:pointer;background-color:#f5f5f5;*background-color:#e6e6e6;background-image:-moz-linear-gradient(top,#fff,#e6e6e6);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fff),to(#e6e6e6));background-image:-webkit-linear-gradient(top,#fff,#e6e6e6);background-image:-o-linear-gradient(top,#fff,#e6e6e6);background-image:linear-gradient(to bottom,#fff,#e6e6e6);background-repeat:repeat-x;border:1px solid #ccc;*border:0;border-color:#e6e6e6 #e6e6e6 #bfbfbf;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);border-bottom-color:#b3b3b3;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff',endColorstr='#ffe6e6e6',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);*zoom:1;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05)}.btn:hover,.btn:focus,.btn:active,.btn.active,.btn.disabled,.btn[disabled]{color:#333;background-color:#e6e6e6;*background-color:#d9d9d9}.btn:active,.btn.active{background-color:#ccc \9}.btn:first-child{*margin-left:0}.btn:hover,.btn:focus{color:#333;text-decoration:none;background-position:0 -15px;-webkit-transition:background-position .1s linear;-moz-transition:background-position .1s linear;-o-transition:background-position .1s linear;transition:background-position .1s linear}.btn:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}.btn.active,.btn:active{background-image:none;outline:0;-webkit-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05)}.btn.disabled,.btn[disabled]{cursor:default;background-image:none;opacity:.65;filter:alpha(opacity=65);-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none}.btn-large{padding:11px 19px;font-size:17.5px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.btn-large [class^="icon-"],.btn-large [class*=" icon-"]{margin-top:4px}.btn-small{padding:2px 10px;font-size:11.9px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.btn-small [class^="icon-"],.btn-small [class*=" icon-"]{margin-top:0}.btn-mini [class^="icon-"],.btn-mini [class*=" icon-"]{margin-top:-1px}.btn-mini{padding:0 6px;font-size:10.5px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.btn-block{display:block;width:100%;padding-right:0;padding-left:0;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.btn-block+.btn-block{margin-top:5px}input[type="submit"].btn-block,input[type="reset"].btn-block,input[type="button"].btn-block{width:100%}.btn-primary.active,.btn-warning.active,.btn-danger.active,.btn-success.active,.btn-info.active,.btn-inverse.active{color:rgba(255,255,255,0.75)}.btn-primary{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#006dcc;*background-color:#04c;background-image:-moz-linear-gradient(top,#08c,#04c);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#04c));background-image:-webkit-linear-gradient(top,#08c,#04c);background-image:-o-linear-gradient(top,#08c,#04c);background-image:linear-gradient(to bottom,#08c,#04c);background-repeat:repeat-x;border-color:#04c #04c #002a80;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0044cc',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-primary:hover,.btn-primary:focus,.btn-primary:active,.btn-primary.active,.btn-primary.disabled,.btn-primary[disabled]{color:#fff;background-color:#04c;*background-color:#003bb3}.btn-primary:active,.btn-primary.active{background-color:#039 \9}.btn-warning{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#faa732;*background-color:#f89406;background-image:-moz-linear-gradient(top,#fbb450,#f89406);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fbb450),to(#f89406));background-image:-webkit-linear-gradient(top,#fbb450,#f89406);background-image:-o-linear-gradient(top,#fbb450,#f89406);background-image:linear-gradient(to bottom,#fbb450,#f89406);background-repeat:repeat-x;border-color:#f89406 #f89406 #ad6704;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fffbb450',endColorstr='#fff89406',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-warning:hover,.btn-warning:focus,.btn-warning:active,.btn-warning.active,.btn-warning.disabled,.btn-warning[disabled]{color:#fff;background-color:#f89406;*background-color:#df8505}.btn-warning:active,.btn-warning.active{background-color:#c67605 \9}.btn-danger{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#da4f49;*background-color:#bd362f;background-image:-moz-linear-gradient(top,#ee5f5b,#bd362f);background-image:-webkit-gradient(linear,0 0,0 100%,from(#ee5f5b),to(#bd362f));background-image:-webkit-linear-gradient(top,#ee5f5b,#bd362f);background-image:-o-linear-gradient(top,#ee5f5b,#bd362f);background-image:linear-gradient(to bottom,#ee5f5b,#bd362f);background-repeat:repeat-x;border-color:#bd362f #bd362f #802420;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffee5f5b',endColorstr='#ffbd362f',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-danger:hover,.btn-danger:focus,.btn-danger:active,.btn-danger.active,.btn-danger.disabled,.btn-danger[disabled]{color:#fff;background-color:#bd362f;*background-color:#a9302a}.btn-danger:active,.btn-danger.active{background-color:#942a25 \9}.btn-success{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#5bb75b;*background-color:#51a351;background-image:-moz-linear-gradient(top,#62c462,#51a351);background-image:-webkit-gradient(linear,0 0,0 100%,from(#62c462),to(#51a351));background-image:-webkit-linear-gradient(top,#62c462,#51a351);background-image:-o-linear-gradient(top,#62c462,#51a351);background-image:linear-gradient(to bottom,#62c462,#51a351);background-repeat:repeat-x;border-color:#51a351 #51a351 #387038;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff62c462',endColorstr='#ff51a351',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-success:hover,.btn-success:focus,.btn-success:active,.btn-success.active,.btn-success.disabled,.btn-success[disabled]{color:#fff;background-color:#51a351;*background-color:#499249}.btn-success:active,.btn-success.active{background-color:#408140 \9}.btn-info{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#49afcd;*background-color:#2f96b4;background-image:-moz-linear-gradient(top,#5bc0de,#2f96b4);background-image:-webkit-gradient(linear,0 0,0 100%,from(#5bc0de),to(#2f96b4));background-image:-webkit-linear-gradient(top,#5bc0de,#2f96b4);background-image:-o-linear-gradient(top,#5bc0de,#2f96b4);background-image:linear-gradient(to bottom,#5bc0de,#2f96b4);background-repeat:repeat-x;border-color:#2f96b4 #2f96b4 #1f6377;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de',endColorstr='#ff2f96b4',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-info:hover,.btn-info:focus,.btn-info:active,.btn-info.active,.btn-info.disabled,.btn-info[disabled]{color:#fff;background-color:#2f96b4;*background-color:#2a85a0}.btn-info:active,.btn-info.active{background-color:#24748c \9}.btn-inverse{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#363636;*background-color:#222;background-image:-moz-linear-gradient(top,#444,#222);background-image:-webkit-gradient(linear,0 0,0 100%,from(#444),to(#222));background-image:-webkit-linear-gradient(top,#444,#222);background-image:-o-linear-gradient(top,#444,#222);background-image:linear-gradient(to bottom,#444,#222);background-repeat:repeat-x;border-color:#222 #222 #000;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff444444',endColorstr='#ff222222',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-inverse:hover,.btn-inverse:focus,.btn-inverse:active,.btn-inverse.active,.btn-inverse.disabled,.btn-inverse[disabled]{color:#fff;background-color:#222;*background-color:#151515}.btn-inverse:active,.btn-inverse.active{background-color:#080808 \9}button.btn,input[type="submit"].btn{*padding-top:3px;*padding-bottom:3px}button.btn::-moz-focus-inner,input[type="submit"].btn::-moz-focus-inner{padding:0;border:0}button.btn.btn-large,input[type="submit"].btn.btn-large{*padding-top:7px;*padding-bottom:7px}button.btn.btn-small,input[type="submit"].btn.btn-small{*padding-top:3px;*padding-bottom:3px}button.btn.btn-mini,input[type="submit"].btn.btn-mini{*padding-top:1px;*padding-bottom:1px}.btn-link,.btn-link:active,.btn-link[disabled]{background-color:transparent;background-image:none;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none}.btn-link{color:#08c;cursor:pointer;border-color:transparent;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.btn-link:hover,.btn-link:focus{color:#005580;text-decoration:underline;background-color:transparent}.btn-link[disabled]:hover,.btn-link[disabled]:focus{color:#333;text-decoration:none}.btn-group{position:relative;display:inline-block;*display:inline;*margin-left:.3em;font-size:0;white-space:nowrap;vertical-align:middle;*zoom:1}.btn-group:first-child{*margin-left:0}.btn-group+.btn-group{margin-left:5px}.btn-toolbar{margin-top:10px;margin-bottom:10px;font-size:0}.btn-toolbar>.btn+.btn,.btn-toolbar>.btn-group+.btn,.btn-toolbar>.btn+.btn-group{margin-left:5px}.btn-group>.btn{position:relative;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.btn-group>.btn+.btn{margin-left:-1px}.btn-group>.btn,.btn-group>.dropdown-menu,.btn-group>.popover{font-size:14px}.btn-group>.btn-mini{font-size:10.5px}.btn-group>.btn-small{font-size:11.9px}.btn-group>.btn-large{font-size:17.5px}.btn-group>.btn:first-child{margin-left:0;-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-bottomleft:4px;-moz-border-radius-topleft:4px}.btn-group>.btn:last-child,.btn-group>.dropdown-toggle{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-moz-border-radius-topright:4px;-moz-border-radius-bottomright:4px}.btn-group>.btn.large:first-child{margin-left:0;-webkit-border-bottom-left-radius:6px;border-bottom-left-radius:6px;-webkit-border-top-left-radius:6px;border-top-left-radius:6px;-moz-border-radius-bottomleft:6px;-moz-border-radius-topleft:6px}.btn-group>.btn.large:last-child,.btn-group>.large.dropdown-toggle{-webkit-border-top-right-radius:6px;border-top-right-radius:6px;-webkit-border-bottom-right-radius:6px;border-bottom-right-radius:6px;-moz-border-radius-topright:6px;-moz-border-radius-bottomright:6px}.btn-group>.btn:hover,.btn-group>.btn:focus,.btn-group>.btn:active,.btn-group>.btn.active{z-index:2}.btn-group .dropdown-toggle:active,.btn-group.open .dropdown-toggle{outline:0}.btn-group>.btn+.dropdown-toggle{*padding-top:5px;padding-right:8px;*padding-bottom:5px;padding-left:8px;-webkit-box-shadow:inset 1px 0 0 rgba(255,255,255,0.125),inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 1px 0 0 rgba(255,255,255,0.125),inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 1px 0 0 rgba(255,255,255,0.125),inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05)}.btn-group>.btn-mini+.dropdown-toggle{*padding-top:2px;padding-right:5px;*padding-bottom:2px;padding-left:5px}.btn-group>.btn-small+.dropdown-toggle{*padding-top:5px;*padding-bottom:4px}.btn-group>.btn-large+.dropdown-toggle{*padding-top:7px;padding-right:12px;*padding-bottom:7px;padding-left:12px}.btn-group.open .dropdown-toggle{background-image:none;-webkit-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05)}.btn-group.open .btn.dropdown-toggle{background-color:#e6e6e6}.btn-group.open .btn-primary.dropdown-toggle{background-color:#04c}.btn-group.open .btn-warning.dropdown-toggle{background-color:#f89406}.btn-group.open .btn-danger.dropdown-toggle{background-color:#bd362f}.btn-group.open .btn-success.dropdown-toggle{background-color:#51a351}.btn-group.open .btn-info.dropdown-toggle{background-color:#2f96b4}.btn-group.open .btn-inverse.dropdown-toggle{background-color:#222}.btn .caret{margin-top:8px;margin-left:0}.btn-large .caret{margin-top:6px}.btn-large .caret{border-top-width:5px;border-right-width:5px;border-left-width:5px}.btn-mini .caret,.btn-small .caret{margin-top:8px}.dropup .btn-large .caret{border-bottom-width:5px}.btn-primary .caret,.btn-warning .caret,.btn-danger .caret,.btn-info .caret,.btn-success .caret,.btn-inverse .caret{border-top-color:#fff;border-bottom-color:#fff}.btn-group-vertical{display:inline-block;*display:inline;*zoom:1}.btn-group-vertical>.btn{display:block;float:none;max-width:100%;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.btn-group-vertical>.btn+.btn{margin-top:-1px;margin-left:0}.btn-group-vertical>.btn:first-child{-webkit-border-radius:4px 4px 0 0;-moz-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0}.btn-group-vertical>.btn:last-child{-webkit-border-radius:0 0 4px 4px;-moz-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px}.btn-group-vertical>.btn-large:first-child{-webkit-border-radius:6px 6px 0 0;-moz-border-radius:6px 6px 0 0;border-radius:6px 6px 0 0}.btn-group-vertical>.btn-large:last-child{-webkit-border-radius:0 0 6px 6px;-moz-border-radius:0 0 6px 6px;border-radius:0 0 6px 6px}.alert{padding:8px 35px 8px 14px;margin-bottom:20px;text-shadow:0 1px 0 rgba(255,255,255,0.5);background-color:#fcf8e3;border:1px solid #fbeed5;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.alert,.alert h4{color:#c09853}.alert h4{margin:0}.alert .close{position:relative;top:-2px;right:-21px;line-height:20px}.alert-success{color:#468847;background-color:#dff0d8;border-color:#d6e9c6}.alert-success h4{color:#468847}.alert-danger,.alert-error{color:#b94a48;background-color:#f2dede;border-color:#eed3d7}.alert-danger h4,.alert-error h4{color:#b94a48}.alert-info{color:#3a87ad;background-color:#d9edf7;border-color:#bce8f1}.alert-info h4{color:#3a87ad}.alert-block{padding-top:14px;padding-bottom:14px}.alert-block>p,.alert-block>ul{margin-bottom:0}.alert-block p+p{margin-top:5px}.nav{margin-bottom:20px;margin-left:0;list-style:none}.nav>li>a{display:block}.nav>li>a:hover,.nav>li>a:focus{text-decoration:none;background-color:#eee}.nav>li>a>img{max-width:none}.nav>.pull-right{float:right}.nav-header{display:block;padding:3px 15px;font-size:11px;font-weight:bold;line-height:20px;color:#999;text-shadow:0 1px 0 rgba(255,255,255,0.5);text-transform:uppercase}.nav li+.nav-header{margin-top:9px}.nav-list{padding-right:15px;padding-left:15px;margin-bottom:0}.nav-list>li>a,.nav-list .nav-header{margin-right:-15px;margin-left:-15px;text-shadow:0 1px 0 rgba(255,255,255,0.5)}.nav-list>li>a{padding:3px 15px}.nav-list>.active>a,.nav-list>.active>a:hover,.nav-list>.active>a:focus{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.2);background-color:#08c}.nav-list [class^="icon-"],.nav-list [class*=" icon-"]{margin-right:2px}.nav-list .divider{*width:100%;height:1px;margin:9px 1px;*margin:-5px 0 5px;overflow:hidden;background-color:#e5e5e5;border-bottom:1px solid #fff}.nav-tabs,.nav-pills{*zoom:1}.nav-tabs:before,.nav-pills:before,.nav-tabs:after,.nav-pills:after{display:table;line-height:0;content:""}.nav-tabs:after,.nav-pills:after{clear:both}.nav-tabs>li,.nav-pills>li{float:left}.nav-tabs>li>a,.nav-pills>li>a{padding-right:12px;padding-left:12px;margin-right:2px;line-height:14px}.nav-tabs{border-bottom:1px solid #ddd}.nav-tabs>li{margin-bottom:-1px}.nav-tabs>li>a{padding-top:8px;padding-bottom:8px;line-height:20px;border:1px solid transparent;-webkit-border-radius:4px 4px 0 0;-moz-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0}.nav-tabs>li>a:hover,.nav-tabs>li>a:focus{border-color:#eee #eee #ddd}.nav-tabs>.active>a,.nav-tabs>.active>a:hover,.nav-tabs>.active>a:focus{color:#555;cursor:default;background-color:#fff;border:1px solid #ddd;border-bottom-color:transparent}.nav-pills>li>a{padding-top:8px;padding-bottom:8px;margin-top:2px;margin-bottom:2px;-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px}.nav-pills>.active>a,.nav-pills>.active>a:hover,.nav-pills>.active>a:focus{color:#fff;background-color:#08c}.nav-stacked>li{float:none}.nav-stacked>li>a{margin-right:0}.nav-tabs.nav-stacked{border-bottom:0}.nav-tabs.nav-stacked>li>a{border:1px solid #ddd;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.nav-tabs.nav-stacked>li:first-child>a{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topright:4px;-moz-border-radius-topleft:4px}.nav-tabs.nav-stacked>li:last-child>a{-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-moz-border-radius-bottomright:4px;-moz-border-radius-bottomleft:4px}.nav-tabs.nav-stacked>li>a:hover,.nav-tabs.nav-stacked>li>a:focus{z-index:2;border-color:#ddd}.nav-pills.nav-stacked>li>a{margin-bottom:3px}.nav-pills.nav-stacked>li:last-child>a{margin-bottom:1px}.nav-tabs .dropdown-menu{-webkit-border-radius:0 0 6px 6px;-moz-border-radius:0 0 6px 6px;border-radius:0 0 6px 6px}.nav-pills .dropdown-menu{-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.nav .dropdown-toggle .caret{margin-top:6px;border-top-color:#08c;border-bottom-color:#08c}.nav .dropdown-toggle:hover .caret,.nav .dropdown-toggle:focus .caret{border-top-color:#005580;border-bottom-color:#005580}.nav-tabs .dropdown-toggle .caret{margin-top:8px}.nav .active .dropdown-toggle .caret{border-top-color:#fff;border-bottom-color:#fff}.nav-tabs .active .dropdown-toggle .caret{border-top-color:#555;border-bottom-color:#555}.nav>.dropdown.active>a:hover,.nav>.dropdown.active>a:focus{cursor:pointer}.nav-tabs .open .dropdown-toggle,.nav-pills .open .dropdown-toggle,.nav>li.dropdown.open.active>a:hover,.nav>li.dropdown.open.active>a:focus{color:#fff;background-color:#999;border-color:#999}.nav li.dropdown.open .caret,.nav li.dropdown.open.active .caret,.nav li.dropdown.open a:hover .caret,.nav li.dropdown.open a:focus .caret{border-top-color:#fff;border-bottom-color:#fff;opacity:1;filter:alpha(opacity=100)}.tabs-stacked .open>a:hover,.tabs-stacked .open>a:focus{border-color:#999}.tabbable{*zoom:1}.tabbable:before,.tabbable:after{display:table;line-height:0;content:""}.tabbable:after{clear:both}.tab-content{overflow:auto}.tabs-below>.nav-tabs,.tabs-right>.nav-tabs,.tabs-left>.nav-tabs{border-bottom:0}.tab-content>.tab-pane,.pill-content>.pill-pane{display:none}.tab-content>.active,.pill-content>.active{display:block}.tabs-below>.nav-tabs{border-top:1px solid #ddd}.tabs-below>.nav-tabs>li{margin-top:-1px;margin-bottom:0}.tabs-below>.nav-tabs>li>a{-webkit-border-radius:0 0 4px 4px;-moz-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px}.tabs-below>.nav-tabs>li>a:hover,.tabs-below>.nav-tabs>li>a:focus{border-top-color:#ddd;border-bottom-color:transparent}.tabs-below>.nav-tabs>.active>a,.tabs-below>.nav-tabs>.active>a:hover,.tabs-below>.nav-tabs>.active>a:focus{border-color:transparent #ddd #ddd #ddd}.tabs-left>.nav-tabs>li,.tabs-right>.nav-tabs>li{float:none}.tabs-left>.nav-tabs>li>a,.tabs-right>.nav-tabs>li>a{min-width:74px;margin-right:0;margin-bottom:3px}.tabs-left>.nav-tabs{float:left;margin-right:19px;border-right:1px solid #ddd}.tabs-left>.nav-tabs>li>a{margin-right:-1px;-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.tabs-left>.nav-tabs>li>a:hover,.tabs-left>.nav-tabs>li>a:focus{border-color:#eee #ddd #eee #eee}.tabs-left>.nav-tabs .active>a,.tabs-left>.nav-tabs .active>a:hover,.tabs-left>.nav-tabs .active>a:focus{border-color:#ddd transparent #ddd #ddd;*border-right-color:#fff}.tabs-right>.nav-tabs{float:right;margin-left:19px;border-left:1px solid #ddd}.tabs-right>.nav-tabs>li>a{margin-left:-1px;-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.tabs-right>.nav-tabs>li>a:hover,.tabs-right>.nav-tabs>li>a:focus{border-color:#eee #eee #eee #ddd}.tabs-right>.nav-tabs .active>a,.tabs-right>.nav-tabs .active>a:hover,.tabs-right>.nav-tabs .active>a:focus{border-color:#ddd #ddd #ddd transparent;*border-left-color:#fff}.nav>.disabled>a{color:#999}.nav>.disabled>a:hover,.nav>.disabled>a:focus{text-decoration:none;cursor:default;background-color:transparent}.navbar{*position:relative;*z-index:2;margin-bottom:20px;overflow:visible}.navbar-inner{min-height:40px;padding-right:20px;padding-left:20px;background-color:#fafafa;background-image:-moz-linear-gradient(top,#fff,#f2f2f2);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fff),to(#f2f2f2));background-image:-webkit-linear-gradient(top,#fff,#f2f2f2);background-image:-o-linear-gradient(top,#fff,#f2f2f2);background-image:linear-gradient(to bottom,#fff,#f2f2f2);background-repeat:repeat-x;border:1px solid #d4d4d4;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff',endColorstr='#fff2f2f2',GradientType=0);*zoom:1;-webkit-box-shadow:0 1px 4px rgba(0,0,0,0.065);-moz-box-shadow:0 1px 4px rgba(0,0,0,0.065);box-shadow:0 1px 4px rgba(0,0,0,0.065)}.navbar-inner:before,.navbar-inner:after{display:table;line-height:0;content:""}.navbar-inner:after{clear:both}.navbar .container{width:auto}.nav-collapse.collapse{height:auto;overflow:visible}.navbar .brand{display:block;float:left;padding:10px 20px 10px;margin-left:-20px;font-size:20px;font-weight:200;color:#777;text-shadow:0 1px 0 #fff}.navbar .brand:hover,.navbar .brand:focus{text-decoration:none}.navbar-text{margin-bottom:0;line-height:40px;color:#777}.navbar-link{color:#777}.navbar-link:hover,.navbar-link:focus{color:#333}.navbar .divider-vertical{height:40px;margin:0 9px;border-right:1px solid #fff;border-left:1px solid #f2f2f2}.navbar .btn,.navbar .btn-group{margin-top:5px}.navbar .btn-group .btn,.navbar .input-prepend .btn,.navbar .input-append .btn,.navbar .input-prepend .btn-group,.navbar .input-append .btn-group{margin-top:0}.navbar-form{margin-bottom:0;*zoom:1}.navbar-form:before,.navbar-form:after{display:table;line-height:0;content:""}.navbar-form:after{clear:both}.navbar-form input,.navbar-form select,.navbar-form .radio,.navbar-form .checkbox{margin-top:5px}.navbar-form input,.navbar-form select,.navbar-form .btn{display:inline-block;margin-bottom:0}.navbar-form input[type="image"],.navbar-form input[type="checkbox"],.navbar-form input[type="radio"]{margin-top:3px}.navbar-form .input-append,.navbar-form .input-prepend{margin-top:5px;white-space:nowrap}.navbar-form .input-append input,.navbar-form .input-prepend input{margin-top:0}.navbar-search{position:relative;float:left;margin-top:5px;margin-bottom:0}.navbar-search .search-query{padding:4px 14px;margin-bottom:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px;font-weight:normal;line-height:1;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}.navbar-static-top{position:static;margin-bottom:0}.navbar-static-top .navbar-inner{-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.navbar-fixed-top,.navbar-fixed-bottom{position:fixed;right:0;left:0;z-index:1030;margin-bottom:0}.navbar-fixed-top .navbar-inner,.navbar-static-top .navbar-inner{border-width:0 0 1px}.navbar-fixed-bottom .navbar-inner{border-width:1px 0 0}.navbar-fixed-top .navbar-inner,.navbar-fixed-bottom .navbar-inner{padding-right:0;padding-left:0;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:940px}.navbar-fixed-top{top:0}.navbar-fixed-top .navbar-inner,.navbar-static-top .navbar-inner{-webkit-box-shadow:0 1px 10px rgba(0,0,0,0.1);-moz-box-shadow:0 1px 10px rgba(0,0,0,0.1);box-shadow:0 1px 10px rgba(0,0,0,0.1)}.navbar-fixed-bottom{bottom:0}.navbar-fixed-bottom .navbar-inner{-webkit-box-shadow:0 -1px 10px rgba(0,0,0,0.1);-moz-box-shadow:0 -1px 10px rgba(0,0,0,0.1);box-shadow:0 -1px 10px rgba(0,0,0,0.1)}.navbar .nav{position:relative;left:0;display:block;float:left;margin:0 10px 0 0}.navbar .nav.pull-right{float:right;margin-right:0}.navbar .nav>li{float:left}.navbar .nav>li>a{float:none;padding:10px 15px 10px;color:#777;text-decoration:none;text-shadow:0 1px 0 #fff}.navbar .nav .dropdown-toggle .caret{margin-top:8px}.navbar .nav>li>a:focus,.navbar .nav>li>a:hover{color:#333;text-decoration:none;background-color:transparent}.navbar .nav>.active>a,.navbar .nav>.active>a:hover,.navbar .nav>.active>a:focus{color:#555;text-decoration:none;background-color:#e5e5e5;-webkit-box-shadow:inset 0 3px 8px rgba(0,0,0,0.125);-moz-box-shadow:inset 0 3px 8px rgba(0,0,0,0.125);box-shadow:inset 0 3px 8px rgba(0,0,0,0.125)}.navbar .btn-navbar{display:none;float:right;padding:7px 10px;margin-right:5px;margin-left:5px;color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#ededed;*background-color:#e5e5e5;background-image:-moz-linear-gradient(top,#f2f2f2,#e5e5e5);background-image:-webkit-gradient(linear,0 0,0 100%,from(#f2f2f2),to(#e5e5e5));background-image:-webkit-linear-gradient(top,#f2f2f2,#e5e5e5);background-image:-o-linear-gradient(top,#f2f2f2,#e5e5e5);background-image:linear-gradient(to bottom,#f2f2f2,#e5e5e5);background-repeat:repeat-x;border-color:#e5e5e5 #e5e5e5 #bfbfbf;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff2f2f2',endColorstr='#ffe5e5e5',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.075);-moz-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.075);box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.075)}.navbar .btn-navbar:hover,.navbar .btn-navbar:focus,.navbar .btn-navbar:active,.navbar .btn-navbar.active,.navbar .btn-navbar.disabled,.navbar .btn-navbar[disabled]{color:#fff;background-color:#e5e5e5;*background-color:#d9d9d9}.navbar .btn-navbar:active,.navbar .btn-navbar.active{background-color:#ccc \9}.navbar .btn-navbar .icon-bar{display:block;width:18px;height:2px;background-color:#f5f5f5;-webkit-border-radius:1px;-moz-border-radius:1px;border-radius:1px;-webkit-box-shadow:0 1px 0 rgba(0,0,0,0.25);-moz-box-shadow:0 1px 0 rgba(0,0,0,0.25);box-shadow:0 1px 0 rgba(0,0,0,0.25)}.btn-navbar .icon-bar+.icon-bar{margin-top:3px}.navbar .nav>li>.dropdown-menu:before{position:absolute;top:-7px;left:9px;display:inline-block;border-right:7px solid transparent;border-bottom:7px solid #ccc;border-left:7px solid transparent;border-bottom-color:rgba(0,0,0,0.2);content:''}.navbar .nav>li>.dropdown-menu:after{position:absolute;top:-6px;left:10px;display:inline-block;border-right:6px solid transparent;border-bottom:6px solid #fff;border-left:6px solid transparent;content:''}.navbar-fixed-bottom .nav>li>.dropdown-menu:before{top:auto;bottom:-7px;border-top:7px solid #ccc;border-bottom:0;border-top-color:rgba(0,0,0,0.2)}.navbar-fixed-bottom .nav>li>.dropdown-menu:after{top:auto;bottom:-6px;border-top:6px solid #fff;border-bottom:0}.navbar .nav li.dropdown>a:hover .caret,.navbar .nav li.dropdown>a:focus .caret{border-top-color:#333;border-bottom-color:#333}.navbar .nav li.dropdown.open>.dropdown-toggle,.navbar .nav li.dropdown.active>.dropdown-toggle,.navbar .nav li.dropdown.open.active>.dropdown-toggle{color:#555;background-color:#e5e5e5}.navbar .nav li.dropdown>.dropdown-toggle .caret{border-top-color:#777;border-bottom-color:#777}.navbar .nav li.dropdown.open>.dropdown-toggle .caret,.navbar .nav li.dropdown.active>.dropdown-toggle .caret,.navbar .nav li.dropdown.open.active>.dropdown-toggle .caret{border-top-color:#555;border-bottom-color:#555}.navbar .pull-right>li>.dropdown-menu,.navbar .nav>li>.dropdown-menu.pull-right{right:0;left:auto}.navbar .pull-right>li>.dropdown-menu:before,.navbar .nav>li>.dropdown-menu.pull-right:before{right:12px;left:auto}.navbar .pull-right>li>.dropdown-menu:after,.navbar .nav>li>.dropdown-menu.pull-right:after{right:13px;left:auto}.navbar .pull-right>li>.dropdown-menu .dropdown-menu,.navbar .nav>li>.dropdown-menu.pull-right .dropdown-menu{right:100%;left:auto;margin-right:-1px;margin-left:0;-webkit-border-radius:6px 0 6px 6px;-moz-border-radius:6px 0 6px 6px;border-radius:6px 0 6px 6px}.navbar-inverse .navbar-inner{background-color:#1b1b1b;background-image:-moz-linear-gradient(top,#222,#111);background-image:-webkit-gradient(linear,0 0,0 100%,from(#222),to(#111));background-image:-webkit-linear-gradient(top,#222,#111);background-image:-o-linear-gradient(top,#222,#111);background-image:linear-gradient(to bottom,#222,#111);background-repeat:repeat-x;border-color:#252525;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff222222',endColorstr='#ff111111',GradientType=0)}.navbar-inverse .brand,.navbar-inverse .nav>li>a{color:#999;text-shadow:0 -1px 0 rgba(0,0,0,0.25)}.navbar-inverse .brand:hover,.navbar-inverse .nav>li>a:hover,.navbar-inverse .brand:focus,.navbar-inverse .nav>li>a:focus{color:#fff}.navbar-inverse .brand{color:#999}.navbar-inverse .navbar-text{color:#999}.navbar-inverse .nav>li>a:focus,.navbar-inverse .nav>li>a:hover{color:#fff;background-color:transparent}.navbar-inverse .nav .active>a,.navbar-inverse .nav .active>a:hover,.navbar-inverse .nav .active>a:focus{color:#fff;background-color:#111}.navbar-inverse .navbar-link{color:#999}.navbar-inverse .navbar-link:hover,.navbar-inverse .navbar-link:focus{color:#fff}.navbar-inverse .divider-vertical{border-right-color:#222;border-left-color:#111}.navbar-inverse .nav li.dropdown.open>.dropdown-toggle,.navbar-inverse .nav li.dropdown.active>.dropdown-toggle,.navbar-inverse .nav li.dropdown.open.active>.dropdown-toggle{color:#fff;background-color:#111}.navbar-inverse .nav li.dropdown>a:hover .caret,.navbar-inverse .nav li.dropdown>a:focus .caret{border-top-color:#fff;border-bottom-color:#fff}.navbar-inverse .nav li.dropdown>.dropdown-toggle .caret{border-top-color:#999;border-bottom-color:#999}.navbar-inverse .nav li.dropdown.open>.dropdown-toggle .caret,.navbar-inverse .nav li.dropdown.active>.dropdown-toggle .caret,.navbar-inverse .nav li.dropdown.open.active>.dropdown-toggle .caret{border-top-color:#fff;border-bottom-color:#fff}.navbar-inverse .navbar-search .search-query{color:#fff;background-color:#515151;border-color:#111;-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.15);-moz-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.15);box-shadow:inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.15);-webkit-transition:none;-moz-transition:none;-o-transition:none;transition:none}.navbar-inverse .navbar-search .search-query:-moz-placeholder{color:#ccc}.navbar-inverse .navbar-search .search-query:-ms-input-placeholder{color:#ccc}.navbar-inverse .navbar-search .search-query::-webkit-input-placeholder{color:#ccc}.navbar-inverse .navbar-search .search-query:focus,.navbar-inverse .navbar-search .search-query.focused{padding:5px 15px;color:#333;text-shadow:0 1px 0 #fff;background-color:#fff;border:0;outline:0;-webkit-box-shadow:0 0 3px rgba(0,0,0,0.15);-moz-box-shadow:0 0 3px rgba(0,0,0,0.15);box-shadow:0 0 3px rgba(0,0,0,0.15)}.navbar-inverse .btn-navbar{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#0e0e0e;*background-color:#040404;background-image:-moz-linear-gradient(top,#151515,#040404);background-image:-webkit-gradient(linear,0 0,0 100%,from(#151515),to(#040404));background-image:-webkit-linear-gradient(top,#151515,#040404);background-image:-o-linear-gradient(top,#151515,#040404);background-image:linear-gradient(to bottom,#151515,#040404);background-repeat:repeat-x;border-color:#040404 #040404 #000;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff151515',endColorstr='#ff040404',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.navbar-inverse .btn-navbar:hover,.navbar-inverse .btn-navbar:focus,.navbar-inverse .btn-navbar:active,.navbar-inverse .btn-navbar.active,.navbar-inverse .btn-navbar.disabled,.navbar-inverse .btn-navbar[disabled]{color:#fff;background-color:#040404;*background-color:#000}.navbar-inverse .btn-navbar:active,.navbar-inverse .btn-navbar.active{background-color:#000 \9}.breadcrumb{padding:8px 15px;margin:0 0 20px;list-style:none;background-color:#f5f5f5;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.breadcrumb>li{display:inline-block;*display:inline;text-shadow:0 1px 0 #fff;*zoom:1}.breadcrumb>li>.divider{padding:0 5px;color:#ccc}.breadcrumb>.active{color:#999}.pagination{margin:20px 0}.pagination ul{display:inline-block;*display:inline;margin-bottom:0;margin-left:0;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;*zoom:1;-webkit-box-shadow:0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:0 1px 2px rgba(0,0,0,0.05);box-shadow:0 1px 2px rgba(0,0,0,0.05)}.pagination ul>li{display:inline}.pagination ul>li>a,.pagination ul>li>span{float:left;padding:4px 12px;line-height:20px;text-decoration:none;background-color:#fff;border:1px solid #ddd;border-left-width:0}.pagination ul>li>a:hover,.pagination ul>li>a:focus,.pagination ul>.active>a,.pagination ul>.active>span{background-color:#f5f5f5}.pagination ul>.active>a,.pagination ul>.active>span{color:#999;cursor:default}.pagination ul>.disabled>span,.pagination ul>.disabled>a,.pagination ul>.disabled>a:hover,.pagination ul>.disabled>a:focus{color:#999;cursor:default;background-color:transparent}.pagination ul>li:first-child>a,.pagination ul>li:first-child>span{border-left-width:1px;-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-bottomleft:4px;-moz-border-radius-topleft:4px}.pagination ul>li:last-child>a,.pagination ul>li:last-child>span{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-moz-border-radius-topright:4px;-moz-border-radius-bottomright:4px}.pagination-centered{text-align:center}.pagination-right{text-align:right}.pagination-large ul>li>a,.pagination-large ul>li>span{padding:11px 19px;font-size:17.5px}.pagination-large ul>li:first-child>a,.pagination-large ul>li:first-child>span{-webkit-border-bottom-left-radius:6px;border-bottom-left-radius:6px;-webkit-border-top-left-radius:6px;border-top-left-radius:6px;-moz-border-radius-bottomleft:6px;-moz-border-radius-topleft:6px}.pagination-large ul>li:last-child>a,.pagination-large ul>li:last-child>span{-webkit-border-top-right-radius:6px;border-top-right-radius:6px;-webkit-border-bottom-right-radius:6px;border-bottom-right-radius:6px;-moz-border-radius-topright:6px;-moz-border-radius-bottomright:6px}.pagination-mini ul>li:first-child>a,.pagination-small ul>li:first-child>a,.pagination-mini ul>li:first-child>span,.pagination-small ul>li:first-child>span{-webkit-border-bottom-left-radius:3px;border-bottom-left-radius:3px;-webkit-border-top-left-radius:3px;border-top-left-radius:3px;-moz-border-radius-bottomleft:3px;-moz-border-radius-topleft:3px}.pagination-mini ul>li:last-child>a,.pagination-small ul>li:last-child>a,.pagination-mini ul>li:last-child>span,.pagination-small ul>li:last-child>span{-webkit-border-top-right-radius:3px;border-top-right-radius:3px;-webkit-border-bottom-right-radius:3px;border-bottom-right-radius:3px;-moz-border-radius-topright:3px;-moz-border-radius-bottomright:3px}.pagination-small ul>li>a,.pagination-small ul>li>span{padding:2px 10px;font-size:11.9px}.pagination-mini ul>li>a,.pagination-mini ul>li>span{padding:0 6px;font-size:10.5px}.pager{margin:20px 0;text-align:center;list-style:none;*zoom:1}.pager:before,.pager:after{display:table;line-height:0;content:""}.pager:after{clear:both}.pager li{display:inline}.pager li>a,.pager li>span{display:inline-block;padding:5px 14px;background-color:#fff;border:1px solid #ddd;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}.pager li>a:hover,.pager li>a:focus{text-decoration:none;background-color:#f5f5f5}.pager .next>a,.pager .next>span{float:right}.pager .previous>a,.pager .previous>span{float:left}.pager .disabled>a,.pager .disabled>a:hover,.pager .disabled>a:focus,.pager .disabled>span{color:#999;cursor:default;background-color:#fff}.modal-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:1040;background-color:#000}.modal-backdrop.fade{opacity:0}.modal-backdrop,.modal-backdrop.fade.in{opacity:.8;filter:alpha(opacity=80)}.modal{position:fixed;top:10%;left:50%;z-index:1050;width:560px;margin-left:-280px;background-color:#fff;border:1px solid #999;border:1px solid rgba(0,0,0,0.3);*border:1px solid #999;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;outline:0;-webkit-box-shadow:0 3px 7px rgba(0,0,0,0.3);-moz-box-shadow:0 3px 7px rgba(0,0,0,0.3);box-shadow:0 3px 7px rgba(0,0,0,0.3);-webkit-background-clip:padding-box;-moz-background-clip:padding-box;background-clip:padding-box}.modal.fade{top:-25%;-webkit-transition:opacity .3s linear,top .3s ease-out;-moz-transition:opacity .3s linear,top .3s ease-out;-o-transition:opacity .3s linear,top .3s ease-out;transition:opacity .3s linear,top .3s ease-out}.modal.fade.in{top:10%}.modal-header{padding:9px 15px;border-bottom:1px solid #eee}.modal-header .close{margin-top:2px}.modal-header h3{margin:0;line-height:30px}.modal-body{position:relative;max-height:400px;padding:15px;overflow-y:auto}.modal-form{margin-bottom:0}.modal-footer{padding:14px 15px 15px;margin-bottom:0;text-align:right;background-color:#f5f5f5;border-top:1px solid #ddd;-webkit-border-radius:0 0 6px 6px;-moz-border-radius:0 0 6px 6px;border-radius:0 0 6px 6px;*zoom:1;-webkit-box-shadow:inset 0 1px 0 #fff;-moz-box-shadow:inset 0 1px 0 #fff;box-shadow:inset 0 1px 0 #fff}.modal-footer:before,.modal-footer:after{display:table;line-height:0;content:""}.modal-footer:after{clear:both}.modal-footer .btn+.btn{margin-bottom:0;margin-left:5px}.modal-footer .btn-group .btn+.btn{margin-left:-1px}.modal-footer .btn-block+.btn-block{margin-left:0}.tooltip{position:absolute;z-index:1030;display:block;font-size:11px;line-height:1.4;opacity:0;filter:alpha(opacity=0);visibility:visible}.tooltip.in{opacity:.8;filter:alpha(opacity=80)}.tooltip.top{padding:5px 0;margin-top:-3px}.tooltip.right{padding:0 5px;margin-left:3px}.tooltip.bottom{padding:5px 0;margin-top:3px}.tooltip.left{padding:0 5px;margin-left:-3px}.tooltip-inner{max-width:200px;padding:8px;color:#fff;text-align:center;text-decoration:none;background-color:#000;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.tooltip-arrow{position:absolute;width:0;height:0;border-color:transparent;border-style:solid}.tooltip.top .tooltip-arrow{bottom:0;left:50%;margin-left:-5px;border-top-color:#000;border-width:5px 5px 0}.tooltip.right .tooltip-arrow{top:50%;left:0;margin-top:-5px;border-right-color:#000;border-width:5px 5px 5px 0}.tooltip.left .tooltip-arrow{top:50%;right:0;margin-top:-5px;border-left-color:#000;border-width:5px 0 5px 5px}.tooltip.bottom .tooltip-arrow{top:0;left:50%;margin-left:-5px;border-bottom-color:#000;border-width:0 5px 5px}.popover{position:absolute;top:0;left:0;z-index:1010;display:none;max-width:276px;padding:1px;text-align:left;white-space:normal;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;-webkit-box-shadow:0 5px 10px rgba(0,0,0,0.2);-moz-box-shadow:0 5px 10px rgba(0,0,0,0.2);box-shadow:0 5px 10px rgba(0,0,0,0.2);-webkit-background-clip:padding-box;-moz-background-clip:padding;background-clip:padding-box}.popover.top{margin-top:-10px}.popover.right{margin-left:10px}.popover.bottom{margin-top:10px}.popover.left{margin-left:-10px}.popover-title{padding:8px 14px;margin:0;font-size:14px;font-weight:normal;line-height:18px;background-color:#f7f7f7;border-bottom:1px solid #ebebeb;-webkit-border-radius:5px 5px 0 0;-moz-border-radius:5px 5px 0 0;border-radius:5px 5px 0 0}.popover-title:empty{display:none}.popover-content{padding:9px 14px}.popover .arrow,.popover .arrow:after{position:absolute;display:block;width:0;height:0;border-color:transparent;border-style:solid}.popover .arrow{border-width:11px}.popover .arrow:after{border-width:10px;content:""}.popover.top .arrow{bottom:-11px;left:50%;margin-left:-11px;border-top-color:#999;border-top-color:rgba(0,0,0,0.25);border-bottom-width:0}.popover.top .arrow:after{bottom:1px;margin-left:-10px;border-top-color:#fff;border-bottom-width:0}.popover.right .arrow{top:50%;left:-11px;margin-top:-11px;border-right-color:#999;border-right-color:rgba(0,0,0,0.25);border-left-width:0}.popover.right .arrow:after{bottom:-10px;left:1px;border-right-color:#fff;border-left-width:0}.popover.bottom .arrow{top:-11px;left:50%;margin-left:-11px;border-bottom-color:#999;border-bottom-color:rgba(0,0,0,0.25);border-top-width:0}.popover.bottom .arrow:after{top:1px;margin-left:-10px;border-bottom-color:#fff;border-top-width:0}.popover.left .arrow{top:50%;right:-11px;margin-top:-11px;border-left-color:#999;border-left-color:rgba(0,0,0,0.25);border-right-width:0}.popover.left .arrow:after{right:1px;bottom:-10px;border-left-color:#fff;border-right-width:0}.thumbnails{margin-left:-20px;list-style:none;*zoom:1}.thumbnails:before,.thumbnails:after{display:table;line-height:0;content:""}.thumbnails:after{clear:both}.row-fluid .thumbnails{margin-left:0}.thumbnails>li{float:left;margin-bottom:20px;margin-left:20px}.thumbnail{display:block;padding:4px;line-height:20px;border:1px solid #ddd;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.055);-moz-box-shadow:0 1px 3px rgba(0,0,0,0.055);box-shadow:0 1px 3px rgba(0,0,0,0.055);-webkit-transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;transition:all .2s ease-in-out}a.thumbnail:hover,a.thumbnail:focus{border-color:#08c;-webkit-box-shadow:0 1px 4px rgba(0,105,214,0.25);-moz-box-shadow:0 1px 4px rgba(0,105,214,0.25);box-shadow:0 1px 4px rgba(0,105,214,0.25)}.thumbnail>img{display:block;max-width:100%;margin-right:auto;margin-left:auto}.thumbnail .caption{padding:9px;color:#555}.media,.media-body{overflow:hidden;*overflow:visible;zoom:1}.media,.media .media{margin-top:15px}.media:first-child{margin-top:0}.media-object{display:block}.media-heading{margin:0 0 5px}.media>.pull-left{margin-right:10px}.media>.pull-right{margin-left:10px}.media-list{margin-left:0;list-style:none}.label,.badge{display:inline-block;padding:2px 4px;font-size:11.844px;font-weight:bold;line-height:14px;color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);white-space:nowrap;vertical-align:baseline;background-color:#999}.label{-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.badge{padding-right:9px;padding-left:9px;-webkit-border-radius:9px;-moz-border-radius:9px;border-radius:9px}.label:empty,.badge:empty{display:none}a.label:hover,a.label:focus,a.badge:hover,a.badge:focus{color:#fff;text-decoration:none;cursor:pointer}.label-important,.badge-important{background-color:#b94a48}.label-important[href],.badge-important[href]{background-color:#953b39}.label-warning,.badge-warning{background-color:#f89406}.label-warning[href],.badge-warning[href]{background-color:#c67605}.label-success,.badge-success{background-color:#468847}.label-success[href],.badge-success[href]{background-color:#356635}.label-info,.badge-info{background-color:#3a87ad}.label-info[href],.badge-info[href]{background-color:#2d6987}.label-inverse,.badge-inverse{background-color:#333}.label-inverse[href],.badge-inverse[href]{background-color:#1a1a1a}.btn .label,.btn .badge{position:relative;top:-1px}.btn-mini .label,.btn-mini .badge{top:0}@-webkit-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-moz-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-ms-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-o-keyframes progress-bar-stripes{from{background-position:0 0}to{background-position:40px 0}}@keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}.progress{height:20px;margin-bottom:20px;overflow:hidden;background-color:#f7f7f7;background-image:-moz-linear-gradient(top,#f5f5f5,#f9f9f9);background-image:-webkit-gradient(linear,0 0,0 100%,from(#f5f5f5),to(#f9f9f9));background-image:-webkit-linear-gradient(top,#f5f5f5,#f9f9f9);background-image:-o-linear-gradient(top,#f5f5f5,#f9f9f9);background-image:linear-gradient(to bottom,#f5f5f5,#f9f9f9);background-repeat:repeat-x;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff5f5f5',endColorstr='#fff9f9f9',GradientType=0);-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1);-moz-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1);box-shadow:inset 0 1px 2px rgba(0,0,0,0.1)}.progress .bar{float:left;width:0;height:100%;font-size:12px;color:#fff;text-align:center;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#0e90d2;background-image:-moz-linear-gradient(top,#149bdf,#0480be);background-image:-webkit-gradient(linear,0 0,0 100%,from(#149bdf),to(#0480be));background-image:-webkit-linear-gradient(top,#149bdf,#0480be);background-image:-o-linear-gradient(top,#149bdf,#0480be);background-image:linear-gradient(to bottom,#149bdf,#0480be);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff149bdf',endColorstr='#ff0480be',GradientType=0);-webkit-box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);-moz-box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-transition:width .6s ease;-moz-transition:width .6s ease;-o-transition:width .6s ease;transition:width .6s ease}.progress .bar+.bar{-webkit-box-shadow:inset 1px 0 0 rgba(0,0,0,0.15),inset 0 -1px 0 rgba(0,0,0,0.15);-moz-box-shadow:inset 1px 0 0 rgba(0,0,0,0.15),inset 0 -1px 0 rgba(0,0,0,0.15);box-shadow:inset 1px 0 0 rgba(0,0,0,0.15),inset 0 -1px 0 rgba(0,0,0,0.15)}.progress-striped .bar{background-color:#149bdf;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);-webkit-background-size:40px 40px;-moz-background-size:40px 40px;-o-background-size:40px 40px;background-size:40px 40px}.progress.active .bar{-webkit-animation:progress-bar-stripes 2s linear infinite;-moz-animation:progress-bar-stripes 2s linear infinite;-ms-animation:progress-bar-stripes 2s linear infinite;-o-animation:progress-bar-stripes 2s linear infinite;animation:progress-bar-stripes 2s linear infinite}.progress-danger .bar,.progress .bar-danger{background-color:#dd514c;background-image:-moz-linear-gradient(top,#ee5f5b,#c43c35);background-image:-webkit-gradient(linear,0 0,0 100%,from(#ee5f5b),to(#c43c35));background-image:-webkit-linear-gradient(top,#ee5f5b,#c43c35);background-image:-o-linear-gradient(top,#ee5f5b,#c43c35);background-image:linear-gradient(to bottom,#ee5f5b,#c43c35);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffee5f5b',endColorstr='#ffc43c35',GradientType=0)}.progress-danger.progress-striped .bar,.progress-striped .bar-danger{background-color:#ee5f5b;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-success .bar,.progress .bar-success{background-color:#5eb95e;background-image:-moz-linear-gradient(top,#62c462,#57a957);background-image:-webkit-gradient(linear,0 0,0 100%,from(#62c462),to(#57a957));background-image:-webkit-linear-gradient(top,#62c462,#57a957);background-image:-o-linear-gradient(top,#62c462,#57a957);background-image:linear-gradient(to bottom,#62c462,#57a957);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff62c462',endColorstr='#ff57a957',GradientType=0)}.progress-success.progress-striped .bar,.progress-striped .bar-success{background-color:#62c462;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-info .bar,.progress .bar-info{background-color:#4bb1cf;background-image:-moz-linear-gradient(top,#5bc0de,#339bb9);background-image:-webkit-gradient(linear,0 0,0 100%,from(#5bc0de),to(#339bb9));background-image:-webkit-linear-gradient(top,#5bc0de,#339bb9);background-image:-o-linear-gradient(top,#5bc0de,#339bb9);background-image:linear-gradient(to bottom,#5bc0de,#339bb9);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de',endColorstr='#ff339bb9',GradientType=0)}.progress-info.progress-striped .bar,.progress-striped .bar-info{background-color:#5bc0de;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-warning .bar,.progress .bar-warning{background-color:#faa732;background-image:-moz-linear-gradient(top,#fbb450,#f89406);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fbb450),to(#f89406));background-image:-webkit-linear-gradient(top,#fbb450,#f89406);background-image:-o-linear-gradient(top,#fbb450,#f89406);background-image:linear-gradient(to bottom,#fbb450,#f89406);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fffbb450',endColorstr='#fff89406',GradientType=0)}.progress-warning.progress-striped .bar,.progress-striped .bar-warning{background-color:#fbb450;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.accordion{margin-bottom:20px}.accordion-group{margin-bottom:2px;border:1px solid #e5e5e5;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.accordion-heading{border-bottom:0}.accordion-heading .accordion-toggle{display:block;padding:8px 15px}.accordion-toggle{cursor:pointer}.accordion-inner{padding:9px 15px;border-top:1px solid #e5e5e5}.carousel{position:relative;margin-bottom:20px;line-height:1}.carousel-inner{position:relative;width:100%;overflow:hidden}.carousel-inner>.item{position:relative;display:none;-webkit-transition:.6s ease-in-out left;-moz-transition:.6s ease-in-out left;-o-transition:.6s ease-in-out left;transition:.6s ease-in-out left}.carousel-inner>.item>img,.carousel-inner>.item>a>img{display:block;line-height:1}.carousel-inner>.active,.carousel-inner>.next,.carousel-inner>.prev{display:block}.carousel-inner>.active{left:0}.carousel-inner>.next,.carousel-inner>.prev{position:absolute;top:0;width:100%}.carousel-inner>.next{left:100%}.carousel-inner>.prev{left:-100%}.carousel-inner>.next.left,.carousel-inner>.prev.right{left:0}.carousel-inner>.active.left{left:-100%}.carousel-inner>.active.right{left:100%}.carousel-control{position:absolute;top:40%;left:15px;width:40px;height:40px;margin-top:-20px;font-size:60px;font-weight:100;line-height:30px;color:#fff;text-align:center;background:#222;border:3px solid #fff;-webkit-border-radius:23px;-moz-border-radius:23px;border-radius:23px;opacity:.5;filter:alpha(opacity=50)}.carousel-control.right{right:15px;left:auto}.carousel-control:hover,.carousel-control:focus{color:#fff;text-decoration:none;opacity:.9;filter:alpha(opacity=90)}.carousel-indicators{position:absolute;top:15px;right:15px;z-index:5;margin:0;list-style:none}.carousel-indicators li{display:block;float:left;width:10px;height:10px;margin-left:5px;text-indent:-999px;background-color:#ccc;background-color:rgba(255,255,255,0.25);border-radius:5px}.carousel-indicators .active{background-color:#fff}.carousel-caption{position:absolute;right:0;bottom:0;left:0;padding:15px;background:#333;background:rgba(0,0,0,0.75)}.carousel-caption h4,.carousel-caption p{line-height:20px;color:#fff}.carousel-caption h4{margin:0 0 5px}.carousel-caption p{margin-bottom:0}.hero-unit{padding:60px;margin-bottom:30px;font-size:18px;font-weight:200;line-height:30px;color:inherit;background-color:#eee;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.hero-unit h1{margin-bottom:0;font-size:60px;line-height:1;letter-spacing:-1px;color:inherit}.hero-unit li{line-height:30px}.pull-right{float:right}.pull-left{float:left}.hide{display:none}.show{display:block}.invisible{visibility:hidden}.affix{position:fixed}

BUFF_EOF;

        $buff[2] = <<<BUFF_EOF
/*!
 * Bootstrap Responsive v2.3.2
 *
 * Copyright 2012 Twitter, Inc
 * Licensed under the Apache License v2.0
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Designed and built with all the love in the world @twitter by @mdo and @fat.
 */.clearfix{*zoom:1}.clearfix:before,.clearfix:after{display:table;line-height:0;content:""}.clearfix:after{clear:both}.hide-text{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.input-block-level{display:block;width:100%;min-height:30px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}@-ms-viewport{width:device-width}.hidden{display:none;visibility:hidden}.visible-phone{display:none!important}.visible-tablet{display:none!important}.hidden-desktop{display:none!important}.visible-desktop{display:inherit!important}@media(min-width:768px) and (max-width:979px){.hidden-desktop{display:inherit!important}.visible-desktop{display:none!important}.visible-tablet{display:inherit!important}.hidden-tablet{display:none!important}}@media(max-width:767px){.hidden-desktop{display:inherit!important}.visible-desktop{display:none!important}.visible-phone{display:inherit!important}.hidden-phone{display:none!important}}.visible-print{display:none!important}@media print{.visible-print{display:inherit!important}.hidden-print{display:none!important}}@media(min-width:1200px){.row{margin-left:-30px;*zoom:1}.row:before,.row:after{display:table;line-height:0;content:""}.row:after{clear:both}[class*="span"]{float:left;min-height:1px;margin-left:30px}.container,.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:1170px}.span12{width:1170px}.span11{width:1070px}.span10{width:970px}.span9{width:870px}.span8{width:770px}.span7{width:670px}.span6{width:570px}.span5{width:470px}.span4{width:370px}.span3{width:270px}.span2{width:170px}.span1{width:70px}.offset12{margin-left:1230px}.offset11{margin-left:1130px}.offset10{margin-left:1030px}.offset9{margin-left:930px}.offset8{margin-left:830px}.offset7{margin-left:730px}.offset6{margin-left:630px}.offset5{margin-left:530px}.offset4{margin-left:430px}.offset3{margin-left:330px}.offset2{margin-left:230px}.offset1{margin-left:130px}.row-fluid{width:100%;*zoom:1}.row-fluid:before,.row-fluid:after{display:table;line-height:0;content:""}.row-fluid:after{clear:both}.row-fluid [class*="span"]{display:block;float:left;width:100%;min-height:30px;margin-left:2.564102564102564%;*margin-left:2.5109110747408616%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="span"]:first-child{margin-left:0}.row-fluid .controls-row [class*="span"]+[class*="span"]{margin-left:2.564102564102564%}.row-fluid .span12{width:100%;*width:99.94680851063829%}.row-fluid .span11{width:91.45299145299145%;*width:91.39979996362975%}.row-fluid .span10{width:82.90598290598291%;*width:82.8527914166212%}.row-fluid .span9{width:74.35897435897436%;*width:74.30578286961266%}.row-fluid .span8{width:65.81196581196582%;*width:65.75877432260411%}.row-fluid .span7{width:57.26495726495726%;*width:57.21176577559556%}.row-fluid .span6{width:48.717948717948715%;*width:48.664757228587014%}.row-fluid .span5{width:40.17094017094017%;*width:40.11774868157847%}.row-fluid .span4{width:31.623931623931625%;*width:31.570740134569924%}.row-fluid .span3{width:23.076923076923077%;*width:23.023731587561375%}.row-fluid .span2{width:14.52991452991453%;*width:14.476723040552828%}.row-fluid .span1{width:5.982905982905983%;*width:5.929714493544281%}.row-fluid .offset12{margin-left:105.12820512820512%;*margin-left:105.02182214948171%}.row-fluid .offset12:first-child{margin-left:102.56410256410257%;*margin-left:102.45771958537915%}.row-fluid .offset11{margin-left:96.58119658119658%;*margin-left:96.47481360247316%}.row-fluid .offset11:first-child{margin-left:94.01709401709402%;*margin-left:93.91071103837061%}.row-fluid .offset10{margin-left:88.03418803418803%;*margin-left:87.92780505546462%}.row-fluid .offset10:first-child{margin-left:85.47008547008548%;*margin-left:85.36370249136206%}.row-fluid .offset9{margin-left:79.48717948717949%;*margin-left:79.38079650845607%}.row-fluid .offset9:first-child{margin-left:76.92307692307693%;*margin-left:76.81669394435352%}.row-fluid .offset8{margin-left:70.94017094017094%;*margin-left:70.83378796144753%}.row-fluid .offset8:first-child{margin-left:68.37606837606839%;*margin-left:68.26968539734497%}.row-fluid .offset7{margin-left:62.393162393162385%;*margin-left:62.28677941443899%}.row-fluid .offset7:first-child{margin-left:59.82905982905982%;*margin-left:59.72267685033642%}.row-fluid .offset6{margin-left:53.84615384615384%;*margin-left:53.739770867430444%}.row-fluid .offset6:first-child{margin-left:51.28205128205128%;*margin-left:51.175668303327875%}.row-fluid .offset5{margin-left:45.299145299145295%;*margin-left:45.1927623204219%}.row-fluid .offset5:first-child{margin-left:42.73504273504273%;*margin-left:42.62865975631933%}.row-fluid .offset4{margin-left:36.75213675213675%;*margin-left:36.645753773413354%}.row-fluid .offset4:first-child{margin-left:34.18803418803419%;*margin-left:34.081651209310785%}.row-fluid .offset3{margin-left:28.205128205128204%;*margin-left:28.0987452264048%}.row-fluid .offset3:first-child{margin-left:25.641025641025642%;*margin-left:25.53464266230224%}.row-fluid .offset2{margin-left:19.65811965811966%;*margin-left:19.551736679396257%}.row-fluid .offset2:first-child{margin-left:17.094017094017094%;*margin-left:16.98763411529369%}.row-fluid .offset1{margin-left:11.11111111111111%;*margin-left:11.004728132387708%}.row-fluid .offset1:first-child{margin-left:8.547008547008547%;*margin-left:8.440625568285142%}input,textarea,.uneditable-input{margin-left:0}.controls-row [class*="span"]+[class*="span"]{margin-left:30px}input.span12,textarea.span12,.uneditable-input.span12{width:1156px}input.span11,textarea.span11,.uneditable-input.span11{width:1056px}input.span10,textarea.span10,.uneditable-input.span10{width:956px}input.span9,textarea.span9,.uneditable-input.span9{width:856px}input.span8,textarea.span8,.uneditable-input.span8{width:756px}input.span7,textarea.span7,.uneditable-input.span7{width:656px}input.span6,textarea.span6,.uneditable-input.span6{width:556px}input.span5,textarea.span5,.uneditable-input.span5{width:456px}input.span4,textarea.span4,.uneditable-input.span4{width:356px}input.span3,textarea.span3,.uneditable-input.span3{width:256px}input.span2,textarea.span2,.uneditable-input.span2{width:156px}input.span1,textarea.span1,.uneditable-input.span1{width:56px}.thumbnails{margin-left:-30px}.thumbnails>li{margin-left:30px}.row-fluid .thumbnails{margin-left:0}}@media(min-width:768px) and (max-width:979px){.row{margin-left:-20px;*zoom:1}.row:before,.row:after{display:table;line-height:0;content:""}.row:after{clear:both}[class*="span"]{float:left;min-height:1px;margin-left:20px}.container,.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:724px}.span12{width:724px}.span11{width:662px}.span10{width:600px}.span9{width:538px}.span8{width:476px}.span7{width:414px}.span6{width:352px}.span5{width:290px}.span4{width:228px}.span3{width:166px}.span2{width:104px}.span1{width:42px}.offset12{margin-left:764px}.offset11{margin-left:702px}.offset10{margin-left:640px}.offset9{margin-left:578px}.offset8{margin-left:516px}.offset7{margin-left:454px}.offset6{margin-left:392px}.offset5{margin-left:330px}.offset4{margin-left:268px}.offset3{margin-left:206px}.offset2{margin-left:144px}.offset1{margin-left:82px}.row-fluid{width:100%;*zoom:1}.row-fluid:before,.row-fluid:after{display:table;line-height:0;content:""}.row-fluid:after{clear:both}.row-fluid [class*="span"]{display:block;float:left;width:100%;min-height:30px;margin-left:2.7624309392265194%;*margin-left:2.709239449864817%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="span"]:first-child{margin-left:0}.row-fluid .controls-row [class*="span"]+[class*="span"]{margin-left:2.7624309392265194%}.row-fluid .span12{width:100%;*width:99.94680851063829%}.row-fluid .span11{width:91.43646408839778%;*width:91.38327259903608%}.row-fluid .span10{width:82.87292817679558%;*width:82.81973668743387%}.row-fluid .span9{width:74.30939226519337%;*width:74.25620077583166%}.row-fluid .span8{width:65.74585635359117%;*width:65.69266486422946%}.row-fluid .span7{width:57.18232044198895%;*width:57.12912895262725%}.row-fluid .span6{width:48.61878453038674%;*width:48.56559304102504%}.row-fluid .span5{width:40.05524861878453%;*width:40.00205712942283%}.row-fluid .span4{width:31.491712707182323%;*width:31.43852121782062%}.row-fluid .span3{width:22.92817679558011%;*width:22.87498530621841%}.row-fluid .span2{width:14.3646408839779%;*width:14.311449394616199%}.row-fluid .span1{width:5.801104972375691%;*width:5.747913483013988%}.row-fluid .offset12{margin-left:105.52486187845304%;*margin-left:105.41847889972962%}.row-fluid .offset12:first-child{margin-left:102.76243093922652%;*margin-left:102.6560479605031%}.row-fluid .offset11{margin-left:96.96132596685082%;*margin-left:96.8549429881274%}.row-fluid .offset11:first-child{margin-left:94.1988950276243%;*margin-left:94.09251204890089%}.row-fluid .offset10{margin-left:88.39779005524862%;*margin-left:88.2914070765252%}.row-fluid .offset10:first-child{margin-left:85.6353591160221%;*margin-left:85.52897613729868%}.row-fluid .offset9{margin-left:79.8342541436464%;*margin-left:79.72787116492299%}.row-fluid .offset9:first-child{margin-left:77.07182320441989%;*margin-left:76.96544022569647%}.row-fluid .offset8{margin-left:71.2707182320442%;*margin-left:71.16433525332079%}.row-fluid .offset8:first-child{margin-left:68.50828729281768%;*margin-left:68.40190431409427%}.row-fluid .offset7{margin-left:62.70718232044199%;*margin-left:62.600799341718584%}.row-fluid .offset7:first-child{margin-left:59.94475138121547%;*margin-left:59.838368402492065%}.row-fluid .offset6{margin-left:54.14364640883978%;*margin-left:54.037263430116376%}.row-fluid .offset6:first-child{margin-left:51.38121546961326%;*margin-left:51.27483249088986%}.row-fluid .offset5{margin-left:45.58011049723757%;*margin-left:45.47372751851417%}.row-fluid .offset5:first-child{margin-left:42.81767955801105%;*margin-left:42.71129657928765%}.row-fluid .offset4{margin-left:37.01657458563536%;*margin-left:36.91019160691196%}.row-fluid .offset4:first-child{margin-left:34.25414364640884%;*margin-left:34.14776066768544%}.row-fluid .offset3{margin-left:28.45303867403315%;*margin-left:28.346655695309746%}.row-fluid .offset3:first-child{margin-left:25.69060773480663%;*margin-left:25.584224756083227%}.row-fluid .offset2{margin-left:19.88950276243094%;*margin-left:19.783119783707537%}.row-fluid .offset2:first-child{margin-left:17.12707182320442%;*margin-left:17.02068884448102%}.row-fluid .offset1{margin-left:11.32596685082873%;*margin-left:11.219583872105325%}.row-fluid .offset1:first-child{margin-left:8.56353591160221%;*margin-left:8.457152932878806%}input,textarea,.uneditable-input{margin-left:0}.controls-row [class*="span"]+[class*="span"]{margin-left:20px}input.span12,textarea.span12,.uneditable-input.span12{width:710px}input.span11,textarea.span11,.uneditable-input.span11{width:648px}input.span10,textarea.span10,.uneditable-input.span10{width:586px}input.span9,textarea.span9,.uneditable-input.span9{width:524px}input.span8,textarea.span8,.uneditable-input.span8{width:462px}input.span7,textarea.span7,.uneditable-input.span7{width:400px}input.span6,textarea.span6,.uneditable-input.span6{width:338px}input.span5,textarea.span5,.uneditable-input.span5{width:276px}input.span4,textarea.span4,.uneditable-input.span4{width:214px}input.span3,textarea.span3,.uneditable-input.span3{width:152px}input.span2,textarea.span2,.uneditable-input.span2{width:90px}input.span1,textarea.span1,.uneditable-input.span1{width:28px}}@media(max-width:767px){body{padding-right:20px;padding-left:20px}.navbar-fixed-top,.navbar-fixed-bottom,.navbar-static-top{margin-right:-20px;margin-left:-20px}.container-fluid{padding:0}.dl-horizontal dt{float:none;width:auto;clear:none;text-align:left}.dl-horizontal dd{margin-left:0}.container{width:auto}.row-fluid{width:100%}.row,.thumbnails{margin-left:0}.thumbnails>li{float:none;margin-left:0}[class*="span"],.uneditable-input[class*="span"],.row-fluid [class*="span"]{display:block;float:none;width:100%;margin-left:0;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.span12,.row-fluid .span12{width:100%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="offset"]:first-child{margin-left:0}.input-large,.input-xlarge,.input-xxlarge,input[class*="span"],select[class*="span"],textarea[class*="span"],.uneditable-input{display:block;width:100%;min-height:30px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.input-prepend input,.input-append input,.input-prepend input[class*="span"],.input-append input[class*="span"]{display:inline-block;width:auto}.controls-row [class*="span"]+[class*="span"]{margin-left:0}.modal{position:fixed;top:20px;right:20px;left:20px;width:auto;margin:0}.modal.fade{top:-100px}.modal.fade.in{top:20px}}@media(max-width:480px){.nav-collapse{-webkit-transform:translate3d(0,0,0)}.page-header h1 small{display:block;line-height:20px}input[type="checkbox"],input[type="radio"]{border:1px solid #ccc}.form-horizontal .control-label{float:none;width:auto;padding-top:0;text-align:left}.form-horizontal .controls{margin-left:0}.form-horizontal .control-list{padding-top:0}.form-horizontal .form-actions{padding-right:10px;padding-left:10px}.media .pull-left,.media .pull-right{display:block;float:none;margin-bottom:10px}.media-object{margin-right:0;margin-left:0}.modal{top:10px;right:10px;left:10px}.modal-header .close{padding:10px;margin:-10px}.carousel-caption{position:static}}@media(max-width:979px){body{padding-top:0}.navbar-fixed-top,.navbar-fixed-bottom{position:static}.navbar-fixed-top{margin-bottom:20px}.navbar-fixed-bottom{margin-top:20px}.navbar-fixed-top .navbar-inner,.navbar-fixed-bottom .navbar-inner{padding:5px}.navbar .container{width:auto;padding:0}.navbar .brand{padding-right:10px;padding-left:10px;margin:0 0 0 -5px}.nav-collapse{clear:both}.nav-collapse .nav{float:none;margin:0 0 10px}.nav-collapse .nav>li{float:none}.nav-collapse .nav>li>a{margin-bottom:2px}.nav-collapse .nav>.divider-vertical{display:none}.nav-collapse .nav .nav-header{color:#777;text-shadow:none}.nav-collapse .nav>li>a,.nav-collapse .dropdown-menu a{padding:9px 15px;font-weight:bold;color:#777;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.nav-collapse .btn{padding:4px 10px 4px;font-weight:normal;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.nav-collapse .dropdown-menu li+li a{margin-bottom:2px}.nav-collapse .nav>li>a:hover,.nav-collapse .nav>li>a:focus,.nav-collapse .dropdown-menu a:hover,.nav-collapse .dropdown-menu a:focus{background-color:#f2f2f2}.navbar-inverse .nav-collapse .nav>li>a,.navbar-inverse .nav-collapse .dropdown-menu a{color:#999}.navbar-inverse .nav-collapse .nav>li>a:hover,.navbar-inverse .nav-collapse .nav>li>a:focus,.navbar-inverse .nav-collapse .dropdown-menu a:hover,.navbar-inverse .nav-collapse .dropdown-menu a:focus{background-color:#111}.nav-collapse.in .btn-group{padding:0;margin-top:5px}.nav-collapse .dropdown-menu{position:static;top:auto;left:auto;display:none;float:none;max-width:none;padding:0;margin:0 15px;background-color:transparent;border:0;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none}.nav-collapse .open>.dropdown-menu{display:block}.nav-collapse .dropdown-menu:before,.nav-collapse .dropdown-menu:after{display:none}.nav-collapse .dropdown-menu .divider{display:none}.nav-collapse .nav>li>.dropdown-menu:before,.nav-collapse .nav>li>.dropdown-menu:after{display:none}.nav-collapse .navbar-form,.nav-collapse .navbar-search{float:none;padding:10px 15px;margin:10px 0;border-top:1px solid #f2f2f2;border-bottom:1px solid #f2f2f2;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1);-moz-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1);box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1)}.navbar-inverse .nav-collapse .navbar-form,.navbar-inverse .nav-collapse .navbar-search{border-top-color:#111;border-bottom-color:#111}.navbar .nav-collapse .nav.pull-right{float:none;margin-left:0}.nav-collapse,.nav-collapse.collapse{height:0;overflow:hidden}.navbar .btn-navbar{display:block}.navbar-static .navbar-inner{padding-right:10px;padding-left:10px}}@media(min-width:980px){.nav-collapse.collapse{height:auto!important;overflow:visible!important}}

BUFF_EOF;

        $buff['cust'] = <<<BUFF_EOF
/* My CSS */
/* Custom container */
body {
  padding-top: 20px;
  padding-bottom: 60px;
}
.container {
  margin: 0 auto;
  max-width: 1000px;
}
.container > hr {
  margin: 60px 0;
}

.social_links, .app_ver {
    float : right;
}

.main_container {
    border:2px solid #555;
    padding:1%;
    _min-height:500px;
}

#EMAIL {
  border:1px solid #555;
  font-size: 26px;
  outline:0 none;
  width:25%;
  min-height:45px;
}

.mc_embed_signup {
    background:#ccc;
    padding:5px;
}

.jumbotron {
    margin: 40px 0;
}

ul.nav {
    border:1px solid #ccc;
    padding:10px;
    margin:0;
}

ul.nav li {
    display:inline;
    padding-left:5px;
    border-left:1px solid #ccc;
    margin-left:5px;
}

ul.nav li:first-child {
    border-left:0;
}

ul.nav li:last-child {
    border-right:0;
}

.app-align-left {
    float:left;
}

ul.nav li.right, .app-align-right {
    float:right;
}

.app-code-textarea {
    width:100%;
    min-height: 250px;
}

.app-table {
    border:1px solid #999;
    margin:0;
    padding:0;
    table-layout: fixed;
    width: 100%;
}

.app-table-long-first-col tr td:first-child { 
	width : 80%;
}

.app-table-long-first-col tr td:last-child { 
	text-align:center;
}

.app-table-header-row {
    font-weight:bolder;
	background:#666;
	text-align:center;
	color:#fff;
}

.app-table-row-odd {
    background:#dadada;
}

.app-table-data-row-centered {
    text-align: center;
}

/*
This color is the same as the color of the links in the main nav.
There's a separate rule for links because it seems there's another CSS
which makes them look bad or blend with the background.
*/
.app-table-data-row:hover, .app-table-data-row:hover td a, .app-table-data-row-centered:hover, .app-table-data-row-centered:hover td a {
    background:	#0088CC;
    color:white;
}

.app-table-hightlist-row {
    background:yellow;
    font-weight:bold;
}

.app-table tr td.app-table-action-cell, .app-align-center {
    text-align: center;
}

.app-module-self-destroy-button {
   background:red;
   padding:3px;
   font-weight:bold;
   color:white;
}

/* Form fields */
.app_full_width {
	width:100%;
}

/* Buttons */
.app-btn-primary  {
   background:green;
   padding:3px;
   color:white;
   border: 0;
   margin-top:3px;
   margin-bottom:3px;
}

.app-question-box {
   padding:3px;
   font-weight:bold;
   border: 1px solid #777;
   background:#B6CEDB;color:#111;
}

/* Common MSG classes */
.app-alert-error {
    background: red;
    border: 1px solid #eee;
    color: white;
    padding: 3px;
    text-align: center;
    margin:2px 0px;
}

.app-alert-success {
    background: green;
    border: 1px solid #eee;
    color: white;
    padding: 3px;
    text-align: center;
    margin:2px 0px;
}

.app-alert-notice {
    background: #FFEC8B;
    border: 1px solid #eee;
    padding: 3px;
    text-align: center;
    margin:2px 0px;
}
                
/* Common MSG simple classes */
.app-simple-alert-error {
    padding:3px;
    color: red;
}

.app-simple-alert-success {
    padding:3px;
    color: green;
}

.app-simple-alert-notice {
    padding:3px;
    color: #FFEC8B;
}

.app_hide {
    display:none;
}

BUFF_EOF;

        $buffer = empty($buff[$file_id]) ? '' : $buff[$file_id];

        $file = basename(__FILE__);

        // we'll modify the CSS so it points to us and we'll return the image (availalbe from a constant)
        $buffer = str_replace('../img/glyphicons-halflings.png', $file . '?img=glyphicons-halflings', $buffer);
        $buffer = str_replace('../img/glyphicons-halflings-white.png', $file . '?img=glyphicons-halflings-white', $buffer);

//file_put_contents($file_id . '.css.txt', $buffer);

        $this->sendHeader(self::HEADER_CSS, $buffer);
	}
}