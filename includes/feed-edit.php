<div class="wrap">
    <h1><?php _e('Edit Plugin Feed', 'wp-standard'); ?></h1>

    <?php if (isset($_GET['feed-updated'])) { ?>
        <div class="notice notice-success">
            <p><?php _e('Feed has been updated successfully!', 'wp-standard'); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    <?php } ?>

    <form action="" method="post">
        <table class="form-table">
            <tbody>
                <tr class="row<?php echo $this->has_error('slack_webhook') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="slack_webhook"><?php _e('Slack Webhook', 'wp-standard'); ?></label>
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
                        <label for="plugin_feed_url"><?php _e(' Slack Webhook Plugin Slug (wordpress.org)', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="plugin_feed_url" id="plugin_feed_url" value="<?php echo $feed_info->plugin_feed_url; ?>" />
                    </td>
                </tr>
                <!-- <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php //_e('Cron Minutewise:', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="minutewise" id="minutewise" class="regular-text" value="<?php //echo esc_attr($feed_info->minutewise); ?>">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php //echo $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr>
                <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php //_e('Cron Hourly: ', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="hourly" id="hourly" class="regular-text" value="<?php //echo esc_attr($feed_info->hourly); ?>">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php //echo $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr>
                <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php //_e('Cron Daywise: ', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="daywise" id="cron_time_interval" class="regular-text" value="<?php //echo esc_attr($feed_info->daywise); ?>">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php //echo $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr>
                <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php //_e('Cron Weekly', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="weekly" id="cron_time_interval" class="regular-text" value="<?php //echo esc_attr($feed_info->weekly); ?>">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php // $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr> -->
                
                <tr>
                    <th scope="row">
                        <label for="custom_slack_message"><?php _e('Custom Slack Message', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <textarea class="regular-text" name="custom_slack_message" id="custom_slack_message" value="<?php echo $feed_info->custom_message; ?>"><?php echo $feed_info->custom_message; ?></textarea>
                    </td>
                </tr>
                <!-- <tr>
                    <th scope="row">
                        <label for="enable_rating"><?php //_e('Enable Plugin Rating Notification', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" class="regular-text" name="enable_rating" id="enable_rating" value="1" <?php //checked( $feed_info->enable_rating , 1 ); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="download_count"><?php //_e('Enable Daily Download Counter Notification', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" class="regular-text" name="download_count" id="download_count" value="1" <?php //checked( $feed_info->download_count , 1 ); ?> />
                    </td>
                </tr> -->
            </tbody>
        </table>

        <input type="hidden" name="id" value="<?php echo esc_attr($feed_info->id); ?>">
        <?php wp_nonce_field('new-cron'); ?>
        <?php submit_button(__('Update Plugin Feed', 'wp-standard'), 'primary', 'submit_plugin_cron'); ?>
    </form>
</div>