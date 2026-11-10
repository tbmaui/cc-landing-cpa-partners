<?php
/**
 * Shared renderer for the industry landing pages.
 *
 * The three industry starters (construction, healthcare, property management)
 * share one structure and differ only in copy, so each pattern file holds its
 * data and hands it to cc_modern_render_industry_page(). The output is plain
 * Gutenberg block markup, so every section stays editable in the editor.
 *
 * @package cc-modern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inner SVG paths for the feature-card icons, keyed by name.
 *
 * @return array<string, string>
 */
function cc_modern_industry_icons() {
	return array(
		'house'  => '<path d="M3 20h18M5 20V10l7-5 7 5v10"/><path d="M9 20v-6h6v6"/>',
		'cash'   => '<rect x="3" y="6" width="18" height="12" rx="1"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5v5M17.5 9.5v5"/>',
		'chart'  => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
		'sheet'  => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 8h6M9 12h6M9 16h3"/>',
		'file'   => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
		'lock'   => '<rect x="5" y="10" width="14" height="10" rx="1"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		'box'    => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
		'cloud'  => '<path d="M7 18a4 4 0 0 1-.5-8A5.5 5.5 0 0 1 17 8.5 4.5 4.5 0 0 1 17.5 18z"/><path d="M12 11v5M9.8 13.5 12 11l2.2 2.5"/>',
		'layers' => '<path d="M12 3 3 8l9 5 9-5-9-5z"/><path d="M3 13l9 5 9-5"/>',
		'home'   => '<path d="M4 11 12 4l8 7"/><path d="M6 10v10h12V10"/><path d="M10 20v-5h4v5"/>',
	);
}

/**
 * Block markup for a paragraph.
 *
 * @param string $text  Plain text, escaped here.
 * @param string $class Optional class name.
 * @return string
 */
function cc_modern_ind_p( $text, $class = '' ) {
	$attr = '' === $class ? '' : ' ' . wp_json_encode( array( 'className' => $class ) );
	$cls  = '' === $class ? '' : ' class="' . esc_attr( $class ) . '"';

	return '<!-- wp:paragraph' . $attr . " -->\n<p" . $cls . '>' . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->\n";
}

/**
 * Block markup for a heading.
 *
 * @param int    $level Heading level.
 * @param string $text  Plain text, escaped here.
 * @param string $size  Font size preset slug: h2, card-sm or card.
 * @return string
 */
function cc_modern_ind_h( $level, $text, $size ) {
	$kebab = preg_replace( '/([a-z])([0-9])/', '$1-$2', $size );

	return '<!-- wp:heading ' . wp_json_encode(
		array(
			'level'    => $level,
			'fontSize' => $size,
		)
	) . " -->\n<h" . $level . ' class="wp-block-heading has-' . esc_attr( $kebab ) . '-font-size">' . esc_html( $text ) . '</h' . $level . ">\n<!-- /wp:heading -->\n";
}

/**
 * Open a group block.
 *
 * @param string $class Class name(s).
 * @param string $tag   div or section.
 * @param array  $extra Extra block attributes.
 * @return string
 */
function cc_modern_ind_open( $class, $tag = 'div', array $extra = array() ) {
	$attrs = array();

	if ( 'div' !== $tag ) {
		$attrs['tagName'] = $tag;
	}
	if ( isset( $extra['anchor'] ) ) {
		$attrs['anchor'] = $extra['anchor'];
	}
	$attrs['className'] = $class;
	foreach ( array( 'templateLock', 'backgroundColor' ) as $key ) {
		if ( isset( $extra[ $key ] ) ) {
			$attrs[ $key ] = $extra[ $key ];
		}
	}
	$attrs['layout'] = array( 'type' => 'default' );

	$classes = 'wp-block-group ' . $class;
	if ( isset( $extra['backgroundColor'] ) ) {
		$classes .= ' has-' . $extra['backgroundColor'] . '-background-color has-background';
	}
	$id = isset( $extra['anchor'] ) ? ' id="' . esc_attr( $extra['anchor'] ) . '"' : '';

	return '<!-- wp:group ' . wp_json_encode( $attrs ) . " -->\n<" . $tag . $id . ' class="' . esc_attr( $classes ) . "\">\n";
}

/**
 * Close a group block.
 *
 * @param string $tag div or section.
 * @return string
 */
