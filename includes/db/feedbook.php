<?php

/**
 * Undocumented class
 */
class FeedBook
{

    public function form_handler()
    {
        if (!isset($_POST['submit_plugin_cron'])) {
            return;
        }

        if (isset($_POST['_wpnonce']) && !wp_verify_nonce($_POST['_wpnonce'], 'new-cron')) {
            wp_die("Hey stop!");
        }

        if (!current_user_can('manage_options')) {
            wp_die("I said stop");
        }
        $id      = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $slack_webhook    = isset($_POST['slack_webhook']) ? sanitize_text_field($_POST['slack_webhook']) : '';
        $plugin_feed_url = isset($_POST['plugin_feed_url']) ? sanitize_textarea_field($_POST['plugin_feed_url']) : '';
        $theme_or_plugin = isset($_POST['theme_or_plugin']) ? sanitize_textarea_field($_POST['theme_or_plugin']) : '';
        $custom_message   = isset($_POST['custom_slack_message']) ? sanitize_text_field($_POST['custom_slack_message']) : '';
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        $plugin_info = plugins_api( 'plugin_information', array( 'slug' => $plugin_feed_url ) );
        $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';

        if (!empty($this->errors)) {
            return;
        }

        $insert_id = $this->wp_support_slack_insert_address([
            'slack_webhook'    => $slack_webhook,
            'plugin_feed_url' => $plugin_feed_url,
            'plugin_theme' => $theme_or_plugin,
            'plugin_name'   => $plugin_name,
            'custom_message'   => $custom_message
        ]);
        
        if (is_wp_error($insert_id)) {
            wp_die($insert_id->get_error_message());
        }
        if($id || $insert_id){

            $minutewise = get_option('minutewise');
            $hourly = get_option('hourly');
            $daywise = get_option('daywise');
            $weekly = get_option('weekly');
            $monthly = get_option('monthly');

            $cron_int_list = array(
                'minutewise' => $minutewise,
                'hourly' => $hourly,
                'daywise' => $daywise,
                'weekly' => $weekly,
                'monthly' => $monthly,
            );

            foreach ($cron_int_list as $cron_key => $cron_value) {

                if(!empty($cron_value) && $cron_value > 0 && !wp_next_scheduled( 'TestCron_cron_event_'.$plugin_feed_url .'' )){
                    wp_schedule_event(time(), $cron_key, 'TestCron_cron_event_'.$plugin_feed_url.'', array(
                        'plugin_feed_url' => $plugin_feed_url,
                        'slack_webhook' => $slack_webhook,
                        'plugin_name' => $plugin_name,
                        'custom_message' => $custom_message,
                    ));
                }
            }

        }
        if ($id) {
            $redirected_to = admin_url('admin.php?page=wp_support_to_slack_page&action=edit&feed-updated=true&id=' . $id);
        } else {
            $redirected_to = admin_url('admin.php?page=wp_support_to_slack_page&inserted=true');
        }

        wp_redirect($redirected_to);
        exit;
//////////////////
        

        if (!empty($this->errors)) {
            return;
        }

        if (is_wp_error($insert_id)) {
            wp_die($insert_id->get_error_message());
        }

        
    }

    public function delete_address()
    {
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_support_to_slack_page-delete-cron')) {
            wp_die('Hey stop!');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Hey i said stop!');
        }

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        if (wpslack_delete_feed($id)) {
            $redirected_to = admin_url('admin.php?page=wp_support_to_slack_page&cron-deleted=true');
        } else {
            $redirected_to = admin_url('admin.php?page=wp_support_to_slack_page&cron-deleted=false');
        }

        wp_redirect($redirected_to);
        exit;
    }

    /**
     * Undocumented function
     *
     * @param array $args
     * @return void
     */
    public function wp_support_slack_insert_address($args = []) {
        global $wpdb;
        $id      = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $defaults = [
            'slack_webhook'       => '',
            'plugin_feed_url'       => '',
            'custom_message'   => '',
            'plugin_theme'   => '',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($args, $defaults);
        if (!empty($id) && $id > 0) {
            //write_log($_POST);
            //die();
            $inserted = $wpdb->update(
                $wpdb->prefix . '_pluginfeeds',
                $data,
                array('id' => $id)
            );
        }else{
            $inserted = $wpdb->insert(
                $wpdb->prefix . '_pluginfeeds',
                $data
            );
        }

        if (!$inserted) {
            return new \WP_Error('failed-to-insert', __('Failed to insert data', 'support-to-slack'));
        }

        return $wpdb->insert_id;
    }

    public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
                <?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </p>
                <?php
            }

            public function display() {
                $singular = $this->_args['singular'];

                $this->display_tablenav( 'top' );

                $this->screen->render_screen_reader_content( 'heading_list' );
                ?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>
            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo " data-wp-lists='list:$singular'";
                }
                ?>
                >
                <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

            <tfoot>
            <tr>
                <?php $this->print_column_headers( false ); ?>
            </tr>
            </tfoot>

        </table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

}