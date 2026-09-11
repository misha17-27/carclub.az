#!/usr/bin/env python3
"""
Build responsive variants for every photo under assets/img.

For each source image two widths are produced, each as WebP and as JPEG:

    kia-sorento-2020-1.jpeg
      -> kia-sorento-2020-1-800.webp   kia-sorento-2020-1-800.jpg
      -> kia-sorento-2020-1-1400.webp  kia-sorento-2020-1-1400.jpg

The originals stay untouched, so nothing is lost and the run is repeatable.
picture() in includes/functions.php picks the variants up automatically; if
they are missing it falls back to the original file, so the site works either
way.

    python tools/optimize-images.py [--force]
"""

import os
import sys

try:
    from PIL import Image
except ImportError:
    sys.exit('Pillow is required:  python -m pip install Pillow')

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
IMG = os.path.join(ROOT, 'assets', 'img')

WIDTHS = [400, 800, 1400]
Q_WEBP = 74
Q_JPEG = 78
SOURCE_EXT = {'.jpg', '.jpeg', '.png'}
SKIP_DIRS = {'brands'}          # small logos, nothing to gain
SKIP_FILES = {'logo.png', 'favicon.png', 'logo.jpg'}   # transparency / tiny
VARIANT = ('-400.', '-800.', '-1400.')


def is_variant(name):
    return any(v in name for v in VARIANT)


def build(src, force=False):
    made, saved = 0, 0
    name, ext = os.path.splitext(src)
    try:
        im = Image.open(src)
        im.load()
    except Exception as e:
        print('  skip %s (%s)' % (os.path.basename(src), e))
        return 0, 0

    if im.mode in ('P', 'LA'):
        im = im.convert('RGBA' if 'transparency' in im.info else 'RGB')
    rgb = im.convert('RGB') if im.mode not in ('RGB', 'RGBA') else im

    for w in WIDTHS:
        if im.width <= w and w != WIDTHS[-1]:
            # smaller than this step: only makes sense for the largest one
            target = im
        else:
            ratio = min(1.0, w / im.width)
            target = im if ratio == 1.0 else im.resize(
                (max(1, round(im.width * ratio)), max(1, round(im.height * ratio))),
                Image.LANCZOS)

        for out_ext, kwargs in (('.webp', dict(quality=Q_WEBP, method=6)),
                                ('.jpg', dict(quality=Q_JPEG, optimize=True, progressive=True))):
            dst = '%s-%d%s' % (name, w, out_ext)
            if os.path.isfile(dst) and not force:
                continue
            pic = target if out_ext == '.webp' else target.convert('RGB')
            pic.save(dst, **kwargs)
            made += 1
            saved += os.path.getsize(src) - os.path.getsize(dst)
    return made, saved


def main():
    force = '--force' in sys.argv
    total_made = 0
    before = after = 0
    for root, dirs, files in os.walk(IMG):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for f in sorted(files):
            ext = os.path.splitext(f)[1].lower()
            if ext not in SOURCE_EXT or is_variant(f) or f in SKIP_FILES:
                continue
            src = os.path.join(root, f)
            before += os.path.getsize(src)
            made, _ = build(src, force)
            total_made += made
            base = os.path.splitext(src)[0]
            small = base + '-800.webp'
            if os.path.isfile(small):
                after += os.path.getsize(small)

    print('variants written : %d' % total_made)
    if before:
        print('originals        : %.1f MB' % (before / 1048576))
        print('800px webp set   : %.1f MB  (%.0f%% of the originals)'
              % (after / 1048576, after / before * 100))


if __name__ == '__main__':
    main()