function cc_modern_ind_close( $tag = 'div' ) {
	return '</' . $tag . ">\n<!-- /wp:group -->\n";
}

/**
 * One button block.
 *
 * @param string $label Button label.
 * @param string $href  Link target.
 * @param string $class Button variant class.
 * @return string
 */
function cc_modern_ind_button( $label, $href, $class = 'cc-btn' ) {
	return '<!-- wp:buttons {"className":"cc-actions"} -->' . "\n" . '<div class="wp-block-buttons cc-actions">' . "\n"
		. '<!-- wp:button ' . wp_json_encode( array( 'className' => $class ) ) . " -->\n"
		. '<div class="wp-block-button ' . esc_attr( $class ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $href ) . '">' . esc_html( $label ) . "</a></div>\n"
		. "<!-- /wp:button -->\n</div>\n<!-- /wp:buttons -->\n";
}

/**
 * Section heading block: eyebrow, h2 and optional intro.
 *
 * @param string $eyebrow Eyebrow text.
 * @param string $title   H2 text.
 * @param string $intro   Optional lead paragraph.
 * @param string $extra   Extra class on the wrapper.
 * @return string
 */
function cc_modern_ind_head( $eyebrow, $title, $intro = '', $extra = '' ) {
	$out  = cc_modern_ind_open( trim( 'cc-section-head cc-measure ' . $extra ) );
	$out .= cc_modern_ind_p( $eyebrow, 'cc-eyebrow' );
	$out .= cc_modern_ind_h( 2, $title, 'h2' );
	if ( '' !== $intro ) {
		$out .= cc_modern_ind_p( $intro, 'cc-lead' );
	}
	$out .= cc_modern_ind_close();

	return $out;
}

/**
 * Render a complete industry landing page as block markup.
 *
 * @param array $d Page data. See the page-industry-*.php patterns for the shape.
 * @return string
 */
