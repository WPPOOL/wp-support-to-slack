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
		global $wpdb;
		$result = $wpdb->get_results ( "SELECT * FROM {$wpdb->prefix}_pluginfeeds");
		foreach ($result as $key => $value) {
			add_action('TestCron_cron_event_'.$value->plugin_feed_url.'', array($this, 'TestCron_cron_fct'), 10, 4);
		}
        
        //add_action( 'admin_menu', array($this, 'support_to_slack_settings_menu'));
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'support_add_plugin_page_settings_link'));
        add_action('admin_enqueue_scripts', array($this, 'script_file'));
        register_activation_hook(__FILE__, [$this, 'create_tables']);
        include_once( plugin_dir_path( __FILE__ ) . '/includes/db/FeedList.php');
        include_once( plugin_dir_path( __FILE__ ) . '/includes/db/feedbook.php');
        include_once( plugin_dir_path( __FILE__ ) . '/includes/functions.php');
        $feedbook = new FeedBook();
        add_action("admin_init", [$feedbook, 'form_handler']);
        add_action('admin_post_wp_support_to_slack_page-delete-cron', [$feedbook, 'delete_address']);
		add_action( 'init', array($this,'cron_save_org_downloads') );
		add_action( 'admin_menu', array($this,'FeedMenu') );
		add_action( 'admin_init',  array($this, 'field_register_setting'));
    }

	public function FeedMenu() {
        add_menu_page(
            __( 'WordPress Support To Slack', 'wpdocs-webnail-modules' ),
            __( 'WP To Slack', 'wpdocs-webnail-modules' ),
            'manage_options',
            'wp_support_to_slack_page',
            array( $this, 'support_to_slack_fields' ),
            'dashicons-superhero'
        );
		add_submenu_page(
            'wp_support_to_slack_page', 'Settings', 'Settings', 'manage_options', 'supoort_to_slack_setings', 
            array( $this, 'supoort_to_slack_setings' )
        );
    }
	public function supoort_to_slack_setings(){
		echo '<div class="wrap">
		<h1>'.esc_html__('Support To Slack Global Settings', '').'</h1>
		<form method="post" action="options.php">';

			settings_fields( 'wp_slack_settings' ); // settings group name
			do_settings_sections( 'supoort_to_slack_setings' ); // just a page slug
			submit_button();
	
		echo '</form></div>';
	}

	public function field_register_setting(){
 
		register_setting(
			'wp_slack_settings', // settings group name
			'enable_rating', // option name
			'sanitize_text_field' // sanitization function
		);
		register_setting(
			'wp_slack_settings', // settings group name
			'enable_download_count', // option name
			'sanitize_text_field' // sanitization function
		);

		register_setting(
			'wp_slack_settings', // settings group name
			'minutewise', // option name
			'sanitize_text_field' // sanitization function
		);
		register_setting(
			'wp_slack_settings', // settings group name
			'hourly', // option name
			'sanitize_text_field' // sanitization function
		);
		register_setting(
			'wp_slack_settings', // settings group name
			'daywise', // option name
			'sanitize_text_field' // sanitization function
		);
		register_setting(
			'wp_slack_settings', // settings group name
			'weekly', // option name
			'sanitize_text_field' // sanitization function
		);
		register_setting(
			'wp_slack_settings', // settings group name
			'monthly', // option name
			'sanitize_text_field' // sanitization function
		);
		register_setting(
			'wp_slack_settings', // settings group name
			'download_count_hook', // option name
			'sanitize_text_field' // sanitization function
		);
	 
		add_settings_section(
			'wp_slack_settings_id', // section ID
			'', // title (if needed)
			'', // callback function (if needed)
			'supoort_to_slack_setings' // page slug
		);
	 
		add_settings_field(
			'enable_rating_id',
			'Enable Plugin Rating  Notification',
			array($this, 'rating_notification'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'enable_rating',
				'class' => 'enable_rating', // for <tr> element
			)
		);

		add_settings_field(
			'enable_count_id',
			'Enable Plugin Daily Download  Notification',
			array($this, 'download_count'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'enable_count',
				'class' => 'enable_count', // for <tr> element
			)
		);
		add_settings_field(
			'download_count_hook',
			'Add slack webhook for plugins daily download report ',
			array($this, 'download_count_hook'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'download_count_hook',
				'class' => 'download_count_hook', // for <tr> element
			)
		);

		add_settings_field(
			'minutewise_interval',
			'Minute Wise Cron Interval',
			array($this, 'minutewise_interval'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'minutewise_interval',
				'class' => 'minutewise_interval', // for <tr> element
			)
		);
		add_settings_field(
			'hourly_interval',
			'Hourly Cron Interval',
			array($this, 'hourly_interval'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'hourly_interval',
				'class' => 'hourly_interval', // for <tr> element
			)
		);
		add_settings_field(
			'daywise_interval',
			'Daywise Cron Interval',
			array($this, 'daywise_interval'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'daywise_interval',
				'class' => 'daywise_interval', // for <tr> element
			)
		);
		add_settings_field(
			'weekly_interval',
			'Daywise Cron Interval',
			array($this, 'weekly_interval'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'weekly_interval',
				'class' => 'weekly_interval', // for <tr> element
			)
		);
		add_settings_field(
			'monthly_interval',
			'Monthly Cron Interval',
			array($this, 'monthly_interval'), // function which prints the field
			'supoort_to_slack_setings', // page slug
			'wp_slack_settings_id', // section ID
			array( 
				'label_for' => 'monthly_interval',
				'class' => 'monthly_interval', // for <tr> element
			)
		);
	 
	}

	public function rating_notification(){
 
		$enable_rating = get_option( 'enable_rating' );
		$checked = '';
		if(!empty($enable_rating) && $enable_rating >= 1){
			$checked = 'checked';		
		}
		echo '<input type="checkbox" class="regular-text" id="enable_rating" name="enable_rating" value="1" '.$checked.' />';
	}

	public function download_count(){
 
		$enable_rating = get_option( 'enable_download_count' );
		$checked = '';
		if(!empty($enable_rating) && $enable_rating >= 1){
			$checked = 'checked';		
		}
		echo '<input type="checkbox" class="regular-text" id="enable_download_count" name="enable_download_count" value="1" '.$checked.' />';
	}

	public function minutewise_interval(){
		$minutewise = get_option( 'minutewise' );

		echo '<input type="number" class="regular-text" id="minutewise" name="minutewise" value="'.$minutewise.'" />';
	}
	public function hourly_interval(){
		$hourly = get_option( 'hourly' );

		echo '<input type="number" class="regular-text" id="hourly" name="hourly" value="'.$hourly.'" />';
	}
	public function daywise_interval(){
		$daywise = get_option( 'daywise' );

		echo '<input type="number" class="regular-text" id="daywise" name="daywise" value="'.$daywise.'" />';
	}
	public function weekly_interval(){
		$weekly = get_option( 'weekly' );

		echo '<input type="number" class="regular-text" id="weekly" name="weekly" value="'.$weekly.'" />';
	}
	public function monthly_interval(){
		$monthly = get_option( 'monthly' );

		echo '<input type="number" class="regular-text" id="monthly" name="monthly" value="'.$monthly.'" />';
	}
	public function download_count_hook(){
		$download_count_hook = get_option( 'download_count_hook' );

		echo '<textarea class="regular-text" id="download_count_hook" name="download_count_hook" value="'.$download_count_hook.'" >'.$download_count_hook.'</textarea>';
	}

    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}_pluginfeeds` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `slack_webhook` VARCHAR(255) NULL , `plugin_feed_url` VARCHAR(255) NULL, `plugin_name` VARCHAR(255) NULL, `minutewise` INT(11) NULL, `hourly` INT(11) NULL, `daywise` INT(11) NULL, `weekly` INT(11) NULL, `custom_message` VARCHAR(255) NULL, `enable_rating` INT(11) NULL, `download_count` INT(11) NULL, `created_by` BIGINT(20) NOT NULL , `created_at` DATETIME NOT NULL , PRIMARY KEY (`id`)) $charset_collate";

        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        dbDelta($schema);
    }

    public function script_file(){
        wp_enqueue_script('wp-slack-admin-script', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'wp-util'), time(), true);
        wp_enqueue_style('wp-slack-admin-css', plugins_url('assets/css/admin.css', __FILE__), null, '1.0');
    }

    /**
     * Undocumented function
     *
     * @param [type] $links
     * @return void
     */
    public static function support_add_plugin_page_settings_link( $links ) {
        $links[] = '<a href="' .
            admin_url( 'admin.php?page=supoort_to_slack_setings' ) .
            '">' . __('Settings', 'support-to-slack') . '</a>';
        return $links;
    }
    /**
     * Registers a new settings page under Settings.
     */
    /* public function support_to_slack_settings_menu() {
        add_options_page(
            __( 'Wordpress Support To Slack', 'support-to-slack' ),
            __( 'WP Support To Slack', 'support-to-slack' ),
            'manage_options',
            'support_settings_page',
            [$this, 'support_to_slack_fields']
        );
    } */

    // settings field output form
    public function support_to_slack_fields(){
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
        switch ($action) {
            case "new":
                $template = __DIR__ . "/includes/feed-new.php";
                break;
            case "edit":
                $feed_info  = wpslack_get_plugin_info($id);
                $template = __DIR__ . "/includes/feed-edit.php";
                break;
            case "view":
                $template = __DIR__ . "/includes/feed-view.php";
                break;
            default:
                $template = __DIR__ . "/includes/feed-list.php";
        }

        if (file_exists($template)) {
            include $template;
        }
    }
    public function has_error($key)
    {
        return isset($this->errors[$key]) ? true : false;
    }

    /**
     * Plugin activation code
     *
     * @return void
     */
    public function support_slack_cron_activate() 
    {
        // Dont't Run => Not in Schedule Cronjobs 
        //$args = get_current_user_id();
        global $wpdb;
        $id = get_current_user_id();
        $result = $wpdb->get_results ( "SELECT * FROM {$wpdb->prefix}_pluginfeeds");
		$minutewise = get_option('minutewise');
		$hourly = get_option('hourly');
		$daywise = get_option('daywise');
		$weekly = get_option('weekly');
		$monthly = get_option('monthly');
		
        
		foreach ($result as $key => $plugin_info) {
			if(!empty($minutewise) && $minutewise > 0 && !wp_next_scheduled( 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'' )){
				wp_schedule_event(time(), 'minutewise', 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'', array(
					'plugin_feed_url' => $plugin_info->plugin_feed_url,
					'slack_webhook' => $plugin_info->slack_webhook,
					'plugin_name' => $plugin_info->plugin_name,
					'custom_message' => $plugin_info->custom_message,
				));
			}elseif(!empty($hourly) && $hourly > 0 && !wp_next_scheduled( 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'' )){
				wp_schedule_event(time(), 'hourly', 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'', array(
					'plugin_feed_url' => $plugin_info->plugin_feed_url,
					'slack_webhook' => $plugin_info->slack_webhook,
					'plugin_name' => $plugin_info->plugin_name,
					'custom_message' => $plugin_info->custom_message,
				));
			}elseif(!empty($daywise) && $daywise > 0 && !wp_next_scheduled( 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'' )){
				wp_schedule_event(time(), 'daywise', 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'', array(
					'plugin_feed_url' => $plugin_info->plugin_feed_url,
					'slack_webhook' => $plugin_info->slack_webhook,
					'plugin_name' => $plugin_info->plugin_name,
					'custom_message' => $plugin_info->custom_message,
				));
			}elseif(!empty($weekly) && $weekly > 0 && !wp_next_scheduled( 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'' )){
				wp_schedule_event(time(), 'weekly', 'TestCron_cron_event_'.$plugin_info->plugin_feed_url.'', array(
					'plugin_feed_url' => $plugin_info->plugin_feed_url,
					'slack_webhook' => $plugin_info->slack_webhook,
					'plugin_name' => $plugin_info->plugin_name,
					'custom_message' => $plugin_info->custom_message,
				));
			}
		}
        

		if ( ! wp_next_scheduled( 'cron_save_org_downloads' ) ) :
			wp_schedule_event( time(), 'daily', 'cron_save_org_downloads' ); // 1407110400 is 08 / 4 / 2014 @ 0:0:0 UTC
		endif;
        
    }

    /**
     *  Plugin deactivation code
     *
     * @return void
     */
    public function support_slack_cron_deactivate() 
    {
		global $wpdb;
		$id = get_current_user_id();
		$result =  $wpdb->get_results ( "SELECT * FROM {$wpdb->prefix}_pluginfeeds");
		foreach ($result as $key => $value) {
			wp_clear_scheduled_hook('TestCron_cron_event_'.$value->plugin_feed_url.'');
		}
		wp_clear_scheduled_hook('cron_save_org_downloads');
    }

    /**
     * Setting time interval for cron job
     *
     * @return void
     */
    public function support_slack_cron_update_schedules($schedules) 
    {
		$minutewise = get_option('minutewise');
		$hourly = get_option('hourly');
		$daywise = get_option('daywise');
		$weekly = get_option('weekly');
		$monthly = get_option('monthly');
		if(!empty($minutewise)){
			$schedules['minutewise'] = array('interval' => 60 * $minutewise,  'display' => 'Once in hour');
		}elseif(!empty($hourly)){
			$schedules['hourly'] = array('interval' => 60 * 60 * $hourly,  'display' => 'Once in hour');
		}elseif(!empty($weekly)){
			$schedules['weekly'] = array('interval' => 60 * 60 * 24 * 7 * $weekly,  'display' => 'Once in hour');
		}elseif(!empty($daywise)){
			$schedules['daywise'] = array('interval' => 60 * 60 * 24 * $daywise,  'display' => 'Once in hour');
		}elseif(!empty($monthly)){
			$schedules['monthly'] = array('interval' => 60 * 60 * $monthly,  'display' => 'Once in hour');
		}else{
			$schedules['daywise'] = array('interval' => 60 * 60 * 24,  'display' => 'Once in hour');
		}
		$download_count = get_option('enable_download_count');
		if(!empty($download_count) && $download_count >= 1){
			$schedules['daily'] = array('interval' => 60,  'display' => 'Once in a day');
		}
        return $schedules;
    }

    /**
     * Main function to execute when cron works
     *
     * @return void
     */
    public function get_plugin_info(){
        global $wpdb;
        $id = get_current_user_id();
        return $wpdb->get_results ( "SELECT * FROM {$wpdb->prefix}_pluginfeeds");
    }

    public function TestCron_cron_fct($plugin_feed_url, $hook_url, $plugin_name, $custom_message ) 
    {
        global $wpdb;
        $id = get_current_user_id();
        $result = $wpdb->get_results ( "SELECT * FROM {$wpdb->prefix}_pluginfeeds");
        $plugin_feed = 'https://wordpress.org/support/plugin/'.$plugin_feed_url.'/feed';
        //$hook_url = $plugin_info->slack_webhook;
        $custom_message = !empty($custom_message) ? $custom_message : "new support ticket from " . $plugin_name;
        
        /**
         * Checking plugin feed and hook url is not empty
         */
        if (!empty($plugin_feed_url) && !empty($hook_url)) {
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
			if(key_exists('item', $arrOutput['channel']) && $objXmlDocument == true){
				$support_item = $arrOutput['channel']['item'];
				$new_seq = array();
				// Creating new array based on format of slack sending message data
				foreach ($support_item as $key => $value) {
					$matches = preg_match_all('/<[^>]*class="[^"]*\bresolved\b[^"]*"[^>]*>/i', $value['title'], $matches);
					if (!$matches) {
						$sec_array = array();
						$sec_array['type'] = 'section';
						$sec_array['text']['type'] = 'mrkdwn';
						$sec_array['text']['text'] =  ++$key .'. '. strip_tags($value['title']) . '<' . $value['link'] . ' From '. $plugin_name .' | Click here>';
						$new_seq[] = $sec_array;
					}
				}
            // checking is new array is empty or not
				if (!empty($new_seq)) {
					$message = array('payload' => json_encode(array(
					'text' => $custom_message,
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
					//return $result;
				}
        	}
        }
		$rating_notification = get_option('enable_rating');
		if(!empty($rating_notification) && $rating_notification > 0 && !empty($plugin_feed_url)){
			libxml_use_internal_errors(TRUE);
			$wp_review_feed = 'https://wordpress.org/support/plugin/'.$plugin_feed_url.'/reviews/feed';
			$review_document = simplexml_load_file($wp_review_feed, "SimpleXMLElement", LIBXML_NOCDATA);
			if ($review_document === FALSE) {
				echo "There were errors parsing the XML file.\n";
				foreach(libxml_get_errors() as $error) {
					echo $error->message;
				}
				exit;
			}
			$objJsonreview = json_encode($review_document);
			$arrOutputReview = json_decode($objJsonreview, TRUE);
			$reviews_item = $arrOutputReview['channel']['item'];
			$yesterday_rating = !empty(get_option('total_rating'))? get_option('total_rating') : count($reviews_item);
  			array_splice($reviews_item, count($reviews_item) - $yesterday_rating, $yesterday_rating);
			//print_r($arrOutputReview['channel']['item']);
			$rating_arr = array();
			foreach($reviews_item  as $key => $value){
			  $str = $value['description'];
			  if (preg_match_all('/Rating:(.*?)star/', $str, $match)) {
				  if (floatval($match[1][0]) == true) {
					  echo '<p>hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. Details: '. $value['link'];
					  $sec_array = array();
					  $sec_array['type'] = 'section';
					  $sec_array['text']['type'] = 'mrkdwn';
					  $sec_array['text']['text'] =  ++$key .'. '. 'Hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. '.$plugin_name.': '. $value['link'];
					  $rating_arr[] = $sec_array;
				  }
			  }
			}
			update_option('total_rating', count($reviews_item));
			  
			if (!empty($rating_arr)) {
				$message = array('payload' => json_encode(array(
				'text' => 'New ratings on wordpress',
				"blocks" =>
						$rating_arr
					)));
				// Use curl to send your message
				$ch = curl_init($hook_url);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				curl_close($ch);
				//return $result;
			}
		}
        //}
    }

	function js_get_plugin_downloads($plugin_slug) {

		$url 		= 'https://api.wordpress.org/plugins/info/1.0/';
		$response 	= wp_remote_post( $url, array(
			'body'		=> array(
				'action'	=> 'plugin_information',
				'request'	=> serialize( (object) array(
					'slug' => $plugin_slug,
					'fields'	=> array(
						'downloaded'		=> true,
						'rating'		=> false,
						'description'		=> false,
						'short_description' 	=> false,
						'donate_link'		=> false,
						'tags'			=> false,
						'sections'		=> false,
						'homepage'		=> false,
						'added'			=> false,
						'last_updated'		=> false,
						'compatibility'		=> false,
						'tested'		=> false,
						'requires'		=> false,
						'downloadlink'		=> false,
					)
				) ),
			),
		) );

		$response = unserialize( $response['body'] );
		return isset( $response->downloaded ) ? $response->downloaded : array();
	}
	public function cron_save_org_downloads() {
		$plugin_list = array();
		foreach ($this->get_plugin_info() as $key => $value) {
			$plugin_list[$value->plugin_feed_url] = $this->js_get_plugin_downloads(($value->plugin_feed_url));
		}
		$downloaded =  get_option( 'total_downloaded' );
		//write_log($downloaded);
		$download_count = get_option('enable_download_count');
		$count_report_hook = get_option('download_count_hook');
		$new_seq = array();
		$i = 0;

		$subtracted = array_map(function ($x, $y) { return $y-$x; } , $plugin_list, $downloaded);
		$result     = array_combine(array_keys($plugin_list), $subtracted);
		write_log($result);
		foreach($result as $single_key => $single_d){
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $single_key ) );
			$plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
			$sec_array = array();
            $sec_array['type'] = 'section';
            $sec_array['text']['type'] = 'mrkdwn';
            $sec_array['text']['text'] = $plugin_name . ' todays download '.$single_d.'';
            $new_seq[] = $sec_array;
			$i++;
		}

		if(!empty($count_report_hook) && !empty($download_count) && $download_count >= 1){
			//write_log($downloaded);
			$message = array('payload' => json_encode(array(
			'text' => 'yesterday plugin\'s download report',
			"blocks" =>
					$new_seq
				)));
			// Use curl to send your message
			$ch = curl_init($count_report_hook);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		
		//write_log($plugin_list);
		update_option( 'total_downloaded', $plugin_list );
	}
}

$wp_to_slack = new WPSupportToSlack();