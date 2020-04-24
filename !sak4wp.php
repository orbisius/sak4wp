<?php
/**
Swiss Army Knife for WordPress - standalone open source tool (GPL) that allows you to do system admin work on your WordPress site. Created by @orbisius
You must remove it after the work is complete to avoid security issues.

License: GPL (v2 or later)
Author: Svetoslav Marinov (SLAVI)
Author Site: https://orbisius.com
Product Site: https://orbisius.com/products/tools/swiss-army-knife-for-wordpress
Copyright: All Rights Reserved.

Disclaimer: By using this tool you take full responsibility to remove it.

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
define('ORBISIUS_WP_SAK_APP_URL', 'https://orbisius.com/products/tools/swiss-army-knife-for-wordpress');
define('ORBISIUS_WP_SAK_APP_VER', '1.1.6');
define('ORBISIUS_WP_SAK_APP_SCRIPT', basename(__FILE__));
define('ORBISIUS_WP_SAK_APP_BASE_DIR', dirname(__FILE__));
define('ORBISIUS_WP_SAK_HOST', str_replace('www.', '', $_SERVER['HTTP_HOST']));

//define('WP_DEBUG_DISPLAY', 0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $ctrl = Orbisius_WP_SAK_Controller::getInstance();
    $ctrl->init();

	// @todo: check for wp-cli and offer quick install via
	// set ups from orbisius.com account.
    $ctrl->preRun();
	
	
	// Do we define quick run without theme loading???
	
    // This WP load may fail which we'll check()
	$wp_load_locations = array(
		dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'wp-load.php',
		dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wp-load.php',
		dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'wp-load.php',
		dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'wp-load.php',
		dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . DIRECTORY_SEPARATOR . 'wp-load.php',
	);
	
	foreach ( $wp_load_locations as $wp_load_php ) {
		if ( file_exists( $wp_load_php ) ) {
			include_once( $wp_load_php );
		
			if ( defined( 'ABSPATH' ) ) {
				break;
			}
		}
	}

	// This stops WP Super Cache and W3 Total Cache from caching
	defined( 'WP_CACHE' ) || define( 'WP_CACHE', false );
		
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
class Orbisius_WP_SAK_Controller_Module_PostMeta extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>Post Meta</h4>
<p>
    This module allows you to view and edit post meta information.
</p>
EOF;

        $form_js_handler = <<<BUFF_EOF
        // Orbisius_WP_SAK_Controller_Module_PostMeta
        // Load form.
        $('.mod_post_meta_form').submit(function() {
            var form = $(this);
            var container = '.results_container';

            $(container).empty().append("<div class='app-ajax-message app-alert-notice'>loading ...</div>");

            var action = 'getPostMetaAjax';

            if ( form.hasClass('mod_post_meta_edit_form') ) {
                action = 'setPostMetaAjax';
            }
                
            if ( form.hasClass('mod_post_meta_delete_form') ) {
                action = 'setPostMetaAjax';
            }

            $.ajax({
                type : "post",
                dataType : "json",
                url : wpsak_json_cfg.ajax_url, // contains all the necessary params
                data : $(form).serialize() + '&module=PostMeta&action=' + action,
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
        });
        // Orbisius_WP_SAK_Controller_Module_PostMeta
BUFF_EOF;

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $ctrl->enqeueOnDocumentReady($form_js_handler);
    }

    /**
     *
     */
    public function run() {
        $buff = '';

        $post_id = empty($_REQUEST['post_id']) ? '' : (int) $_REQUEST['post_id'];
        $meta_key = empty($_REQUEST['meta_key']) ? '' : trim($_REQUEST['meta_key']);
        $meta_value = empty($_REQUEST['meta_value']) ? '' : trim( $_REQUEST['meta_value'] );
        
        $post_id_esc = esc_attr($post_id);
        $meta_key_esc = esc_attr($meta_key);
        $meta_value_esc = esc_attr($meta_value);

        //$buff .= "<br/><h4>Plugin List from Text/HTML File</h4>\n";
        $buff .= "<h4>Load Post Meta</h4>";
		$buff .= "<form method='post' class='mod_post_meta_form mod_post_meta_load_form' id='mod_post_meta_load_form'>\n";
		$buff .= "<input type='hidden' id='cmd' name='cmd' value='get_post_meta' />\n";
		$buff .= "ID: <input type='text' id='post_id' name='post_id' value='$post_id_esc' /> \n";
		$buff .= "<input type='submit' class='app-btn-primary' value='Load Post Meta' />\n<br/>";
        $buff .= "Examples: <br/>- Enter ID e.g. 1 <br/>- OR 1, 2, 3 <br/>- OR even a page slug e.g. my-service-page\n";
		$buff .= "</form>\n";
        $buff .= "<hr />\n";
        
		$buff .= "<div id='results_container' class='results_container'></div>\n";

        $buff .= "<h4>Edit Post Meta</h4>";
        $buff .= "<form method='post' class='mod_post_meta_form mod_post_meta_edit_form' id='mod_post_meta_edit_form'>\n";
		$buff .= "<input type='hidden' id='cmd' name='cmd' value='edit_post_meta' />\n";
		$buff .= "ID: <input type='text' id='post_id' name='post_id' value='$post_id_esc' /> \n";
		$buff .= "Meta Key: <input type='text' id='meta_key' name='meta_key' value='$meta_key_esc' /> \n";
		$buff .= "Meta Value: <textarea id='meta_value' name='meta_value' value='$meta_value_esc' rows='4'></textarea>\n";
		$buff .= "<input type='submit' class='app-btn-primary' value='Update Post Meta' />\n<br/>";
        $buff .= "Examples: <br/>- Enter ID e.g. 1 <br/>- OR 1, 2, 3 <br/>- OR even a page slug e.g. my-service-page\n";
		$buff .= "</form>\n";
		$buff .= "<hr />\n";

        $buff .= "<h4>Delete Post Meta</h4>";
        $buff .= "<form method='post' class='mod_post_meta_form mod_post_meta_delete_form' id='mod_post_meta_delete_form'>\n";
		$buff .= "<input type='hidden' id='cmd' name='cmd' value='delete_post_meta' />\n";
		$buff .= "ID: <input type='text' id='post_id' name='post_id' value='$post_id_esc' /> \n";
		$buff .= "Meta Key: <input type='text' id='meta_key' name='meta_key' value='$meta_key_esc' /> \n";
		$buff .= "<input type='submit' class='app-btn-primary' value='Delete Post Meta' />\n<br/>";
        $buff .= "Examples: <br/>- Enter ID e.g. 1 <br/>- OR 1, 2, 3 <br/>- OR even a page slug e.g. my-service-page\n";
		$buff .= "</form>\n";

        if (!empty($_REQUEST['cmd'])) {
            if ($_REQUEST['cmd'] == 'delete_post_meta') {
                $post_id = empty($_REQUEST['post_id']) ? 0 : $_REQUEST['post_id'];
                $this->deletePostMeta($post_id, $meta_key);
            }

            if ($_REQUEST['cmd'] == 'edit_post_meta') {
                $this->setPostMeta($post_id, $meta_key, $meta_value);
            }

            // This should fetch the freshest info.
            if ($_REQUEST['cmd'] == 'get_post_meta') {
                $buff .= $this->getMetaAsString($post_id);
                $buff .= "<br/>";
            }
        }

        return $buff;
    }

    /**
     * This method is called when we have the module and action specified.
     */
    public function getPostMetaAjaxAction() {
        $msg = '';
        $result_html = '';
        $status = 1;

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $post_ids_list = $ctrl->getVar('post_id'); // could be 1 or more IDs

        $ids = explode(',', $post_ids_list);
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids); // non empty ones
        $ids = array_unique($ids);

        foreach ($ids as $post_id) {
            // If the user has entered a slug we'll use it to get the post ID
            if (!is_numeric($post_id)) {
                $post = get_page_by_path($post_id);

                if (empty($post)) {
                    $result_html .= Orbisius_WP_SAK_Util::msg('Path: [' . esc_attr($post_id) . '] was not found. Skipping. <br/>', 0);
                    continue;
                }

                $post_id = $post->ID;
            }

            $result_html .= $this->getMetaAsString($post_id);
        }

        $result_status = array('status' => $status, 'message' => $msg, 'results' => $result_html, );
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $result_status);
    }

    /**
     * This method is called when we have the module and action specified.
     */
    public function setPostMetaAjaxAction() {
        $msg = '';
        $result_html = '';
        $status = 1;

        $cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : null;

        $cmd_label = preg_match('#delete#si', $cmd) ? 'Delete Meta' : 'Update Meta';

        // raw data
        $meta_key = empty($_REQUEST['meta_key']) ? '' : trim($_REQUEST['meta_key']);
        $meta_value = isset($_REQUEST['meta_value']) ? trim($_REQUEST['meta_value']) : null;

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $post_ids_list = $ctrl->getVar('post_id'); // could be 1 or more IDs
        
        $ids = explode(',', $post_ids_list);
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids); // non empty ones
        $ids = array_unique($ids);

        foreach ($ids as $post_id) {
            // If the user has entered a slug we'll use it to get the post ID
            if (!is_numeric($post_id)) {
                $post = get_page_by_path($post_id);

                if (empty($post)) {
                    $result_html .= Orbisius_WP_SAK_Util::msg('Path: [' . esc_attr($post_id) . '] was not found. Skipping. <br/>', 0);
                    continue;
                }

                $post_id = $post->ID;
            }

            $res = $this->setPostMeta($post_id, $meta_key, $meta_value);

            $result_html .= ($res === false)
                    ? Orbisius_WP_SAK_Util::msg($cmd_label . ' Error. post meta for post/page ID: ' . esc_attr($post_id) . '] or the new value matches the old one. <br/>', 0)
                    : Orbisius_WP_SAK_Util::msg($cmd_label. ' OK meta for post/page ID: ' . esc_attr($post_id) . ']. <br/>', 1);
        }

        $result_status = array('status' => $status, 'message' => $msg, 'results' => $result_html, );
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $result_status);
    }

    /**
     *
     * @param int $post_id
     * @param str $meta_key
     * @param str $meta_value
     * @see https://codex.wordpress.org/Function_Reference/update_post_meta
     */
    public function setPostMeta($post_id, $meta_key, $meta_value) {
        if ( is_null( $meta_value ) ) {
            $mixed_res = delete_post_meta($post_id, $meta_key);
        } else {
            /*Returns meta_id if the meta doesn't exist, otherwise returns true on success and false on failure.
             * NOTE: If the meta_value passed to this function is the same as the value that is already in the database, this function returns false. */
            $mixed_res = update_post_meta($post_id, $meta_key, $meta_value);
        }
        
        return $mixed_res;
    }

    /**
     * Sample Method
     */
    public function getMetaAsString($post_id) {
        $str = '';
        $post = get_post($post_id);

        $meta = !empty($post)
                ? get_post_meta($post_id)
                : null;
        
        $author_meta = !empty($post->post_author)
                ? get_user_meta($post->post_author)
                : null;

        $link_str = '';

        if (!empty($post)) {
            $link = get_permalink($post_id);
            $link_str = "(<a href='$link' target='_blank'>View</a>)";
        }

        // if the item is one element that means that it's one value
        if (count($meta) == 1) {
            $meta = $meta[0];
        }

        $str .= "<h3>Post/Page ID: $post_id $link_str</h3><pre>" . var_export($post, 1) . "</pre>\n";
        $str .= '<h3>Post/Page Meta</h3><pre class="toggle_info000">' . var_export($meta, 1) . "</pre>\n";
        $str .= '<h3>Author Meta</h3><pre class="toggle_info000">' . var_export($author_meta, 1) . "</pre>\n";

        return $str;
    }
}

