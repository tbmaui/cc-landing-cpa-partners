<?php
/**
 * Renders the CAS-i voice agent embed when enabled.
 *
 * @package cc-modern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CC_MODERN_VA_META = '_cc_voice_agent';

/**
 * Decide whether the widget should render on the current request.
 *
 * Site-wide setting is the baseline; a per-page override can force it on or off.
 *
 * @return bool
 */
function cc_modern_voice_agent_active() {
	// The reference homepage includes CAS-i in the hero by default. Other pages
	// remain governed by the site-wide switch, and every page can still opt out.
	$enabled = is_front_page() || (bool) get_theme_mod( 'cc_va_enabled', false );

	if ( is_singular() ) {
		$override = get_post_meta( get_the_ID(), CC_MODERN_VA_META, true );

		if ( 'on' === $override ) {
			$enabled = true;
		} elseif ( 'off' === $override ) {
			$enabled = false;
		}
	}

	/**
	 * Filters whether the CAS-i widget renders.
	 *
	 * @param bool $enabled Whether to render the widget.
	 */
	return (bool) apply_filters( 'cc_modern_voice_agent_active', $enabled );
}

/**
 * Print the widget loader script tag in the footer.
 *
 * Attributes with an empty value are omitted entirely so the widget falls back to
 * its own documented defaults rather than receiving an empty string.
 */
function cc_modern_render_voice_agent() {
	if ( ! cc_modern_voice_agent_active() ) {
		return;
	}

	$fields = cc_modern_voice_agent_fields();
	$src    = get_theme_mod( 'cc_va_src', $fields['cc_va_src']['default'] );

	if ( empty( $src ) ) {
		return;
	}

	$attrs    = array();
	$fallback = get_theme_mod( 'cc_va_fallback', $fields['cc_va_fallback']['default'] );

	foreach ( $fields as $key => $field ) {
		if ( empty( $field['attr'] ) ) {
			continue;
		}

		// data-top and data-right are only meaningful in fixed fallback mode.
		if ( in_array( $field['attr'], array( 'data-top', 'data-right' ), true ) && 'fixed' !== $fallback ) {
			continue;
		}

		$value = get_theme_mod( $key, $field['default'] );

		if ( '' === $value || null === $value ) {
			continue;
		}

		$attrs[ $field['attr'] ] = (string) $value;
	}

	$rendered = '';
	foreach ( $attrs as $attr => $value ) {
		$rendered .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
	}

	// The CAS-i loader's default pricing URL differs on this WordPress.com staging site.
	// Intercept its pricing handoff before the loader's own message listener runs.
	if ( 'thad16b9177065a-fzohr.wpcomstaging.com' === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		?>
<script id="cc-modern-casi-navigation-bridge">
window.addEventListener('message', function (event) {
	if (event.origin !== 'https://cas-i-voice-agent.vercel.app') {
		return;
	}

	var data = event.data;
	if (!data || data.type !== 'casi-navigate' || data.slug !== '/pricing') {
		return;
	}

	event.stopImmediatePropagation();
	window.location.assign('/pricing-sheet/');
}, true);
</script>
<?php
	}

	printf(
		'<script id="cas-i-widget-loader" src="%s"%s></script>' . "\n",
		esc_url( $src ),
		$rendered // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each attribute escaped above.
	);
}
add_action( 'wp_footer', 'cc_modern_render_voice_agent', 20 );

/**
 * Register the per-page override control in the editor sidebar.
 *
 * Registered as post meta with `show_in_rest` so the block editor exposes it
 * without a classic meta box.
 */
function cc_modern_register_voice_agent_meta() {
	foreach ( array( 'page', 'post' ) as $post_type ) {
		register_post_meta(
			$post_type,
			CC_MODERN_VA_META,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => static function ( $value ) {
					return in_array( $value, array( 'on', 'off' ), true ) ? $value : '';
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'cc_modern_register_voice_agent_meta' );
