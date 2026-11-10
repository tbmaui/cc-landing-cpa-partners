<?php
/**
 * Renders the theme's patterns to a standalone HTML file for visual verification.
 *
 * This stubs the small surface of WordPress the patterns actually touch (i18n and
 * escaping) and reimplements the parts of theme.json that WordPress turns into
 * CSS: preset custom properties, the `has-*` utility classes and element styles.
 *
 * The point is to check design fidelity against the live site without needing a
 * WordPress install in the loop. It does NOT verify editor behaviour, pattern
 * registration or locking — those need real WordPress.
 *
 * Usage:
 *   php tools/render-static.php > build/preview.html
 *   php tools/render-static.php --page=inner > build/inner.html
 */

define( 'ABSPATH', true );
define( 'CC_MODERN_DIR', dirname( __DIR__ ) . '/wordpress' );

require_once CC_MODERN_DIR . '/inc/icons.php';
require_once CC_MODERN_DIR . '/inc/industry-page.php';

/* ------------------------------------------------- WordPress stubs ------- */

function __( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $text, $domain = null ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = null ) { echo esc_attr( $text ); }
function esc_html__( $text, $domain = null ) { return esc_html( $text ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $data, $flags = 0 ) { return json_encode( $data, $flags ); }
function wp_trim_words( $text, $num_words = 55, $more = null ) {
	$words = preg_split( '/\s+/', trim( $text ) );
	if ( count( $words ) <= $num_words ) { return $text; }
	return implode( ' ', array_slice( $words, 0, $num_words ) ) . ( null === $more ? '&hellip;' : $more );
}
function get_template_directory_uri() { return '../wordpress/assets/..'; }

/* ------------------------------- theme.json -> CSS (as WordPress does) ---- */

/**
 * Convert a preset slug to the kebab form WordPress uses in class names.
 *
 * WordPress splits letter/digit boundaries, so `h2` becomes `h-2` and the class
 * is `has-h-2-font-size`. Reproducing this exactly matters — get it wrong and
 * font sizes silently fall back to the body size.
 */
function cc_kebab( $slug ) {
	$slug = preg_replace( '/([a-z])([0-9])/', '$1-$2', $slug );
	$slug = preg_replace( '/([0-9])([a-z])/', '$1-$2', $slug );

	return $slug;
}

function cc_build_css( array $theme ) {
	$settings = $theme['settings'];
	$styles   = $theme['styles'];
	$vars     = array();
	$rules    = array();
	$faces    = array();

	foreach ( $settings['color']['palette'] as $item ) {
		$vars[] = "--wp--preset--color--{$item['slug']}: {$item['color']};";
		$k      = cc_kebab( $item['slug'] );
		$rules[] = ".has-{$k}-color{color:var(--wp--preset--color--{$item['slug']}) !important}";
		$rules[] = ".has-{$k}-background-color{background-color:var(--wp--preset--color--{$item['slug']}) !important}";
	}

	foreach ( $settings['typography']['fontSizes'] as $item ) {
		$vars[] = "--wp--preset--font-size--{$item['slug']}: {$item['size']};";
		$k      = cc_kebab( $item['slug'] );
		$rules[] = ".has-{$k}-font-size{font-size:var(--wp--preset--font-size--{$item['slug']}) !important}";
	}

	foreach ( $settings['typography']['fontFamilies'] as $item ) {
		$vars[] = "--wp--preset--font-family--{$item['slug']}: {$item['fontFamily']};";
		$k      = cc_kebab( $item['slug'] );
		$rules[] = ".has-{$k}-font-family{font-family:var(--wp--preset--font-family--{$item['slug']}) !important}";

		foreach ( $item['fontFace'] ?? array() as $face ) {
			$src     = str_replace( 'file:./assets', '../wordpress/assets', $face['src'][0] );
			$faces[] = sprintf(
				"@font-face{font-family:'%s';font-weight:%s;font-style:%s;font-display:%s;src:url('%s') format('woff2')}",
				$face['fontFamily'],
				$face['fontWeight'],
				$face['fontStyle'],
				$face['fontDisplay'] ?? 'swap',
				$src
			);
		}
	}

	foreach ( $settings['spacing']['spacingSizes'] as $item ) {
		$vars[] = "--wp--preset--spacing--{$item['slug']}: {$item['size']};";
	}

	/** Flatten a styles node into declarations. */
	$decl = static function ( array $node ) {
		$out = array();
		$map = array(
			'fontFamily'    => 'font-family',
			'fontSize'      => 'font-size',
			'fontWeight'    => 'font-weight',
			'lineHeight'    => 'line-height',
			'letterSpacing' => 'letter-spacing',
			'textTransform' => 'text-transform',
			'textDecoration' => 'text-decoration',
			'fontStyle'     => 'font-style',
		);

		foreach ( $node['typography'] ?? array() as $key => $value ) {
			if ( isset( $map[ $key ] ) ) {
				$out[] = "{$map[$key]}:{$value}";
			}
		}
		if ( isset( $node['color']['text'] ) ) {
			$out[] = "color:{$node['color']['text']}";
		}
		if ( isset( $node['color']['background'] ) ) {
			$out[] = "background-color:{$node['color']['background']}";
		}
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( isset( $node['spacing']['padding'][ $side ] ) ) {
				$out[] = "padding-{$side}:{$node['spacing']['padding'][$side]}";
			}
		}
		if ( isset( $node['border']['radius'] ) ) { $out[] = "border-radius:{$node['border']['radius']}"; }
		if ( isset( $node['border']['width'] ) )  { $out[] = "border-width:{$node['border']['width']}"; }
		if ( isset( $node['border']['style'] ) )  { $out[] = "border-style:{$node['border']['style']}"; }
		if ( isset( $node['border']['color'] ) )  { $out[] = "border-color:{$node['border']['color']}"; }

		return implode( ';', $out );
	};

	$body = $decl( $styles );
	if ( $body ) {
		$rules[] = "body{{$body}}";
	}

	$selectors = array(
		'heading' => 'h1,h2,h3,h4,h5,h6',
		'h1'      => 'h1',
		'h2'      => 'h2',
		'h3'      => 'h3',
		'h4'      => 'h4',
		'h5'      => 'h5',
		'h6'      => 'h6',
		'link'    => 'a:where(:not(.wp-element-button))',
		'button'  => '.wp-element-button,.wp-block-button__link',
	);

	foreach ( $selectors as $element => $selector ) {
		if ( ! isset( $styles['elements'][ $element ] ) ) {
			continue;
		}
		$d = $decl( $styles['elements'][ $element ] );
		if ( $d ) {
			$rules[] = "{$selector}{{$d}}";
		}
	}

	foreach ( $styles['blocks'] ?? array() as $block => $node ) {
		$selector = '.wp-block-' . str_replace( 'core/', '', $block );
		$d        = $decl( $node );
		if ( $d ) {
			$rules[] = "{$selector}{{$d}}";
		}
	}

	return implode( "\n", $faces ) . "\n:root{\n" . implode( "\n", $vars ) . "\n}\n" . implode( "\n", $rules );
}