/**
 * Example Module - Handles ...
 */
class Orbisius_WP_SAK_Controller_Module_UserMeta extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>User Meta</h4>
<p>
    This module allows you to view and edit user meta information.
</p>
EOF;

        $form_js_handler = <<<BUFF_EOF
        // Orbisius_WP_SAK_Controller_Module_UserMeta
        // Load form.
        $('.mod_user_meta_form').submit(function() {
            var form = $(this);
            var container = '.results_container';

            $(container).empty().append("<div class='app-ajax-message app-alert-notice'>loading ...</div>");

            var action = 'getUserMetaAjax';

            if ( form.hasClass('mod_user_meta_edit_form') ) {
                action = 'setUserMetaAjax';
            }

            if ( form.hasClass('mod_user_meta_delete_form') ) {
                action = 'setUserMetaAjax';
            }

            $.ajax({
                //type : "user",
                dataType : "json",
                url : wpsak_json_cfg.ajax_url, // contains all the necessary params
                data : $(form).serialize() + '&module=UserMeta&action=' + action,
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
        });
        // Orbisius_WP_SAK_Controller_Module_UserMeta
BUFF_EOF;

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $ctrl->enqeueOnDocumentReady($form_js_handler);
    }

    /**
     *
     */
    public function run() {
        $buff = '';

        $user_id = empty($_REQUEST['user_id']) ? '' : (int) $_REQUEST['user_id'];
        $meta_key = empty($_REQUEST['meta_key']) ? '' : trim($_REQUEST['meta_key']);
        $meta_value = empty($_REQUEST['meta_value']) ? '' : trim( $_REQUEST['meta_value'] );

        $user_id_esc = esc_attr($user_id);
        $meta_key_esc = esc_attr($meta_key);
        $meta_value_esc = esc_attr($meta_value);

        //$buff .= "<br/><h4>Plugin List from Text/HTML File</h4>\n";
        $buff .= "<h4>Load User Meta</h4>";
		$buff .= "<form method='post' class='mod_user_meta_form mod_user_meta_load_form' id='mod_user_meta_load_form'>\n";
		$buff .= "<input type='hidden' id='cmd' name='cmd' value='get_user_meta' />\n";
		$buff .= "ID: <input type='text' id='user_id' name='user_id' value='$user_id_esc' /> \n";
		$buff .= "<input type='submit' class='app-btn-primary' value='Load User Meta' />\n<br/>";
        $buff .= "Examples: <br/>- Enter ID e.g. 1 <br/>- OR 1, 2, 3 <br/>- OR even a page slug e.g. my-service-page\n";
		$buff .= "</form>\n";
        $buff .= "<hr />\n";

		$buff .= "<div id='results_container' class='results_container'></div>\n";

        $buff .= "<h4>Edit User Meta</h4>";
        $buff .= "<form method='post' class='mod_user_meta_form mod_user_meta_edit_form' id='mod_user_meta_edit_form'>\n";
		$buff .= "<input type='hidden' id='cmd' name='cmd' value='edit_user_meta' />\n";
		$buff .= "ID: <input type='text' id='user_id' name='user_id' value='$user_id_esc' /> \n";
		$buff .= "Meta Key: <input type='text' id='meta_key' name='meta_key' value='$meta_key_esc' /> \n";
		$buff .= "Meta Value: <textarea id='meta_value' name='meta_value' value='$meta_value_esc' rows='4'></textarea>\n";
		$buff .= "<input type='submit' class='app-btn-primary' value='Update User Meta' />\n<br/>";
        $buff .= "Examples: <br/>- Enter ID e.g. 1 <br/>- OR 1, 2, 3 <br/>- OR even a page slug e.g. my-service-page\n";
		$buff .= "</form>\n";
		$buff .= "<hr />\n";

        $buff .= "<h4>Delete User Meta</h4>";
        $buff .= "<form method='post' class='mod_user_meta_form mod_user_meta_delete_form' id='mod_user_meta_delete_form'>\n";
		$buff .= "<input type='hidden' id='cmd' name='cmd' value='delete_user_meta' />\n";
		$buff .= "ID: <input type='text' id='user_id' name='user_id' value='$user_id_esc' /> \n";
		$buff .= "Meta Key: <input type='text' id='meta_key' name='meta_key' value='$meta_key_esc' /> \n";
		$buff .= "<input type='submit' class='app-btn-primary' value='Delete User Meta' />\n<br/>";
        $buff .= "Examples: <br/>- Enter ID e.g. 1 <br/>- OR 1, 2, 3 <br/>- OR even a page slug e.g. my-service-page\n";
		$buff .= "</form>\n";

        if (!empty($_REQUEST['cmd'])) {
            if ($_REQUEST['cmd'] == 'delete_user_meta') {
                $user_id = empty($_REQUEST['user_id']) ? 0 : $_REQUEST['user_id'];
                $this->deleteUserMeta($user_id, $meta_key);
            }

            if ($_REQUEST['cmd'] == 'edit_user_meta') {
                $this->setUserMeta($user_id, $meta_key, $meta_value);
            }

            // This should fetch the freshest info.
            if ($_REQUEST['cmd'] == 'get_user_meta') {
                $buff .= $this->getMetaAsString($user_id);
                $buff .= "<br/>";
            }
        }

        return $buff;
    }

    /**
     * This method is called when we have the module and action specified.
     */
    public function getUserMetaAjaxAction() {
        $msg = '';
        $result_html = '';
        $status = 1;

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $user_ids_list = $ctrl->getVar('user_id'); // could be 1 or more IDs

        $ids = explode(',', $user_ids_list);
        $ids = array_map('abs', $ids);
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids); // non empty ones
        $ids = array_unique($ids);

        foreach ($ids as $user_id) {
            $result_html .= $this->getMetaAsString($user_id);
        }

        $result_status = array('status' => $status, 'message' => $msg, 'results' => $result_html, );
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $result_status);
    }

    /**
     * This method is called when we have the module and action specified.
     */
    public function setUserMetaAjaxAction() {
        $msg = '';
        $result_html = '';
        $status = 1;

        $cmd = isset($_REQUEST['cmd']) ? trim($_REQUEST['cmd']) : null;

        $cmd_label = preg_match('#delete#si', $cmd) ? 'Delete Meta' : 'Update Meta';

        // raw data
        $meta_key = empty($_REQUEST['meta_key']) ? '' : trim($_REQUEST['meta_key']);
        $meta_value = isset($_REQUEST['meta_value']) ? trim($_REQUEST['meta_value']) : null;

        $ctrl = Orbisius_WP_SAK_Controller::getInstance();
        $user_ids_list = $ctrl->getVar('user_id'); // could be 1 or more IDs

        $ids = explode(',', $user_ids_list);
        $ids = array_map('trim', $ids);
        $ids = array_map('abs', $ids);
        $ids = array_filter($ids); // non empty ones
        $ids = array_unique($ids);

        foreach ($ids as $user_id) {
            $res = $this->setUserMeta($user_id, $meta_key, $meta_value);

            $result_html .= ($res === false)
                    ? Orbisius_WP_SAK_Util::msg($cmd_label . ' Error. user meta for user ID: ' . esc_attr($user_id) . '] or the new value matches the old one. <br/>', 0)
                    : Orbisius_WP_SAK_Util::msg($cmd_label. ' OK meta for user ID: ' . esc_attr($user_id) . ']. <br/>', 1);
        }

        $result_status = array('status' => $status, 'message' => $msg, 'results' => $result_html, );
        $ctrl->sendHeader(Orbisius_WP_SAK_Controller::HEADER_JS, $result_status);
    }

    /**
     *
     * @param int $user_id
     * @param str $meta_key
     * @param str $meta_value
     * @see https://codex.wordpress.org/Function_Reference/update_user_meta
     */
    public function setUserMeta($user_id, $meta_key, $meta_value = null) {
        if ( is_null( $meta_value ) ) {
            $mixed_res = delete_user_meta($user_id, $meta_key);
        } else {
            /*Returns meta_id if the meta doesn't exist, otherwise returns true on success and false on failure.
             * NOTE: If the meta_value passed to this function is the same as the value that is already in the database, this function returns false. */
            $mixed_res = update_user_meta($user_id, $meta_key, $meta_value);
        }

        return $mixed_res;
    }

    /**
     * Sample Method
     */
    public function getMetaAsString($user_id) {
        $str = '';
        $user_id = abs($user_id);
        $user = get_user_by( 'id', $user_id);

        $meta = !empty($user)
                ? get_user_meta($user_id)
                : null;

        $link_str = '';

        // if the item is one element that means that it's one value
        if (count($meta) == 1) {
            $meta = $meta[0];
        }

        $str .= "<h3>User ID: $user_id $link_str</h3><pre>" . var_export($user, 1) . "</pre>\n";
        $str .= '<h3>User Meta</h3><pre class="toggle_info000">' . var_export($meta, 1) . "</pre>\n";

        return $str;
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

        if ( empty( $_REQUEST['load_user_meta'] ) ) {
           $this->description .= " <a href='?page=mod_user_manager&load_user_meta=1'>Load user meta</a> | ";
        } else {
            $this->description .= " <a href='?page=mod_user_manager&load_user_meta=0'>Skip user meta loading</a> | ";
        }

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

        $args = array(
            'number' => 250,
            'orderby' => 'registered',
        );
        
		$data = get_users( $args );
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

            if ( ! empty( $_REQUEST['load_user_meta'] ) ) {
                $user_meta_html = '<pre class="toggle_info app_hide">' . var_export( get_user_meta( $user_obj->ID ), 1 ) . "</pre>\n";
                $rec['ID'] .= " (<a href='javascript:void(0);' class='toggle_info_trigger'>show/hide meta</a>)\n" . $user_meta_html;
            }

			if ( user_can( $user_obj->ID, 'manage_options' ) ) {
				$highlight_admins[] = $idx;
                $rec['user_login'] .= ' (admin)';
			}
            
			$records[] = $rec;
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

        $find_bin = Orbisius_WP_SAK_Util_File::getBinary('find');

		// this searches for folders that contain wp-includes and that's where we'll read version.php
		$cmd = "$find_bin $start_folder -type d -name \"wp-includes\" 2>/dev/null";

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
 * Search Module - Searches for text using grep
 */
class Orbisius_WP_SAK_Controller_Module_Search extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>Search</h4>
<p>Search for a given text keyword within files of WordPress.
</p>
EOF;
    }

    /**
     *
     */
    public function run() {
        $buff = '';

		$q = empty( $_REQUEST['q'] ) ? '' : trim( wp_kses( $_REQUEST['q'] , array() ) );
		$q_esc = esc_attr($q);

		$buff .= "<br/><form method='post' id='mod_search_form'>\n";
		$buff .= "<input type='hidden' name='cmd' value='mod_search' />\n";
		$buff .= "Search Keyword:<br/><input type='text' name='q' id='q' value='$q_esc' class='app_full_width00' />\n";
		$buff .= "<input type='submit' name='submit' class='app-btn-primary' value='Search' />\n";
		$buff .= "</form>\n";

        if ( ! empty( $q ) ) {

            $buff .= "<p class='results'>";
            $buff .= "<h3>Results</h3>";
            $buff .= "<pre>";
            $buff .= htmlentities( $this->searchAction(), ENT_QUOTES, "UTF-8" );
            $buff .= "</pre>";
            $buff .= "</p>\n";
            $buff .= "<p><br/><a href='?page=mod_search&cmd=search' class='app-btn-primary mod_search_for_wordpress'>New Search</a></p>\n";
        }

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

        $q = empty($params['q']) ? '' : trim( $params['q'] );

        if ( empty( $q ) ) {
            return;
        }
        
        $start_folder = empty($params['start_folder']) ? ABSPATH : $params['start_folder'];
        $start_folder = str_replace( '/', DIRECTORY_SEPARATOR, $start_folder );
        $start_folder = trim( $start_folder, '\\/' );

        $q_esc = escapeshellarg($q);
        $start_folder_esc = escapeshellarg($start_folder);

        $s = 0;
        $msg = '';

        $bin = Orbisius_WP_SAK_Util_File::getBinary('grep');

		// this searches for folders that contain wp-includes and that's where we'll read version.php
		$cmd = "$bin -irn $q_esc $start_folder_esc 2>&1";
        $search_buffer = `$cmd`;
        $search_buffer = trim( $search_buffer );

        return $search_buffer;
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
	##AuthGroupFile None
	Require valid-user
</FilesMatch>

# Stop access to config file
<files wp-config.php>
	order allow,deny
	deny from all
</files>

######## SAK4WP_END ########

BUFF;

        $htaccess_buff_wp_login = <<<BUFF

######## SAK4WP_PROTECT_LOGIN_START ########
<FilesMatch "wp-login.php">
	AuthUserFile $htpasswd_file

	AuthType Basic
	AuthName "Protected Area"
	##AuthGroupFile None
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
 * This module handles lists page templates.
 */
class Orbisius_WP_SAK_Controller_Module_Db_Dump extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>Db Dump</h4>
<p>This module allows you to dump the database into a nice file and provides download links.
</p>
EOF;
    }

    /**
     *
     */
    public function run() {
        global $wpdb;
        $buff = '';
        $exp_params = array();
        $db_export_file_prefix = '!sak4wp-db-export-';

        // @TODO: download only tables that are specific to the selected install!
        // see http://qSandbox.com db dump for ideas.
        // let's allow the script to run longer in case we download lots of files.
        $old_time_limit = ini_get('max_execution_time');
        set_time_limit(600);

		$buff .= "<form method='post' id='mod_db_export_form'>\n";
		$buff .= "<input type='hidden' id='page' name='page' value='mod_db_dump' />\n";

        $buff .= "<br /><strong>Stats / Info</strong>\n";

		$dir = ORBISIUS_WP_SAK_APP_BASE_DIR;

        $bin_check = array(
            'mysql',
            'mysqldump',
            'gzip',
        );

        foreach ($bin_check as $bin_file) {
            $buff .= "<ul class='app-no-bullets-list app-no-padding'>\n";

            $tmp_res = `$bin_file --help 2>&1`;
            $found_binary = Orbisius_WP_SAK_Util_File::getBinary($bin_file);

            if (preg_match('#help|usage#si', $tmp_res) || ($found_binary !== false)) {
                $buff .= "\t<li>" . Orbisius_WP_SAK_Util::msg("$bin_file ($found_binary) found", 1) . "</li>";
            } else {
                $buff .= "\t<li>" . Orbisius_WP_SAK_Util::msg("$bin_file NOT found. Res: [$tmp_res]", 0) . "</li>";
			}

            $buff .= "</ul>\n";
        }

        $buff .= "<br/><br/><strong>Archive Type</strong>\n";
		$buff .= "<br/><label><input type='radio' id='cmd1' name='cmd' value='dump_sql' checked='checked' /> Archive (sql)</label>\n";
		$buff .= "<br/><label><input type='radio' id='cmd2' name='cmd' value='dump_sql_gz' /> Archive (sql.gz)</label>\n";

        $buff .= "<br/><br/><strong>Backup Type</strong>\n";
		$buff .= "<br/><label><input type='radio' id='backup_type1' name='backup_type' value='site_only' checked='checked' /> Current WordPress Site only</label>\n";
		$buff .= "<br/><label><input type='radio' id='backup_type2' name='backup_type' value='full' /> Full (all files)</label>\n";

        $buff .= "<br/><br/><strong>Misc</strong>\n";
		$buff .= "<br/><label><input type='checkbox' id='bg' name='bg' value='1' /> Run the task in background (linux only) (recommended for larger sites > 100MB)</label>\n";

        $buff .= "<br/>Database Info (in case it can't be parsed automatically) \n";
        $buff .= "<br/><input type='text' name='db_host' value='localhost' placeholder='db host' />\n";
        $buff .= "<br/><input type='text' name='db_user' value='' placeholder='db user' />\n";
        $buff .= "<br/><input type='text' name='db_pass' value='' placeholder='db pass' />\n";
        $buff .= "<br/><input type='text' name='db_name' value='' placeholder='db name' /><br/>\n";

		$buff .= "<br/><input type='submit' name='submit_btn' class='btn btn-primary' value='Archive' />\n";
		$buff .= "</form>\n";

        if ( !empty( $_REQUEST['cmd'] ) ) {
            
            if ( preg_match('#dump_sql#si', $_REQUEST['cmd'] ) ) {
                $mod_obj = new Orbisius_WP_SAK_Controller_Module_Stats();
                
                $data = array();
                $db_fields = array();
                $db_field_keys = array( 'db_host', 'db_user', 'db_pass', 'db_name', );

                foreach ( $db_field_keys as $key ) {
                    if ( !empty($_REQUEST[$key])) {
                        $db_fields[$key] = $_REQUEST[$key];
                    }
                }

                // if the user has entered all of the fields then don't load wp-config
                if (count($db_fields) == count($db_field_keys)) {
                    $data = array_merge($db_fields, $data);
                } else {
                    $data = $mod_obj->read_wp_config();
                }

                $exp_params[] = '--single-transaction';
                $exp_params[] = '--hex-blob';
                $exp_params[] = '--skip-add-drop-table';

                $exp_params[] = '-h' . escapeshellarg( $data['db_host'] );
                $exp_params[] = '-u' . escapeshellarg( $data['db_user'] );
                $exp_params[] = '-p' . escapeshellarg( $data['db_pass'] );
                $exp_params[] = escapeshellarg( $data['db_name'] ); // keep it last!

                $file_suffix = 'full';

                // Backup only tables that belong to the current site.
                if ( empty($_REQUEST['backup_type']) || $_REQUEST['backup_type'] == 'site_only' ) {
                    $x = esc_sql($wpdb->prefix); // JIC
                    $table_names = $wpdb->get_col("SHOW TABLES LIKE '$x%'");

                    if (!empty($table_names)) {
                        $table_names = array_map('escapeshellarg', $table_names); // JIC
                        $exp_params[] = ' ' . join(' ', $table_names);
                    }

                    $file_suffix = 'site_only';
                }

                $target_sql = $db_export_file_prefix
                        . ORBISIUS_WP_SAK_HOST
                        . '-'
                        . date( 'Ymd_his' )
                        . '-ts-'
                        . time()
                        . '-'
                        . sha1( microtime() )
                        . '-' . $file_suffix
                        . '.sql';

                if ( preg_match('#gz#si', $_REQUEST['cmd'] ) ) {
                    $exp_params[] = '| ' . Orbisius_WP_SAK_Util_File::getBinary('gzip') . ' -c';
                    $target_sql .= '.gz';
                }

                $target_sql_esc = escapeshellarg( $target_sql );
                $output_error_log_file = $target_sql . '.error.log';
                $output_error_log_file_esc = escapeshellarg($output_error_log_file);

                $cmd = Orbisius_WP_SAK_Util_File::getBinary('mysqldump') . ' ' . join( ' ', $exp_params ) . ' > ' . $target_sql_esc;
                $cmd .= ' 2>' . $output_error_log_file_esc;

                if ( !empty( $_REQUEST['bg'] ) ) {
                    // creating a .done file to let the user know that the archiving has finished. Cool, eh?
                    //$done_file_esc = escapeshellarg( $target_sql . '.done' );
                    //$cmd = "(($cmd) && (touch $done_file_esc)) &";
                    $cmd .= " &";
                }

                $result = `$cmd`;

                // No need for an empty file only if NOT running in bg
                if (empty($_REQUEST['bg']) && file_exists($output_error_log_file) && filesize($output_error_log_file) == 0) {
                    unlink($output_error_log_file);
                }

                $buff .= "<pre>";
                $buff .= "<br/>CMD: [$cmd]";
                $buff .= " / Result: [$result]";
                $buff .= "</pre>";
            } elseif ( // delete file
                        preg_match('#delete_file#si', $_REQUEST['cmd'] )
                        && !empty($_REQUEST['file'])
                        && preg_match('#^' . preg_quote($db_export_file_prefix) . '#si', $_REQUEST['file'] )
                    ) {
                $file = ORBISIUS_WP_SAK_APP_BASE_DIR . '/' . $_REQUEST['file'];

                if (file_exists($file)) {
                    unlink( $file);
                }
            }
        }

        $folder = ORBISIUS_WP_SAK_APP_BASE_DIR;
        $files_arr = glob($folder . '/' . $db_export_file_prefix . '*.*'); // list only sa4kwp exported files.

        // @todo: email the db archive as attachment.
        foreach ($files_arr as $file) {
            $file_base_name = basename($file);
            $delete_link = '?page=mod_db_dump&cmd=delete_file&file=' . urlencode($file_base_name);
            $dl_link = site_url($file_base_name);
            $size = filesize($file);
            $size_fmt = Orbisius_WP_SAK_Util::formatFileSize($size);
            $buff .= "<br/><a href='$delete_link' class='btn btn-sm btn-danger'>[X]</a> ";
            $buff .= "<a href='$dl_link' target='_blank'>$file_base_name ($size_fmt)</a>";
            $buff .= "<br/>";
        }

        set_time_limit($old_time_limit);

        return $buff;
    }
}

