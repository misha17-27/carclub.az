#!/usr/bin/env python3
"""
Build a static HTML snapshot of the site into html/.

Renders every page in every language from the running PHP server and rewrites
the links so the result works straight from disk (file://) as well as from a
plain web server.

    php -S 127.0.0.1:8031 router.php     # in one terminal
    python tools/build-static.py         # in another

Layout produced:
    html/index.html            -> redirects to en/
    html/<lang>/index.html     home
    html/<lang>/cars.html      listing
    html/<lang>/about.html
    html/<lang>/contact.html
    html/<lang>/404.html
    html/<lang>/car-<slug>.html
"""

import json
import os
import re
import shutil
import sys
import urllib.error
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
BASE = os.environ.get('CARCLUB_BASE', 'http://127.0.0.1:8031')
OUT = os.path.join(ROOT, 'html')

LANGS = {'en': '', 'az': 'az', 'ru': 'ru', 'ar': 'ar'}
SLUGS = {'cars': 'masinlar', 'about': 'haqqimizda', 'contact': 'elaqe'}
PAGES = ['home', 'cars', 'about', 'contact']


def fetch(path):
    try:
        with urllib.request.urlopen(BASE + path, timeout=30) as r:
            return r.read().decode('utf-8')
    except urllib.error.HTTPError as ex:          # 404 page is a real page here
        return ex.read().decode('utf-8')


def src_url(lang, page, slug=''):
    p = '/' + (LANGS[lang] + '/' if LANGS[lang] else '')
    if page == 'car':
        return p + slug + '/'
    if page == 'home':
        return p
    return p + SLUGS[page] + '/'


def out_name(page, slug=''):
    if page == 'home':
        return 'index.html'
    if page == 'car':
        return 'car-' + slug + '.html'
    return page + '.html'


def rewrite(html, lang, cars):
    """Turn absolute site URLs into relative paths inside html/<lang>/."""

    # assets: /assets/x.css?v=1 -> ../../assets/x.css?v=1
    # the ?v= is kept on purpose: without it a browser happily serves a stale
    # stylesheet from cache after the snapshot is rebuilt
    html = re.sub(r'(["\'(])/assets/([^"\'?)]+)(\?[^"\')]*)?',
                  lambda m: m.group(1) + '../../assets/' + m.group(2) + (m.group(3) or ''), html)

    # absolute URLs the server printed for canonical/og/hreflang: keep them,
    # they describe the live site, not this snapshot.

    # page links, longest first so /az/masinlar/ wins over /az/
    pairs = []
    for lg, prefix in LANGS.items():
        base = '/' + (prefix + '/' if prefix else '')
        rel = '' if lg == lang else '../' + lg + '/'
        for car in cars:
            pairs.append((base + car + '/', rel + out_name('car', car)))
        for page in ('cars', 'about', 'contact'):
            pairs.append((base + SLUGS[page] + '/', rel + out_name(page)))
        pairs.append((base, rel + 'index.html'))
    pairs.sort(key=lambda kv: -len(kv[0]))

    for src, dst in pairs:
        html = html.replace('href="' + src + '"', 'href="' + dst + '"')
        html = html.replace('href="' + src + '#', 'href="' + dst + '#')

    # forms cannot post anywhere in a static snapshot
    html = re.sub(r'(<form class="form" method="post" action=")[^"]*(")',
                  r'\1#\2 data-static="1"', html)

    return html


def main():
    try:
        urllib.request.urlopen(BASE, timeout=10)
    except Exception as ex:
        sys.exit('Dev server not reachable at %s (%s).\n'
                 'Start it first:  php -S 127.0.0.1:8031 router.php' % (BASE, ex))

    cars_file = os.path.join(ROOT, 'storage', 'cars.json')
    cars = [c['slug'] for c in json.load(open(cars_file, encoding='utf-8')) if c.get('published')]

    if os.path.isdir(OUT):
        shutil.rmtree(OUT)
    os.makedirs(OUT, exist_ok=True)

    total = 0
    for lang in LANGS:
        d = os.path.join(OUT, lang)
        os.makedirs(d, exist_ok=True)
        jobs = [(p, '') for p in PAGES] + [('car', s) for s in cars]
        for page, slug in jobs:
            html = rewrite(fetch(src_url(lang, page, slug)), lang, cars)
            with open(os.path.join(d, out_name(page, slug)), 'w', encoding='utf-8') as f:
                f.write(html)
            total += 1
        # 404
        html = rewrite(fetch('/' + (LANGS[lang] + '/' if LANGS[lang] else '') + 'page-not-found/'), lang, cars)
        open(os.path.join(d, '404.html'), 'w', encoding='utf-8').write(html)
        total += 1
        print('%-3s %2d pages' % (lang, len(jobs) + 1))

    with open(os.path.join(OUT, 'index.html'), 'w', encoding='utf-8') as f:
        f.write('<!DOCTYPE html>\n<html lang="en">\n<head>\n'
                '<meta charset="UTF-8">\n'
                '<meta http-equiv="refresh" content="0; url=en/index.html">\n'
                '<title>Carclub.az</title>\n</head>\n<body>\n'
                '<p>Redirecting to <a href="en/index.html">the English version</a>.</p>\n'
                '<ul>\n'
                '<li><a href="en/index.html">English</a></li>\n'
                '<li><a href="az/index.html">Azərbaycan</a></li>\n'
                '<li><a href="ru/index.html">Русский</a></li>\n'
                '<li><a href="ar/index.html">العربية</a></li>\n'
                '</ul>\n</body>\n</html>\n')

    print('\nWrote %d files to %s' % (total + 1, OUT))


if __name__ == '__main__':
    main()
