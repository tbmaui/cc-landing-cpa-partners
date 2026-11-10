"""Export a page repository's reviewed WordPress content as deployable HTML.

Usage: python tools/export_html.py [--repo PATH] [--php PATH]
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


def export_page(repo, php):
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
    (dest / "assets/css").mkdir(parents=True, exist_ok=True)
    (dest / "assets/js").mkdir(parents=True, exist_ok=True)
    for directory in ("fonts", "images"):
        source = repo / "wordpress/assets" / directory
        if source.exists():
            shutil.copytree(source, dest / "assets" / directory, dirs_exist_ok=True)

    slug = metadata["repository"].split("/")[-1]
    page_id = "cc-html-" + slug
    scope = "#" + page_id
    body = body.replace("../wordpress/assets/../assets/", "assets/").replace("../wordpress/assets/", "assets/")
    css = "\n".join(re.findall(r"<style[^>]*>(.*?)</style>", rendered, re.S))
    css += "\n" + (repo / "wordpress/assets/css/theme.css").read_text(encoding="utf-8")
    # Native previews use font URLs relative to build/; deployment CSS lives at assets/css/.
    css = css.replace("../wordpress/assets/", "../")
    css = scope_css(css, scope, ".wp-site-blocks")
    css += f"\n{scope} {{ display:block; margin:0; }}\n{scope} [hidden] {{ display:none !important; }}\n"
    cpa = repo / "wordpress/assets/css/cpa-partners.css"
    if cpa.exists():
        css += scope_css(cpa.read_text(encoding="utf-8"), scope)
    # The reset must be emitted before component rules so their spacing wins.
    # The preview renderer emits the flow reset last; preserve its original specificity
    # by moving it before the component stylesheet in this scoped bundle.
    reset = f"{scope} :where(.is-layout-flow) > * {{ margin-block-start:0; margin-block-end:0; }}\n"
    css = reset + css
    (dest / "assets/css/page.css").write_text(css, encoding="utf-8", newline="\n")

    js = (repo / "wordpress/assets/js/enhance.js").read_text(encoding="utf-8")
    js = js.replace("'use strict';", "'use strict';\n\tvar ccPageRoot = document.getElementById(" + json.dumps(page_id) + ");\n\tif (!ccPageRoot || ccPageRoot.dataset.ccHtmlReady) return;\n\tccPageRoot.dataset.ccHtmlReady = 'true';", 1)
    js = js.replace("document.querySelectorAll(", "ccPageRoot.querySelectorAll(").replace("document.querySelector(", "ccPageRoot.querySelector(")
    js = js.replace("\t\tinitVoiceAgentDock();", "\t\t// The optional hosted voice widget is configured separately by the site admin.")
    (dest / "assets/js/page.js").write_text(js, encoding="utf-8", newline="\n")
    scripts = '<script src="assets/js/page.js" defer></script>'
    cpa_js = repo / "wordpress/assets/js/cpa-partners.js"
    if cpa_js.exists():
        shutil.copy2(cpa_js, dest / "assets/js/cpa-partners.js")
        scripts += '\n<script src="assets/js/cpa-partners.js" defer></script>'

    content = f'<main id="{page_id}" class="cc-static-page wp-site-blocks">\n{body.strip()}\n</main>\n'
    (dest / "content.html").write_text(content, encoding="utf-8", newline="\n")
    title = metadata["page"] + " | Complete Controller"
    description = metadata.get("html_description", "Complete Controller — " + metadata["page"] + ".")
    document = '<!doctype html>\n<html lang="en">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n'
    document += "<title>" + html.escape(title) + '</title>\n<meta name="description" content="' + html.escape(description, quote=True) + '">\n'
    document += '<link rel="stylesheet" href="assets/css/page.css">\n<style>body{margin:0}</style>\n</head>\n<body>\n' + content + scripts + '\n</body>\n</html>\n'
    (dest / "index.html").write_text(document, encoding="utf-8", newline="\n")

    script_instructions = scripts.replace('src="assets/', 'src="/YOUR-PAGE/assets/')
    instructions = f"""# {metadata['page']} — plain HTML option

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
{script_instructions}
```

The CSS is scoped to `{scope}` to protect the surrounding site. The JavaScript also scopes its page-content queries. Use one copy of this page per document. Keep the wrapper ID/classes, field names and calculator/dialog IDs intact. A restrictive CMS may strip scripts, styles or forms from pasted content; enqueue those files through the site's supported admin/developer mechanism instead of pasting a full HTML document into an editor.

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
    args = parser.parse_args()
    export_page(args.repo.resolve(), args.php)
