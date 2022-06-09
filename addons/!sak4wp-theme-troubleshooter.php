<?php

/*
  Plugin Name: SAK4WP Theme Troubleshooter
  Plugin URI: http://sak4wp.com/
  Description: This plugin overrides current theme's settings. Your site will use one of the default WordPress themes until <strong>Swiss Army Knife for WordPress (SAK4WP)</strong> tool is running.
  Version: 1.0.0
  Author: Orbisius.com
  Author URI: https://orbisius.com
 */

/*
= What does this plugin do? =
It overrides the current theme with one of the default WordPress ones. It is useful if your current theme is broken.

= Usage / Installation =
Upload this plugin in www/wp-content/mu-plugins/
If the mu-plugins folder doesn't exist create it.

Next, login as you would normally log into WP admin. Then go to Appearance > Themes. 
One of the default WordPress themes should be active.
Now, you need to activate a theme that you know it works.
The page will refresh but it will still showing one of the default WordPress themes.
Now you can remove the !sak4wp-theme-troubleshooter.php file from /wp-content/mu-plugins/
*/
 
$sak4wp_theme_troubleshooter_obj = new SAK4WP_Theme_Troubleshooter();

if ($sak4wp_theme_troubleshooter_obj->is_enabled()) {
    add_filter('stylesheet', array($sak4wp_theme_troubleshooter_obj, 'get_stylesheet'));
    add_filter('template', array($sak4wp_theme_troubleshooter_obj, 'get_template'));
}

/**
 * @package SAK4WP
 * @site http://sakwp.com
 */
class SAK4WP_Theme_Troubleshooter {
    /**
     * We'll override theme files only if !sak4wp.php file is in the root location.
     * @return bool if the SAK4WP is there this plugin will be enabled.
     */
    public function is_enabled() {
        $enabled = file_exists(ABSPATH . '!sak4wp.php');
        return $enabled;
    }

    /**
     * Loops and checks if one of the default themes is there and sets it.
     * This can be used for troubleshooting.
     *
     * @return mixed false/object
     */
    public function get_working_theme() {
        $default_themes = array('twentythirteen', 'twentytwelve', 'twentyeleven');

        foreach ($default_themes as $theme_dir) {
            $theme = wp_get_theme($theme_dir);

            if ($theme->exists()) {
                return $theme;
            }
        }

        return false;
    }

    /**
     * Returns the directory of the theme e.g. twentytwelve
     * @param string $stylesheet
     * @return string
     */
    public function get_stylesheet($stylesheet = '') {
        $theme = $this->get_working_theme();
        $stylesheet = empty($theme) ? $stylesheet : $theme['Stylesheet'];

        return $stylesheet;
    }

    /**
     * Returns the directory of the theme e.g. twentytwelve, not sure why
     * @param type $template
     * @return type
     */
    public function get_template($template) {
        $theme = $this->get_working_theme();
        $template = empty($theme) ? $template : $theme['Template'];

        return $template;
    }
}
