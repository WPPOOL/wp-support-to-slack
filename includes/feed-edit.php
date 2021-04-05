<div class="wrap">
    <h1><?php _e('Edit Plugin Feed', 'support-to-slack'); ?></h1>

    <?php //if (isset($_GET['feed-updated'])) { ?>
        <!-- <div id="message" class="updated notice is-dismissible">
            <p>Feed Updated.</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div> -->
    <?php //} ?>

    <form action="" method="post">
        <table class="form-table">
            <tbody>
                <tr class="row<?php echo $this->has_error('slack_webhook') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="slack_webhook"><?php _e('Slack Webhook', 'support-to-slack'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="slack_webhook" id="slack_webhook" class="regular-text" value="<?php echo esc_attr($feed_info->slack_webhook); ?>">

                        <?php if ($this->has_error('slack_webhook')) { ?>
                            <p class="description error"><?php echo $this->get_error('slack_webhook'); ?></p>
                        <?php } ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="theme_or_plugin"><?php _e(' Plugin / Theme', 'support-to-slack'); ?></label>
                    </th>
                    <td>
                        <select class="regular-text" name="theme_or_plugin" id="theme_or_plugin" >
                            <option value="plugin" <?php selected( $feed_info->theme_or_plugin , 'plugin', false) ?>><?php esc_html_e( 'Plugin' , 'support-to-slack' ) ?></option>
                            <option value="theme" <?php selected( $feed_info->theme_or_plugin , 'theme', false) ?> ><?php esc_html_e( 'Theme' , 'support-to-slack' ) ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="plugin_feed_url" id="plugin_feed_label_id"><?php _e(' Plugin Slug (wordpress.org)', 'support-to-slack'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="plugin_feed_url" id="plugin_feed_url" value="<?php echo $feed_info->plugin_feed_url; ?>" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="custom_slack_message"><?php _e('Custom Slack Message', 'support-to-slack'); ?></label>
                    </th>
                    <td>
                        <textarea class="regular-text" name="custom_slack_message" id="custom_slack_message" value="<?php echo $feed_info->custom_message; ?>"><?php echo $feed_info->custom_message; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <input type="hidden" name="id" value="<?php echo esc_attr($feed_info->id); ?>">
        <?php wp_nonce_field('new-cron'); ?>
        <?php submit_button(__('Update Plugin Feed', 'support-to-slack'), 'primary', 'submit_plugin_cron'); ?>
    </form>
</div>