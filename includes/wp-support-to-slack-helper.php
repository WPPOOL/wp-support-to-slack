<?php
    // If this file is called directly, abort.
    if (! defined('WPINC')) {
        die;
    }

    /**
     * WP Support To Slack class with lots of static method for quick use
     *
     * Class WP_To_Slack_Helper
     */
    class WP_To_Slack_Helper {

        /**
         * @param array $value
         *
         * @return mixed
         */
        public static function sanitize_callback_plugin_theme_feed($feeds) {
            if (is_array($feeds) && sizeof($feeds) > 0) {
                $exceptions = isset($feeds['plugin_theme_feed']) ? $feeds['plugin_theme_feed'] : array();
            }

            $feed_list = get_option( 'theme_plugin_list');
            if( isset($feed_list['plugin_theme_feed']['feed'])){
                foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $single_feed) {
                    wp_clear_scheduled_hook('support_to_slack_event_'.$key.'');
                    wp_clear_scheduled_hook('unresolved_support_interval_'.$key.'');
                }
            }
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
    
            $cron_settings = get_option( 'slack_support_settings');
        
            if(!empty($cron_settings['interval_recurrence']) && isset($feeds['feed'])){
                $recurrence = $cron_settings['interval_recurrence']['recurrence'];
                $interval = $cron_settings['interval_recurrence']['interval'];
                foreach ($feeds['feed'] as $key => $single_feed) {
                    if (!empty($interval) && !empty($recurrence) && !wp_next_scheduled('support_to_slack_event_'.$key.'')) {
                        $slug = basename($single_feed['org_link']);
                        //write_log($single_feed);
                        $global_hook = get_option('slack_support_settings');
                        $hook_type = $single_feed['global_hook'] == 'on' ? $single_feed['webhook'] : $global_hook['download_webhook'];
                        
                        $plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
                        $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
                        wp_schedule_event(time(), $recurrence, 'support_to_slack_event_'.$key.'', array(
                            'plugin_feed_url' => $slug,
                            'slack_webhook' => $hook_type,
                            'plugin_name' => $plugin_name,
                            'custom_message' => $single_feed['message'],
                        ));
                        wp_schedule_event(time(), 'every_12', 'unresolved_support_interval_'.$key.'', array(
                            'plugin_feed_url' => $slug,
                            'slack_webhook' => $hook_type,
                            'plugin_name' => $plugin_name,
                            'custom_message' => $single_feed['message'],
                        ));
                    }
                }
            }
            return $feeds;
        }//end sanitize_callback_plugin_theme_feed


        /**
         * Cron Handler Functions
         *
         * @return void
         */
        public static function support_slack_cron_activate () {
            // Dont't Run => Not in Schedule Cronjobs
            //$args = get_current_user_id();
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
    
            $feed_list = get_option( 'theme_plugin_list');
            $cron_settings = get_option( 'slack_support_settings');
            
            if(!empty($cron_settings['interval_recurrence']) && isset($feed_list['plugin_theme_feed']['feed'])){
                $recurrence = $cron_settings['interval_recurrence']['recurrence'];
                $interval = $cron_settings['interval_recurrence']['interval'];
                foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $single_feed) {
                    //write_log($recurrence);
                    if (!empty($interval) && !empty($recurrence) && !wp_next_scheduled('support_to_slack_event_'.$key.'')) {
                        $slug = basename($single_feed['org_link']);

                        $global_hook = get_option('slack_support_settings');
                        $hook_type = !empty( $single_feed['global_hook'] ) && $single_feed['global_hook'] == 'on' ? $global_hook['download_webhook'] : $single_feed['webhook'];
                        //write_log($single_feed['webhook']);

                        $plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
                        $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
                        wp_schedule_event(time(), $recurrence, 'support_to_slack_event_'.$key.'', array(
                            'plugin_feed_url' => $slug,
                            'slack_webhook' => $hook_type,
                            'plugin_name' => $plugin_name,
                            'custom_message' => $single_feed['message'],
                        ));

                        wp_schedule_event(time(), 'every_12', 'unresolved_support_interval_'.$key.'', array(
                            'plugin_feed_url' => $slug,
                            'slack_webhook' => $hook_type,
                            'plugin_name' => $plugin_name,
                            'custom_message' => $single_feed['message'],
                        ));
                    }
                }
            }
        
            if ( $cron_settings['enable_download_count'] == 'on' && !empty( $cron_settings['download_webhook'] ) && !wp_next_scheduled('cron_save_org_downloads')) :
                wp_schedule_event(time(), 'daily_count', 'cron_save_org_downloads'); // 1407110400 is 08 / 4 / 2014 @ 0:0:0 UTC
            endif;
        }

        /**
         * Handle cron requests
         *
         * @param [type] $plugin_feed_url
         * @param [type] $hook_url
         * @param [type] $plugin_name
         * @param [type] $custom_message
         * @return void
         */
        public static function support_to_slack_msg_request($plugin_feed_url, $hook_url, $plugin_name, $custom_message) {

            libxml_use_internal_errors(true);
            $plugin_feed = 'https://wordpress.org/support/plugin/'.$plugin_feed_url.'/feed';
        
            $custom_message = !empty($custom_message) ? $custom_message : "new support ticket from " . $plugin_name;
            /**
             * Checking plugin feed and hook url is not empty
             */
            if (!empty($plugin_feed_url) && !empty($hook_url)) {
                
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
                if (key_exists('item', $arrOutput['channel']) && $objXmlDocument == true) {
                    $support_item = $arrOutput['channel']['item'];
                    $total_support_thread = count($support_item);
                    $new_seq = array();
                    // Creating new array based on format of slack sending message data
                    $new_array = array();
                    $saved_list = !empty(get_option('saved_thread')) ? get_option('saved_thread') : array();
                    //write_log($support_item);
                    if(isset($support_item[0]) && is_array($support_item[0])){
                        foreach ($support_item as $key => $value) {
                        
                            $matches = preg_match_all('/<[^>]*class="[^"]*\bresolved\b[^"]*"[^>]*>/i', $value['title'], $matches);
                            if (!$matches) {
                                $thread_slug = basename($value['link']);
                                
                                $single_thread_feed = 'https://wordpress.org/support/topic/'.$thread_slug.'/feed';
                                $objXmlthread = simplexml_load_file($single_thread_feed, "SimpleXMLElement", LIBXML_NOCDATA);
                                if ($objXmlthread === false) {
                                    echo "There were errors parsing the XML file.\n";
                                    foreach (libxml_get_errors() as $error) {
                                        echo $error->message;
                                    }
                                    exit;
                                }
                                $objJsonthread = json_encode($objXmlthread);
                                $thread_feed = json_decode($objJsonthread, true);
                                
                                if(isset($thread_feed['channel']['item'][0]) && is_array($thread_feed['channel']['item'][0])){
                                    foreach ($thread_feed['channel']['item'] as $single_reply) {
                                        if(in_array(strtotime($single_reply['pubDate']), $saved_list)){
                                            break;
                                        }
                                        $new_array[] = strtotime($single_reply['pubDate']);
                                    }
                                }
    
                                if(in_array(strtotime($value['pubDate']), $saved_list)){
                                    break;
                                }
                                $new_array[] = strtotime($value['pubDate']);
    
                                $sec_array = array();
                                $sec_array['type'] = 'section';
                                $sec_array['text']['type'] = 'mrkdwn';
                                $sec_array['text']['text'] =  ++$key .'. '. strip_tags($value['title']) . '<' . $value['link'] . ''.' | Click here > ' .' ( From '. $plugin_name .' )'.'';
                                $new_seq[] = $sec_array;
                            }
                        }
                    }else{
                            $matches = preg_match_all('/<[^>]*class="[^"]*\bresolved\b[^"]*"[^>]*>/i', $support_item['title'], $matches);
                            if (!$matches) {
                                $thread_slug = basename($support_item['link']);
                                
                                $single_thread_feed = 'https://wordpress.org/support/topic/'.$thread_slug.'/feed';
                                $objXmlthread = simplexml_load_file($single_thread_feed, "SimpleXMLElement", LIBXML_NOCDATA);
                                if ($objXmlthread === false) {
                                    echo "There were errors parsing the XML file.\n";
                                    foreach (libxml_get_errors() as $error) {
                                        echo $error->message;
                                    }
                                    exit;
                                }
                                $objJsonthread = json_encode($objXmlthread);
                                $thread_feed = json_decode($objJsonthread, true);
                                
                                if(isset($thread_feed['channel']['item'][0]) && is_array($thread_feed['channel']['item'][0])){
                                    foreach ($thread_feed['channel']['item'] as $single_reply) {
                                        if(in_array(strtotime($single_reply['pubDate']), $saved_list)){
                                            break;
                                        }
                                        $new_array[] = strtotime($single_reply['pubDate']);
                                    }
                                }
    
                                if(in_array(strtotime($support_item['pubDate']), $saved_list)){
                                    return false;
                                }
                                $new_array[] = strtotime($support_item['pubDate']);
    
                                $sec_array = array();
                                $sec_array['type'] = 'section';
                                $sec_array['text']['type'] = 'mrkdwn';
                                $sec_array['text']['text'] =  ++$key .'. '. strip_tags($support_item['title']) . '<' . $support_item['link'] . ''.' | Click here > ' .' ( From '. $plugin_name .' )'.'';
                                $new_seq[] = $sec_array;
                            }
                    }

                    $new_array = array_merge($new_array, $saved_list);
                    $new_array = array_unique($new_array);
                    update_option('saved_thread',  $new_array);
                
                    if (!empty($new_seq)) {
                        $message = array('payload' => json_encode(array(
                            'text' => $custom_message,
                            "blocks" => $new_seq
                        )));

                        $args = array(
                            'body'        => $message,
                            'timeout'     => '5',
                            'redirection' => '5',
                            'httpversion' => '1.0',
                            'blocking'    => true,
                            'headers'     => array(),
                            'cookies'     => array(),
                        );
                        $response = wp_remote_post( $hook_url, $args );
                    }
                }
            }
            
            $rating_notification = get_option('slack_support_settings');
            if (!empty($rating_notification['enable_rating'] == 'on') && !empty($plugin_feed_url)) {
                //write_log('kedu');
                libxml_use_internal_errors(true);
                /* if ($plugin_or_theme == 'theme') {
                    $wp_review_feed = 'https://wordpress.org/support/theme/'.$plugin_feed_url.'/reviews/feed';
                } elseif ($plugin_or_theme == 'plugin') {
                    
                } */
                $wp_review_feed = 'https://wordpress.org/support/plugin/'.$plugin_feed_url.'/reviews/feed';
                //$wp_review_feed = 'https://wordpress.org/support/plugin/'.$plugin_feed_url.'/reviews/feed';
                $review_document = simplexml_load_file($wp_review_feed, "SimpleXMLElement", LIBXML_NOCDATA);
                if ($review_document === false) {
                    echo "There were errors parsing the XML file.\n";
                    foreach (libxml_get_errors() as $error) {
                        echo $error->message;
                    }
                    exit;
                }
                $objJsonreview = json_encode($review_document);
                $arrOutputReview = json_decode($objJsonreview, true);
                if(array_key_exists('item', $arrOutputReview['channel']) && is_array($arrOutputReview['channel']['item']) && !empty($arrOutputReview['channel']['item'])){

                    $reviews_item = $arrOutputReview['channel']['item'];
                    //write_log(count($reviews_item));
                    $yesterday_rating = !empty(get_option('total_rating'))? get_option('total_rating') : count($reviews_item);
                    
                    $rating_arr = array();
                    
                    $rating_list = array();
                    $saved_rating = !empty(get_option('saved_rating')) ? get_option('saved_rating') : array();
                    
                    if(is_array($reviews_item[0])){
                        foreach ($reviews_item  as $key => $value) {
                            //write_log($value);
                            $str = $value['description'];
                            if (preg_match_all('/Rating:(.*?)star/', $str, $match)) {
                                //write_log($value);
                                if (floatval($match[1][0]) == true) {
                                    if(in_array(strtotime($value['pubDate']), $saved_rating)){
                                        break;
                                    }
                                    $rating_list[] = strtotime($value['pubDate']);
                                    $first_install = get_option($plugin_feed_url);

                                    echo '<p>Hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. Details: '. $value['link'];
                                    $sec_array = array();
                                    $sec_array['type'] = 'section';
                                    $sec_array['text']['type'] = 'mrkdwn';
                                    $sec_array['text']['text'] =  ++$key .'. '. 'Hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. '.$plugin_name.': '. $value['link'];
                                    
                                    if(!empty($first_install)){
                                        $rating_arr[] = $sec_array;
                                    }
                                }
                            }
                        }
                    }else{
                        $str = $reviews_item['description'];
                        if (preg_match_all('/Rating:(.*?)star/', $str, $match)) {
                            if (floatval($match[1][0]) == true) {
                                if(in_array(strtotime($reviews_item['pubDate']), $saved_rating)){
                                    return false;
                                }
                                $rating_list[] = strtotime($reviews_item['pubDate']);
                                $first_install = get_option($plugin_feed_url);
                                /* echo '<p>hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. Details: '. $reviews_item['link']; */
                                $sec_array = array();
                                $sec_array['type'] = 'section';
                                $sec_array['text']['type'] = 'mrkdwn';
                                $sec_array['text']['text'] =  ++$key .'. '. 'Hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. '.$plugin_name.': '. $reviews_item['link'];
                                
                                if(!empty($first_install)){
                                    $rating_arr[] = $sec_array;
                                }
                            }
                        }
                    }
                    

                    update_option( $plugin_feed_url, 111);
                    $rating_list = array_merge($rating_list, $saved_rating);
                    $rating_list = array_unique($rating_list);
                    update_option('saved_rating', $rating_list);

                    if (!empty($rating_arr)) {
                        $message = array('payload' => json_encode(array(
                            'text' => 'New ratings on wordpress',
                            "blocks" => $rating_arr
                        )));
                        // Use curl to send your message
                        $args = array(
                            'body'        => $message,
                            'timeout'     => '5',
                            'redirection' => '5',
                            'httpversion' => '1.0',
                            'blocking'    => true,
                            'headers'     => array(),
                            'cookies'     => array(),
                        );
                        $response = wp_remote_post( $hook_url, $args );
                    }
                }
            }
        }

        public static function get_plugin_downloads($plugin_slug) {
            $url 		= 'https://api.wordpress.org/plugins/info/1.0/';
            $response 	= wp_remote_post($url, array(
                'body'		=> array(
                    'action'	=> 'plugin_information',
                    'request'	=> serialize((object) array(
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
                    )),
                ),
            ));

            $response = unserialize($response['body']);
            return isset($response->downloaded) ? $response->downloaded : array();
        }

        public static function org_daily_download_count($plugin_list = array()) {
            require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

            //$plugin_info = plugins_api('plugin_information', array( 'slug' => 'wp-dark-mode' ));
            $downloaded =  get_option('total_downloaded');
            $download_count = get_option('enable_download_count');
            $count_report_hook = get_option('slack_support_settings');


            $plugin_list = array();
            $feed_list = get_option( 'theme_plugin_list');
            if ($count_report_hook['enable_download_count'] == 'on') {
                foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $value) {
                    $slug = basename($value['org_link']);
                    $plugin_list[$slug] = self::get_plugin_downloads($slug);
                }

                $new_seq = array();
                $i = 0;

                $subtracted = array_map(function ($x, $y) {
                    return $x - $y;
                }, $plugin_list, $downloaded);

                $result     = array_combine(array_keys($plugin_list), $subtracted);
                

                foreach ($result as $single_key => $single_d) {
                    
                    $plugin_info = plugins_api('plugin_information', array( 'slug' => $single_key ));
                    $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
                    $sec_array = array();
                    $sec_array['type'] = 'section';
                    $sec_array['text']['type'] = 'mrkdwn';
                    $sec_array['text']['text'] = $plugin_name . ' todays download '.$single_d.'';
                    $new_seq[] = $sec_array;
                    $i++;
                }

                if (!empty($new_seq)) {
                    $message = array('payload' => json_encode(array(
                    'text' => 'yesterday plugin\'s download report',
                    "blocks" =>
                        $new_seq
                    )));
                    $args = array(
                        'body'        => $message,
                        'timeout'     => '5',
                        'redirection' => '5',
                        'httpversion' => '1.0',
                        'blocking'    => true,
                        'headers'     => array(),
                        'cookies'     => array(),
                    );
                    $response = wp_remote_post( $hook_url, $args );
                    // Use curl to send your message
                }
                update_option('total_downloaded', $plugin_list);
            }
        }

        public static function unresolved_support_fixed_int_request($plugin_feed_url, $hook_url, $plugin_name, $custom_message){
            libxml_use_internal_errors(true);
            $plugin_feed = 'https://wordpress.org/support/plugin/'.$plugin_feed_url.'/feed';
        
            //$hook_url = $plugin_info->slack_webhook;
            $custom_message = !empty($custom_message) ? $custom_message : "new support ticket from " . $plugin_name;
            /**
             * Checking plugin feed and hook url is not empty
             */
            
            
            if (!empty($plugin_feed_url) && !empty($hook_url)) {
                
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
                if (key_exists('item', $arrOutput['channel']) && $objXmlDocument == true) {
                    $support_item = $arrOutput['channel']['item'];
                    $total_support_thread = count($support_item);
                    $new_seq = array();
                    // Creating new array based on format of slack sending message data
                    foreach ($support_item as $key => $value) {
                        
                        $matches = preg_match_all('/<[^>]*class="[^"]*\bresolved\b[^"]*"[^>]*>/i', $value['title'], $matches);
                        if (!$matches) {
                            //write_log($plugin_name);
                            $sec_array = array();
                            $sec_array['type'] = 'section';
                            $sec_array['text']['type'] = 'mrkdwn';
                            $sec_array['text']['text'] =  ++$key .'. '. strip_tags($value['title']) . '<' . $value['link'] . ''.' | Click here > ' .' ( From '. $plugin_name .' )'.'';
                            $new_seq[] = $sec_array;
                        }
                    }
                    //write_log($new_seq);
                
                    if (!empty($new_seq)) {
                        $message = array('payload' => json_encode(array(
                            'text' => $custom_message,
                            "blocks" =>
                                $new_seq
                        )));
                        $args = array(
                            'body'        => $message,
                            'timeout'     => '5',
                            'redirection' => '5',
                            'httpversion' => '1.0',
                            'blocking'    => true,
                            'headers'     => array(),
                            'cookies'     => array(),
                        );
                        $response = wp_remote_post( $hook_url, $args );
                    }
                }
            }
        }

        /**
         * Undocumented function
         *
         * @param [type] $atts
         * @return void
         */
        public static function active_installs($atts){
            require_once( ABSPATH . 'wp-admin/includes/template.php' );
            require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
            $sc_value = shortcode_atts( array(
                'slug' => ''
            ), $atts );
            $args = [
                'slug' => $sc_value['slug'],
                'fields' => [
                    'short_description' => false,
                    'icons' => true,
                    'reviews' => false, // excludes all reviews
                ],
            ];
            $data = plugins_api('plugin_information', $args);
            return $data->active_installs;
        }
        public static function rating_numbers($atts){
            require_once( ABSPATH . 'wp-admin/includes/template.php' );
            require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
            $sc_value = shortcode_atts( array(
                'slug' => ''
            ), $atts );
            $args = [
                'slug' => $sc_value['slug'],
                'fields' => [
                    'short_description' => false,
                    'icons' => true,
                    'reviews' => false, // excludes all reviews
                ],
            ];
            $data = plugins_api('plugin_information', $args);
            $defaults    = array(
                'rating' => 0,
                'type'   => 'percent',
                'number' => 0,
                'echo'   => true,
            );
            $rating_args = array(
                'rating' => $data->rating,
                'type' => 'percent',
                'number' => $data->num_ratings,
            );
            $parsed_args = wp_parse_args( $rating_args, $defaults );
            
            // Non-English decimal places when the $rating is coming from a string.
            $rating = (float) str_replace( ',', '.', $parsed_args['rating'] );
            
            // Convert percentage to star rating, 0..5 in .5 increments.
            if ( 'percent' === $parsed_args['type'] ) {
                $rating = round( $rating / 10, 1 ) / 2;
            }
            return $rating; 
        }
    }//end class WP Support To Slack
