"""Behavior checks against disposable audit content; never changes saved settings."""
import json
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).parent
MENUS = json.loads((ROOT / 'menus.json').read_text())
RESULTS = []

def render(family, view, params=None, request=None, authenticated=False, global_params=None):
    menu = next(m for m in MENUS if m['family'] == family and m['view'] == view)
    case = dict(family=family, menu=menu['id'], name='audit_probe', value='',
                params=params or {}, request=request or {}, authenticated=authenticated, html=True)
    case['global'] = global_params or {}
    path = ROOT / 'effect-case.json'
    path.write_text(json.dumps(case))
    run = subprocess.run(['C:/wamp64/bin/php/php8.3.28/php.exe', str(ROOT / 'render.php'), str(path)],
                         capture_output=True, text=True, timeout=30)
    data = json.loads(run.stdout)
    if data['status'] != 'rendered':
        raise RuntimeError(data)
    if data.get('warnings'):
        raise RuntimeError(data['warnings'])
    return data['html']

def check(family, label, condition):
    RESULTS.append(dict(family=family, check=label, passed=bool(condition)))
    print(f'{family}: {label}: {"PASS" if condition else "FAIL"}', flush=True)

for family in ['academy', 'blog', 'codex']:
    categories = dict(categories_style='list', maxLevelcat=-1, show_empty_categories_cat=1,
                      show_base_description=1, show_subcat_desc_cat=1)
    for enabled in [0,1]:
        html = render(family, 'categories', dict(categories,show_page_heading=enabled,page_heading='Audit Heading'))
        check(family, 'directory heading '+str(enabled), ('<h1>Audit Heading</h1>' in html)==bool(enabled))
    html = render(family, 'categories', categories)
    check(family, 'base category description shown', 'Audit base category description.' in html)
    check(family, 'child descriptions shown', 'Audit Child description.' in html)
    check(family, 'empty category shown', '>Audit Empty<' in html)
    check(family, 'grandchild at unlimited depth', '>Audit Grandchild<' in html)
    html = render(family, 'categories', dict(categories, maxLevelcat=1))
    check(family, 'depth one excludes grandchild', '>Audit Grandchild<' not in html and '>Audit Child<' in html)
    html = render(family, 'categories', dict(categories, show_empty_categories_cat=0))
    check(family, 'empty categories hidden', '>Audit Empty<' not in html and '>Audit Child<' in html)
    html = render(family, 'categories', dict(categories, show_base_description=0, show_subcat_desc_cat=0))
    check(family, 'descriptions hidden', 'Audit base category description.' not in html and 'Audit Child description.' not in html)
    html = render(family, 'categories', dict(categories, categories_description='Custom audit description'))
    check(family, 'custom base description', 'Custom audit description' in html and 'Audit base category description.' not in html)
    for style in ['default', 'card', 'standard', 'learning', 'simple', 'nickel']:
        params = dict(category_layout='_:' + style, compact_show=0, featured_slider_enabled=0,
                      orderby_pri='none', orderby_sec='alpha', listing_pin_featured=0,
                      show_title=1, items_limit_source='custom', posts_per_page=3)
        html = render(family, 'category', params)
        titles = re.findall(r'Audit Post \d\d', html)
        check(family, style + ' layout renders audit posts', bool(titles))
    base = dict(category_layout='_:card', compact_show=0, featured_slider_enabled=0,
                orderby_pri='none', listing_pin_featured=0, show_title=1,
                items_limit_source='custom', posts_per_page=3)
    for order, date, first in [('alpha','published','01'),('ralpha','published','16'),
                               ('date','created','01'),('rdate','created','16'),
                               ('date','modified','16'),('rdate','modified','01'),
                               ('order','published','01'),('rorder','published','16')]:
        html = render(family, 'category', dict(base, orderby_sec=order, order_date=date))
        titles = re.findall(r'Audit Post \d\d', html)
        check(family, f'order {order}/{date}', bool(titles) and titles[0] == 'Audit Post ' + first)
    for order in ['front','hits','rhits','author','rauthor','vote','rvote','rank','rrank']:
        html = render(family, 'category', dict(base, orderby_sec=order))
        check(family, 'ordering SQL ' + order, 'Audit Post ' in html)
    for enabled in [0,1]:
        html = render(family, 'category', base, global_params=dict(show_powered_by=enabled))
        check(family, 'credit ' + str(enabled), ('class="dazzle-credit"' in html) == bool(enabled))
        html = render(family, 'category', dict(base, compact_show=enabled, num_links=2))
        check(family, 'compact posts ' + str(enabled), ('compact-posts' in html) == bool(enabled))
        html = render(family, 'category', dict(base, show_pagination=enabled, show_pagination_results=1))
        check(family, 'pagination ' + str(enabled), ('content-view-category-blog__pagination' in html) == bool(enabled))
        html = render(family, 'category', dict(base, show_pagination=1, show_pagination_results=enabled))
        check(family, 'pagination summary ' + str(enabled), bool(re.search(r'Page 1 of',html)) == bool(enabled))
    for setting, marker in [('compact_show_title','fw-semibold'), ('compact_show_image','flex-shrink-0'),
                             ('compact_show_rating','fa-star'),('link_featured_image','<a href=')]:
        for enabled in [0,1]:
            compact = dict(base, compact_show=1, num_links=2, compact_show_title=0,
                           compact_show_rating=0, compact_show_image=1, link_featured_image=0)
            compact[setting] = enabled
            if setting == 'compact_show_image':
                compact['compact_show_title'] = 1
            html = render(family, 'category', compact)
            match = re.search(r'<ul class="compact-posts.*?</ul>',html,re.S)
            fragment = match.group() if match else ''
            check(family, setting + ' ' + str(enabled), bool(fragment) and (marker in fragment) == bool(enabled))
    for style in ['list','image_grid']:
        for mode in ['rows','columns']:
            for column_style in ['grid','masonry']:
                html=render(family,'categories',dict(categories,categories_style=style,
                    categories_listing_layout=mode,categories_column_style=column_style,categories_columns=4))
                check(family,f'directory {style}/{mode}/{column_style}',
                    ('row-cols-md-'+('1' if mode=='rows' else '4')) in html
                    and ('data-post-masonry' in html)==(mode=='columns' and column_style=='masonry'))
    html = render(family,'category',base)
    check(family,'unpublished and future posts excluded','Audit Unpublished Post' not in html and 'Audit Scheduled Post' not in html)
    html = render(family,'archive')
    check(family,'archive includes archived fixture','Audit Archived Post' in html)
    for setting, marker in [('show_hits','postmeta-hits'),
                            ('engagement_ratings','postmeta-rating'),('engagement_sharing','postmeta-share '),
                            ('share_facebook','postmeta-share-facebook')]:
        for enabled in [0,1]:
            params=dict(base,show_hits=1,show_vote=1,engagement_ratings=1,engagement_sharing=1)
            params[setting]=enabled
            html=render(family,'category',params)
            check(family, setting+' visibility '+str(enabled),(marker in html)==bool(enabled))
    for provider in ['disabled','native','disqus']:
        html=render(family,'category',dict(base,show_vote=0,engagement_ratings=1,comments_provider=provider))
        check(family, 'card rating summary independent of vote control '+provider, 'postmeta-rating' in html)
        check(family, 'card comment count independent of provider '+provider, 'postmeta-comments' in html)
    html=render(family,'category',dict(base,engagement_sharing=1,share_facebook=1))
    check(family,'sharing URL has one Joomla base path','%2FJoomla%2F%2FJoomla' not in html and 'http%3A%2F%2Flocalhost%2FJoomla%2F' in html)
    for view in ['form','myposts']:
        html = render(family, view, authenticated=True)
        check(family, 'authenticated ' + view + ' renders', len(html) > 100)

(ROOT / 'effects.json').write_text(json.dumps(RESULTS, indent=2))
print('Failed:', sum(not r['passed'] for r in RESULTS))

raise SystemExit(1 if any(not r["passed"] for r in RESULTS) else 0)
