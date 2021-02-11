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

class WPSupportToSlack {
    /**
     * Undocumented function
     */
    function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'support_slack_cron_activate'));
        register_deactivation_hook(__FILE__, array($this, 'support_slack_cron_deactivate'));
        add_filter('cron_schedules', array($this, 'support_slack_cron_update_schedules'));
        add_action('support_slack_cron_event', array($this, 'support_slack_cron_fct'));
        add_action( 'admin_init',  array($this, 'wp_support_slack_register_setting'));
        add_action( 'admin_menu', array($this, 'support_to_slack_settings_menu'));
    }
    

    /**
     * Registers a new settings page under Settings.
     */
    public function support_to_slack_settings_menu() {
        add_options_page(
            __( 'Wordpress Support To Slack', 'support-to-slack' ),
            __( 'WP Support To Slack', 'support-to-slack' ),
            'manage_options',
            'support_settings_page',
            [$this, 'support_to_slack_fields']
        );
    }
    
    /**
     * Registering slack settings field, secton
     *
     * @return void
     */
    public function wp_support_slack_register_setting(){
        register_setting(
            'wp_support_settings', // settings group name
            'hook_url', // option name
            'sanitize_text_field' // sanitization function
        );
    
        register_setting(
            'wp_support_settings', // settings group name
            'plugin_feed', // option name
            'sanitize_text_field' // sanitization function
        );
        /* register_setting(
            'wp_support_settings', // settings group name
            'time_interval', // option name
            'sanitize_text_field' // sanitization function
        ); */
     
        add_settings_section(
            'api_key_section', // section ID
            '', // title (if needed)
            '', // callback function (if needed)
            'support_settings_page' // page slug
        );
     
        add_settings_field(
            'hook_url',
            'Slack Webhook URL',
            [$this, 'hook_url_fields'], // function which prints the field
            'support_settings_page', // page slug
            'api_key_section', // section ID
            array(
                'label_for' => 'hook_url',
                'class' => 'hook_url', // for <tr> element
            )
        );
        add_settings_field(
            'plugin_feed',
            'Plugin Support Feed',
            [$this, 'plugin_feed_slug_field'], // function which prints the field
            'support_settings_page', // page slug
            'api_key_section', // section ID
            array(
                'label_for' => 'plugin_feed',
                'class' => 'plugin_feed', // for <tr> element
            )
        );
        /* add_settings_field(
            'time_interval',
            'Time Interval',
            'slack_time_interval', // function which prints the field
            'support_settings_page', // page slug
            'api_key_section', // section ID
            array(
                'label_for' => 'time_interval',
                'class' => 'time_interval', // for <tr> element
            )
        ); */
    }

    /**
     * Adding webhook url field to wp support to slack plugin settings
     *
     * @return void
     */
    public function hook_url_fields() {
        $hook_url = get_option( 'hook_url' );
        ?>
        <input type="text" value="<?php esc_attr_e($hook_url); ?>" name="hook_url" class="hook_url" id="hook_url" />
    <?php }

    /**
     * Adding plugin feed field to wp support to slack plugin settings
     *
     * @return void
     */
    public function plugin_feed_slug_field(){
        $plugin_feed = get_option( 'plugin_feed' );
        ?>
        <input type="text" value="<?php esc_attr_e($plugin_feed); ?>" name="plugin_feed" class="plugin_feed" id="plugin_feed" /><span style="margin-left:30px">e.g. <code>https://wordpress.org/support/plugin/your_plugin_slug/feed</code> </span>
    <?php }

    // settings field output form
    public function support_to_slack_fields(){
        echo '<div class="wrap">
        <h1>'.esc_html("WordPress Support To Slack Settings").'</h1>
        <form method="post" action="options.php">';
            settings_fields( 'wp_support_settings' ); // settings group name
            do_settings_sections( 'support_settings_page' ); // just a page slug
            submit_button();
        echo '</form></div>';
    }

    /**
     * Plugin activation code
     *
     * @return void
     */
    public function support_slack_cron_activate() 
    {
        // Dont't Run => Not in Schedule Cronjobs 
        if (!wp_next_scheduled('support_slack_cron_event')) {
             wp_schedule_event(time(), 'in_per_minute', 'support_slack_cron_event'); 
        }
    }

    /**
     *  Plugin deactivation code
     *
     * @return void
     */
    public function support_slack_cron_deactivate() 
    {
        wp_clear_scheduled_hook('support_slack_cron_event');
    }

    /**
     * Setting time interval for corn job
     *
     * @return void
     */
    public function support_slack_cron_update_schedules() 
    {
        return array(
            'in_per_minute' => array('interval' => 60, 'display' => 'In every minute'),
            'in_per_ten_minute' => array('interval' => 60 * 10, 'display' => 'Once in Ten minutes'),
            'one_hourly' => array('interval' => 60 * 60,  'display' => 'Once in hour')
        );  
    }

    /**
     * Main function to execute when cron works
     *
     * @return void
     */
    public function support_slack_cron_fct() 
    {
        // get support feed data
        $plugin_feed = get_option('plugin_feed');
        $hook_url = get_option('hook_url');
        if (!empty($plugin_feed) && !empty($hook_url)) {
      
            libxml_use_internal_errors(true);
            $objXmlDocument = simplexml_load_file($plugin_feed, "SimpleXMLElement", LIBXML_NOCDATA);
            if ($objXmlDocument === false) {
                echo "There were errors parsing the XML file.\n";
                foreach (libxml_get_errors() as $error) {
                    echo $error->message;
                }
                exit;
            }
            $objJsonDocument = json_encode($objXmlDocument);
            $arrOutput = json_decode($objJsonDocument, true);

            $support_item = $arrOutput['channel']['item'];
            $new_seq = array();
            foreach ($support_item as $key => $value) {
                preg_match_all('/<[^>]*class="[^"]*\bresolved\b[^"]*"[^>]*>/i', $value['title'], $matches);

                if (!$matches) {
                    $sec_array = array();
                    $sec_array['type'] = 'section';
                    $sec_array['text']['type'] = 'mrkdwn';
                    $sec_array['text']['text'] =  ++$key .'. '. strip_tags($value['title']) . '<' . $value['link'] .' | Click here>';
                    $new_seq[] = $sec_array;
                }
            }
            if (!empty($new_seq)) {
                $message = array('payload' => json_encode(array(
                'text' => 'You have support ticket from wordpress.org to resolve',
                "blocks" =>
                        $new_seq
                    )));
                // Use curl to send your message
                $ch = curl_init($hook_url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);
                // Laravel-specific log writing method
                // Log::info("Sent to Slack: " . $message, array('context' => 'Notifications'));
                return $result;
            }
        }
    }   
}

$wp_to_slack = new WPSupportToSlack();