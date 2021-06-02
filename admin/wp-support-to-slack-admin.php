<?php

    /**
     * The admin-specific functionality of the plugin.
     *
     * @link       https://codeboxr.com
     * @since      1.0.0
     *
     * @package    WPSupportToSLack
     * @subpackage WPSupportToSLack/admin
     */

    /**
     * The admin-specific functionality of the plugin.
     *
     * Defines the plugin name, version, and two examples hooks for how to
     * enqueue the admin-specific stylesheet and JavaScript.
     *
     * @package    WPSupportToSLack
     * @subpackage WPSupportToSLack/admin
     * @author     WPPOOL <info@wpppool.com>
     */
    class WP_Support_Slack_Admin
    {

        /**
         * The Name of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string $plugin_name The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string $version The current version of this plugin.
         */
        private $version;

        private $setting;

        /**
         * Initialize the class and set its properties.
         *
         * @param string $plugin_name The name of this plugin.
         * @param string $version     The version of this plugin.
         *
         * @since    1.0.0
         *
         */
        public function __construct() {
            $this->setting = new WP_To_Slack_Settings();
            add_action('admin_menu', array( $this, 'create_admin_menu' ));
            add_action('admin_init', array( $this, 'settings_init' ));
            add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));
            add_action('admin_enqueue_scripts', array( $this, 'enqueue_styles' ));
            add_action('wp_enqueue_scripts', array( $this, 'frontend_styles' ));

            $feed_list = get_option( 'theme_plugin_list');
            if(!empty($feed_list['plugin_theme_feed']) && array_key_exists('feed', $feed_list['plugin_theme_feed'])) {
                foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $value) {
                    add_action('support_to_slack_event_'.$key.'', 'WP_To_Slack_Helper::support_to_slack_msg_request', 10, 4);
                    add_action('unresolved_support_interval_'.$key.'', 'WP_To_Slack_Helper::unresolved_support_fixed_int_request', 10, 4);
                }
            }
            $notification_settings = get_option('slack_support_settings');
            if($notification_settings['enable_download_count'] == 'on'){
                add_action( 'cron_save_org_downloads', 'WP_To_Slack_Helper::org_daily_download_count' );
            }
            add_shortcode( 'active-installs', 'WP_To_Slack_Helper::active_installs' );
            add_shortcode( 'rating-number', 'WP_To_Slack_Helper::rating_numbers' );

        }

        public function settings_init(){
            $this->setting->set_sections($this->get_settings_sections());
            $this->setting->set_fields($this->get_settings_field());
            $this->setting->admin_init();
        }// end of settings_init method

        /**
         * Register the stylesheets for the admin area.
         *
         * @since    1.0.0
         */
        public function enqueue_styles($hook = ''){
            global $post;
            $settings = $this->setting;
            $page     = isset($_GET['page']) ? esc_attr(wp_unslash($_GET['page'])) : '';

            if ($page == 'wp-support-to-slack-page') {
                wp_register_style( 'select2', plugin_dir_url( __FILE__ ) . '../assets/select2/css/select2.min.css', array(), $this->version );
				wp_register_style( 'jquery-timepicker', plugin_dir_url( __FILE__ ) . '../assets/css/jquery.timepicker.min.css', array(), $this->version, 'all' );
				wp_register_style( 'jquery-ui', plugin_dir_url( __FILE__ ) . '../assets/css/jquery-ui.css', array(), $this->version, 'all' );
				wp_enqueue_style( 'select2' );
				wp_enqueue_style( 'jquery-timepicker' );
				wp_enqueue_style( 'jquery-ui' );
				wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_style('thickbox');
                wp_enqueue_style('wp-slack-admin-css', plugins_url('../assets/css/admin.css', __FILE__), null, '1.0');
                
            }
        }//end enqueue_styles

        public function frontend_styles(){
            wp_enqueue_style('wp-slack-frontend-css', plugins_url('../assets/css/frontend.css', __FILE__), null, '1.0');
        }

        /**
         * Register the JavaScript for the admin area.
         *
         * @since    1.0.0
         */
        public function enqueue_scripts($hook = ''){
            global $post;
            $settings       = $this->setting;
            $page           = isset($_GET['page']) ? esc_attr(wp_unslash($_GET['page'])) : '';


            if ($page == 'wp-support-to-slack-page') {
                wp_register_script('select2', plugin_dir_url(__FILE__) . '../assets/select2/js/select2.min.js', array( 'jquery' ), $this->version, true);
                wp_register_script(
                    'support_to_slack_admin',
                    plugin_dir_url(__FILE__) . '../assets/js/admin.js',
                    array(
                        'jquery',
                        'select2'
                    ),
                    $this->version,
                    true
                );

                // Localize the script with translation
                $translation_placeholder = apply_filters(
                    'support_to_slack_admin_js_vars',
                    array(
                        'remove'       => esc_html__('Remove', 'support-to-slack'),
                        'date'         => esc_html__('Webhook', 'support-to-slack'),
                        'start'        => esc_html__('Theme/Plugin Link', 'support-to-slack'),
                        'end'          => esc_html__('Message', 'support-to-slack'),
                        'subject'      => esc_html__('Subject', 'support-to-slack'),
                        'plugin_directory'      => SUPPORT_TO_SLACK_URL,
                        'ajax_url'      => admin_url('admin-ajax.php'),
                    )
                );

                wp_localize_script('support_to_slack_admin', 'support_to_slack_admin_setting', $translation_placeholder);

                wp_enqueue_script('jquery');
                wp_enqueue_media();
                wp_enqueue_script('select2');
                wp_enqueue_script( 'jquery-timepicker' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'wp-color-picker');
                wp_enqueue_script('thickbox'); 
                wp_enqueue_script('support_to_slack_admin');
            }
        }//end enqueue_scripts

        /**
         * This admin_menu method will create options page
         */
        public function create_admin_menu(){
            add_menu_page(
                __('WordPress Support To Slack', 'wpdocs-webnail-modules'),
                __('WP To Slack', 'wpdocs-webnail-modules'),
                'manage_options',
                'wp-support-to-slack-page',
                array( $this, 'support_to_slack_fields' ),
                'dashicons-superhero'
            );
        }// end of admin_menu method

        /**
         * This callback method
         */
        public function support_to_slack_fields(){
            $setting = $this->setting;
            echo support_to_slack_get_template_html('admin/setting.php', array( 'ref' => $this, 'setting' => $setting ));
        }// end of wp_slack_support_options_page_data method

        public function get_settings_sections(){
            $sections = array(
                array(
                    'id'    => 'theme_plugin_list',
                    'title' => esc_html__('Plugin & Theme\'s', 'support-to-slack'),
                ),
                array(
                    'id'    => 'slack_support_settings',
                    'title' => esc_html__('Settings', 'support-to-slack'),
                ),
                array(
                    'id'    => 'slack_support_doc',
                    'title' => esc_html__('Documentation', 'support-to-slack'),
                )
            );

            return apply_filters('support-to-slack-admin_sections', $sections);
        }// end of get_settings_sections method

        public function get_settings_field(){
            $settings_builtin_fields = array(
                'theme_plugin_list'       => array(
                    array(
                        'name'              => 'plugin_theme_feed',
                        'label'             => esc_html__('Add Plugin / Theme', 'support-to-slack'),
                        'type'              => 'plugin_theme_feed',
                        'sanitize_callback' => array( 'WP_To_Slack_Helper', 'sanitize_callback_plugin_theme_feed' ),
                    ),
                ),

                'slack_support_settings'    => array(
                    array(
                        'name'    => 'enable_rating',
                        'label'   => esc_html__('Enable Rating Notification', 'support-to-slack'),
                        'type'    => 'checkbox',
                        'default' => 'on'
                    ),
                    array(
                        'name'    => 'enable_download_count',
                        'label'   => esc_html__('Enable Daily Download Count', 'support-to-slack'),
                        'type'    => 'checkbox',
                    ),
                    array(
                        'name' => 'download_webhook',
                        'label' => __('Slack Webhook To Send Download Notification', 'support-to-slack').'<div class="webhook_tooltip">?<span class="webhook_tooltip_text">Which Slack channnel you wnant to send notifications</span></div>',
                        'desc' => '',
                        'type' => 'text',
                        'default' => ''
                    ),
                    array(
                        'name'    => 'interval_recurrence',
                        'label'   => esc_html__('Cron Interval', 'support-to-slack'),
                        'type'    => 'select_number',
                        'options' => array(
                            'minutewise' => esc_html__('Minutewise', 'support-to-slack'),
                            'hourly' => esc_html__('Hourly', 'support-to-slack'),
                            'daily' => esc_html__('Daywise', 'support-to-slack'),
                            'weekly' => esc_html__('Weekly', 'support-to-slack'),
                            'monthly' => esc_html__('Monthly', 'support-to-slack'),
                        ),
                    ),
                ),
                'slack_support_doc'    => array(
                    array(
                        'name' => 'documentation',
                        'label' => '',
                        'type' => 'doc',
                    )
                )
            );

            $settings_fields = array(); //final setting array that will be passed to different filters

            $sections = $this->get_settings_sections();

            foreach ($sections as $section) {
                if (! isset($settings_builtin_fields[ $section['id'] ])) {
                    $settings_builtin_fields[ $section['id'] ] = array();
                }
            }

            foreach ($sections as $section) {
                $settings_fields[ $section['id'] ] = apply_filters(
                    'wp_slack_support_global_' . $section['id'] . '_fields',
                    $settings_builtin_fields[ $section['id'] ]
                );
            }

            $settings_fields = apply_filters('wp_slack_support_global_fields', $settings_fields); //final filter if need

            return $settings_fields;
        }//end get_settings_field
        
        
    }//end class WP_Support_Slack_Admin
