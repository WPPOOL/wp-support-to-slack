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
                        <label for="theme_or_plugin"><?php _e(' Plugin / Theme', 'support-to-slack'); ?></label>
                    </th>
                    <td>
                        <select class="regular-text" name="theme_or_plugin" id="theme_or_plugin" >
                            <option value="plugin" ><?php esc_html_e( 'Plugin' , 'support-to-slack' ) ?></option>
                            <option value="theme" ><?php esc_html_e( 'Theme' , 'support-to-slack' ) ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="plugin_feed_url" id="plugin_feed_label_id"><?php _e('Plugin Slug (wordpress.org)', 'support-to-slack') ?></label>
                    </th>
                    <td>
                        <input type="text" name="plugin_feed_url" id="plugin_feed_url" class="regular-text" value=""></textarea>
                    </td>
                </tr>

                
                <tr>
                    <th scope="row">
                        <label for="custom_slack_message"><?php _e('Custom Slack Message', 'support-to-slack'); ?></label>
                    </th>
                    <td>
                        <textarea class="regular-text" name="custom_slack_message" id="custom_slack_message" value=""></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        wp_nonce_field("new-cron");
        submit_button(__("Add Cron", "support-to-slack"), 'primary', 'submit_plugin_cron')
        ?>
    </form>
</div>