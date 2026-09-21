# -*- coding: utf-8 -*-
"""
Genere les variantes .min.css / .min.js des assets du plugin.

    pip install rcssmin rjsmin
    python tools/minify-assets.py

Chaque .min commence par l'empreinte md5 de sa source ; le plugin ne la sert
que si cette empreinte correspond encore (voir ajth_asset_relative()). Apres
toute modification d'un fichier source, relancer ce script avant de deployer ;
sinon la source non minifiee est servie, sans rien casser.
"""
import hashlib
import io
import os
import sys

try:
    import rcssmin
    import rjsmin
except ImportError:  # pragma: no cover
    sys.exit("Installez d'abord les minifieurs : pip install rcssmin rjsmin")

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS = os.path.join(ROOT, 'assets')

total_before = total_after = 0
for sub, ext, minify in (('css', '.css', rcssmin.cssmin), ('js', '.js', rjsmin.jsmin)):
    folder = os.path.join(ASSETS, sub)
    for name in sorted(os.listdir(folder)):
        if not name.endswith(ext) or name.endswith('.min' + ext):
            continue
        src = os.path.join(folder, name)
        dst = src[:-len(ext)] + '.min' + ext
        # Empreinte calculee sur les octets du fichier, comme md5_file() cote PHP.
        raw = io.open(src, 'rb').read()
        source = raw.decode('utf-8')
        output = '/*! src-md5:' + hashlib.md5(raw).hexdigest() + ' */' + minify(source)
        io.open(dst, 'w', encoding='utf-8', newline='\n').write(output)
        total_before += len(raw)
        total_after += len(output.encode('utf-8'))
        print('%-45s %7d -> %7d octets' % (os.path.relpath(dst, ROOT), len(raw), len(output.encode('utf-8'))))

print('total : %d -> %d octets (-%d %%)' % (total_before, total_after, round(100 - total_after * 100.0 / max(1, total_before))))
