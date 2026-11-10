# CPA Partners — plain HTML option

This folder is a deployment deliverable. It contains no WordPress/PHP/React dependency, Gutenberg comments or build step. Fonts and images are local.

## Option A: standalone document

Upload this entire folder, keeping its relative paths, and serve `index.html`. This is the complete page-content document. Your web admin can add the site's header, footer and navigation around it; they are not duplicated in this export. The optional CAS-i voice widget is not automatically embedded.

## Option B: content inside the existing site

1. Upload `assets/` to the chosen page folder.
2. Load `assets/css/page.css` in the document head.
3. Place the contents of `content.html` in the site's content area. It contains its own main landmark; if your template already supplies `<main>`, change this outer tag to `<div>` while preserving its ID and classes.
4. Update the fragment's `assets/...` image paths if the asset folder is hosted at a different URL.
5. Load the scripts once, after the fragment or with `defer`:

```html
<script src="/YOUR-PAGE/assets/js/page.js" defer></script>
<script src="/YOUR-PAGE/assets/js/cpa-partners.js" defer></script>
```

The CSS is scoped to `#cc-html-cc-landing-cpa-partners` to protect the surrounding site. The JavaScript also scopes its page-content queries. Use one copy of this page per document. Keep the wrapper ID/classes, field names and calculator/dialog IDs intact. A restrictive CMS may strip scripts, styles or forms from pasted content; enqueue those files through the site's supported admin/developer mechanism instead of pasting a full HTML document into an editor.

## Forms and launch configuration

The consultation form, where present, preserves the existing theme's HubSpot integration. Production submissions require an HTTPS origin permitted by that service. No test leads were sent while preparing this package. The CPA application/referral forms are preview-only, and its commission terms are illustrative pending approval; connect an approved server/CRM route before launch. Do not treat a preview confirmation as a delivered lead.

Review the intended page URL, navigation links, SEO/canonical settings, form field mappings and any tracking configuration before publishing. Neither an HTML upload nor a theme update overwrites existing hosted pages automatically.

## Updating this export

Maintain the reviewed WordPress pattern/shared assets in the repository, then rebuild this HTML with `python tools/export_html.py` after installing `tools/requirements-export.txt`. PHP 8+ is needed only to rebuild, not to host the exported HTML.
