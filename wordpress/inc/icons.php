<?php
/**
 * Inline SVG icon set.
 *
 * Icons are returned inline rather than referenced as files so they inherit
 * `currentColor` — which means one icon works on a white card and on a dark
 * green section without a second asset — and cost no extra HTTP requests.
 *
 * All are drawn on a 24x24 grid with a 1.25 stroke and no fill, to match the
 * hairline weight the rest of the design uses.
 *
 * @package cc-modern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The icon path data, keyed by name.
 *
 * @return array<string, string>
 */
function cc_modern_icon_paths() {
	return array(
		// Core services.
		'ledger'      => '<path d="M5 3h11l3 3v15H5z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
		'review'      => '<circle cx="12" cy="12" r="8"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
		'invoice'     => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
		'receipt'     => '<path d="M5 3h14v18l-2.3-1.6-2.35 1.6-2.35-1.6L9.65 21 7.3 19.4 5 21z"/><path d="M9 9h6M9 13h4"/>',
		'chart'       => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
		'trend'       => '<path d="M4 20V4M4 20h16"/><path d="M7 15l3.5-4 3 2.5L20 7"/><path d="M20 7h-3.5M20 7v3.5"/>',
		'folder'      => '<path d="M3 7a2 2 0 012-2h4l2 2.5h8a2 2 0 012 2V18a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
		'shield'      => '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6z"/><path d="M9 12.5l2 2 4-4.5"/>',
		'clock'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.5 2"/>',
		'people'      => '<circle cx="9" cy="8.5" r="3"/><path d="M3 20v-1.5A4.5 4.5 0 017.5 14h3A4.5 4.5 0 0115 18.5V20"/><path d="M16 5.5a3 3 0 010 6"/><path d="M18 14a4.5 4.5 0 013 4.2V20"/>',

		// Supporting.
		'bank'        => '<path d="M3 9.5L12 4l9 5.5"/><path d="M5 9.5V20h14V9.5"/><path d="M9 20v-6h6v6"/>',
		'calculator'  => '<rect x="5" y="3" width="14" height="18" rx="1.5"/><path d="M8 7h8"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 16h.01M12 16h.01M15.5 16h.01"/>',
		'handshake'   => '<path d="M3 12l4-4 3.5 3 2-1.5 2 1.5 3.5-3 4 4-4 4-2.5-2-3 2.5-3-2.5L7 16z"/>',
		'search'      => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
		'sparkle'     => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M18.5 16.5l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>',
		'lock'        => '<rect x="5" y="10.5" width="14" height="10.5" rx="1.5"/><path d="M8.5 10.5V7.5a3.5 3.5 0 017 0v3"/>',
		'cloud'       => '<path d="M7.5 18a4 4 0 01-.5-8 5 5 0 019.6 1.2A3.4 3.4 0 0117.5 18z"/>',
		'phone'       => '<path d="M8 3h3l1.5 4-2 1.5a10 10 0 005 5l1.5-2 4 1.5v3a2 2 0 01-2.2 2A16 16 0 016 5.2 2 2 0 018 3z"/>',
		'mail'        => '<rect x="3" y="5.5" width="18" height="13" rx="1.5"/><path d="M3.5 6.5l8.5 6 8.5-6"/>',
		'pin'         => '<path d="M12 21s6.5-6 6.5-10.5a6.5 6.5 0 10-13 0C5.5 15 12 21 12 21z"/><circle cx="12" cy="10.5" r="2.5"/>',
	);
}

/**
 * Return an inline SVG icon.
 *
 * @param string $name Icon key from cc_modern_icon_paths().
 * @param int    $size Pixel size. Defaults to 28.
 * @return string SVG markup, or an empty string if the icon does not exist.
 */
function cc_modern_icon( $name, $size = 28 ) {
	$paths = cc_modern_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="cc-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" ' .
		'stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" ' .
		'aria-hidden="true" focusable="false">%2$s</svg>',
		absint( $size ),
		$paths[ $name ]
	);
}