/**
 * This module handles lists page templates.
 */
class Orbisius_WP_SAK_Controller_Module_Site_Packager extends Orbisius_WP_SAK_Controller_Module {
    /**
     * Setups the object stuff and defines some descriptions
     */
    public function __construct() {
        $this->description = <<<EOF
<h4>Site Packager</h4>
<p>This module allows you to compress the current site (whole site or partially) in tar or tar.gz formats.
</p>
EOF;
    }

    /**
     *
     */
    public function run() {
        $buff = '';

        // @TODO: download only tables that are specific to the selected install!
        // see http://qSandbox.com db dump for ideas.
        // let's allow the script to run longer in case we download lots of files.
        $old_time_limit = ini_get('max_execution_time');
        set_time_limit(600);

        // We want the directory prefix that goes into the archive to be
        // the folder name and not the full system path.
        // e.g. /var/www/vhosts/etc/aaaaa/htdocs
        // we'll go 1 level up and zip the folder that way and later come back to the current dir.
        $target_dir = ORBISIUS_WP_SAK_APP_BASE_DIR . '/';
        $db_export_file_prefix = '!sak4wp-site-packager-';

        $buff .= "<form method='post' id='mod_site_packager_form'>\n";
		$buff .= "<input type='hidden' id='page' name='page' value='mod_site_packager' />\n";

        $buff .= "<br /><strong>Stats / Info</strong>\n";

        $dir = ORBISIUS_WP_SAK_APP_BASE_DIR;

        $bin_check = array(
            'find',
            'tar',
            'gzip',
            'du',
        );

        foreach ($bin_check as $bin_file) {
            $buff .= "<ul class='app-no-bullets-list app-no-padding'>\n";

            $tmp_res = `$bin_file --help 2>&1`;
            $found_binary = Orbisius_WP_SAK_Util_File::getBinary($bin_file);

            if (preg_match('#help|usage#si', $tmp_res) || ($found_binary !== false)) {
                $buff .= "\t<li>" . Orbisius_WP_SAK_Util::msg("$bin_file ($found_binary) found", 1) . "</li>";
            } else {
                $buff .= "\t<li>" . Orbisius_WP_SAK_Util::msg("$bin_file NOT found. Res: [$tmp_res]", 0) . "</li>";
			}

            $buff .= "</ul>\n";
        }

        $du_bin = Orbisius_WP_SAK_Util_File::getBinary('du');

        $buff .= "<br/><br/><strong>Archive Type</strong>\n";
		$buff .= "<br/><label><input type='radio' id='cmd1' name='cmd' value='export_sql' checked='checked' /> Archive (tar)</label>\n";
		$buff .= "<br/><label><input type='radio' id='cmd2' name='cmd' value='export_sql_gz' /> Archive (tar.gz - linux only)</label>\n";

        $buff .= "<br/><br/><strong>Archive Folder Prefix</strong>\n";

        // Uploads disk usage
        $upload_dir_rec = wp_upload_dir();
        $upload_dir = $upload_dir_rec['basedir'];

        $arch_dirs = array(
            rtrim( ABSPATH, '/' ),
            dirname( __FILE__ ),
            rtrim( $target_dir, '/' ),
            dirname( $target_dir ),
            rtrim( $upload_dir, '/' ),
            // @todo: add plugins, themes dir
        );

        $arch_dirs = array_unique($arch_dirs);
        sort($arch_dirs);

        foreach ( $arch_dirs as $idx => $archive_dir ) {
            $dir_e = escapeshellarg($archive_dir);
            $dir_attr_e = esc_attr($archive_dir);
            
            $du = `$du_bin -sh $dir_e 2>&1`;
            $du = trim($du);
            $du_label = "Disk Usage ($du)";

            $ch = $idx == 0 ? " checked='checked' " : '';
            $buff .= "<br/><label><input type='radio' id='archive_start$idx' name='archive_start' value='$dir_attr_e' $ch /> "
                . "Archive starts from [$archive_dir] ($du_label)</label>\n";
        }

        $buff .= "<br/><br/><strong>Backup Type</strong>\n";
		$buff .= "<br/><label><input type='radio' id='backup_type1' name='backup_type' value='site_only' checked='checked' /> Current WordPress Site only (smaller size; backups are ignored)</label>\n";
		$buff .= "<br/><label><input type='radio' id='backup_type2' name='backup_type' value='full' /> Full (all files)</label>\n";

        $buff .= "<br/><br/><strong>Misc</strong>\n";
		$buff .= "<br/><label><input type='checkbox' id='bg' name='bg' value='1' /> Run the task in background (linux only) (recommended for larger sites > 100MB)</label>\n";
		$buff .= "<br/><label><input type='checkbox' id='verify' name='verify' value='1' /> Verify archive (output saved in the log file)</label>\n";

		$buff .= "<br/><input type='submit' name='submit_btn' class='btn btn-primary' value='Archive' />\n";
		$buff .= "</form>\n";

        if ( !empty( $_REQUEST['cmd'] ) ) {
            $archive_start = empty($_REQUEST['archive_start']) ? ABSPATH : $_REQUEST['archive_start'];

            $cur_dir = getcwd();
            $dir2chdir = $archive_start;
            $dir2compress = $archive_start;
            $cmd_params_arr = array();

            if (1 || $archive_start == 'add_cur_folder') {
                $dir2compress = basename($archive_start);
                $dir2chdir = dirname($archive_start);
                chdir($dir2chdir);

                // This is passed as 1st param to the tar command
                // if archivig current folder and using text file no need to specify folder. I think.
                $cmd_params_arr[] = $dir2compress;
            } else {
                $cmd_params_arr[] = '.';
            }

            if ( preg_match('#export#si', $_REQUEST['cmd'] ) ) {
                // fixes: "file changed as we read it" due to creation of the log and gz file
                // src: http://www.ensode.net/roller/dheffelfinger/entry/tar_failing_with_error_message
                $cmd_params_arr[] = ' --ignore-failed-read';

                // On Windows tar zcvf produces this error: tar: Cannot use compressed or remote archives
                // If we need gzip on Windows we can do it in 2 steps.
                // 1. tar
                // 2. gzip $gz_cmd = "gzip < $target_sql_esc > $target_sql_gz_esc";
                $archive_type = preg_match('#gz#si', $_REQUEST['cmd']) && !Orbisius_WP_SAK_Util::isWindows() ? '.tar.gz' : '.tar';
                $file_suffix = 'full';

                // Let's make tar include only some files.
                if ( empty($_REQUEST['backup_type']) || $_REQUEST['backup_type'] == 'site_only' ) {
                   // http://php.net/manual/en/function.tempnam.php
                   $tmp_file = tempnam(sys_get_temp_dir(), '!sak4wp-site-pkg-');
                   Orbisius_WP_SAK_Util_File::get_wp_files($dir2compress, $tmp_file);
                   $cmd_params_arr[] = '--files-from=' . escapeshellarg($tmp_file);
                   $file_suffix = 'site_only';
                }

                $output_file = $target_dir
                        . $db_export_file_prefix
                        . ORBISIUS_WP_SAK_HOST
                        . '-'
                        . date( 'Ymd_his' )
                        . '-ts-'
                        . time()
                        . '-'
                        . sha1( microtime() )
                        . '-' . $file_suffix
                        . $archive_type;

                $output_log_file = $output_file . '.log';
                $output_error_log_file = $output_file . '.error.log';
                $output_done_file = $output_file . '.done';

                $exclude_items = array(
                     '!sak4wp.php', // sak4wp is not necessary in the pkg
                     '.ht-sak4wp*',
                     'Maildir/*',
                     '*.log',
                     'logs/*',
                     'tmp/*',
                     'sess_*', // php session files
                     '*.cache',

                     // there is another function to get backups: is_wp_backup_resource
                     'wp-content/uploads/*backup*',
                     'wp-content/*updraft*',
                     'wp-content/*backup*',
                     'wp-content/uploads/backupbuddy*',
                     'wp-content/backupwordpress*',
                     'wp-snapshots/*', // Duplicator
                    
                     '__MACOSX', // mac
                     '.DS_Store', // mac
                     $db_export_file_prefix . '*',
                     $db_export_file_prefix . '*.*',

                     // full paths
                     $output_file,
                     $output_log_file,
                     $output_done_file,
                     $output_error_log_file,

                     // just names
                     basename($output_file),
                     basename($output_log_file),
                     basename($output_done_file),
                     basename($output_error_log_file),
                );

                foreach ($exclude_items as $line) {
                     $cmd_params_arr[] = "--exclude=" . escapeshellarg($line);
                     $cmd_params_arr[] = "--exclude=" . escapeshellarg($line . '/*');
                     $cmd_params_arr[] = "--exclude=" . escapeshellarg('*/'. $line);
                     $cmd_params_arr[] = "--exclude=" . escapeshellarg('./'. $line);
                }

                // Verify only tar files.
                if ( ! empty( $_REQUEST['verify'] ) && preg_match( '#\.tar$#si', $archive_type ) ) {
                    $cmd_params_arr[] = '--verify';
                }

                $cmd_param_str = join(' ', $cmd_params_arr);

                $output_file_esc = escapeshellarg( $output_file );
                $output_log_file_esc = escapeshellarg( $output_log_file );
                $output_error_log_file_esc = escapeshellarg( $output_error_log_file );

                // Are we creating a tar or tar.gz file
                $tar_main_cmd_arg = preg_match('#\.(tar\.gz|t?gz)$#si', $output_file) ? 'zcvf' : 'cvf';
                $cmd = Orbisius_WP_SAK_Util_File::getBinary('tar') . " $tar_main_cmd_arg $output_file_esc $cmd_param_str > $output_log_file_esc 2> $output_error_log_file_esc";

                if ( ! empty( $_REQUEST['bg'] ) ) {
                    // @note: for some weird reason I can't fork the process and execute a task after it finishes.
                    // This is strange! I wanted to know if the tar has finished.
                    // creating a .done file to let the user know that the archiving has finished. Cool, eh?
                    /*$output_done_file_esc = escapeshellarg( $output_file . '.done' );
                    $cmd = "($cmd ; touch $output_done_file_esc) &";*/
                    $cmd .= " &";
                }

//                $result = `$cmd`;
                $ret_val = 0;
                system($cmd, $ret_val);
                chdir($cur_dir);

                if (empty($_REQUEST['bg'])) {
                    // No need for an empty file only if NOT running in bg
                    if (file_exists($output_log_file) && filesize($output_log_file) == 0) {
                        unlink($output_log_file);
                    }

                    if (file_exists($output_error_log_file) && filesize($output_error_log_file) == 0) {
                        unlink($output_error_log_file);
                    }
                }

                $buff .= "<pre>";
                $buff .= "<br/>CMD: [$cmd]";
                $buff .= " / Result: [$result]";
                $buff .= "</pre>";
            } elseif ( // delete file
                        preg_match('#delete_file#si', $_REQUEST['cmd'] )
                        && !empty($_REQUEST['file'])
                        && preg_match('#^' . preg_quote($db_export_file_prefix) . '#si', $_REQUEST['file'] )
                    ) {
                $file = ORBISIUS_WP_SAK_APP_BASE_DIR . '/' . $_REQUEST['file'];

                if (file_exists($file)) {
                    unlink( $file);
                }
            }
        }

        $folder = ORBISIUS_WP_SAK_APP_BASE_DIR;
        $files_arr = glob($folder . '/' . $db_export_file_prefix . '*.*'); // list only sa4kwp exported files.

        foreach ($files_arr as $file) {
            $file_base_name = basename($file);
            $delete_link = '?page=mod_site_packager&cmd=delete_file&file=' . urlencode($file_base_name);
            $dl_link = site_url($file_base_name);
            $size = filesize($file);
            $size_fmt = Orbisius_WP_SAK_Util::formatFileSize($size);
            $buff .= "<br/><a href='$delete_link' class='btn btn-sm btn-danger'>[X]</a> ";
            $buff .= "<a href='$dl_link'>$file_base_name ($size_fmt)</a>";
            $buff .= "<br/>";
        }

        set_time_limit($old_time_limit);

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
<p> This section allows you to unblock yourself or another IP address that was blocked by
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
                //$ip_who_is_link = "<a href='http://who.is/whois-ip/ip-address/$ip/' target='_blank' data-ip='$ip' title='view ip info'>$ip</a> $you";
                $ip_who_is_link = "<a href='http://ipduh.com/ip/?$ip' target='_blank' data-ip='$ip' title='view ip info'>$ip</a> $you";
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
        $cfg['db_version'] = $wpdb ? $wpdb->get_var("SELECT VERSION()") : 'N/A';
        $buff .= $ctrl->renderKeyValueTable('Database Info', $cfg);

        $data = array();
        $latest_wp_version = Orbisius_WP_SAK_Util::getLatestWordPressVersion();
        $wp_version_label = $wp_version;

        $data['function_exists(exec)'] = function_exists( 'exec' ) ? Orbisius_WP_SAK_Util::m('Yes', 1) : Orbisius_WP_SAK_Util::m('No');
        $data['function_exists(system)'] = function_exists( 'system' ) ? Orbisius_WP_SAK_Util::m('Yes', 1) : Orbisius_WP_SAK_Util::m('No');
        $data['function_exists(shell_exec)'] = function_exists( 'shell_exec' ) ? Orbisius_WP_SAK_Util::m('Yes', 1) : Orbisius_WP_SAK_Util::m('No');

        $data['WP Base Dir'] = defined('ABSPATH')
                ? ABSPATH 
                : 'N/A';
        
        $data['display_errors'] = @ini_get('display_errors');

        $data['WP Caching'] = defined('WP_CACHE') && WP_CACHE
                ? Orbisius_WP_SAK_Util::m( 'Enabled', 1 ) 
                : Orbisius_WP_SAK_Util::m( 'Not Enabled', 0 );

        $data['WP Cache dir (WPCACHEHOME)'] = defined('WPCACHEHOME')
                ? WPCACHEHOME
                : 'N/A';

        if ( defined('WPCACHEHOME')
                && defined('ABSPATH')
                && ( stripos( ABSPATH, WPCACHEHOME ) === false ) ) {
                $label = Orbisius_WP_SAK_Util::m( "Warning: Cache dir resides in another site's doc root!" );
                $data['WP Cache dir (WPCACHEHOME)'] .= $label;
        }
        
        $consts_we_care_about = array(
            // https://codex.wordpress.org/Changing_The_Site_URL
            'WP_HOME',
            'WP_SITEURL',

            'WP_MEMORY_LIMIT',
            
            'WP_DEBUG',
            'WP_DEBUG_LOG',
            'WP_DEBUG_DISPLAY',
            'SCRIPT_DEBUG',
            
            'WPLANG',
            'SAVEQUERIES',
            'AUTOSAVE_INTERVAL',
            'WP_POST_REVISIONS',
        );

		foreach ( $consts_we_care_about as $const ) {
            if ( ! defined( $const ) ) {
                continue;
            }

            $val = constant( $const );

            // We want to visualize these values.
            if ( $val === false ) {
                $val = Orbisius_WP_SAK_Util::m( 'false' );
            } elseif ( $val === true ) {
                $val = Orbisius_WP_SAK_Util::m( 'true', 1 );
            }

            $data[ $const ] = $val;
        }
			
        // Not available on Windows
        $data['Load Average (1, 5, 15 mins)'] = function_exists('sys_getloadavg')
            ? var_export( sys_getloadavg(), 1 )
            : 'N/A';
                
        $data['PHP Version'] = phpversion();
        
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

        // wp-cli detection.
        $wp_cli_bin = Orbisius_WP_SAK_Util_File::getBinary('wp');
        $wp_cli_version = `$wp_cli_bin --info`;
        $wp_cli_version = trim($wp_cli_version);
        $wp_cli_version = empty($wp_cli_version) ? 'Not Installed/Detected' : "<pre>" . $wp_cli_version . "</pre>";
        $data['wp-cli'] = $wp_cli_version;

		// Disk space usage
        $dir = dirname(__FILE__); // that's where the sak is installed.
        $du_bin = Orbisius_WP_SAK_Util_File::getBinary('du');
        $disk_usage = `$du_bin -sh $dir`;
        $disk_usage = trim($disk_usage);
        $disk_usage = empty($disk_usage) ? 'N/A' : $disk_usage;
        $data['Site Disk Space Usage (du -sh .)'] = $disk_usage;

        $df_bin = Orbisius_WP_SAK_Util_File::getBinary('df');
        $inode_usage = `$df_bin -i`;
        $inode_usage = trim($inode_usage);
        $inode_usage = empty($inode_usage) ? 'N/A' : "<pre>" . $inode_usage . "</pre>";
        $data['OS inode Usage (du -i .)'] = $inode_usage;

		// Free Disk space
        $df_bin = Orbisius_WP_SAK_Util_File::getBinary('df');
		$disk_free_space = `$df_bin --human-readable`;
		$disk_free_space = trim($disk_free_space);
		$disk_free_space = empty($disk_free_space) ? 'N/A' : '<pre>' .
		$disk_free_space = preg_replace('#(/dev/[\w/\-]+\s+)([\d\.]+[bkmgtp]?)(\s+)([\d\.]+[bkmgtp]?)(\s+)([\d\.]+[bkmgtp]?)(\s+)([\d\.]+\%?)(.*)#im'
			, '<span class="du_line">$1<span class="du_total">$2</span>$3<span class="du_used">$4</span>$5<span class="du_free">$6</span>$7'
			. '<span class="du_percent">$8</span>$9</span>', $disk_free_space) . '</pre>';
		$data['Total Disk Usage (df --human-readable)'] = $disk_free_space;

        $top_bin = Orbisius_WP_SAK_Util_File::getBinary('top');

		// Processes info
		$cmd_buff = `$top_bin -b -n 1`;
		$cmd_buff = trim($cmd_buff);
		$cmd_buff = empty($cmd_buff) ? 'N/A' : '<textarea rows="4" style="width:100%;" readonly="readonly">' . $cmd_buff . '</textarea>';
		$data['Processes Info (top -b -n 1)'] = $cmd_buff;

		// Free memory
        $free_bin = Orbisius_WP_SAK_Util_File::getBinary('free');
		$cmd_buff = `$free_bin -m`;
		$cmd_buff = trim($cmd_buff);
		$cmd_buff = empty($cmd_buff) ? 'N/A' : '<textarea rows="4" style="width:100%;" readonly="readonly">' . $cmd_buff . '</textarea>';
		$data['Free Memory (free -m)'] = $cmd_buff;

		// lists 15 files and sorts them by size. e.g. 100K
        $du_bin = Orbisius_WP_SAK_Util_File::getBinary('du');
		$large_files = `$du_bin -ah $dir | sort -nr | head -n +15`;
        $large_files = trim($large_files);
        $large_files = str_replace($dir, '', $large_files);
        $data['Large Files'] = empty($large_files) ? 'n/a' : '<textarea rows="4" style="width:100%;" readonly="readonly">' . $large_files . '</textarea>';

        $buff .= $ctrl->renderKeyValueTable('System Info', $data);

        $data = array();
        $data['User(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users");
        $data['User Meta Row(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta");
        $data['Comment(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments");
        $data['Comment Meta Rows(s)'] = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->commentmeta");
        $data['Posts'] = $wpdb->get_var("SELECT COUNT(ID) as rev_cnt FROM $wpdb->posts p WHERE p.post_type = 'post'");
        $data['Pages'] = $wpdb->get_var("SELECT COUNT(ID) as rev_cnt FROM $wpdb->posts p WHERE p.post_type = 'page'");
        $data['Custom Posts'] = $wpdb->get_var("SELECT COUNT(ID) as rev_cnt FROM $wpdb->posts p WHERE "
                . "(p.post_type != 'page' AND p.post_type != 'post')");
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
		$php_info = preg_replace('#</body.*#si', '', $php_info);
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
        $memory_limit = self::get_memory_limit();

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

        // Some hostings report this in bytes? instead of MB e.g. 256M
        if ( $memory_limit >= 1024 * 1024 ) { // more than 1MB
            $memory_limit /= 1024 * 1024;
        }

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
     * Attempts to find the path to a binary by iterating over several cases.
     *
     * Usage: Orbisius_WP_SAK_Util_File::getBinary();
     * @param str $file
     * borrowed from SAK4WP
     */
    public static function getBinary($file) {
        $file_esc = escapeshellcmd($file);

        // hmm, what did we receive? that required escaping?
        if ($file != $file_esc) {
            return false;
        }

        $options = $output_arr = array();
        $return_var = false;

        $options[] = $file;
        $options[] = basename($file);
        $options[] = "/usr/bin/$file";
        $options[] = "/usr/local/bin/$file";
        $options[] = "/usr/sbin/$file";

        $options = array_unique($options);

        foreach ($options as $file) {
            $cmd = "$file --help 2>&1";
            exec($cmd, $output_arr, $return_var);

            if (empty($return_var)) { // found it! exit code 0 means success in linux
                return $file;
            }
        }

        return false;
    }

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

    /**
     *
     * @param str $file
     * @return int
     */
    public static function is_wp_backup_resource($file) {
       $is_backup_res = 0;

       $exclude_list = array(
           'wp-snapshots', // Duplicator
           'wp-content/updraft',
           'wp-content/backup',
           'wp-content/uploads/backup',
           '.log',
           '.bak',
       );

        foreach ($exclude_list as $exclude_file) {
            if (stripos($file, $exclude_file) !== false) {
                $is_backup_res = 1;
                break;
            }
        }

        return $is_backup_res;
    }

    /**
    * Finds all WP related files from a given directory.
    * the result is either returned as an array OR saved in txt file
    * which can later be used when using tar --files-from=files.txt
     * Usage: Orbisius_WP_SAK_Util_File::get_wp_files();
    * @param str $start_folder
    * @param str $target_file
    * @return array or bool when file is saved.
    */
   public static function get_wp_files($start_folder = '.', $target_file = '') {
       $find_bin = Orbisius_WP_SAK_Util_File::getBinary('find');

       $start_folder = str_replace('\\', '/', $start_folder); // win -> linux slashes
       $start_folder = rtrim($start_folder, '/');
       $result = `$find_bin $start_folder -name "wp-*" -print`; // only one type of extensions is search not OR search; sometimes it puts files that match wp-
       $result = trim($result); // rm last empty line
       $files = preg_split('#[\r\n]+#si', $result);
       $files = preg_grep('#^'.preg_quote($start_folder) . '[./\\\]*wp-#si', $files); // there could be a starting ./ & make sure they all start with wp-

       $auto_append_files = array(
           '.htaccess',
           'index.php',
       );

       $x = rtrim($start_folder, '/') . '/';

       foreach ($auto_append_files as $file) {
           if (is_file( $x . $file)) {
               $files[] = $x . $file;
           }
       }

       foreach ($files as $idx => $file) {
           $file = str_replace('\\', '/', $file); // win -> linux slashes
           $file = ltrim($file, './'); // rm leading ./
           $file = preg_replace('#^' . preg_quote($start_folder) . '[./]*#si', '', $file);

           if (self::is_wp_backup_resource($file)) {
               unset($files[$idx]);
               continue;
           }

           if ($file == 'htaccess') { // restore the dot
               $file = '.' . $file;
           }

           $files[$idx] = $file;
       }

       if (!empty($target_file)) {
           $stat = file_put_contents($target_file, join("\n", $files), LOCK_EX);
           return $stat;
       }

       return $files;
   }
}

/**
* Cool functions that do not belong to a class and can be called individually.
*/
class Orbisius_WP_SAK_Util {
	public static $curl_options = array(
        CURLOPT_USERAGENT => 'SAK4WP/1.0',
        CURLOPT_AUTOREFERER => true,
        CURLOPT_COOKIEFILE => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 600,
	);

    /**
     * Orbisius_WP_SAK_Util::isWindows()
     * @return bool
     */
    public static function isWindows() {
        $yes = stristr(PHP_OS, 'WIN');
        return $yes;
    }

    /**
     * Gets IP. This may require checking some $_SERVER variables ... if the user is using a proxy.
     * @return string
     */
    public static function getIP() {
	    $ip = empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

    /**
     * Gets Server IP from env.
     * @return string
     */
    public static function getServerIP() {
        $ip = empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'];
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
         * Orbisius_WP_SAK_Util::msg()
     */
    static function msg($msg, $status = 0, $use_simple_css = 0) {
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
     * Orbisius_WP_SAK_Util::m()
     * a simple status message, no formatting except color.
     * status is 0, 1 or 2
     */
    static function m($msg, $status = 0, $use_simple_css = 0) {
        $msg = self::msg( $msg, $status, 1 );
        
        // use a simple CSS e.g. a nice span to alert, not just huge divs
        if ($use_simple_css) {
            $msg = str_replace('div', 'span', $msg);
        }

        return $msg;
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
            $error = $result->get_error_message();
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

		if (!file_exists($file)) {
            return false;
        }

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
        //curl_setopt($ch, CURLOPT_SSLVERSION,3);

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
    private $on_document_ready_assets = array();

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
     * Gets a variable from the request and removes any tags and trims spaces.
     * That's of course if options are passed so the orignal value will be returned.
     */
    public function getVar($key, $default = '', $options = array()) {
        $val = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;

        if (!isset($options['raw'])) {
            $val = strip_tags($val);
            $val = trim($val);
        }

        return $val;
    }

    /**
     * Gets a variable and casts it to an INT
     */
    public function getIntVar($key, $default = 0) {
        $val = isset($_REQUEST[$key]) ? intval($_REQUEST[$key]) : $default;

        return $val;
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
				$this->doExit('Cannot self destroy. Please delete <strong>' 
					. __FILE__
					. '</strong> manually.');
            }
            
			// Redirect to the main site (/) and not to the file itself because this would log 
			// a page not found as if somebody was trying to access sak4wp file.
            $this->redirect('/');
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
            $module = $params['module'];
            $action = $params['action'];

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
        try {
            $descr = '';
            
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

                case 'mod_search':
                    $mod_obj = new Orbisius_WP_SAK_Controller_Module_Search();
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

                case 'mod_post_meta':
                    $mod_obj = new Orbisius_WP_SAK_Controller_Module_PostMeta();
                    $descr = $mod_obj->getInfo();
                    $descr .= $mod_obj->run();
                    break;

                case 'mod_user_meta':
                    $mod_obj = new Orbisius_WP_SAK_Controller_Module_UserMeta();
                    $descr = $mod_obj->getInfo();
                    $descr .= $mod_obj->run();
                    break;

                case 'mod_db_dump':
                    $mod_obj = new Orbisius_WP_SAK_Controller_Module_Db_Dump();
                    $descr = $mod_obj->getInfo();
                    $descr .= $mod_obj->run();
                    break;

                case 'mod_site_packager':
                    $mod_obj = new Orbisius_WP_SAK_Controller_Module_Site_Packager();
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
    <h4>Project Page on GitHub</h4>
    <p><a href="https://github.com/orbisius/sak4wp/" target="_blank">https://github.com/orbisius/sak4wp/</a></p>

    <br/>
    <h4>Suggestions</h4>
    <p>If you have a suggestion submit a ticket at <a href="https://github.com/orbisius/sak4wp/issues" target="_blank">github's issue tracker</a> too.</p>

    <br/>
    <h4>Help Videos</h4>
    <p> Check out this YouTube playlist: <a href="http://www.youtube.com/playlist?list=PLfGsyhWLtLLiiU_wvXdOUBvBAXEZGrZfw" target="_blank">http://www.youtube.com/playlist?list=PLfGsyhWLtLLiiU_wvXdOUBvBAXEZGrZfw</a></p>

    <br/>
    <h4>Security</h4>
    <p>
        <strong>If you've found a security bug please <a href="https://orbisius.com/site/contact/" target="_blank">Contact us</a> right away!</strong>
    </p>

BUFF_EOF;

                    break;

                case 'about':
                    $descr = <<<BUFF_EOF
    <h4>About</h4>
    <p>$app_name was created by Svetoslav Marinov (SLAVI), <a href="https://orbisius.com" target="_blank">https://orbisius.com</a>.</p>

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
        } catch ( Exception $e ) {
            $descr .= "Errror: " . Orbisius_WP_SAK_Util::msg( $e->getMessage() );
            $descr .= "<br/>Trace: " . Orbisius_WP_SAK_Util::msg( "<pre>" . $e->getTraceAsString() . "</pre>" );
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
        <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet" />
        <!--<link href="?css=1" type="text/css" rel="stylesheet" />
        <link href="//netdna.bootstrapcdn.com/bootstrap/2.3.2/css/bootstrap.min.css" type="text/css" rel="stylesheet" />-->
        <link href="?css=cust" type="text/css" rel="stylesheet" />
        <!--<link href="?css=2" type="text/css" rel="stylesheet" />-->
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
				  <a href="https://twitter.com/sak4wp" class=
				  "twitter-follow-button" data-show-count="false">Follow @sak4wp</a>

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

        <ul class="nav nav-pills">
            <li class="active"><a href="$script">Dashboard</a></li>
			<li class="dropdown">
				<a class="dropdown-toggle" data-toggle="dropdown" href="#">Modules <span class="caret"></span></a>
				<ul class="dropdown-menu">
					<li><a href="$script?page=mod_stats" title="Lists WordPress Site Stats.">Stats</a></li>
					<li><a href="$script?page=mod_user_meta" title="Pulls User Meta info">User Meta</a></li>
					<li><a href="$script?page=mod_post_meta" title="Pulls Post Meta info from posts or pages">Post Meta</a></li>
					<li><a href="$script?page=mod_db_dump" title="Export current site db">Db Dump</a></li>
					<li><a href="$script?page=mod_site_packager" title="Archive your site">Site Packager</a></li>
					<li><a href="$script?page=mod_unblock" title="Unblocks your IP from Limit Login Attempts ban list">Unblock</a></li>
					<li><a href="$script?page=mod_list_page_templates" title="Lists Page Templates">Page Templates</a></li>
					<li><a href="$script?page=mod_htaccess" title="Lists Page Templates">.htaccess</a></li>
					<li><a href="$script?page=mod_locate_wp" title="Searches for WordPress Installations starting for a given folder">Locate WordPress</a></li>
					<li><a href="$script?page=mod_search" title="Searches for text within the main WordPress folder">Search</a></li>
					<li><a href="$script?page=mod_plugin_manager" title="Searches and installs plugins">Plugin Manager</a></li>
					<li><a href="$script?page=mod_user_manager" title="User Manager">User Manager</a></li>
					<!--<li> <a href="$script?page=mod_self_protect" title="Self Protect">Self Protect</a>	</li>-->
				</ul>
			</li>
            <li class='right'>
				<a href='$script?destroy' class='app-module-self-destroy-button' title="This will remove this script.
                If you see the same script that means the self destruction didn't happen. Please remove the file manually by connecting using an FTP client."
                onclick="return confirm('This will remove this script. If you see the same script that means the self destruction didn\'t happen.
                    Please confirm self destroy operation.', '');">Self Destroy</a>
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
				  data-show-count="false">Follow @Orbisius</a>
				<a href="https://twitter.com/sak4wp" class=
			  "twitter-follow-button" data-show-count="false">Follow @sak4wp</a>
				<script type="text/javascript">
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

    /**
     *
     * @param str $buffer
     * @param bool $render_in_footer
     */
    public function enqeueOnDocumentReady($buffer) {
        $this->on_document_ready_assets[] = $buffer;
    }

	public function displayFooter() {
        $script = ORBISIUS_WP_SAK_APP_SCRIPT;
        $on_document_ready_assets_str = join("\n\n", $this->on_document_ready_assets);

		$buff = <<<BUFF_EOF
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
        <script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-1.8.2.min.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
        <!--<script src="?js=1"></script>-->

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
                $on_document_ready_assets_str

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
.app_50_width {
	width:50%;
}

.app_80_width {
	width:80%;
}

.app_90_width {
	width:90%;
}

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

ul.app-no-bullets-list {
    list-style-type: none;
}

.app-no-padding {
    padding:0;
}

.app-no-margin {
    margin:0;
}

.du_line {
	background:yellow;
	padding-top:3px;
	padding-bottom:3px;
}

.du_total {
	color: #fff;
	background:#428BCA;
}

.du_used {
	color: #fff;
	background:red;
}

.du_free {
	color: #fff;
	background:green;
}

.du_percent {
	color: #fff;
	background:teal;
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