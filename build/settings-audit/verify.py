from pathlib import Path
import zipfile,json,hashlib,io
ROOT=Path(__file__).resolve().parents[2];SITE=Path('C:/wamp64/www/Joomla');results=[]
def verify_zip(path,source):
 with zipfile.ZipFile(path) as z:
  members={n.replace('\\','/'):n for n in z.namelist()}
  files=[p for p in source.rglob('*') if p.is_file()]
  bad=[str(p) for p in files if p.relative_to(source).as_posix() not in members or z.read(members[p.relative_to(source).as_posix()])!=p.read_bytes()]
 results.append(dict(check=str(path),files=len(files),mismatches=bad))
def verify_deploy(source,dest,language_dest=None):
 files=[p for p in source.rglob('*') if p.is_file()];bad=[]
 for p in files:
  rel=p.relative_to(source)
  target=language_dest/p.name if language_dest and rel.parts[0]=='language' else dest/rel
  if not target.is_file() or p.read_bytes()!=target.read_bytes():bad.append(str(target))
 results.append(dict(check=str(dest),files=len(files),mismatches=bad))
for f in ('academy','blog','codex'):
 base=ROOT/f;dist=base/'dist';comp=base/('com_'+f)
 verify_zip(dist/('com_'+f+'.zip'),comp)
 for src,dst in [('admin',SITE/'administrator/components'/('com_'+f)),('site',SITE/'components'/('com_'+f)),('media',SITE/'media'/('com_'+f)),('api',SITE/'api/components'/('com_'+f)),('language/en-GB',SITE/'administrator/language/en-GB'),('site-language/en-GB',SITE/'language/en-GB')]:verify_deploy(comp/src,dst)
 for p in (base/'plugins').iterdir():
  if not p.is_dir():continue
  group,element={'mailqueue':('task',f+'_mailqueue'),'branding':('system',f+'branding'),'loader':('system',f+'loader')}.get(p.name,(f,p.name))
  verify_zip(dist/f'plg_{group}_{element}.zip',p);verify_deploy(p,SITE/'plugins'/group/element,SITE/'administrator/language/en-GB')
 for p in (base/'modules').iterdir():
  if not p.is_dir():continue
  name=f'mod_{f}_{p.name}';verify_zip(dist/(name+'.zip'),p);verify_deploy(p,SITE/'modules'/name,SITE/'language/en-GB')
 with zipfile.ZipFile(dist/('pkg_'+f+'.zip')) as z:
  for name in ('com_'+f+'.zip','plugins.zip','modules.zip','plg_neuralnetwork_modelcatalog.zip'):
   disk=ROOT/'dist'/name if name.startswith('plg_neural') else dist/name
   results.append(dict(check=f'{f} full installer/{name}',files=1,mismatches=[] if z.read(name)==disk.read_bytes() else [name]))
 for bundle in ('plugins','modules'):
  with zipfile.ZipFile(dist/(bundle+'.zip')) as z:
   for name in z.namelist():
    if name.endswith('.zip'):results.append(dict(check=f'{f}/{bundle}/{name}',files=1,mismatches=[] if z.read(name)==(dist/name).read_bytes() else [name]))
for group,element in [('content','video'),('editors-xtd','video'),('user','genesisprofile'),('neuralnetwork','modelcatalog')]:
 src=ROOT/'plugins'/group/element;verify_deploy(src,SITE/'plugins'/group/element)
 if element!='video':verify_zip(ROOT/'dist'/f'plg_{group}_{element}.zip',src)
 else:
  with zipfile.ZipFile(ROOT/'dist/pkg_video_feature.zip') as z:verify_zip(io.BytesIO(z.read(f'plg_{group}_{element}.zip')),src)
Path(__file__).with_name('verification.json').write_text(json.dumps(results,indent=2))
print(len(results),'checks;',sum(x['files'] for x in results),'file comparisons;',sum(len(x['mismatches']) for x in results),'mismatches')
for x in results:
 if x['mismatches']:print(x)

raise SystemExit(1 if any(r["mismatches"] for r in results) else 0)
