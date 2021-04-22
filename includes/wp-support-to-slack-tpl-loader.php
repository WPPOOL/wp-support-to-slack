<?php
	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}
	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	function support_to_slack_template_path() {
		return apply_filters( 'support_to_slack_template_path', 'support_to_slack/' );
	}//end support_to_slack_template_path

	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 * yourtheme/$template_path/$template_name
	 * yourtheme/$template_name
	 * $default_path/$template_name
	 *
	 * @param string $template_name Template name.
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 *
	 * @return string
	 */
	function support_to_slack_locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = support_to_slack_template_path();
		}

		if ( ! $default_path ) {
			$default_path = SUPPORT_TO_SLACK_ROOT_PATH . 'templates/';
		}

		// Look within passed path within the theme - this is priority.
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);

		// Get default template/.
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		// Return what we found.
		return apply_filters( 'support_to_slack_locate_template', $template, $template_name, $template_path );
	}//end function support_to_slack_locate_template



	/**
	 * Get other templates (e.g. product attributes) passing attributes and including the file.
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 */
	function support_to_slack_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args ); // @codingStandardsIgnoreLine
		}

		$located = support_to_slack_locate_template( $template_name, $template_path, $default_path );

		// Allow 3rd party plugin filter template file from their plugin.
		$located = apply_filters( 'support_to_slack_get_template', $located, $template_name, $args, $template_path, $default_path );

		do_action( 'support_to_slack_before_template_part', $template_name, $template_path, $located, $args );

		include $located;

		do_action( 'support_to_slack_after_template_part', $template_name, $template_path, $located, $args );
	}//end function support_to_slack_get_template

	/**
	 * Like wc_get_template, but returns the HTML instead of outputting.
	 *
	 * @see   wc_get_template
	 * @since 2.5.0
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 *
	 * @return string
	 */
	function support_to_slack_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		ob_start();
		support_to_slack_get_template( $template_name, $args, $template_path, $default_path );

		return ob_get_clean();
	}//end function support_to_slack_get_template_html