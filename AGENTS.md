# Page repository guidelines

Keep the supplied page content and WordPress implementation consistent. These repositories depend on cc-modern 1.3.14; do not turn an overlay into an installable theme without supplying the complete theme structure. Preserve the shared design tokens and established page style.

Use tab-indented WordPress PHP, prefixed snake_case helpers, escaped dynamic output and valid Gutenberg markup. If WordPress tools are present, run `node tools/validate-blocks.js` and PHP syntax checks after changing WordPress files. Preview at 390, 768, 1024, 1440 and 1920 pixels for layout changes; inspect changed patterns in the actual WordPress editor before claiming compatibility.

Do not commit credentials, node_modules, generated previews, screenshots, archives or environment files. Do not overwrite existing WordPress pages automatically. Shared theme files are versioned snapshots: coordinate updates with the parent theme and the sibling page repositories. Preserve original source exports for provenance. Follow WRITING-STYLE.md for copy changes.

The html/ directory is an intentional delivery format requested by the user and must be tracked. Rebuild it with tools/export_html.py after changing its WordPress source; generated scratch previews remain under ignored build/.
