<?php
/**
 * Theme supports, asset enqueues and editor styles.
 *
 * @package cc-modern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare theme support. Block themes get most of this implicitly; the explicit
 * calls below are the ones that still matter in 6.6.
 */
function cc_modern_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 48,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_editor_style( 'assets/css/theme.css' );
	add_editor_style( 'assets/css/cpa-partners.css' );

	load_theme_textdomain( 'cc-modern', CC_MODERN_DIR . '/languages' );
}
add_action( 'after_setup_theme', 'cc_modern_setup' );

/**
 * Front-end assets.
 *
 * The component stylesheet is a dependency of the theme's own style.css handle so
 * it always loads after theme.json's generated custom properties.
 */
function cc_modern_enqueue_assets() {
	wp_enqueue_style(
		'cc-modern-theme',
		CC_MODERN_URI . '/assets/css/theme.css',
		array(),
		CC_MODERN_VERSION
	);

	wp_enqueue_script(
		'cc-modern-enhance',
		CC_MODERN_URI . '/assets/js/enhance.js',
		array(),
		CC_MODERN_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_enqueue_style( 'cc-modern-cpa', CC_MODERN_URI . '/assets/css/cpa-partners.css', array( 'cc-modern-theme' ), CC_MODERN_VERSION );
	wp_enqueue_script( 'cc-modern-cpa', CC_MODERN_URI . '/assets/js/cpa-partners.js', array(), CC_MODERN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	// The orbdust shell — and its extra webfont — only load where it is used.
	if ( cc_modern_is_legacy_template() ) {
		wp_enqueue_style(
			'cc-modern-legacy',
			CC_MODERN_URI . '/assets/css/legacy.css',
			array( 'cc-modern-theme' ),
			CC_MODERN_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'cc_modern_enqueue_assets' );

/**
 * Whether the current request renders through the legacy page template.
 *
 * @return bool
 */
function cc_modern_is_legacy_template() {
	if ( ! is_singular() ) {
		return false;
	}

	return 'page-legacy' === get_page_template_slug( get_queried_object_id() );
}

/**
 * Preload the two self-hosted variable fonts.
 *
 * Both are used above the fold on every page, so the round trip is worth removing.
 */
function cc_modern_preload_fonts() {
	$fonts = array( 'montserrat-var.woff2', 'cormorant-var.woff2' );

	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( CC_MODERN_URI . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'cc_modern_preload_fonts', 1 );

/**
 * Mark <html> as JS-capable before first paint.
 *
 * Reveal animations hide content until observed, so the hidden state is gated on
 * this class. If scripts fail or are blocked the class never lands and everything
 * renders visible — content is never lost to a broken enhancement.
 */
function cc_modern_js_class() {
	echo '<script>document.documentElement.classList.add("cc-js");</script>' . "\n";
}
add_action( 'wp_head', 'cc_modern_js_class', 0 );
