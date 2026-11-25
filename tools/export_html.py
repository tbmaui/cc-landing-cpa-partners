"""Export a page repository's reviewed WordPress content as deployable HTML.

Usage: python tools/export_html.py [--repo PATH] [--php PATH] [--asset-base URL]
Install tools/requirements-export.txt before rebuilding. The delivered HTML
itself needs no PHP, Python, npm, React or WordPress runtime.
"""

import argparse
import html
import json
import re
import shutil
import subprocess
from pathlib import Path

import tinycss2

# Where the live site serves this package's assets/ folder. The HTML links every
# image, stylesheet and script from here. Change this one value (or pass
# --asset-base) and rebuild if the folder is hosted somewhere else.
ASSET_BASE = "/assets/"

# Families the page CSS uses. They are renamed to page-only names in the export
# so the page's @font-face rules can never replace a font the host site uses.
PAGE_FONT_FAMILIES = ("Montserrat", "Old Standard TT")


def scope_css(css, scope, root_class=None):
    """Scope selectors while retaining fonts, media queries and keyframes."""
    def selector(tokens):
        parts, current = [], []
        for token in tokens:
            if token.type == "literal" and token.value == ",":
                parts.append(tinycss2.serialize(current).strip())
                current = []
            else:
                current.append(token)
        parts.append(tinycss2.serialize(current).strip())
        result = []
        for item in parts:
            if not item:
                continue
            if ":root" in item:
                item = item.replace(":root", scope)
            elif re.match(r"^(?:html|body)(?=[\s.:#>\[]|$)", item):
                item = re.sub(r"^(?:html|body)", scope, item, count=1)
            elif root_class and item.startswith(root_class):
                item = scope + item[len(root_class):]
            else:
                item = scope + " " + item
            result.append(item)
        return ",\n".join(result)

    def rules(items):
        output = []
        for rule in items:
            if rule.type == "error":
                raise ValueError(f"CSS parse error: {rule.message}")
            if rule.type == "qualified-rule":
                output.append(selector(rule.prelude) + " {" + tinycss2.serialize(rule.content) + "}")
            elif rule.type == "at-rule" and rule.content is not None and rule.lower_at_keyword in {
                "media", "supports", "layer", "container", "scope", "starting-style"
            }:
                nested = tinycss2.parse_rule_list(rule.content, skip_whitespace=True)
                output.append("@" + rule.at_keyword + tinycss2.serialize(rule.prelude) + "{" + rules(nested) + "}")
            else:
                output.append(tinycss2.serialize([rule]))
        return "\n".join(output)

    return rules(tinycss2.parse_stylesheet(css, skip_whitespace=True))


def private_font_names(css, asset_name):
    """Give each page font a page-only family name so @font-face cannot leak."""
    label = asset_name.replace("-", " ").title()
    for family in PAGE_FONT_FAMILIES:
        private = f'"CC {label} {family}"'
        css = re.sub(r'(["\'])' + re.escape(family) + r'\1', private, css)
        css = re.sub(r'(?<![\w"\'-])' + re.escape(family) + r'(?![\w"\'-])', private, css)
    return css


def link_assets(markup, base):
    """Point HTML asset links at the hosted assets folder."""
    return re.sub(r'(\b(?:src|href|srcset|poster)=["\'])assets/', lambda m: m.group(1) + base, markup)


