"""Generate display-sized WebP assets; retain originals for full-size previews."""
from pathlib import Path
from PIL import Image

root = Path(__file__).resolve().parents[1]
jobs = [
    ('public/images/site-brand-mark-shield-v2.png', 128),
    ('public/images/home-reference-avatars.png', 170),
    ('public/images/authors/jakub-wisniewski.png', 128),
    ('public/images/authors/katarzyna-wisniewska.png', 128),
    ('public/images/partners/ministerstwo-infrastruktury.png', 452),
    ('public/images/partners/cepik-gov.png', 506),
    ('resources/images/analytics/cookie-mascot.png', 144),
    ('resources/images/home/hero-mobile.png', 298),
    ('resources/images/home/mobile-app-banner.webp', 1440),
    ('resources/images/home/mistakes-learning-poster.jpg', 640),
]
jobs += [(str(p.relative_to(root)), 640) for p in (root / 'resources/images/home/proof').glob('*.webp')]
jobs += [(str(p.relative_to(root)), 148) for p in (root / 'resources/images/home/contact').glob('advisor-*.jpg')]
saved = 0
for relative, width in jobs:
    source = root / relative
    target = source.with_name(source.stem + '-optimized.webp')
    with Image.open(source) as im:
        im.thumbnail((width, width * 4), Image.Resampling.LANCZOS)
        im.save(target, 'WEBP', quality=82, method=6)
    saved += source.stat().st_size - target.stat().st_size
    print(f'{target.relative_to(root)}: {target.stat().st_size} bytes')
print(f'Total saved: {saved:,} bytes')