function cc_modern_render_industry_page( array $d ) {
	$icons = cc_modern_industry_icons();
	$lock  = array( 'templateLock' => 'contentOnly' );
	$out   = '';

	$out .= cc_modern_ind_open( 'cc-starter-page cc-starter-page--industry ' . $d['class'] );

	/* ---- Hero ---------------------------------------------------------- */
	$h    = $d['hero'];
	$out .= cc_modern_ind_open( 'cc-hero-ledger', 'section', $lock );
	$out .= cc_modern_ind_open( 'cc-hero-ledger__inner' );
	$out .= cc_modern_ind_open( 'cc-hero-ledger__copy' );
	$out .= cc_modern_ind_p( $h['eyebrow'], 'cc-eyebrow' );
	$out .= '<!-- wp:heading {"level":1,"fontSize":"h1"} -->' . "\n" . '<h1 class="wp-block-heading has-h-1-font-size">' . esc_html( $h['title'] ) . "</h1>\n<!-- /wp:heading -->\n";
	$out .= cc_modern_ind_p( $h['lead'], 'cc-lead' );
	$out .= cc_modern_ind_open( 'cc-hero-ledger__actions' );
	$out .= cc_modern_ind_button( __( 'Book a free consultation', 'cc-modern' ), '#consultation' );
	$out .= '<!-- wp:paragraph {"className":"cc-hero-ledger__call"} -->' . "\n" . '<p class="cc-hero-ledger__call">' . esc_html__( 'or call', 'cc-modern' ) . ' <a href="tel:+18664438879">866-443-8879</a></p>' . "\n<!-- /wp:paragraph -->\n";
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_p( $h['trust'], 'cc-hero-ledger__trust' );
	$out .= cc_modern_ind_close();
	$out .= '<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"cc-hero-ledger__art"} -->' . "\n"
		. '<figure class="wp-block-image size-full cc-hero-ledger__art"><img src="' . esc_url( get_template_directory_uri() . '/assets/images/' . $h['image'] ) . '" alt="' . esc_attr( $h['alt'] ) . '"/></figure>' . "\n<!-- /wp:image -->\n";
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Trust strip --------------------------------------------------- */
	$out .= cc_modern_ind_open( 'cc-ind-trust', 'section', $lock );
	$out .= cc_modern_ind_open( 'cc-ind-trust__list' );
	foreach ( $d['trust'] as $item ) {
		$out .= cc_modern_ind_p( $item );
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Problem ------------------------------------------------------- */
	$p    = $d['problem'];
	$out .= cc_modern_ind_open( 'cc-section cc-ind-problem', 'section', $lock + array( 'backgroundColor' => 'white' ) );
	$out .= cc_modern_ind_open( 'cc-container' );
	$out .= cc_modern_ind_p( __( 'The Problem', 'cc-modern' ), 'cc-eyebrow' );
	$out .= cc_modern_ind_h( 2, $p['title'], 'h2' );
	$out .= cc_modern_ind_open( 'cc-ind-problem__cols' );
	$out .= cc_modern_ind_p( $p['p1'] );
	$out .= cc_modern_ind_p( $p['p2'] );
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_open( 'cc-ind-callout' );
	$out .= cc_modern_ind_p( $p['callout_lead'], 'cc-ind-callout__lead' );
	$out .= cc_modern_ind_p( $p['callout_body'] );
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Features ------------------------------------------------------ */
	$f    = $d['features'];
	$out .= cc_modern_ind_open( 'cc-section cc-ind-features', 'section', $lock + array( 'backgroundColor' => 'cc-light' ) );
	$out .= cc_modern_ind_open( 'cc-container' );
	$out .= cc_modern_ind_open( 'cc-section-head cc-measure' );
	$out .= cc_modern_ind_p( __( 'What You Get', 'cc-modern' ), 'cc-eyebrow' );
	$out .= cc_modern_ind_h( 2, $f['title'], 'h2' );
	if ( ! empty( $f['pillars'] ) ) {
		$out .= cc_modern_ind_open( 'cc-ind-pillars' );
		foreach ( $f['pillars'] as $pillar ) {
			$out .= cc_modern_ind_p( $pillar );
		}
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_open( 'cc-ind-features__grid' );
	foreach ( $f['items'] as $item ) {
		$out .= cc_modern_ind_open( 'cc-card' );
		if ( ! empty( $item['tag'] ) ) {
			$out .= cc_modern_ind_p( $item['tag'], 'cc-ind-tag' );
		}
		$out .= "<!-- wp:html -->\n" . '<span class="cc-card__icon cc-card__icon--round"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[ $item['icon'] ] . "</svg></span>\n<!-- /wp:html -->\n";
		$out .= cc_modern_ind_h( 3, $item['title'], 'card-sm' );
		$out .= cc_modern_ind_p( $item['copy'] );
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Team band ----------------------------------------------------- */
	$t    = $d['team'];
	$out .= cc_modern_ind_open( 'cc-section cc-section--dark cc-ind-team', 'section', $lock + array( 'backgroundColor' => 'cc-dark' ) );
	$out .= cc_modern_ind_open( 'cc-container' );
	$out .= cc_modern_ind_head( __( 'Your Team on Every Account', 'cc-modern' ), $t['title'] );
	$out .= '<!-- wp:paragraph {"className":"cc-ind-statement"} -->' . "\n" . '<p class="cc-ind-statement">' . esc_html( $t['statement_before'] ) . '<em class="cc-accent">' . esc_html( $t['statement_accent'] ) . '</em></p>' . "\n<!-- /wp:paragraph -->\n";
	$out .= cc_modern_ind_p( $t['intro'], 'cc-ind-team__intro' );
	$out .= cc_modern_ind_open( 'cc-ind-tiles cc-grid--hairline' );
	foreach ( $t['tiles'] as $tile ) {
		$out .= cc_modern_ind_open( 'cc-tile' );
		$out .= cc_modern_ind_p( $tile['label'], 'cc-tile__meta' );
		$out .= cc_modern_ind_h( 3, $tile['title'], 'card-sm' );
		$out .= cc_modern_ind_p( $tile['body'] );
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Steps --------------------------------------------------------- */
	$out .= cc_modern_ind_open( 'cc-section cc-how', 'section', $lock + array( 'backgroundColor' => 'white' ) );
	$out .= cc_modern_ind_open( 'cc-container' );
	$out .= cc_modern_ind_head( __( 'How It Works', 'cc-modern' ), __( 'Getting started takes three steps.', 'cc-modern' ) );
	$out .= cc_modern_ind_open( 'cc-grid cc-grid--3' );
	foreach ( $d['steps'] as $index => $step ) {
		$number = sprintf( '%02d', $index + 1 );
		$out   .= cc_modern_ind_open( 'cc-step' );
		$out   .= "<!-- wp:html -->\n" . '<p class="cc-step__num" aria-hidden="true">' . esc_html( $number ) . "</p>\n<!-- /wp:html -->\n";
		$out   .= cc_modern_ind_p( 'Step ' . $number, 'cc-step__label' );
		$out   .= cc_modern_ind_h( 3, $step['title'], 'card-sm' );
		$out   .= cc_modern_ind_p( $step['copy'] );
		$out   .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- CAS-i --------------------------------------------------------- */
	$out .= cc_modern_ind_open( 'cc-section cc-ind-casi', 'section', $lock + array( 'backgroundColor' => 'cc-tint' ) );
	$out .= cc_modern_ind_open( 'cc-container cc-center cc-measure--wide' );
	$out .= cc_modern_ind_p( __( 'Your Team, Any Time', 'cc-modern' ), 'cc-eyebrow' );
	$out .= cc_modern_ind_h( 2, __( 'Numbers that make sense, so you can make better decisions.', 'cc-modern' ), 'h2' );
	$out .= cc_modern_ind_p( __( "CAS-i is Complete Controller's AI and the member of your team you can reach any time. Ask about your numbers in plain language, by voice or chat, and get clear answers about what they mean, so you can make better-informed decisions about your business. CAS-i never changes your books; your Controller and Bookkeeper do the work, and your Controller still plans ahead with you.", 'cc-modern' ), 'cc-lead' );
	$out .= cc_modern_ind_open( 'cc-ind-casi__asks' );
	foreach ( $d['asks'] as $ask ) {
		$out .= cc_modern_ind_open( 'cc-ind-ask' );
		$out .= cc_modern_ind_p( __( 'Ask CAS-i', 'cc-modern' ), 'cc-ind-ask__label' );
		$out .= cc_modern_ind_p( $ask );
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Plans --------------------------------------------------------- */
	$pl   = $d['plans'];
	$out .= cc_modern_ind_open( 'cc-section cc-ind-plans-section', 'section', $lock + array( 'backgroundColor' => 'white' ) + array( 'anchor' => 'plans' ) );
	$out .= cc_modern_ind_open( 'cc-container' );
	$out .= cc_modern_ind_head( __( 'Choosing a Plan', 'cc-modern' ), $pl['title'], $pl['intro'] );
	$out .= cc_modern_ind_open( 'cc-ind-plans' );
	foreach ( $pl['items'] as $plan ) {
		$out .= cc_modern_ind_open( 'cc-ind-plan' . ( ! empty( $plan['pick'] ) ? ' cc-ind-plan--pick' : '' ) );
		$out .= cc_modern_ind_p( $plan['tag'], 'cc-ind-tag' );
		$out .= cc_modern_ind_h( 3, $plan['name'], 'card-sm' );
		$out .= '<!-- wp:paragraph {"className":"cc-ind-plan__price"} -->' . "\n" . '<p class="cc-ind-plan__price">' . esc_html( $plan['price'] ) . '<small>' . esc_html( $plan['period'] ) . "</small></p>\n<!-- /wp:paragraph -->\n";
		$out .= cc_modern_ind_p( $plan['setup'], 'cc-ind-plan__setup' );
		$out .= '<!-- wp:paragraph {"className":"cc-ind-plan__fit"} -->' . "\n" . '<p class="cc-ind-plan__fit"><strong>' . esc_html__( 'Best for:', 'cc-modern' ) . '</strong> ' . esc_html( $plan['fit'] ) . "</p>\n<!-- /wp:paragraph -->\n";
		$out .= "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";
		foreach ( $plan['features'] as $feature ) {
			$out .= "<!-- wp:list-item -->\n<li>" . esc_html( $feature ) . "</li>\n<!-- /wp:list-item -->\n";
		}
		$out .= "</ul>\n<!-- /wp:list -->\n";
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= '<!-- wp:paragraph {"className":"cc-ind-plan-note"} -->' . "\n" . '<p class="cc-ind-plan-note">' . esc_html( $pl['note'] ) . ' <a href="https://www.completecontroller.com/pricing-sign-up/">' . esc_html__( 'See all plans and pricing', 'cc-modern' ) . "</a>.</p>\n<!-- /wp:paragraph -->\n";
	$out .= cc_modern_ind_open( 'cc-ind-plan-cta' );
	$out .= cc_modern_ind_button( __( 'Book a free consultation', 'cc-modern' ), '#consultation', 'cc-btn cc-btn--outline' );
	$out .= cc_modern_ind_p( __( "Not sure which level fits? We'll recommend one on the call.", 'cc-modern' ) );
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Client quotes ------------------------------------------------- */
	$q     = $d['quotes'];
	$items = $q['items'];
	$out  .= cc_modern_ind_open( 'cc-section cc-section--tight cc-ind-quotes', 'section', $lock + array( 'backgroundColor' => 'cc-light' ) );
	$out  .= cc_modern_ind_open( 'cc-container' );
	$out  .= cc_modern_ind_head( __( 'Client Stories', 'cc-modern' ), $q['title'] );
	if ( ! empty( $q['featured'] ) ) {
		$feat = array_shift( $items );
		$out .= cc_modern_ind_open( 'cc-quote cc-quote--featured' );
		$out .= cc_modern_ind_p( '“' . $feat['text'] . '”', 'cc-quote__text' );
		$out .= cc_modern_ind_quote_author( $feat );
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_open( 'cc-grid ' . ( 3 === count( $items ) ? 'cc-grid--quotes' : 'cc-grid--2' ) );
	foreach ( $items as $item ) {
		$out .= cc_modern_ind_open( 'cc-quote' );
		$out .= cc_modern_ind_p( '“' . $item['text'] . '”', 'cc-quote__text' );
		$out .= cc_modern_ind_quote_author( $item );
		$out .= cc_modern_ind_close();
	}
	$out .= cc_modern_ind_close();
	$out .= '<!-- wp:paragraph {"className":"cc-ind-plan-note"} -->' . "\n" . '<p class="cc-ind-plan-note"><a href="https://www.completecontroller.com/reputation-clients/">' . esc_html__( 'See what our clients say', 'cc-modern' ) . " →</a></p>\n<!-- /wp:paragraph -->\n";
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- FAQ ----------------------------------------------------------- */
	$out .= cc_modern_ind_open( 'cc-section cc-ind-faq', 'section', $lock + array( 'backgroundColor' => 'white' ) );
	$out .= cc_modern_ind_open( 'cc-container' );
	$out .= cc_modern_ind_open( 'cc-measure' );
	$out .= cc_modern_ind_head( __( 'Questions', 'cc-modern' ), $d['faq']['title'] );
	$out .= cc_modern_ind_open( 'cc-faq' );
	foreach ( $d['faq']['items'] as $faq ) {
		$out .= '<!-- wp:details {"className":"cc-faq__item"} -->' . "\n" . '<details class="wp-block-details cc-faq__item"><summary>' . esc_html( $faq['q'] ) . "</summary>\n"
			. cc_modern_ind_p( $faq['a'] ) . "</details>\n<!-- /wp:details -->\n";
	}
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close();
	$out .= cc_modern_ind_close( 'section' );

	/* ---- Consultation form (shared pattern) ---------------------------- */
	ob_start();
	include CC_MODERN_DIR . '/patterns/contact-form.php';
	$out .= ob_get_clean();

	/* ---- FAQ structured data ------------------------------------------- */
	$entities = array();
	foreach ( $d['faq']['items'] as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	$schema = wp_json_encode(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	$out .= "<!-- wp:html -->\n<script type=\"application/ld+json\">" . str_replace( '</', '<\/', $schema ) . "</script>\n<!-- /wp:html -->\n";

	$out .= cc_modern_ind_close();

	return $out;
}

/**
 * Author line under a client quote.
 *
 * @param array $quote Quote data with name and optional role.
 * @return string
 */
function cc_modern_ind_quote_author( array $quote ) {
	$role = isset( $quote['role'] ) && '' !== $quote['role'] ? '<small>' . esc_html( $quote['role'] ) . '</small>' : '';

	return '<!-- wp:paragraph {"className":"cc-quote__author"} -->' . "\n" . '<p class="cc-quote__author"><span class="cc-quote__avatar" aria-hidden="true">' . esc_html( strtoupper( substr( $quote['name'], 0, 1 ) ) ) . '</span><span><strong>' . esc_html( $quote['name'] ) . '</strong>' . $role . "</span></p>\n<!-- /wp:paragraph -->\n";
}