def export_page(repo, php, asset_base=ASSET_BASE):
    metadata = json.loads((repo / "page.json").read_text(encoding="utf-8"))
    if not metadata.get("wordpress_pattern"):
        raise ValueError("A reviewed WordPress pattern is required for a matching HTML export.")
    rendered = subprocess.run(
        [php, "tools/render-preview.php"], cwd=repo, capture_output=True, check=True
    ).stdout.decode("utf-8")
    body = re.search(r"<body[^>]*>(.*?)</body>", rendered, re.S).group(1)
    body = re.sub(r"<!--\s*/?wp:.*?-->", "", body, flags=re.S)
    body = re.sub(r"<script\b[^>]*src=[\"'][^\"']+[\"'][^>]*></script>", "", body)
    if "<?php" in body:
        raise ValueError("An unresolved PHP tag remains in the export.")

    dest = repo / "html"
    # Rebuild only this export's assets, removing stale fonts and generic names.
    assets = (dest / "assets").resolve()
    if assets.parent != dest.resolve() or dest.resolve().parent != repo.resolve():
        raise ValueError("Export assets must remain inside this page repository.")
    if assets.exists():
        shutil.rmtree(assets)
    (dest / "assets/css").mkdir(parents=True, exist_ok=True)
    (dest / "assets/js").mkdir(parents=True, exist_ok=True)
    for directory in ("fonts", "images"):
        source = repo / "wordpress/assets" / directory
        if source.exists():
            shutil.copytree(source, dest / "assets" / directory, dirs_exist_ok=True)

    slug = metadata["repository"].split("/")[-1]
    asset_name = slug.removeprefix("cc-landing-").removeprefix("cc-page-")
    if asset_name == "about":
        asset_name = "about-us"
    asset_paths = {}
    for directory in ("fonts", "images"):
        for asset in sorted((dest / "assets" / directory).rglob("*")):
            if asset.is_file():
                old = asset.relative_to(dest / "assets").as_posix()
                renamed = asset.with_name(asset_name + "-" + asset.name)
                asset.rename(renamed)
                asset_paths[old] = renamed.relative_to(dest / "assets").as_posix()

    def rewrite_assets(value):
        for old, new in asset_paths.items():
            value = value.replace("assets/" + old, "assets/" + new)
            value = value.replace("../" + old, "../" + new)
        return value

    page_id = "cc-html-" + slug
    scope = "#" + page_id
    body = body.replace("../wordpress/assets/../assets/", "assets/").replace("../wordpress/assets/", "assets/")
    body = rewrite_assets(body)
    css = "\n".join(re.findall(r"<style[^>]*>(.*?)</style>", rendered, re.S))
    css += "\n" + (repo / "wordpress/assets/css/theme.css").read_text(encoding="utf-8")
    # Native previews use font URLs relative to build/; deployment CSS lives at assets/css/.
    css = css.replace("../wordpress/assets/", "../")
    css = rewrite_assets(css)
    css = scope_css(css, scope, ".wp-site-blocks")
    css = private_font_names(css, asset_name)
    css += f"\n{scope} {{ display:block; margin:0; }}\n{scope} [hidden] {{ display:none !important; }}\n"
    cpa = repo / "wordpress/assets/css/cpa-partners.css"
    if cpa.exists():
        css += private_font_names(scope_css(cpa.read_text(encoding="utf-8"), scope), asset_name)
    # The reset must be emitted before component rules so their spacing wins.
    # The preview renderer emits the flow reset last; preserve its original specificity
    # by moving it before the component stylesheet in this scoped bundle.
    reset = f"{scope} :where(.is-layout-flow) > * {{ margin-block-start:0; margin-block-end:0; }}\n"
    css = reset + css
    (dest / f"assets/css/{asset_name}.css").write_text(css, encoding="utf-8", newline="\n")

    js = (repo / "wordpress/assets/js/enhance.js").read_text(encoding="utf-8")
    js = js.replace("'use strict';", "'use strict';\n\tvar ccPageRoot = document.getElementById(" + json.dumps(page_id) + ");\n\tif (!ccPageRoot || ccPageRoot.dataset.ccHtmlReady) return;\n\tccPageRoot.dataset.ccHtmlReady = 'true';", 1)
    js = js.replace("document.querySelectorAll(", "ccPageRoot.querySelectorAll(").replace("document.querySelector(", "ccPageRoot.querySelector(")
    js = js.replace("\t\tinitVoiceAgentDock();", "\t\t// The optional hosted voice widget is configured separately by the site admin.")
    (dest / f"assets/js/{asset_name}.js").write_text(js, encoding="utf-8", newline="\n")
    scripts = f'<script src="assets/js/{asset_name}.js" defer></script>'
    cpa_js = repo / "wordpress/assets/js/cpa-partners.js"
    if cpa_js.exists():
        shutil.copy2(cpa_js, dest / f"assets/js/{asset_name}-calculator.js")
        scripts += f'\n<script src="assets/js/{asset_name}-calculator.js" defer></script>'

    if not asset_base.endswith("/"):
        asset_base += "/"
    body = link_assets(body, asset_base)
    scripts = link_assets(scripts, asset_base)
    content = f'<main id="{page_id}" class="cc-static-page wp-site-blocks">\n{body.strip()}\n</main>\n'
    (dest / "content.html").write_text(content, encoding="utf-8", newline="\n")
    title = metadata["page"] + " | Complete Controller"
    description = metadata.get("html_description", "Complete Controller — " + metadata["page"] + ".")
    document = '<!doctype html>\n<html lang="en">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n'
    document += "<title>" + html.escape(title) + '</title>\n<meta name="description" content="' + html.escape(description, quote=True) + '">\n'
    document += f'<link rel="stylesheet" href="{asset_base}css/{asset_name}.css">\n<style>body{{margin:0}}</style>\n</head>\n<body>\n' + content + scripts + '\n</body>\n</html>\n'
    (dest / "index.html").write_text(document, encoding="utf-8", newline="\n")

    script_instructions = scripts
    font_label = asset_name.replace("-", " ").title()
    instructions = f"""# {metadata['page']} — plain HTML option

This folder is a deployment deliverable. It contains no WordPress/PHP/React dependency, Gutenberg comments or build step. Fonts and images are local.

## Option A: standalone document

Upload `assets/` so it is served at `{asset_base}`, then serve `index.html`. This is the complete page-content document. Your web admin can add the site's header, footer and navigation around it; they are not duplicated in this export. The optional CAS-i voice widget is not automatically embedded.

## Option B: content inside the existing site

1. Upload `assets/` so it is served at `{asset_base}` on the site. All asset filenames start with `{asset_name}` so the five page packages can share one assets folder without overwriting one another. Keep the css, fonts, images and js subfolders.
2. Load `{asset_base}css/{asset_name}.css` in the document head.
3. Place the contents of `content.html` in the site's content area. It contains its own main landmark; if your template already supplies `<main>`, change this outer tag to `<div>` while preserving its ID and classes.
4. Every image, stylesheet and script link in this package is written as `{asset_base}...`. If the folder lives somewhere else, rebuild with `python tools/export_html.py --asset-base /your/path/` (or change `ASSET_BASE` in that script) instead of editing links by hand.
5. Load the scripts once, after the fragment or with `defer`:

```html
{script_instructions}
```

Every CSS rule is scoped to `{scope}` so it cannot style the site's header, footer or menus. The page's fonts are registered under page-only names (`CC {font_label} Montserrat` and `CC {font_label} Old Standard TT`), so they never replace a font the site already uses. Font files load relative to the stylesheet, so they need no path changes. The JavaScript also scopes its page-content queries. Use one copy of this page per document. Keep the wrapper ID/classes, field names and calculator/dialog IDs intact. A restrictive CMS may strip scripts, styles or forms from pasted content; enqueue those files through the site's supported admin/developer mechanism instead of pasting a full HTML document into an editor.

## Forms and launch configuration

The consultation form, where present, preserves the existing theme's HubSpot integration. Production submissions require an HTTPS origin permitted by that service. No test leads were sent while preparing this package. The CPA application/referral forms are preview-only, and its commission terms are illustrative pending approval; connect an approved server/CRM route before launch. Do not treat a preview confirmation as a delivered lead.

Review the intended page URL, navigation links, SEO/canonical settings, form field mappings and any tracking configuration before publishing. Neither an HTML upload nor a theme update overwrites existing hosted pages automatically.

## Updating this export

Maintain the reviewed WordPress pattern/shared assets in the repository, then rebuild this HTML with `python tools/export_html.py` after installing `tools/requirements-export.txt`. PHP 8+ is needed only to rebuild, not to host the exported HTML.
"""
    (dest / "README.md").write_text(instructions, encoding="utf-8", newline="\n")
    print(f"Exported {metadata['page']}: {dest}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo", type=Path, default=Path(__file__).resolve().parents[1])
    parser.add_argument("--php", default="php")
    parser.add_argument("--asset-base", default=ASSET_BASE,
                        help="URL path where the assets/ folder is hosted (default: %(default)s)")
    args = parser.parse_args()
    export_page(args.repo.resolve(), args.php, args.asset_base)
