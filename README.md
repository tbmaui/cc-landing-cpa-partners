# Complete Controller ? CPA Partners

[View page in your browser](https://tbmaui.github.io/cc-landing-cpa-partners/)

Open the link above to see the styled HTML page with its bundled fonts and images. No download or HTML viewer is needed.

This page is available in **WordPress and plain HTML**, based on the reviewed cc-modern 1.3.15 theme styling.

[Download both formats from the latest release](https://github.com/tbmaui/cc-landing-cpa-partners/releases/latest).

## Choose a format

| Option | Files | Use |
|---|---|---|
| Plain HTML | `html/index.html` and `html/assets/` | Upload a complete page-content document without WordPress, PHP, React or npm. |
| HTML fragment | `html/content.html` and `html/assets/` | Place the page under the existing site's header/footer; load its scoped CSS and JavaScript as described in `html/README.md`. |
| WordPress | Full `cc-modern-1.3.15.zip` theme attached to the release | Upload the shared theme, create a Full Width review draft and insert this page's starter. |
| Existing theme integration | `wordpress/` | Page-specific files plus shared dependency snapshots for an existing cc-modern installation. This folder alone is not an installable theme. |

The standalone HTML contains page content; it does not duplicate a site's header, footer, navigation or optional voice widget. The CSS is scoped to the supplied page wrapper. Local fonts/images are included. No build step is needed to host the delivered HTML.

## WordPress installation

1. Back up the current theme and affected page. Upload the complete theme ZIP from the release through Appearance ? Themes ? Add New ? Upload Theme.
2. Create a review draft using the **Full Width** template.
3. Insert `cc-modern/page-cpa-partners` from **CC ? Whole Page Starters**. The starter importer can also create missing drafts; it skips existing URLs.
4. Preserve any later site-specific edits, CRM configuration and navigation. Review the draft before applying its content to an existing page. A theme update does not replace previously saved page content.

All page releases attach the same shared theme ZIP; it only needs to be installed once. The page repositories do not automatically synchronize the separate [parent theme repository](https://github.com/tbmaui/complete-controller-modern-theme).

The CPA integration now includes native editable text/group blocks, with Custom HTML blocks for the calculator, FAQs, forms, SVG illustrations and referral dialog. The calculator and accessible dialog retain the supplied behavior. **Application/referral submissions are preview-only**, and commission terms remain illustrative/pending approval. Connect an approved server/CRM route and finalize terms before publishing this page. No real application or referral was submitted during testing.

The supplied HTML reference remains under `source/index.html`. The maintained `html/` deliverable is generated from the reviewed WordPress pattern so both delivery formats share the same current content and theme styling.

## Updating and checking the page

```sh
node tools/validate-blocks.js
php tools/build-preview.php
python -m pip install -r tools/requirements-export.txt
python tools/export_html.py
```

The first two commands validate Gutenberg comments and create an ignored native preview. Rebuilding the deliverable requires PHP 8+, Python and the export dependency; serving the HTML requires none of these. Commit intentional changes under `html/` alongside the source pattern/assets. Scratch previews, archives, dependencies and environment files stay outside Git.

The WordPress patterns were inspected in the actual WordPress 6.6.3 editor. HTML layout checks cover 390, 768, 1024, 1440 and 1920px, with no horizontal overflow, broken images or JavaScript errors. Embedding checks confirm the page CSS does not change surrounding host text/layout. The CPA calculator/dialog and consultation request behavior were exercised without sending real leads.

See `html/README.md` for asset paths, supported embedding and launch configuration. Follow `WRITING-STYLE.md` for copy changes.

## Browser preview

GitHub Pages publishes the tracked `html/` export whenever that folder changes on `main`. You can also republish it from Actions > Publish page preview > Run workflow. The preview includes a noindex directive to discourage search indexing. It uses the standalone page content described above.

## November 24 package correction

Uses only the approved Old Standard TT and Montserrat brand fonts, including a local Old Standard italic face. Every HTML asset filename is page-specific, so the five packages can share one assets directory. Use release v1.1.1 and the shared cc-modern 1.3.15 theme; existing saved page content still needs a deliberate refresh. Original source exports are retained for provenance.

To repeat the font, asset collision and responsive checks, install `tools/requirements-check.txt`, run `python -m playwright install chromium`, then `python tools/check-delivery.py --repo .`. Supply several `--repo` paths to check all page packages together. Screenshots and results stay in ignored `build/delivery-check/`.


## November 25 package correction

Use release v1.1.2. Two fixes from staging:

- **Asset links start at the site root.** Every image, stylesheet and script link in `html/` is written as `/assets/...`, so the pages work at any URL without hand edits. The base path is one setting: rebuild with `python tools/export_html.py --asset-base /your/path/` (or change `ASSET_BASE` in that script) if the assets folder lives somewhere else. Font links in the stylesheet use the same base path.
- **Page fonts no longer touch the site header.** The page registered its fonts as plain `Montserrat` and `Old Standard TT`, which replaced the site's own fonts with those names, including in the header menu. The export now gives them page-only names, and `tools/check-delivery.py` checks a host header that uses the same font names stays unchanged.