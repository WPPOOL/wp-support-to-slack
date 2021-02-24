<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e("New Plugin Feed", "support-to-slack") ?></h1>

    <form action="" method="post">
        <table class="form-table">
            <tbody>
                <tr class="row<?php //echo $this->has_error('name') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="name"><?php _e('Slack Webhook', 'support-to-slack') ?></label>
                    </th>
                    <td>
                        <input type="text" name="slack_webhook" id="slack_webhook" class="regular-text" value="">

                        <?php //if ($this->has_error('name')) { ?>
                            <p class="description error"><?php //echo $this->get_error('name'); ?></p>
                        <?php //} ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="plugin_feed_url"><?php _e('Plugin Slug (wordpress.org)', 'support-to-slack') ?></label>
                    </th>
                    <td>
                        <input type="text" name="plugin_feed_url" id="plugin_feed_url" class="regular-text" value=""></textarea>
                    </td>
                </tr>
                <!-- <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php //_e('Cron Minutewise:', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="minutewise" id="minutewise" class="regular-text" value="">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php //echo $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr>
                <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php ///_e('Cron Hourly: ', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="hourly" id="hourly" class="regular-text" value="">

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
                        <input type="text" name="daywise" id="cron_time_interval" class="regular-text" value="">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php //echo $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr>
                <tr class="row<?php //echo $this->has_error('cron_interval') ? ' form-invalid' : ''; ?>">
                    <th scope="row">
                        <label for="cron_time_interval"><?php// _e('Cron Weekly', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="weekly" id="cron_time_interval" class="regular-text" value="">

                        <?php //if ($this->has_error('cron_time_interval')) { ?>
                            <p class="description error"><?php //echo $this->get_error('cron_time_interval'); ?></p>
                        <?php //} ?>
                    </td>
                </tr> -->
                
                <tr>
                    <th scope="row">
                        <label for="custom_slack_message"><?php _e('Custom Slack Message', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <textarea class="regular-text" name="custom_slack_message" id="custom_slack_message" value=""></textarea>
                    </td>
                </tr>
                <!-- <tr>
                    <th scope="row">
                        <label for="enable_rating"><?php //_e('Enable Plugin Rating Notification', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" class="regular-text" name="enable_rating" id="enable_rating" value= 1 />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="download_count"><?php //_e('Enable Daily Download Counter Notification', 'wp-standard'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" class="regular-text" name="download_count" id="download_count" value = 1 />
                    </td>
                </tr> -->
            </tbody>
        </table>
        <?php
        wp_nonce_field("new-cron");
        submit_button(__("Add Cron", "support-to-slack"), 'primary', 'submit_plugin_cron')
        ?>
    </form>
</div>