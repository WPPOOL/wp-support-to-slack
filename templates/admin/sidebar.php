<?php
if (!defined('WPINC')) {
	die;
}
?>
<div id="postbox-container-1" class="postbox-container">
    <div class="meta-box-sortables" id="ourplugin_info_sidebar">
        <div class="postbox">
            <h3><?php _e( 'Help & Supports', 'support-to-slack' ); ?></h3>
            <div class="inside">
                <p><?php _e( 'Support: ', 'support-to-slack'); ?><a href="<?php echo esc_url('https://wppool.dev/contact/'); ?>" target="_blank"><span class="dashicons dashicons-external"></span> <?php _e( 'Contact Us', 'support-to-slack'); ?></a></p>
                <p><span class="dashicons dashicons-email"></span> <a href="mailto:support@wppool.dev
"><?php _e( 'support@wppool.dev', 'support-to-slack' ) ?></a></p>
                <p><span class="dashicons dashicons-editor-help"></span> <a href="<?php echo esc_url('https://wppool.dev'); ?>" target="_blank"><?php _e('Plugin Documentation', 'support-to-slack'); ?></a></p>
                <p><span class="dashicons dashicons-star-half"></span> <a href="<?php echo esc_url('https://www.wppool.dev'); ?>" target="_blank"><?php esc_html_e('Review This Plugin', 'support-to-slack'); ?></a></p>
            </div>
        </div>
        <div class="postbox">
            <h3><?php esc_html_e( 'Our Other Products', 'support-to-slack' ); ?></h3>
            <div class="inside">
                <div class="wppool_products_sec">
                    <div class="wppool_single_product">
                        <div class="product_images">
                            <div class="wppool_p_logo">
                                <span>
                                    <a href="<?php esc_url("https://wppool.dev/wp-dark-mode/"); ?>"><img class="product_logo" src="<?php echo SUPPORT_TO_SLACK_ASSETS; ?>/images/dark-mode-logo.gif" /></a>
                                </span>
                            </div>
                            <div class="product_desc">
                                <a href="<?php esc_url("https://wppool.dev/wp-dark-mode/"); ?>"><h3><?php _e('WP Dark Mode', '') ?></h3></a>
                                <p><?php _e('Use WP Dark Mode plugin to create a stunning dark version for your WordPress website. WP Dark Mode works automatically without going into any complicated settings.', ''); ?></p>
                                <a href="<?php esc_url("https://wppool.dev/wp-dark-mode/"); ?>" target="_blank" class="view_feat"><?php _e('View features', '') ?><span aria-hidden="true" class="wppool-icon-arrow-right"></span></a>
                            </div>
                        </div>
                    </div>
                    <div class="wppool_single_product">
                        <div class="product_images">
                            <div class="wppool_p_logo">
                                <span>
                                    <a href="<?php esc_url("https://wppool.dev/wp-markdown-editor/"); ?>"><img class="product_logo" src="<?php echo SUPPORT_TO_SLACK_ASSETS; ?>/images/markdown_logo.png" /></a>
                                </span>
                            </div>
                            <div class="product_desc">
                                <a href="<?php esc_url("https://wppool.dev/wp-markdown-editor/"); ?>"><h3><?php _e('WP Markdown Editor (Formerly Dark Mode)', '') ?></h3></a>
                                <p><?php _e('Use WP Dark Mode plugin to create a stunning dark version for your WordPress website. WP Dark Mode works automatically without going into any complicated settings.', ''); ?></p>
                                <a href="<?php esc_url("https://wppool.dev/wp-markdown-editor/"); ?>" target="_blank" class="view_feat"><?php _e('View features', '') ?><span aria-hidden="true" class="wppool-icon-arrow-right"></span></a>
                            </div>
                        </div>
                    </div>
                    <div class="wppool_single_product">
                        <div class="product_images">
                            <div class="wppool_p_logo">
                                <span>
                                    <a href="<?php esc_url("https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/"); ?>"><img class="product_logo" src="<?php echo SUPPORT_TO_SLACK_ASSETS; ?>/images/jitsi_meet.jpg" /></a>
                                </span>
                            </div>
                            <div class="product_desc">
                                <a href="<?php esc_url("https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/"); ?>"><h3><?php _e('Webinar and Video Conference with Jitsi Meet', '') ?></h3></a>
                                <p><?php _e('Use WP Dark Mode plugin to create a stunning dark version for your WordPress website. WP Dark Mode works automatically without going into any complicated settings.', ''); ?></p>
                                <a href="<?php esc_url("https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/"); ?>" target="_blank" class="view_feat"><?php _e('View features', '') ?><span aria-hidden="true" class="wppool-icon-arrow-right"></span></a>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>