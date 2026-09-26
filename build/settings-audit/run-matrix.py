from pathlib import Path
import json,xml.etree.ElementTree as E,subprocess,concurrent.futures
root=Path(__file__).parent
menus=json.loads((root/'menus.json').read_text()); cases=[]
for menu in menus:
 t=E.parse(menu['form'])
 for field in t.findall('.//fields[@name="params"]/fieldset/field'):
  typ=field.get('type');name=field.get('name')
  if typ in ('note','spacer','hidden','password','checkbox','text','textarea','editor','media','neuralnetworkmodel'):continue
  vals=[o.get('value','') for o in field.findall('option')]
  if typ=='number':vals=['0','1',field.get('max','5')]
  if typ=='componentlayout':vals=['_:default','_:card'] if field.get('view')=='category' else ['_:default','_:wiki']
  for val in dict.fromkeys(vals):cases.append(dict(family=menu['family'],menu=menu['id'],view=menu['view'],name=name,value=val,scope='menu'))
# Each component control is exercised on a representative listing. This is render coverage, not a proof of effect.
for f in ('academy','blog','codex'):
 m=next(m for m in menus if m['family']==f and m['view']=='featured')
 t=E.parse(Path(f)/f'com_{f}/admin/forms/settings.xml')
 for field in t.findall('.//field'):
  name=field.get('name');typ=field.get('type')
  if name.startswith('ai_') or typ in ('password','text','textarea','editor','media','note','spacer','checkbox'):continue
  vals=[o.get('value','') for o in field.findall('option')]
  if typ=='number':vals=['0','1',field.get('max','5')]
  for val in dict.fromkeys(vals):cases.append(dict(family=f,menu=m['id'],view='featured',name=name,value=val,scope='component'))
(root/'cases.json').write_text(json.dumps(cases,indent=2))
def run(pair):
 i,case=pair
 if case['view']=='myposts':case=dict(case,authenticated=True)
 p=root/f'case-{i}.json';p.write_text(json.dumps(case))
 try:
  r=subprocess.run(['C:/wamp64/bin/php/php8.3.28/php.exe',str(root/'render.php'),str(p)],capture_output=True,text=True,timeout=25)
  try:result=json.loads(r.stdout)
  except:result={'status':'runner_error','output':(r.stdout+r.stderr)[-700:]}
  return dict(case,**result)
 except subprocess.TimeoutExpired:return dict(case,status='timeout')
 finally:p.unlink(missing_ok=True)
results=[]
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as ex:
 for result in ex.map(run,enumerate(cases)):
  results.append(result)
  if len(results)%100==0:print(f'{len(results)}/{len(cases)}',flush=True)
(root/'matrix.json').write_text(json.dumps(results,indent=2))
print('Complete:',len(results),'cases',flush=True)
