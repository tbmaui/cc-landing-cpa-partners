<?php
/** Generate a local page-content preview; the installed theme supplies the site header/footer. */
$cc_preview_dir = dirname( __DIR__ ) . '/build';
if ( ! is_dir( $cc_preview_dir ) ) {
	mkdir( $cc_preview_dir, 0777, true );
}
ob_start();
require __DIR__ . '/render-preview.php';
$cc_preview_html = ob_get_clean();
file_put_contents( $cc_preview_dir . '/preview.html', $cc_preview_html );
echo "Created build/preview.html\n";
