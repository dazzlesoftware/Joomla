"""Compare rendered output for boolean settings. No-change is a review candidate, not a defect verdict."""
import importlib.util
import json
import re
import subprocess
import xml.etree.ElementTree as ET
from pathlib import Path

root=Path(__file__).parent
menus=json.loads((root/'menus.json').read_text())
results=[]
def normalize(html):
    html=re.sub(r'[a-f0-9]{32,64}', 'TOKEN', html)
    html=re.sub(r'(?:academy|blog|codex)-featured-[a-f0-9]+', 'SLIDER', html)
    html=re.sub(r'postnav-[a-f0-9]{8}', 'POSTNAV', html)
    html=re.sub(r'\d+ Hits', 'HITS', html)
    return re.sub(r'\s+', ' ', html)
for family in ['academy','blog','codex']:
    form=ET.parse(Path(family)/('com_'+family)/'admin/forms/settings.xml')
    for section in form.findall('.//fieldset'):
        section_name=section.get('name')
        if section_name not in ['posts','engagement','engagement_appearance','category','categories','compact_posts','shared','list_display']:
            continue
        view='post' if section_name in ['posts','engagement','engagement_appearance'] else 'categories' if section_name=='categories' else 'category'
        menu=next(m for m in menus if m['family']==family and m['view']==view)
        for field in section.findall('field'):
            values=[o.get('value') for o in field.findall('option')]
            if set(values)!={'0','1'}:continue
            name=field.get('name');outputs=[]
            warnings=[]
            for value in [0,1]:
                params=dict(featured_slider_enabled=0,category_layout='_:card',compact_show=1,num_links=2,
                            compact_selection='next',show_title=1,show_hits=1,show_vote=1)
                params[name]=value
                case=dict(family=family,menu=menu['id'],name=name,value=value,params=params,html=True)
                case['global']={name:value}
                path=root/'scan-case.json';path.write_text(json.dumps(case))
                proc=subprocess.run(['C:/wamp64/bin/php/php8.3.28/php.exe',str(root/'render.php'),str(path)],capture_output=True,text=True,timeout=30)
                try:
                    data=json.loads(proc.stdout);outputs.append(normalize(data['html']));warnings.extend(data.get('warnings',[]))
                except Exception:
                    outputs.append('ERROR '+proc.stdout[-300:])
            status='output_changed' if outputs[0]!=outputs[1] else 'no_change_review'
            results.append(dict(family=family,section=section_name,view=view,name=name,status=status,warnings=warnings))
    print(family,'scanned',flush=True)
(root/'behavior-scan.json').write_text(json.dumps(results,indent=2))
print('Checks:',len(results))
