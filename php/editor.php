<?php

namespace Code_Snippets;

/**
 * Get the attributes for the code editor
 *
 * @param string $type          Type of code editor – either 'php' or 'css
 * @param array  $override_atts Pass an array of attributes to override the saved ones
 * @param bool   $json_encode   Encode the data as JSON
 *
 * @return array|string Array if $json_encode is false, JSON string if it is true
 */
function get_code_editor_atts( $type, $override_atts = array(), $json_encode = true ) {

	// default attributes for the CodeMirror editor
	$default_atts = array(
		'mode'           => 'css' === $type ? 'text/css' : 'text/x-php',
		'matchBrackets'  => true,
		'extraKeys'      => array( 'Alt-F' => 'findPersistent' ),
		'gutters'        => array( 'CodeMirror-lint-markers' ),
		'lint'           => true,
		'viewportMargin' => 'Infinity',
	);

	// add relevant saved setting values to the default attributes
	$settings = get_settings();
	$fields = code_snippets_get_settings_fields();

	foreach ( $fields['editor'] as $field_id => $field ) {
		// the 'codemirror' setting field specifies the name of the attribute
		$default_atts[ $field['codemirror'] ] = $settings['editor'][ $field_id ];
	}

	// merge the default attributes with the ones passed into the function
	$atts = wp_parse_args( $default_atts, $override_atts );
	$atts = apply_filters( 'code_snippets_codemirror_atts', $atts );

	// encode the attributes for display if requested
	if ( $json_encode ) {

		// JSON_UNESCAPED_SLASHES was added in PHP 5.4
		if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
			$atts = json_encode( $atts, JSON_UNESCAPED_SLASHES );
		} else {
			// Use a fallback for < 5.4
			$atts = str_replace( '\\/', '/', json_encode( $atts ) );
		}

		// Infinity is a constant and needs to be unquoted
		$atts = str_replace( '"Infinity"', 'Infinity', $atts );
	}

	return $atts;
}

/**
 * Register and load the CodeMirror library
 *
 * @uses wp_enqueue_style() to add the stylesheets to the queue
 * @uses wp_enqueue_script() to add the scripts to the queue
 */
function enqueue_code_editor_assets() {
	$url = plugin_dir_url( PLUGIN_FILE );
	$plugin_version = code_snippets()->version;

	/* Remove other CodeMirror styles */
	wp_deregister_style( 'codemirror' );
	wp_deregister_style( 'wpeditor' );

	/* CodeMirror */
	wp_enqueue_style( 'code-snippets-editor', $url . 'css/min/editor.css', array(), $plugin_version );
	wp_enqueue_script( 'code-snippets-editor', $url . 'js/min/editor.js', array(), $plugin_version );

	/* CodeMirror Theme */
	$theme = get_setting( 'editor', 'theme' );

	if ( 'default' !== $theme ) {

		wp_enqueue_style(
			'code-snippets-editor-theme-' . $theme,
			$url . "css/min/editor-themes/$theme.css",
			array( 'code-snippets-editor' ), $plugin_version
		);
	}
}

/**
 * Retrieve a list of the available CodeMirror themes
 * @return array the available themes
 */
function get_available_editor_themes() {
	static $themes = null;

	if ( ! is_null( $themes ) ) {
		return $themes;
	}

	$themes = array();
	$themes_dir = plugin_dir_path( PLUGIN_FILE ) . 'css/min/editor-themes/';
	$theme_files = glob( $themes_dir . '*.css' );

	foreach ( $theme_files as $i => $theme ) {
		$theme = str_replace( $themes_dir, '', $theme );
		$theme = str_replace( '.css', '', $theme );
		$themes[] = $theme;
	}

	return $themes;
}
