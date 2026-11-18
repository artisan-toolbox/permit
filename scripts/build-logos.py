"""Build Permit's vector identity with the Artisan Toolbox palette and wordmark."""

import argparse
from pathlib import Path

from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.ttLib import TTFont

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--font", default="/System/Library/Fonts/Avenir Next.ttc")
parser.add_argument("--font-number", type=int, default=0)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
font = TTFont(args.font, fontNumber=args.font_number)
glyphs = font.getGlyphSet()
cmap = font.getBestCmap()
scale = 44 / font["head"].unitsPerEm
pen = SVGPathPen(glyphs, ntos=lambda value: f"{value:.2f}")
x = 80
for letter in "Permit":
    name = cmap[ord(letter)]
    glyphs[name].draw(TransformPen(pen, (scale, 0, 0, -scale, x, 47)))
    x += font["hmtx"][name][0] * scale

artwork = '''  <rect x="2" y="2" width="60" height="60" rx="14" fill="#20252B"/>
  <rect x="2.75" y="2.75" width="58.5" height="58.5" rx="13.25" fill="none" stroke="#FFFFFF" stroke-opacity=".16" stroke-width="1.5"/>
  <path d="m30 11 17 6v12c0 12-8 19-17 24-9-5-17-12-17-24V17Z" fill="#FFF7EF"/>
  <path d="m30 11 17 6v12c0 12-8 19-17 24Z" fill="#FF9A86"/>
  <path d="M34 26a4 4 0 1 0-6 3.46V36h4v-6.54A4 4 0 0 0 34 26Z" fill="#20252B"/>
  <circle cx="44" cy="43" r="11" fill="#FF5947" stroke="#20252B" stroke-width="2.5"/>
  <path d="m39 43 3.5 3.5 6.5-7" fill="none" stroke="#FFF7EF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
'''
output = root / "logo"
output.mkdir(exist_ok=True)
for theme, color in (("icon", None), ("light", "#20252B"), ("dark", "#FFF7EF")):
    width = 64 if color is None else round(x + 4)
    wordmark = "" if color is None else f'  <path fill="{color}" d="{pen.getCommands()}"/>\n'
    title = "Permit icon" if color is None else "Permit"
    (output / f"permit-{theme}.svg").write_text(
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} 64" role="img" aria-labelledby="title">\n'
        f'  <title id="title">{title}</title>\n{artwork}{wordmark}</svg>\n'
    )
