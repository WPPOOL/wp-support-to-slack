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
            //write_log($dayexception);

            $feed_list = get_option( 'theme_plugin_list');
            if( isset($feed_list['plugin_theme_feed']['feed'])){
                foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $single_feed) {
                    wp_clear_scheduled_hook('support_to_slack_event_'.$key.'');
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
                        $plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
                        $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
                        wp_schedule_event(time(), $recurrence, 'support_to_slack_event_'.$key.'', array(
                            'plugin_feed_url' => $slug,
                            'slack_webhook' => $single_feed['webhook'],
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
        public static function support_slack_cron_activate ( $feed_list = null ) {
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
                        $plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
                        $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
                        wp_schedule_event(time(), $recurrence, 'support_to_slack_event_'.$key.'', array(
                            'plugin_feed_url' => $slug,
                            'slack_webhook' => $single_feed['webhook'],
                            'plugin_name' => $plugin_name,
                            'custom_message' => $single_feed['message'],
                        ));
                    }
                }
            }
        
            if (! wp_next_scheduled('cron_save_org_downloads')) :
                wp_schedule_event(time(), 'minute_count', 'cron_save_org_downloads'); // 1407110400 is 08 / 4 / 2014 @ 0:0:0 UTC
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

            /* if ($plugin_or_theme == 'theme') {
                $plugin_feed = 'https://wordpress.org/support/theme/'.$plugin_feed_url.'/feed';
            } elseif ($plugin_or_theme == 'plugin') {
                
            } */

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
                    $new_array = array();
                    $saved_list = get_option('saved_thread');
                    //write_log($plugin_name);
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
                    
                    $new_array = array_merge($new_array, $saved_list);
                    $new_array = array_unique($new_array);
                    update_option('saved_thread',  $new_array);
                
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
                    /* $reviews_item = array(
                        array(
                            'guid' => 'https://wordpress.org/support/topic/i-like-it-847/',
                            'title'    => 'I Like it! (4 stars)',
                            'link' => 'https://wordpress.org/support/topic/i-like-it-847/',
                            'pubDate' => 'Tue, 29 Apr 2026 23:59:19 +0000',
                            'description' => '<p>Replies: 1</p>
                                                <p>Rating: 4 stars</p>
                            <p>It makes my site look great for evening viewing.</p>'
                        ),
                        array(
                            'guid' => 'https://wordpress.org/support/topic/i-like-it-2/',
                            'title'    => 'I Like it 2! (4 stars)',
                            'link' => 'https://wordpress.org/support/topic/i-like-it-2/',
                            'pubDate' => 'Sat, 10 Apr 4056 17:11:00 +0000',
                            'description' => '<p>Replies: 1</p>
                                                <p>Rating: 4 stars</p>
                            <p>It makes my site look great for evening viewing.</p>'
                        ),
                        array(
                            'guid' => 'https://wordpress.org/support/topic/i-like-it-3/',
                            'title'    => 'I Like it 3! (4 stars)',
                            'link' => 'https://wordpress.org/support/topic/i-like-it-3/',
                            'pubDate' => 'Sun, 04 Apr 3055 13:20:01 +0000',
                            'description' => '<p>Replies: 1</p>
                                                <p>Rating: 4 stars</p>
                            <p>It makes my site look great for evening viewing.</p>'
                        )
                    ); */
                    $reviews_item = $arrOutputReview['channel']['item'];
                    //write_log(count($reviews_item));
                    $yesterday_rating = !empty(get_option('total_rating'))? get_option('total_rating') : count($reviews_item);
                    
                    $rating_arr = array();
                    
                    $rating_list = array();
                    $saved_rating = get_option('saved_rating');
                    //write_log($reviews_item);
                    
                    //write_log($reviews_item);
                    foreach ($reviews_item  as $key => $value) {
                        $str = $value['description'];
                        if (preg_match_all('/Rating:(.*?)star/', $str, $match)) {
                            //write_log($value);
                            if (floatval($match[1][0]) == true) {
                                if(in_array(strtotime($value['pubDate']), $saved_rating)){
                                    break;
                                }
                                $rating_list[] = strtotime($value['pubDate']);
                                $first_install = get_option('first_install');
                                /* if(!isset($first_install)){
                                    continue;
                                } */
                                echo '<p>hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. Details: '. $value['link'];
                                $sec_array = array();
                                $sec_array['type'] = 'section';
                                $sec_array['text']['type'] = 'mrkdwn';
                                $sec_array['text']['text'] =  ++$key .'. '. 'Hello you got another '.str_repeat(":star:", floatval($match[1][0])) .' star review. '.$plugin_name.': '. $value['link'];
                                $rating_arr[] = $sec_array;
                            }
                        }
                    }
                    //write_log($rating_arr);
                    update_option('first_install', 111);
                    $rating_list = array_merge($rating_list, $saved_rating);
                    $rating_list = array_unique($rating_list);
                    update_option('saved_rating', $rating_list);

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
                    }
                }
            }

            //}
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

            $plugin_info = plugins_api('plugin_information', array( 'slug' => 'wp-dark-mode' ));
            //write_log($plugin_info);


            $plugin_list = array();
            $feed_list = get_option( 'theme_plugin_list');
            write_log($feed_list);
            // if (empty($plugin_list)) {
                foreach ($feed_list['plugin_theme_feed']['feed'] as $key => $value) {
                    //write_log($value);
                    $slug = basename($value['org_link']);
                    $plugin_list[$slug] = self::get_plugin_downloads($slug);
                }
                $downloaded =  get_option('total_downloaded');

                $download_count = get_option('enable_download_count');
                $count_report_hook = get_option('slack_support_settings');
                $new_seq = array();
                $i = 0;
                /* $plugin_list = array(
                    'webinar-and-video-conference-with-jitsi-meet' => '242952',
                    'wp-dark-mode' => '6723136',
                    'appointment-hour-booking' => '69010443'
                ); */

                //write_log($count_report_hook);
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

                if (true) {
                   // write_log($new_seq);
                    $message = array('payload' => json_encode(array(
                    'text' => 'yesterday plugin\'s download report',
                    "blocks" =>
                        $new_seq
                    )));
                    // Use curl to send your message
                    $ch = curl_init($count_report_hook['download_webhook']);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    curl_close($ch);
                }
                update_option('total_downloaded', $plugin_list);
            // }
        }
    }//end class WP Support To Slack
