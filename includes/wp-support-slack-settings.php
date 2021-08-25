<?php
	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	/**
	 * weDevs Settings API wrapper class
	 *
	 * @version 1.1
	 *
	 * @author Tareq Hasan <tareq@weDevs.com>
	 * @link http://tareq.weDevs.com Tareq's Planet
	 * @example src/settings-api.php How to use the class
	 */
	if (!class_exists('WP_To_Slack_Settings')):

		class WP_To_Slack_Settings
		{

			/**
			 * settings sections array
			 *
			 * @var array
			 */
			private $settings_sections = array();

			/**
			 * Settings fields array
			 *
			 * @var array
			 */
			private $settings_fields = array();

			/**
			 * Singleton instance
			 *
			 * @var object
			 */
			private static $_instance;

			public function __construct(){

			}


			/**
			 * Set settings sections
			 *
			 * @param array $sections setting sections array
			 */
			function set_sections($sections)
			{
				$this->settings_sections = $sections;

				return $this;
			}

			/**
			 * Add a single section
			 *
			 * @param array $section
			 */
			function add_section($section)
			{
				$this->settings_sections[] = $section;

				return $this;
			}

			/**
			 * Set settings fields
			 *
			 * @param array $fields settings fields array
			 */
			function set_fields($fields)
			{
				$this->settings_fields = $fields;

				return $this;
			}

			function add_field($section, $field)
			{
				$defaults = array(
					'name'  => '',
					'label' => '',
					'desc'  => '',
					'type'  => 'text'
				);

				$arg                               = wp_parse_args($field, $defaults);
				$this->settings_fields[$section][] = $arg;

				return $this;
			}


			function admin_init() {
				//register settings sections
				foreach ($this->settings_sections as $section) {
					if (false == get_option($section['id'])) {
						$section_default_value = $this->getDefaultValueBySection($section['id']);
						add_option($section['id'], $section_default_value);
					}
					else{
						$section_default_value = $this->getMissingDefaultValueBySection($section['id']);
						update_option($section['id'], $section_default_value);
					}

					if (isset($section['desc']) && !empty($section['desc'])) {
						$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
						//$callback        = create_function('', 'echo "' . str_replace('"', '\"', $section['desc']) . '";');
						$callback = function () use ( $section ) {
							echo str_replace( '"', '\"', $section['desc'] );
						};
					}
					else if (isset($section['callback'])) {
						$callback = $section['callback'];
					}
					else {
						$callback = null;
					}

					add_settings_section($section['id'], $section['title'], $callback, $section['id']);
				}

				//register settings fields
				foreach ($this->settings_fields as $section => $field) {
					foreach ($field as $option) {

						$name = $option['name'];
						$type = isset( $option['type'] ) ? $option['type'] : 'text';
						$label = isset( $option['label'] ) ? $option['label'] : '';
						$callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

						$args = array(
							'id'                => $option['name'],
							'class'             => isset( $option['class'] ) ? $option['class'] : $name,
							'label_for'         => $args['label_for']  = "{$section}[{$option['name']}]",
							'desc'              => isset($option['desc']) ? $option['desc'] : '',
							'name'              => $label,
							'section'           => $section,
							'size'              => isset($option['size']) ? $option['size'] : null,
							'min'               => isset( $option['min'] ) ? $option['min'] : '',
							'max'               => isset( $option['max'] ) ? $option['max'] : '',
							'step'              => isset($option['step']) ? $option['step'] : '',
							'options'           => isset($option['options']) ? $option['options'] : '',
							'default'               => isset($option['default']) ? $option['default'] : '',
							'sanitize_callback' => isset($option['sanitize_callback']) ? $option['sanitize_callback'] : '',
							'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
							'type'              => $type,
							'optgroup'          => isset($option['optgroup']) ? intval($option['optgroup']) : 0
						);
						//add_settings_field($section . '[' . $option['name'] . ']', $option['label'], array($this, 'callback_' . $type), $section, $section, $args);
						add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
					}
				}
				// creates our settings in the options table
				foreach ($this->settings_sections as $section) {
					register_setting($section['id'], $section['id'], array($this, 'sanitize_options'));
				}
			}

			/**
			 * Prepares default values by section
			 *
			 * @param $section_id
			 *
			 * @return array
			 */
			function getDefaultValueBySection($section_id){
				$default_values = array();

				$fields = $this->settings_fields[$section_id];
				foreach ($fields as $field){
					$default_values[$field['name']] =  isset($field['default'])? $field['default']: '';
				}
				return $default_values;
			}

			/**
			 * Prepares default values by section
			 *
			 * @param $section_id
			 *
			 * @return array
			 */
			function getMissingDefaultValueBySection($section_id){

				$section_value = get_option($section_id);
				$fields = $this->settings_fields[$section_id];
				foreach ($fields as $field){
					if(!isset($section_value[$field['name']])){
						$section_value[$field['name']] =  isset($field['default'])? $field['default']: '';
					}

				}

				return $section_value;
			}

			/**
			 * Get field description for display
			 *
			 * @param array $args settings field args
			 */
			public function get_field_description($args)
			{
				if (!empty($args['desc'])) {
					$desc = sprintf('<p class="description">%s</p>', $args['desc']);
				} else {
					$desc = '';
				}

				return $desc;
			}

			/**
			 * Displays a text field for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_text($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
				$type  = isset($args['type']) ? $args['type'] : 'text';

				$html = sprintf('<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"/>', $type, $size, $args['section'], $args['id'], $value);
				$html .= $this->get_field_description($args);

				echo $html;
			}

			/**
			 * Displays a documentation section
			 *
			 * @param array $args settings field args
			 */
			public function callback_doc($args){
				$html = '<iframe width="600" height="400" src="https://youtube.com/embed/Zs-kUvp5XpY"></iframe>';
				echo $html;
			}

			/**
			 * Displays a date picker as text field for a settings field
			 *
			 * @since 1.0.1
			 *
			 * @param array $args settings field args
			 */
			function callback_date($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
				$type  = 'text';

				$html = sprintf('<input autocomplete="off" type="%1$s" class="%2$s-text %2$s-date datepicker" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"/>', $type, $size, $args['section'], $args['id'], $value);
				$html .= $this->get_field_description($args);

				echo $html;
			}


			/**
			 * Displays a info field
			 *
			 * @param array $args settings field args
			 */
			function callback_title( $args ) {
				$html = sprintf( '<h3 class="setting_heading_title"><span>%s</span></h3>', $args['desc'] );
				echo $html;
			}

			/**
			 * Displays a url field for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_url($args)
			{
				$this->callback_text($args);
			}

			/**
			 * Displays a number field for a settings field
			 *
			 * @param array   $args settings field args
			 */
			function callback_number( $args ) {
				$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['default'] ) );
				$size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
				$type        = isset( $args['type'] ) ? $args['type'] : 'number';
				$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
				$min         = empty( $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
				$max         = empty( $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
				$step        = empty( $args['max'] ) ? '' : ' step="' . $args['step'] . '"';
				$html        = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step );
				$html       .= $this->get_field_description( $args );
				echo $html;
			}





			/**
			 * Displays a multicheckbox a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_radio($args)
			{

				$value = $this->get_option($args['id'], $args['section'], $args['default']);

				$html = '<fieldset>';
				foreach ($args['options'] as $key => $label) {
					$html .= sprintf('<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key);
					$html .= sprintf('<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked($value, $key, false));
					$html .= sprintf('%1$s</label><br>', $label);
				}
				$html .= $this->get_field_description($args);
				$html .= '</fieldset>';

				echo $html;
			}


			/**
			 * Displays a checkbox for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_checkbox($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));

				$html = '<fieldset>';
				$html .= sprintf('<label for="wpuf-%1$s[%2$s]" class="settings_switch">', $args['section'], $args['id']);
				$html .= sprintf('<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id']);
				$html .= sprintf('<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked($value, 'on', false));
				$html .= sprintf('<span class="settings_switch_after"></span>');
				$html .= sprintf('%1$s</label>', $args['desc']);
				$html .= '</fieldset>';

				echo $html;
			}

			/**
			 * Displays a selectbox for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_select($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular selecttwo-select';

				$html = sprintf('<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id']);
				foreach ($args['options'] as $key => $label) {
					$html .= sprintf('<option value="%s"%s>%s</option>', $key, selected($value, $key, false), $label);
				}
				$html .= sprintf('</select>');
				$html .= $this->get_field_description($args);

				echo $html;
			}

			
			function callback_select_number($args)
			{

				$value = $this->get_option($args['id'], $args['section'], $args['default']);
				if(!is_array($value)) $value = array();
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular selecttwo-select';
				//write_log($value);
				$interval = isset($value['interval'])? $value['interval'] : '';
				$html = sprintf('<select class="%1$s" name="%2$s[%3$s][recurrence]" id="%2$s[%3$s]">', $size, $args['section'], $args['id']);
				foreach ($args['options'] as $key => $label) {
					$checked = in_array($key, $value)? ' selected="selected" ' : '';
					$html .= sprintf('<option value="%s"%s>%s</option>', $key, $checked, $label);
				}
				$html .= sprintf('</select>');
				$html .= sprintf('<input style="margin-left:20px" type="number" class="" name="%1$s[%2$s][interval]" value="%3$s">', $args['section'], $args['id'], $interval);
				$html .= $this->get_field_description($args);

				echo $html;
			}

			/**
			 * Displays a multicheckbox settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_multicheck($args)
			{

				$value = $this->get_option($args['id'], $args['section'], $args['default']);
				if(!is_array($value)) $value = array();

				$html = '<fieldset class="multicheck_fields">';
				foreach ($args['options'] as $key => $label) {

					//$checked = isset($value[$key]) ? $value[$key] : '0';
					$checked = in_array($key, $value)? ' checked="checked" ' : '';

					$html .= sprintf('<p class="multicheck_field"><!--<span class="multicheck_field_handle"><i class="dashicons dashicons-move"></i></span>--><label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key);
					$html .= sprintf('<input type="hidden" name="%1$s[%2$s][]" value="" />', $args['section'], $args['id']);
					$html .= sprintf('<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, $checked);
					$html .= sprintf('%1$s</label></p>', $label);
				}
				$html .= $this->get_field_description($args);
				$html .= '</fieldset>';

				echo $html;
			}

			/**
			 * Displays a multi-selectbox for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_multiselect($args){


				$value = $this->get_option($args['id'], $args['section'], $args['default']);

				if (!is_array($value)) $value = array();

				$size = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular selecttwo-select';

				if($args['placeholder'] == '') $args['placeholder'] = esc_html__( 'Please Select', 'support-to-slack' );

				$html = sprintf('<input type="hidden" name="%1$s[%2$s][]" value="" />', $args['section'], $args['id']);
				$html .= sprintf('<select multiple class="%1$s" name="%2$s[%3$s][]" id="%2$s[%3$s]" style="min-width: 150px !important;"  placeholder="%4$s" data-placeholder="%4$s">', $size, $args['section'], $args['id'], $args['placeholder']);


				if (isset($args['optgroup']) && $args['optgroup']) {
					foreach ($args['options'] as $opt_grouplabel => $option_vals) {
						$html .= '<optgroup label="' . $opt_grouplabel . '">';

						if (!is_array($option_vals)) $option_vals = array();
						else{
							//$option_vals = $this->convert_associate($option_vals);
							$option_vals = $option_vals;
						}


						foreach ($option_vals as $key => $val) {
							$selected = in_array($key, $value) ? ' selected="selected" ' : '';
							$html     .= sprintf('<option value="%s" ' . $selected . '>%s</option>', $key, $val);
						}
						$html .= '<optgroup>';
					}
				} else {
					//$option_vals = $this->convert_associate($args['options']);
					$option_vals = $args['options'];

					foreach ($option_vals as $key => $val) {
						$selected = in_array($key, $value) ? ' selected="selected" ' : '';
						$html     .= sprintf('<option value="%s" ' . $selected . '>%s</option>', $key, $val);
					}
				}

				$html .= sprintf('</select>');
				$html .= $this->get_field_description($args);

				echo $html;
			}

			/**
			 * Displays a textarea for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_textarea($args)
			{

				$value = esc_textarea($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

				$html = sprintf('<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value);
				$html .= $this->get_field_description($args);

				echo $html;
			}

			/**
			 * Displays a textarea for a settings field
			 *
			 * @param array $args settings field args
			 * @return string
			 */
			function callback_html($args)
			{
				echo $this->get_field_description($args);
			}

			/**
			 * Displays a rich text textarea for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_wysiwyg($args)
			{

				$value = $this->get_option($args['id'], $args['section'], $args['default']);
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : '500px';

				echo '<div style="max-width: ' . $size . ';">';

				$editor_settings = array(
					'teeny'         => true,
					'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
					'textarea_rows' => 10
				);
				if (isset($args['options']) && is_array($args['options'])) {
					$editor_settings = array_merge($editor_settings, $args['options']);
				}

				wp_editor($value, $args['section'] . '-' . $args['id'], $editor_settings);

				echo '</div>';

				echo $this->get_field_description($args);
			}

			/**
			 * Displays a file upload field for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_file($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
				$id    = $args['section'] . '[' . $args['id'] . ']';
				$label = isset($args['options']['button_label']) ?
					$args['options']['button_label'] :
					esc_html__('Choose File', 'support-to-slack');

				$html = sprintf('<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value);
				$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
				$html .= $this->get_field_description($args);

				echo $html;
			}

			/**
			 * Displays a password field for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_password($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

				$html = sprintf('<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value);
				$html .= $this->get_field_description($args);

				echo $html;
			}

			/**
			 * Displays a color picker field for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_color($args)
			{

				$value = esc_attr($this->get_option($args['id'], $args['section'], $args['default']));
				$size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

				$html = sprintf('<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['default']);
				$html .= $this->get_field_description($args);

				echo $html;
			}


			function callback_plugin_theme_feed( $args ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
				$dayexception = $this->get_option( $args['id'], $args['section'], $args['default']);

				if ( ! is_array( $dayexception ) ) {
					$dayexception = array();
				}

				$unique_last_count = isset( $dayexception['unique_last_count'] ) ? intval( $dayexception['unique_last_count'] ) : 0;
				$exceptions    = isset( $dayexception['feed'] ) ? $dayexception['feed'] : array();

				?>
				<div class="dayexception_wrapper">
					<!-- <h3 class="one_col_field_title"><?php //esc_html_e('Add Plugin Or Theme', 'support-to-slack'); ?></h3> -->
					<div class="dayexception_items">
						<?php
							if ( is_array( $exceptions ) && sizeof( $exceptions ) > 0 ) {
								foreach ( $exceptions as $key => $exception ) {
									
									$slug = basename($exception['org_link']);
									$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
									$plugin_name   = isset($plugin_info->name) ? $plugin_info->name : '';
									//write_log($exception);
									if(isset($exception['global_hook']) && $exception['global_hook'] == "on"){
										$checked = "checked";
										$display = "";
									}else{
										$checked = "";
										$display = "none";
									}
									$disabled = isset($exception['global_hook']) && $exception['global_hook'] == "on" ? 'disabled' : "";
									?>
									<div class="dayexception_item">
                                        <div class="accordion_tab">
                                            <?php echo $plugin_name; ?>
                                            <div class="accordion_arrow">
                                                <img src="https://i.imgur.com/PJRz0Fc.png" alt="arrow">
                                            </div>
                                        </div>
                                    <div class="accordion_content">
                                    <div class="feed_item">
                                        <div class="feed_item_label">
                                            <label for="slack_webhook'+'_<?php echo $key ?>" class="switch"><?php esc_html_e('Use Diffrent Webhook', ''); ?><div class="webhook_tooltip">?<span class="webhook_tooltip_text">Which Slack channnel you want to send notifications</span></div><input type="checkbox" class="diffrent_hook" id="slack_webhook'+'_<?php echo $key ?>" name="<?php echo $args['section'] ?>[<?php echo $args['id'] ?>][feed][<?php echo esc_attr( $key ); ?>][global_hook]" value="on" <?php echo $checked ?> /><span class="switch_after"></span></label>
                                        </div>

                                        <div class="feed_item_field" style="margin-top: 42px;">
                                            <input style="display:<?php echo $display; ?>" type="text" class="support_slack_webhook" placeholder="<?php esc_html_e('Webhook', 'support-to-slack'); ?>" name="<?php echo $args['section'] ?>[<?php echo $args['id'] ?>][feed][<?php echo esc_attr( $key ); ?>][webhook]" value="<?php echo isset($exception['webhook']) ? esc_attr( $exception['webhook'] ) : '' ?>" <?php //echo $disabled; ?> />
                                        </div>
                                    </div>

                                    <div class="feed_item">
                                        <div class="feed_item_label">
                                                <label for="plugin_slug'+'_<?php echo $key ?>'"><?php esc_html_e('Plugin / Theme Link', ''); ?></label>
                                            </div>
                                            <div class="feed_item_field">
                                                <input type="text" class="" placeholder="<?php esc_html_e('Plugin/Theme link', 'support-to-slack') ?>" name="<?php echo $args['section'] ?>[<?php echo $args['id'] ?>][feed][<?php echo esc_attr( $key ); ?>][org_link]"
                                                value="<?php echo esc_attr( $exception['org_link'] ) ?>" />
                                        </div>
                                    </div>

                                    <div class="feed_item">
										<div class="feed_item_label">
											<label for="custom_slack_message'+'_<?php echo $key ?>"><?php esc_html_e('Custom Slack Message', ''); ?></label>
										</div>
                                        <div class="feed_item_field">
                                            <textarea class="" placeholder="<?php esc_html_e('Message', 'support-to-slack'); ?>"
											   name="<?php echo $args['section'] ?>[<?php echo $args['id'] ?>][feed][<?php echo esc_attr( $key ); ?>][message]"
											   value="" rows="4" cols="50" ><?php echo esc_attr( $exception['message'] ) ?></textarea>
                                        </div>
										<a class="remove_exception button"><?php echo '<span class="dashicons dashicons-trash" style="margin-top: 3px;color: red;"></span>' . esc_html__( 'Remove', 'support-to-slack' ); ?></a>
                                    </div>
                                </div>

                                </div>

								<?php } // end foreach
							} // end if condition
						?>
					</div>
					<br/>
					<a class="add_feed button" data-name="<?php echo $args['id'] ?>" data-section="<?php echo $args['section'] ?>">
						<span class="dashicons dashicons-plus-alt2" style="margin-top: 5px;"></span>
						<?php echo esc_html__( 'Add New', 'support-to-slack' ); ?>
					</a>
					<a class="removeall_feed button"><?php echo '<span class="dashicons dashicons-trash" style="margin-top: 3px;color: red;"></span>' . esc_html__( 'Remove All', 'support-to-slack' ); ?></a>
					<input type="hidden" class="dayexception_last_count" name="<?php echo $args['section'] ?>[<?php echo $args['id'] ?>][unique_last_count]"
						   value="<?php echo intval( $unique_last_count ); ?>"/>
				</div>
				<?php
			} // end of method callback_dayexception

			/**
			 * Displays heading field using h3
			 *
			 * @param array $args settings field args
			 */
			function callback_heading( $args ) {

				$html = '<h3 class="setting_heading">' . $args['name'] . '</h3>';
				$html .= $this->get_field_description( $args );

				echo $html;
			}

			/**
			 * Displays heading field using h4
			 *
			 * @param array $args settings field args
			 */
			function callback_subheading( $args ) {

				$html = '<h4 class="setting_subheading">' . $args['name'] . '</h4>';
				$html .= $this->get_field_description( $args );

				echo $html;
			}

			/**
			 * Displays a textarea for a settings field
			 *
			 * @param array $args settings field args
			 */
			function callback_shortcode( $args ) {
				$value     = $args['default'];
				$value_esc = esc_textarea( $value );
				$size      = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
				$class     = isset( $args['class'] ) && ! is_null( $args['class'] ) ? $args['class'] : '';

				$required = isset( $args['required'] ) ? 'required' : '';


				$html = sprintf( '<textarea readonly rows="5" cols="55" class="%1$s-text %6$s" id="%2$s[%3$s]" name="%2$s[%3$s]" %5$s>%4$s</textarea>',
					$size,
					$args['section'],
					$args['id'],
					$value_esc,
					$required, $class );

				$html .= '<a data-target-cp="#' . $args['section'] . '\\[' . $args['id'] . '\\]' . '" class="shortcode_demo_btn" href="#">'.esc_html__('Click to copy shortcode', 'support-to-slack').'</a>';
				$html .= $this->get_field_description( $args );


				$html .= '<div class="shortcode_demo_wrap">' . do_shortcode( $value ) . '</div>';

				echo $html;
			}

			/**
			 * check if any array is associative
			 *
			 * @param array $array
			 *
			 * @return bool
			 */
			private function is_associate(array $array) {
				return count(array_filter(array_keys($array), 'is_string')) > 0;
			}

			/**
			 * Sanitize callback for Settings API
			 */
			function sanitize_options($options)
			{
				foreach ($options as $option_slug => $option_value) {
					$sanitize_callback = $this->get_sanitize_callback($option_slug);

					// If callback is set, call it
					if ($sanitize_callback) {
						$options[$option_slug] = call_user_func($sanitize_callback, $option_value);
						continue;
					}
				}

				return $options;
			}

			/**
			 * Get sanitization callback for given option slug
			 *
			 * @param string $slug option slug
			 *
			 * @return mixed string or bool false
			 */
			function get_sanitize_callback($slug = '')
			{
				if (empty($slug)) {
					return false;
				}

				// Iterate over registered fields and see if we can find proper callback
				foreach ($this->settings_fields as $section => $options) {
					foreach ($options as $option) {
						if ($option['name'] != $slug) {
							continue;
						}

						if($option['type'] == 'multiselect' || $option['type'] == 'multicheck'){
							$option['sanitize_callback'] = array($this, 'sanitize_multi_select_check');
                        }

						// Return the callback name
						return isset($option['sanitize_callback']) && is_callable($option['sanitize_callback']) ? $option['sanitize_callback'] : false;
					}
				}

				return false;
			}

			/**
             * Remove empty values from multi select fields (multi select and multi checkbox)
             *
			 * @param $option_value
			 *
			 * @return array
			 */
			public function sanitize_multi_select_check($option_value){
			    if(is_array($option_value)){
			        return array_filter($option_value);
                }
                return $option_value;
			}

			/**
			 * Get the value of a settings field
			 *
			 * @param string $option settings field name
			 * @param string $section the section name this field belongs to
			 * @param string $default default text if it's not found
			 * @return string
			 */
			function get_option($option, $section, $default = '')
			{

				$options = get_option($section);

				if (isset($options[$option])) {
					return $options[$option];
				}

				return $default;
			}

			/**
			 * Show navigations as tab
			 *
			 * Shows all the settings section labels as tab
			 */
			public function show_navigation()
			{
				$html = '<h2 class="slack-support-nav-tab" id="slack_settings_nav">';

				foreach ($this->settings_sections as $tab) {
					$html .= sprintf('<a href="#%1$s" class="settings_section" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title']);
				}

				$html .= '</h2>';

				echo $html;
			}

			/**
			 * Show the section settings forms
			 *
			 * This function displays every sections in a different form
			 */
			public function show_forms()
			{
				?>
				<div class="metabox-holder">
					<?php foreach ($this->settings_sections as $form) { ?>
						<div id="<?php echo $form['id']; ?>" class="support_to_slack_group" style="display: none;">
							<form method="post" action="options.php">
								<?php
                                //write_log($form);
									do_action('support_to_slack_form_top_' . $form['id'], $form);
									settings_fields($form['id']);
									do_settings_sections($form['id']);
									do_action('support_to_slack_form_bottom_' . $form['id'], $form);
								?>
								<div style="padding-left: 10px">
									<?php submit_button('Save Settings', 'button button-primary button-large'); ?>
								</div>
							</form>
						</div>
					<?php } ?>
				</div>
				<?php
			}

		}
	endif;
