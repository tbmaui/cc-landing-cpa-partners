<?php
/**
 * Customizer panel for the CAS-i voice agent.
 *
 * Field names and defaults mirror the widget's own INSTALL.md contract so the
 * toggle stays in step with what widget-loader.js actually accepts.
 *
 * @package cc-modern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setting definitions, shared by the Customizer and the footer renderer.
 *
 * `attr` is the data-* attribute each setting maps to; settings with a null attr
 * are theme-side only (the enable switch and the loader URL).
 *
 * @return array<string, array<string, mixed>>
 */
function cc_modern_voice_agent_fields() {
	return array(
		'cc_va_enabled'       => array(
			'attr'      => null,
			'default'   => false,
			'type'      => 'checkbox',
			'label'     => __( 'Enable CAS-i voice agent', 'cc-modern' ),
			'desc'      => __( 'Loads the CAS-i widget site-wide. Individual pages can opt out below the editor.', 'cc-modern' ),
			'sanitize'  => 'cc_modern_sanitize_checkbox',
		),
		'cc_va_src'           => array(
			'attr'     => null,
			'default'  => 'https://cas-i-voice-agent.vercel.app/widget-loader.js',
			'type'     => 'url',
			'label'    => __( 'Widget loader URL', 'cc-modern' ),
			'desc'     => __( 'Only change this if the widget is redeployed to a new host.', 'cc-modern' ),
			'sanitize' => 'esc_url_raw',
		),
		'cc_va_anchor'        => array(
			'attr'     => 'data-anchor',
			'default'  => '#spatial',
			'type'     => 'text',
			'label'    => __( 'Anchor selector', 'cc-modern' ),
			'desc'     => __( 'CSS selector of the hero she sits against. Use "none" to disable anchoring.', 'cc-modern' ),
			'sanitize' => 'sanitize_text_field',
		),
		'cc_va_size'          => array(
			'attr'     => 'data-size',
			'default'  => '0.66',
			'type'     => 'text',
			'label'    => __( 'Size', 'cc-modern' ),
			'desc'     => __( 'Her height as a fraction of the anchor height, e.g. 0.66.', 'cc-modern' ),
			'sanitize' => 'sanitize_text_field',
		),
		'cc_va_fallback'      => array(
			'attr'     => 'data-fallback',
			'default'  => 'pinned',
			'type'     => 'select',
			'choices'  => array(
				'pinned' => __( 'Pinned — bottom-right corner', 'cc-modern' ),
				'fixed'  => __( 'Fixed — use the coordinates below', 'cc-modern' ),
			),
			'label'    => __( 'Fallback placement', 'cc-modern' ),
			'desc'     => __( 'Used when the anchor element is not on the page.', 'cc-modern' ),
			'sanitize' => 'cc_modern_sanitize_fallback',
		),
		'cc_va_top'           => array(
			'attr'     => 'data-top',
			'default'  => '300',
			'type'     => 'number',
			'label'    => __( 'Fixed: distance from top (px)', 'cc-modern' ),
			'desc'     => __( 'Applies only when fallback is set to Fixed. Increase if she overlaps the nav.', 'cc-modern' ),
			'sanitize' => 'absint',
		),
		'cc_va_right'         => array(
			'attr'     => 'data-right',
			'default'  => '220',
			'type'     => 'number',
			'label'    => __( 'Fixed: distance from right (px)', 'cc-modern' ),
			'desc'     => __( 'Applies only when fallback is set to Fixed.', 'cc-modern' ),
			'sanitize' => 'absint',
		),
		'cc_va_border'        => array(
			'attr'     => 'data-border',
			'default'  => '',
			'type'     => 'text',
			'label'    => __( 'Border colour', 'cc-modern' ),
			'desc'     => __( 'Any CSS colour, e.g. #F36334. Leave empty for the borderless production look.', 'cc-modern' ),
			'sanitize' => 'sanitize_text_field',
		),
		'cc_va_border_width'  => array(
			'attr'     => 'data-border-width',
			'default'  => '',
			'type'     => 'text',
			'label'    => __( 'Border width (px)', 'cc-modern' ),
			'desc'     => __( 'Defaults to 2. The bottom edge doubles to form her ledge.', 'cc-modern' ),
			'sanitize' => 'sanitize_text_field',
		),
		'cc_va_border_radius' => array(
			'attr'     => 'data-border-radius',
			'default'  => '',
			'type'     => 'text',
			'label'    => __( 'Corner radius (px)', 'cc-modern' ),
			'desc'     => __( 'Defaults to 12. Applies with or without a visible border.', 'cc-modern' ),
			'sanitize' => 'sanitize_text_field',
		),
		'cc_va_shadow'        => array(
			'attr'     => 'data-shadow',
			'default'  => '',
			'type'     => 'text',
			'label'    => __( 'Shadow', 'cc-modern' ),
			'desc'     => __( 'Any CSS box-shadow value. Use "none" to remove the default soft drop shadow.', 'cc-modern' ),
			'sanitize' => 'sanitize_text_field',
		),
	);
}

/**
 * Sanitize a checkbox to a strict boolean.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function cc_modern_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Constrain the fallback setting to the two documented modes.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function cc_modern_sanitize_fallback( $value ) {
	return in_array( $value, array( 'pinned', 'fixed' ), true ) ? $value : 'pinned';
}

/**
 * Register the Customizer section and its controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function cc_modern_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'cc_voice_agent',
		array(
			'title'       => __( 'CAS-i Voice Agent', 'cc-modern' ),
			'priority'    => 130,
			'description' => __( 'Controls the real-time CAS-i voice agent widget. Turn it on here for the whole site, then opt individual pages out from the page editor sidebar.', 'cc-modern' ),
		)
	);

	foreach ( cc_modern_voice_agent_fields() as $key => $field ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize'],
				'transport'         => 'refresh',
			)
		);

		$control = array(
			'label'       => $field['label'],
			'description' => $field['desc'],
			'section'     => 'cc_voice_agent',
			'type'        => $field['type'],
		);

		if ( isset( $field['choices'] ) ) {
			$control['choices'] = $field['choices'];
		}

		$wp_customize->add_control( $key, $control );
	}
}
add_action( 'customize_register', 'cc_modern_customize_register' );
