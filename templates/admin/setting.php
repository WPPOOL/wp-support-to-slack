<?php
	/**
	 * Provide a settings view for the plugin
	 *
	 * This file is used to markup the public-facing aspects of the plugin.
	 *
	 * @link       http://codeboxr.com
	 * @since      1.0.0
	 *
	 * @package    Slack Support
	 * @subpackage Slack Support /admin/templates
	 */
	if ( ! defined( 'WPINC' ) ) {
		die;
	}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<div class="support_settings_head_sec">
		<?php echo '<img id="slack_settings_head" src="'.SUPPORT_TO_SLACK_ASSETS.'/images/settings_head.svg" />'; ?><h2><?php _e( 'WordPress Support To Slack Notification', 'support-to-slack' ); ?></h2>
	</div>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox" id="slack_settings_box">
						<div class="inside">
							<?php
								$setting->show_navigation();
								$setting->show_forms();
							?>
						</div>
					</div>
				</div>
			</div>
			<div class="sidebar_plugin" >
			</div>
			<?php
				echo support_to_slack_get_template_html('admin/sidebar.php', array('ref' => $ref, 'setting' => $setting));
			?>
		</div>
		<div class="clear"></div>
	</div>
</div>