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
        $minutewise   = isset($_POST['minutewise']) ? sanitize_text_field($_POST['minutewise']) : '';
        $hourly   = isset($_POST['hourly']) ? sanitize_text_field($_POST['hourly']) : '';
        $daywise   = isset($_POST['daywise']) ? sanitize_text_field($_POST['daywise']) : '';
        $weekly   = isset($_POST['weekly']) ? sanitize_text_field($_POST['weekly']) : '';
        $custom_message   = isset($_POST['custom_slack_message']) ? sanitize_text_field($_POST['custom_slack_message']) : '';
        $enable_rating   = isset($_POST['enable_rating']) ? sanitize_text_field($_POST['enable_rating']) : 0;
        $download_count   = isset($_POST['download_count']) ? sanitize_text_field($_POST['download_count']) : 0;
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        $plugin_info = plugins_api( 'plugin_information', array( 'slug' => $plugin_feed_url ) );
        $plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
        if (!empty($this->errors)) {
            return;
        }
        $insert_id = $this->wp_support_slack_insert_address([
            'slack_webhook'    => $slack_webhook,
            'plugin_feed_url' => $plugin_feed_url,
            'plugin_name'   => $plugin_name,
            'minutewise'   => $minutewise,
            'hourly'   => $hourly,
            'daywise'   => $daywise,
            'weekly'   => $weekly,
            'custom_message'   => $custom_message,
            'enable_rating'   => $enable_rating,
            'download_count'   => $download_count
        ]);
        
        if (is_wp_error($insert_id)) {
            wp_die($insert_id->get_error_message());
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

    public function wp_support_slack_insert_address($args = []) {
        global $wpdb;
        $id      = isset($_POST['id']) ? intval($_POST['id']) : 0;

        /* if (empty($args['slack_webhook'])) {
            return new \WP_Error('no-name', __('You must provide a name.', 'support-to-slack'));
        } */

        $defaults = [
            'slack_webhook'       => '',
            'plugin_feed_url'       => '',
            'minutewise'   => '',
            'hourly'   => '',
            'daywise'   => '',
            'weekly'   => '',
            'custom_message'   => '',
            'enable_rating'   => '',
            'download_count'   => '',
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
            return new \WP_Error('failed-to-insert', __('Failed to insert data', 'wp-standard'));
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