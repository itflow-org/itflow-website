"""Turn ITFlow screenshots into the site's screenshot files.

    python3 tools/webp.py SOURCE_FOLDER

SOURCE_FOLDER holds PNG screenshots named NAME-light.png and NAME-dark.png
(or just NAME.png for something with one look, like the client portal). Take
them in a browser window 1440 x 900 at 150% zoom or a high-DPI screen, so they
are about 2160 pixels wide. For each, this writes NAME-light-1280.webp and
NAME-light-2160.webp (and the dark ones) into assets/img/shots/, trimming the
scrollbar off the right-hand edge if there is one. Needs Python 3 and Pillow
(pip install pillow).
"""
import os
import sys
from PIL import Image

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'assets', 'img', 'shots')


def trim_scrollbar(im):
    """A plain, light strip down the right edge is the page's scrollbar gutter - cut it off, keeping 16:10."""
    rgb = im.convert('RGB')
    w, h = rgb.size
    x = w - 1
    while x > w * 0.95 and len({rgb.getpixel((x, y)) for y in range(0, h, max(1, h // 40))}) == 1:
        x -= 1
    if x < w - 1:
        rgb = rgb.crop((0, 0, x + 1, min(h, round((x + 1) / 1.6))))
    return rgb


def main(src):
    os.makedirs(OUT, exist_ok=True)
    for name in sorted(os.listdir(src)):
        if not name.lower().endswith('.png'):
            continue
        base = name[:-4]
        im = trim_scrollbar(Image.open(os.path.join(src, name)))
        for width in (1280, 2160):
            w = min(width, im.width)
            out = im if im.width == w else im.resize((w, round(im.height * w / im.width)), Image.LANCZOS)
            out.save(os.path.join(OUT, f'{base}-{width}.webp'), 'WEBP', quality=80, method=6)
        print('wrote', base)


if __name__ == '__main__':
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    main(sys.argv[1])
