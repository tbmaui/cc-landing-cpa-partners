"""Check exported pages independently and with all assets merged into one folder.

Usage: python tools/check-delivery.py --repo PATH [--repo PATH ...]
Requires playwright, fonttools[woff] and an installed Chromium browser.
Screenshots and reports are written only under ignored build/delivery-check/.
"""
import argparse
import functools
import json
import re
import shutil
import threading
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import urljoin

from fontTools.ttLib import TTFont
from playwright.sync_api import sync_playwright

WIDTHS = (390, 768, 1024, 1440, 1920)
APPROVED = {'Montserrat', 'Old Standard TT'}


def private_family(asset_name, family):
    # Matches tools/export_html.py: page fonts use page-only family names.
    return f"CC {asset_name.replace('-', ' ').title()} {family}"


class QuietHandler(SimpleHTTPRequestHandler):
    def log_message(self, *args):
        pass


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--repo', type=Path, action='append', required=True)
    parser.add_argument('--chromium', type=Path)
    args = parser.parse_args()
    repos = [p.resolve() for p in args.repo]
    output = repos[0] / 'build/delivery-check'
    output.mkdir(parents=True, exist_ok=True)
    merged = output / 'merged'
    if merged.exists():
        assert merged.resolve().parent == output.resolve()
        shutil.rmtree(merged)
    merged.mkdir()
    asset_owners = {}
    pages = []
    for repo in repos:
        metadata = json.loads((repo / 'page.json').read_text(encoding='utf-8'))
        slug = metadata['repository'].split('/')[-1]
        asset_name = slug.removeprefix('cc-landing-').removeprefix('cc-page-')
        asset_name = 'about-us' if asset_name == 'about' else asset_name
        for asset in (repo / 'html/assets').rglob('*'):
            if not asset.is_file():
                continue
            relative = asset.relative_to(repo / 'html').as_posix()
            assert relative not in asset_owners, f'Asset collision: {relative}'
            asset_owners[relative] = slug
            target = merged / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(asset, target)
            if asset.suffix == '.woff2':
                with TTFont(asset) as font:
                    family_id = 16 if any(n.nameID == 16 for n in font['name'].names) else 1
                    families = {n.toUnicode() for n in font['name'].names if n.nameID == family_id}
                    # Google's variable Montserrat labels its default 100-weight
                    # instance "Montserrat Thin" in the legacy name table.
                    if 'fvar' in font and families == {'Montserrat Thin'}:
                        families = {'Montserrat'}
                    assert families <= APPROVED, (asset, families)
            if asset.suffix == '.css':
                text = asset.read_text(encoding='utf-8')
                for family in re.findall(r'font-family\s*:\s*([^;}]+)', text):
                    assert not re.search(r'Cormorant Garamond|Helvetica|Georgia|Times New Roman', family, re.I), (asset, family)
        document = (repo / 'html/index.html').read_text(encoding='utf-8')
        assert 'assets/css/page.css' not in document and 'assets/js/page.js' not in document
        (merged / (slug + '.html')).write_text(document, encoding='utf-8')
        assert re.search(r'(?:src|href)="assets/', document) is None, (slug, 'relative asset link')
        pages.append((slug, metadata, asset_name))

    handler = functools.partial(QuietHandler, directory=str(merged))
    server = ThreadingHTTPServer(('127.0.0.1', 0), handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    base = f'http://127.0.0.1:{server.server_port}'
    results = []
    with sync_playwright() as playwright:
        launch = {'headless': True}
        if args.chromium:
            launch['executable_path'] = str(args.chromium)
        browser = playwright.chromium.launch(**launch)
        for slug, metadata, asset_name in pages:
            fonts = {private_family(asset_name, f): f for f in APPROVED}
            montserrat = private_family(asset_name, 'Montserrat')
            old_standard = private_family(asset_name, 'Old Standard TT')
            errors = []
            page = browser.new_page()
            page.on('pageerror', lambda error: errors.append(str(error)))
            page.on('requestfailed', lambda request: errors.append('Request failed: ' + request.url))
            page.on('response', lambda response: errors.append(f'HTTP {response.status}: {response.url}') if response.status >= 400 else None)
            for width in WIDTHS:
                page.set_viewport_size({'width': width, 'height': 1000})
                page.goto(f'{base}/{slug}.html', wait_until='networkidle')
                page.evaluate('''async ([sans, serif]) => {
                    await document.fonts.load(`400 16px "${sans}"`);
                    await document.fonts.load(`400 32px "${serif}"`);
                    await document.fonts.load(`italic 400 32px "${serif}"`);
                    await document.fonts.ready;
                }''', [montserrat, old_standard])
                findings = page.evaluate('''() => {
                    const root = document.querySelector('main');
                    const text = [...root.querySelectorAll('*')].filter(el =>
                        [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim()) && el.getClientRects().length);
                    const fonts = [...new Set(text.map(el => getComputedStyle(el).fontFamily.split(',')[0].trim().replaceAll('"', '')))];
                    return { fonts, overflow: document.documentElement.scrollWidth > innerWidth + 1,
                        brokenImages: [...document.images].filter(img => !img.complete || !img.naturalWidth).map(img => img.src),
                        heading: getComputedStyle(root.querySelector('h1')).fontFamily };
                }''')
                assert set(findings['fonts']) <= set(fonts), (slug, width, findings)
                assert not findings['overflow'], (slug, width, 'horizontal overflow')
                assert not findings['brokenImages'], (slug, width, findings['brokenImages'])
                assert findings['heading'].startswith(f'"{old_standard}"'), (slug, findings)
                assert not errors, (slug, width, errors)
                if width in (390, 1440):
                    page.screenshot(path=str(output / f'{slug}-{width}.png'), full_page=True)
                results.append({'page': slug, 'width': width, **findings})
            # Check embedded CSS does not change surrounding host typography/layout.
            css = re.search(r'<link rel="stylesheet" href="([^"]+)"', (merged / (slug + '.html')).read_text(encoding='utf-8')).group(1)
            fragment = (repos[[s for s, *_ in pages].index(slug)] / 'html/content.html').read_text(encoding='utf-8')
            page.goto(base)
            # A host header that uses the same family names as the page must not change.
            page.set_content('''<style>@font-face{font-family:"Montserrat";src:local("DejaVu Sans")}
                header a{font:500 15px/1.4 Montserrat,sans-serif;letter-spacing:.02em;text-transform:uppercase;color:#123456}</style>
                <header id="host-header"><nav><a href="#">Services</a></nav></header>
                <p id="host" style="font:17px monospace;margin:19px">Host text</p>
                <h2 id="host-h2">Host heading</h2><button id="host-button">Host button</button>''')
            probe = '''() => [...document.querySelectorAll('#host, #host-header a, #host-h2, #host-button')].map(el => {
                const s = getComputedStyle(el);
                return [s.fontFamily, s.fontSize, s.fontWeight, s.letterSpacing, s.lineHeight, s.color, s.margin, el.getBoundingClientRect().height];
            })'''
            before = page.evaluate(probe)
            page.add_style_tag(url=urljoin(base + '/', css))
            page.locator('body').evaluate('(el, html) => el.insertAdjacentHTML("beforeend", html)', fragment)
            page.evaluate('() => document.fonts.ready')
            after = page.evaluate(probe)
            assert before == after, (slug, 'Host styles changed', before, after)
            public_faces = page.evaluate('''(names) => [...document.fonts].map(f => f.family.replaceAll('"', ''))
                .filter(f => names.includes(f))''', sorted(APPROVED))
            assert public_faces == ['Montserrat'], (slug, 'Page registered a public font family', public_faces)
            page.close()
            print(f'PASS {slug}: five widths, approved fonts, loaded italic, merged assets, isolated host header and styles')
        browser.close()
    server.shutdown()
    (output / 'results.json').write_text(json.dumps(results, indent=2), encoding='utf-8')
    print(f'PASS {len(asset_owners)} unique assets; {len(results)} responsive checks; report: {output}')


if __name__ == '__main__':
    main()
