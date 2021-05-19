<?php
/**
 * Plugin Name: WP Support To Slack
 * Plugin URI:  https://wppool.dev
 * Description: This plugin will send notification per interval when there is any unresolved ticket itn wordpress support ticket section
 * Version:     1.0
 * Author:      Saiful Islam
 * Author URI:  https://wppool.dev
 * Text Domain: support-to-slack
 * Domain Path: /languages/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

final class WPSupportToSlack {

	/**
     * Plugin Version
     */
    const version = '1.0';
    /**
     * Undocumented function
     */
    public function __construct()
    {
        register_activation_hook(__FILE__, 'WP_To_Slack_Helper::support_slack_cron_activate');
        register_deactivation_hook(__FILE__, array($this, 'support_slack_cron_deactivate'));
		$this->define_constants();
        // activation hook
        // load plugin important file
        add_action('plugins_loaded', array($this, 'init_plugin') );
		$this->load_dependencies();
		add_filter('cron_schedules', array($this, 'support_slack_cron_update_schedules'));
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'support_add_plugin_page_settings_link'));
        add_action( 'admin_init', array( $this, 'support_to_slack_activation_redirect') );
        register_activation_hook( __FILE__, array( $this, 'support_to_slack_activation') );
    }

	private function load_dependencies(){
		require_once plugin_dir_path(__FILE__) . 'admin/wp-support-to-slack-admin.php';
		require_once plugin_dir_path(__FILE__) . 'includes/wp-support-slack-settings.php';
		require_once plugin_dir_path(__FILE__) . 'includes/wp-support-to-slack-helper.php';
		require_once plugin_dir_path(__FILE__) . 'includes/wp-support-to-slack-tpl-loader.php';
	}

    
    /**
     * includes plugin important file
     *
     * @return void
     */
    public function init_plugin()
    {
        new WP_Support_Slack_Admin();
    }

    /**
     * init function for single tone approach
     *
     * @return void
     */
    public static function init()
    {
        static $instance = false;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function define_constants()
    {
        define('SUPPORT_TO_SLACK_VERSION', self::version);
        define('SUPPORT_TO_SLACK_FILE', __FILE__);
        define('SUPPORT_TO_SLACK_PATH', __DIR__);
        define('SUPPORT_TO_SLACK_URL', plugins_url('', SUPPORT_TO_SLACK_FILE));
        define('SUPPORT_TO_SLACK_ASSETS', SUPPORT_TO_SLACK_URL . '/assets');
        define('SUPPORT_TO_SLACK_ROOT_PATH', plugin_dir_path(__FILE__));
    }

    public function has_error($key)
    {
        return isset($this->errors[$key]) ? true : false;
    }

    /**
     *  Plugin deactivation code
     *
     * @return void
     */
    public function support_slack_cron_deactivate() 
    {
		$feed_list = get_option( 'theme_plugin_list');
        if( isset($feed_list['plugin_theme_feed']['feed'])){
            foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $single_feed) {
                wp_clear_scheduled_hook('support_to_slack_event_'.$key.'');
                wp_clear_scheduled_hook('unresolved_support_interval_'.$key.'');
            }
        }
		
		wp_clear_scheduled_hook('cron_save_org_downloads');
    }

	 /**
         * Undocumented function
         *
         * @param [type] $links
         * @return void
         */
        public function support_add_plugin_page_settings_link($links)
        {
            $links[] = '<a href="' .
            admin_url('admin.php?page=wp-support-to-slack-page') .
            '">' . __('Settings', 'support-to-slack') . '</a>';
            return $links;
        }

		/**
         * Setting time interval for cron job
         *
         * @return void
         */
        public function support_slack_cron_update_schedules($schedules) {
			$feed_list = get_option( 'theme_plugin_list');
            $cron_settings = get_option( 'slack_support_settings');
            // write_log($cron_settings);

            if(!empty($cron_settings)){
                if (!empty($cron_settings['interval_recurrence'])) {
                    $recurrence = $cron_settings['interval_recurrence']['recurrence'];
                    $interval = $cron_settings['interval_recurrence']['interval'];
                    //$schedules[$recurrence] = array('interval' => 60 * $interval,  'display' => 'Minuite Wise');
                    //$download_count = get_option('enable_download_count');
                    switch ($recurrence) {
                        case 'minutewise':
                            $schedules[$recurrence] = array('interval' => 60 * $interval,  'display' => 'Minuite Wise');
                            break;
                        case 'hourly':
                            $schedules[$recurrence] = array('interval' => 60 * 60 * $interval,  'display' => 'Hourly');
                            break;
                        case 'daily':
                            $schedules[$recurrence] = array('interval' => 60 * 60 * 24 * $interval,  'display' => 'Daily');
                            break;
                        case 'weekly':
                            $schedules[$recurrence] = array('interval' => 60 * 60 * 24 * 7 * $interval,  'display' => 'Weekly');
                            break;
                        case 'monthly':
                            $schedules[$recurrence] = array('interval' => 60 * 60 * 24 * 30 * $interval,  'display' => 'Monthly');
                            break;
                        default:
                            $schedules[$recurrence] = array('interval' => 60 * 60,  'display' => 'One Hourly');
                            break;
                    }
                }
                //write_log($schedules);
               
                $schedules['every_12'] = array('interval' => 60 * 60 * 12,  'display' => 'Daily');
                $schedules['daily_count'] = array('interval' => 60 * 60 * 24,  'display' => 'Daily');
                $schedules['minute_count'] = array('interval' => 60,  'display' => 'Daily');
            }
            return $schedules;
        }

        public function support_to_slack_activation(){
            add_option('do_activation_redirect', true);
        }

        public function support_to_slack_activation_redirect() {
            if (get_option('do_activation_redirect', false)) {
                delete_option('do_activation_redirect');
                wp_redirect(admin_url("admin.php?page=wp-support-to-slack-page#slack_support_settings"));
             }
        }

}

/**
 * initialise the main function
 *
 * @return void
 */
function support_slack()
{
    return WPSupportToSlack::init();
}

// let's start the plugin
support_slack();