/* ---------------------------------------------------------- render ------- */

$theme = json_decode( file_get_contents( CC_MODERN_DIR . '/theme.json' ), true );

if ( ! $theme ) {
	fwrite( STDERR, "Could not parse theme.json\n" );
	exit( 1 );
}

$page = 'cpa-partners';
foreach ( $argv as $arg ) {
	if ( str_starts_with( $arg, '--page=' ) ) {
		$page = substr( $arg, 7 );
	}
}

$sets = array( 'cpa-partners' => array( 'page-cpa-partners' ) );

if ( ! isset( $sets[ $page ] ) ) {
	fwrite( STDERR, "Unknown page preview: {$page}\n" );
	exit( 1 );
}

ob_start();
foreach ( $sets[ $page ] as $slug ) {
	include CC_MODERN_DIR . '/patterns/' . $slug . '.php';
}
$body = ob_get_clean();

// Strip the docblock headers the pattern files emit before their markup.
$body = preg_replace( '/^\s*(\/\*\*.*?\*\/)?\s*/s', '', $body );

// WordPress adds the flow-layout classes to every Group at render time. With
// blockGap at 0 its `:root :where(.is-layout-flow) > *` rule zeroes the margin of
// every child, at specificity (0,1,0). Reproduce both so spacing bugs show here.
$body = str_replace( 'class="wp-block-group ', 'class="wp-block-group is-layout-flow wp-block-group-is-layout-flow ', $body );

$css = cc_build_css( $theme );
$extra_assets = 'cpa-partners' === $page ? '<link rel="stylesheet" href="../wordpress/assets/css/cpa-partners.css"><script src="../wordpress/assets/js/cpa-partners.js" defer></script>' : ''; 

echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{$extra_assets}
<title>cc-modern — {$page} preview</title>

<style>
/* WordPress global styles reset the browser's default body margin. */
body { margin: 0; }
*, *::before, *::after { box-sizing: border-box; }
{$css}
</style>
<link rel="stylesheet" href="../wordpress/assets/css/theme.css">
<style>:root :where(.is-layout-flow) > * { margin-block-start: 0; margin-block-end: 0; }</style>
<script>document.documentElement.classList.add("cc-js");</script>
</head>
<body class="wp-site-blocks">
{$body}
<script src="../wordpress/assets/js/enhance.js"></script>
</body>
</html>
HTML;
