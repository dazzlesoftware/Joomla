"""Check menu controls have visible component settings, or an explicit menu-only reason."""
from pathlib import Path
import xml.etree.ElementTree as ET
import re
ROOT = Path(__file__).resolve().parents[2]
MENU_ONLY = {
    'id': 'Route target: selected post, author or category.',
    'tag_id': 'Route target: selected tag; directory defaults are separate.',
    'filter_tag': 'Category route filter; component equivalent is listing_tags.',
    'truncation_content_override': 'Menu inheritance switch; global value is truncation_content_length.',
    'truncation_paragraph_override': 'Menu inheritance switch; global value is truncation_paragraphs.',
    'tags_exclusions_override': 'Menu inheritance switch for the global exclusion controls.',
}
for family in ['academy', 'blog', 'codex']:
    root = ROOT / family / ('com_' + family)
    xml = ET.parse(root / 'admin/forms/settings.xml')
    settings = {f.get('name') for f in xml.iter('field')}
    view = (root / 'admin/src/View/Settings/HtmlView.php').read_text(encoding='utf-8')
    visible = set()
    for fs in xml.iter('fieldset'):
        if "'" + fs.get('name') + "'" in view:
            visible.update(f.get('name') for f in fs.iter('field'))
    count = 0
    for path in (root / 'site/tmpl').rglob('*.xml'):
        for field in ET.parse(path).iter('field'):
            name = field.get('name')
            if field.get('type') in ['spacer', 'hidden', 'note']:
                continue
            if name in MENU_ONLY:
                continue
            assert name in settings, (family, path, name, 'missing global')
            assert name in visible, (family, path, name, 'global not visible')
            count += 1
    print(f'{family}: {count} menu controls have visible component settings.')
print('Menu-only exceptions:')
for name, reason in MENU_ONLY.items():
    print(f'  {name}: {reason}')
