import { JoomlaEditorButton } from 'editor-api';

const family = new URLSearchParams(location.search).get('option')?.match(/^com_(academy|blog|codex)$/)?.[1] || 'post';
const esc = value => String(value || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const attr = value => String(value || '').replace(/[{}"\r\n]/g, '').trim();
const groups = {
  Layout: ['heading','text','tabs','columns','table','section','accordion'],
  Elements: ['alert','quote','button','link','code'],
  Media: ['image','video','audio','comparison'],
  Misc: ['html','rule','readmore','pagebreak','module','polls'],
  Embeddables: ['gist','instagram','spotify','behance','soundcloud','slideshare','codepen','tweet','pinterest','youtube','vimeo','dailymotion','ted','facebook']
};
const labels = {readmore:'Read More',pagebreak:'Page Break',soundcloud:'SoundCloud',slideshare:'SlideShare',codepen:'CodePen',dailymotion:'DailyMotion',tweet:'X / Twitter',polls:'Poll'};
const embedTypes = new Set([...groups.Embeddables, 'polls']);
const availablePolls = Joomla.getOptions(`${family}.polls`, []);
const tabIcons = ['none','home','user','check','info','star','heart','music','camera','video','cog','envelope','search','question','bookmark'];
const templateOptions = {
  quote:[['global','Use global setting'],['simple','Simple Bootstrap'],['color','Color Block'],['framed','Framed'],['card','Quote Card'],['panel','Dark Panel'],['minimal','Minimal']],
  tabs:[['global','Use global setting'],['classic','Classic Bootstrap'],['pills','Pills'],['underline','Underline'],['cards','Card Tabs'],['colorbar','Color Bar'],['icons','Icon Tabs']],
  accordion:[['global','Use global setting'],['classic','Classic Bootstrap'],['separated','Separated Cards'],['numbered','Numbered Process'],['minimal','Minimal FAQ'],['color-panel','Color Panel'],['gradient-card','Gradient Card'],['compact','Compact Dark'],['two-column','Two Columns']],
  columns:[['global','Use global setting'],['equal','Equal Columns'],['sidebar-left','Left Sidebar'],['sidebar-right','Right Sidebar'],['cards','Card Columns'],['bordered','Bordered Columns'],['color','Color Columns'],['gapless','Gapless Split'],['feature','Feature + Supporting']],
  polls:[['global','Use global setting'],['progress','Progress Bars'],['simple','Simple Results'],['badges','Badges']]
};
const templateSelect = type => `<div class="mb-3"><label class="form-label">Template</label><select class="form-select" name="template">${templateOptions[type].map(([value,label])=>`<option value="${value}">${label}</option>`).join('')}</select></div>`;
const schemas = {
  tabs:[['Tab title','text'],['Tab content','content','textarea']],
  columns:[['Left column','text','textarea'],['Right column','content','textarea']],
  section:[['Section heading','text'],['Section content','content','textarea']],
  alert:[['Alert message','text','textarea'],['Style','style','select',['info','success','warning','danger']]],
  quote:[['Quotation','text','textarea'],['Citation','cite']],
  button:[['Button label','text'],['Destination URL','url','url'],['Style','style','select',['primary','secondary','success','danger','warning','info']]],
  notes:[['Note','text','textarea']],
  gallery:[['Image URLs (one per line)','url','textarea'],['Alternative text','alt']],
  audio:[['Audio file URL','url','url'],['Accessible title','title'],['Autoplay','autoplay','checkbox']],
  file:[['Link label','text'],['File URL','url','url']],
  pdf:[['Document title','text'],['PDF URL','url','url'],['Viewer height (pixels)','height','number']],
  comparison:[['Before image URL','url','url'],['After image URL','url2','url'],['Before alt text','alt'],['After alt text','alt2']],
};
const fieldHtml = ([label,name,type='text',choices=[]]) => {
  if(type==='textarea') return `<div class="mb-3"><label class="form-label">${label}</label><textarea class="form-control" name="${name}" rows="5"></textarea></div>`;
  if(type==='select') return `<div class="mb-3"><label class="form-label">${label}</label><select class="form-select" name="${name}">${choices.map(value=>`<option value="${value}">${value[0].toUpperCase()+value.slice(1)}</option>`).join('')}</select></div>`;
  if(type==='pollselect') return `<div class="mb-3"><label class="visually-hidden">Select a poll</label><select class="form-select" name="${name}" required aria-label="Select a poll"><option value="">Select a poll</option>${choices.map(poll=>`<option value="${Number(poll.id)}">${esc(poll.title)}</option>`).join('')}</select>${choices.length?'':'<div class="form-text">No published polls are available. Create and publish a poll first.</div>'}</div>`;
  if(type==='checkbox') return `<div class="form-check mb-3"><input class="form-check-input" type="checkbox" value="1" name="${name}" id="block-${name}"><label class="form-check-label" for="block-${name}">${label}</label></div>`;
  return `<div class="mb-3"><label class="form-label">${label}</label><input class="form-control" name="${name}" type="${type}"${name==='height'?' value="700" min="200"':''}></div>`;
};

const makeHtml = (type, data) => {
  const text = esc(data.text || 'Content'), content=esc(data.content || 'Content'), url = esc(data.url || '#');
  if (type === 'polls') return `{embed provider="polls" url="${attr(data.url)}" template="${attr(data.template||'global')}"}`;
  if (type === 'slideshare') return `{embed provider="slideshare" url="${attr(data.url)}" width="${Number(data.width)||510}" height="${Number(data.height)||420}"}`;
  if (type === 'ted') return `{embed provider="ted" url="${attr(data.url)}" width="${Number(data.width)||1024}" height="${Number(data.height)||576}"}`;
  if (['youtube','vimeo','dailymotion'].includes(type)) return `{embed provider="${type}" url="${attr(data.url)}" width="${Number(data.width)||1024}" height="${Number(data.height)||576}"}`;
  if (type === 'facebook') return `{embed provider="facebook" url="${attr(data.url)}" width="${Number(data.width)||500}" height="${Number(data.height)||736}"}`;
  if (embedTypes.has(type)) return `{embed provider="${type}" url="${attr(data.url)}"}`;
  const html = {
    heading:`<h2>${text}</h2>`, text:`<p>${text}</p>`,
    columns:`<p>${esc(`{columns template="${attr(data.template||'global')}"}{column}${String(data.text||'').replace(/[{}]/g,'')}{/column}{column}${String(data.content||'').replace(/[{}]/g,'')}{/column}{/columns}`)}</p><p><br></p>`,
    table:`<table class="table"><thead><tr><th>Heading</th><th>Heading</th></tr></thead><tbody><tr><td>${text}</td><td>Content</td></tr></tbody></table>`,
    section:`<p>${esc(`{section title="${attr(data.text||'Section')}"}${String(data.content||'').replace(/[{}]/g,'')}{/section}`)}</p><p><br></p>`,
    alert:`<p>${esc(`{alert type="${attr(data.style||'info')}"}${String(data.text||'').replace(/[{}]/g,'')}{/alert}`)}</p><p><br></p>`, quote:`<p>${esc(`{quote cite="${attr(data.cite)}" template="${attr(data.template||'global')}"}${String(data.text||'').replace(/[{}]/g,'')}{/quote}`)}</p><p><br></p>`,
    button:`<p>${esc(`{button url="${attr(data.url||'#')}" style="${attr(data.style||'primary')}"}${String(data.text||'Button').replace(/[{}]/g,'')}{/button}`)}</p><p><br></p>`, link:`<a href="${url}">${text}</a>`,
    code:`<pre><code>${text}</code></pre>`, notes:`<aside class="post-note">${text}</aside>`, image:`<figure><img src="${url}" alt="${text}"></figure>`,
    gallery:`<div class="post-gallery">${String(data.url||'').split(/[\r\n,]+/).filter(Boolean).map(x=>`<img src="${esc(x.trim())}" alt="${esc(data.alt||'')}">`).join('')}</div>`,
    audio:`<p>${esc(`{audio url="${attr(data.url)}" title="${attr(data.title||'Audio player')}" autoplay="${data.autoplay?'1':'0'}"}`)}</p><p><br></p>`, file:`<p><a href="${url}" download>${text}</a></p>`, pdf:`<object data="${url}" type="application/pdf" width="100%" height="${Number(data.height)||700}"><a href="${url}">${text}</a></object>`,
    comparison:`<p>${esc(`{comparison before="${attr(data.url)}" after="${attr(data.url2)}" beforealt="${attr(data.alt||'Before')}" afteralt="${attr(data.alt2||'After')}" }`)}</p><p><br></p>`,
    html:String(data.text||''), rule:'<hr>', readmore:'<hr id="system-readmore">',
    pagebreak:`<hr class="system-pagebreak" title="${text}" alt="${attr(data.alias||'page')}" />`, module:`{loadmoduleid ${attr(data.text)}}`,
    video:`{video url="${attr(data.url)}" type="embed" title="${attr(data.text||'Video')}" ratio="16by9" controls="1" nocookie="1"}`
  };
  if (type === 'accordion') {
    const items = Array.isArray(data.items) && data.items.length ? data.items : [{title: data.text || 'Accordion item', content: data.content || 'Content'}];
    const shortcode=`{accordion template="${attr(data.template||'global')}"}${items.map((item,index)=>`{item title="${attr(item.title||`Accordion item ${index+1}`)}"}${String(item.content||'').replace(/[{}]/g,'')}{/item}`).join('')}{/accordion}`;
    return `<p>${esc(shortcode)}</p><p><br></p>`;
  }
  if (type === 'tabs') {
    const items = Array.isArray(data.items) && data.items.length ? data.items : [{title: data.text || 'Tab 1', content: data.content || 'Content'}];
    const shortcode=`{tabs template="${attr(data.template||'global')}"}${items.map((item,index)=>`{tab title="${attr(item.title||`Tab ${index+1}`)}" icon="${attr(item.icon||'none')}"}${String(item.content||'').replace(/[{}]/g,'')}{/tab}`).join('')}{/tabs}`;
    return `<p>${esc(shortcode)}</p><p><br></p>`;
  }
  return html[type] || '';
};

const accordionFields = () => `${templateSelect('accordion')}<div data-accordion-items><div class="accordion-item-editor border rounded p-3 mb-3"><div class="d-flex justify-content-between align-items-center mb-2"><strong>Accordion item 1</strong><button type="button" class="btn btn-sm btn-outline-danger" data-remove-item hidden>Remove</button></div><label class="form-label">Title</label><input class="form-control mb-3" data-item-title><label class="form-label">Content</label><textarea class="form-control" rows="4" data-item-content></textarea></div></div><button type="button" class="btn btn-outline-primary mb-3" data-add-item>Add accordion item</button><div class="form-text mb-3">The first item opens initially. Add as many items as needed; they will behave as one Bootstrap accordion.</div>`;

const wireAccordionFields = dialog => {
  const list=dialog.querySelector('[data-accordion-items]'), add=dialog.querySelector('[data-add-item]');
  const refresh=()=>list.querySelectorAll('.accordion-item-editor').forEach((item,index)=>{item.querySelector('strong').textContent=`Accordion item ${index+1}`;const remove=item.querySelector('[data-remove-item]');remove.hidden=list.children.length===1;remove.onclick=()=>{item.remove();refresh()}});
  add.onclick=()=>{const item=list.firstElementChild.cloneNode(true);item.querySelector('[data-item-title]').value='';item.querySelector('[data-item-content]').value='';list.appendChild(item);refresh();item.querySelector('[data-item-title]').focus()};refresh();
};

const tabIconSelect = () => `<label class="form-label">Icon</label><select class="form-select mb-3" data-tab-icon>${tabIcons.map(icon=>`<option value="${icon}">${icon==='none'?'No icon':icon[0].toUpperCase()+icon.slice(1)}</option>`).join('')}</select>`;
const tabsFields = () => `${templateSelect('tabs')}<div data-tab-items><div class="tab-item-editor border rounded p-3 mb-3"><div class="d-flex justify-content-between align-items-center mb-2"><strong>Tab 1</strong><button type="button" class="btn btn-sm btn-outline-danger" data-remove-tab hidden>Remove</button></div><label class="form-label">Title</label><input class="form-control mb-3" data-tab-title>${tabIconSelect()}<label class="form-label">Content</label><textarea class="form-control" rows="4" data-tab-content></textarea></div></div><button type="button" class="btn btn-outline-primary mb-3" data-add-tab>Add tab</button><div class="form-text mb-3">The first tab is active initially. Add as many tabs as needed. Icons use Joomla's built-in icon set.</div>`;

const wireTabsFields = dialog => {
  const list=dialog.querySelector('[data-tab-items]'), add=dialog.querySelector('[data-add-tab]');
  const refresh=()=>list.querySelectorAll('.tab-item-editor').forEach((item,index)=>{item.querySelector('strong').textContent=`Tab ${index+1}`;const remove=item.querySelector('[data-remove-tab]');remove.hidden=list.children.length===1;remove.onclick=()=>{item.remove();refresh()}});
  add.onclick=()=>{const item=list.firstElementChild.cloneNode(true);item.querySelector('[data-tab-title]').value='';item.querySelector('[data-tab-icon]').value='none';item.querySelector('[data-tab-content]').value='';list.appendChild(item);refresh();item.querySelector('[data-tab-title]').focus()};refresh();
};

const open = (editor, initialType = '') => {
  if(initialType==='rule'){editor.replaceSelection('<hr>');return;}
  document.getElementById(`${family}-blocks-dialog`)?.remove();
  const dialog=document.createElement('dialog'); dialog.id=`${family}-blocks-dialog`; dialog.className='p-0 border-0 rounded shadow-lg'; dialog.style.width='min(1050px,calc(100vw - 2rem))';
  dialog.innerHTML=`<form method="dialog" class="bg-body"><header class="d-flex justify-content-between border-bottom p-3"><h2 class="h4 m-0">${esc(family[0].toUpperCase()+family.slice(1))} Blocks</h2><button type="button" class="btn-close" data-close></button></header><div class="p-4" style="max-height:72vh;overflow:auto"><input class="form-control mb-4" type="search" placeholder="Search blocks" data-search><div class="row g-4">${Object.entries(groups).map(([group,types])=>`<section class="col-md-6" data-group><h3 class="h5">${group}</h3><div class="d-grid gap-2" style="grid-template-columns:repeat(3,1fr)">${types.map(type=>`<button type="button" class="btn btn-outline-secondary py-3" data-type="${type}">${labels[type]||type[0].toUpperCase()+type.slice(1)}</button>`).join('')}</div></section>`).join('')}</div><div class="border rounded p-3 mt-4" data-options hidden><h3 class="h5" data-title></h3><div data-fields></div><div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-secondary" data-back>Back</button><button type="button" class="btn btn-primary" data-insert>Insert</button></div></div></div></form>`;
  document.body.appendChild(dialog); let selected=''; const picker=dialog.querySelector('.row'), options=dialog.querySelector('[data-options]'), fields=dialog.querySelector('[data-fields]');
  dialog.querySelector('[data-search]').oninput=e=>dialog.querySelectorAll('[data-type]').forEach(b=>b.hidden=!b.textContent.toLowerCase().includes(e.target.value.toLowerCase()));
  dialog.querySelectorAll('[data-type]').forEach(button=>button.onclick=()=>{selected=button.dataset.type;picker.hidden=true;options.hidden=false;dialog.querySelector('[data-title]').textContent=labels[selected]||button.textContent;if(selected==='accordion'){fields.innerHTML=accordionFields();wireAccordionFields(dialog)}else if(selected==='tabs'){fields.innerHTML=tabsFields();wireTabsFields(dialog)}else{const sized=['slideshare','ted','youtube','vimeo','dailymotion','facebook'].includes(selected);fields.innerHTML=(selected==='polls'?[['Poll','url','pollselect',availablePolls]]:(sized?[[`${labels[selected]||button.textContent} embed URL`,'url','url'],['Width','width','number'],['Height','height','number']]:(embedTypes.has(selected)?[[`${labels[selected]||button.textContent} URL`,'url','url']]:(schemas[selected]||[])))).map(fieldHtml).join('');if(sized){fields.querySelector('[name="width"]').value=selected==='slideshare'?'510':(selected==='facebook'?'500':'1024');fields.querySelector('[name="height"]').value=selected==='slideshare'?'420':(selected==='facebook'?'736':'576')}}});
  dialog.querySelectorAll('[data-type="quote"],[data-type="columns"],[data-type="polls"]').forEach(button=>button.addEventListener('click',()=>queueMicrotask(()=>fields.insertAdjacentHTML('afterbegin',templateSelect(button.dataset.type)))));
  dialog.querySelector('[data-back]').onclick=()=>{options.hidden=true;picker.hidden=false}; dialog.querySelector('[data-insert]').onclick=()=>{const data=Object.fromEntries(new FormData(dialog.querySelector('form')));if(selected==='accordion')data.items=[...dialog.querySelectorAll('.accordion-item-editor')].map(item=>({title:item.querySelector('[data-item-title]').value,content:item.querySelector('[data-item-content]').value}));if(selected==='tabs')data.items=[...dialog.querySelectorAll('.tab-item-editor')].map(item=>({title:item.querySelector('[data-tab-title]').value,icon:item.querySelector('[data-tab-icon]').value,content:item.querySelector('[data-tab-content]').value}));editor.replaceSelection(makeHtml(selected,data));dialog.close()};
  dialog.querySelector('[data-close]').onclick=()=>dialog.close(); dialog.addEventListener('close',()=>dialog.remove(),{once:true});
  if (initialType === 'embed') {
    dialog.querySelectorAll('[data-group]').forEach(group => group.hidden = !group.querySelector('[data-type="gist"]'));
    dialog.querySelector('header h2').textContent = 'Embeddables';
    dialog.querySelector('[data-group]:not([hidden]) h3')?.remove();
    dialog.querySelector('[data-search]').hidden = true;
    dialog.querySelector('[data-title]').hidden = true;
  } else if (initialType) {
    dialog.querySelector(`[data-type="${initialType}"]`)?.click();
    dialog.querySelector('[data-search]').hidden=true;
    dialog.querySelector('[data-back]').hidden=true;
    dialog.querySelector('header h2').textContent=labels[initialType]||`${initialType[0].toUpperCase()+initialType.slice(1)}`;
    dialog.querySelector('[data-title]')?.remove();
  }
  dialog.showModal();
};

['tabs','columns','section','accordion','alert','quote','button','audio','comparison','rule','polls','embed'].forEach(type => {
  JoomlaEditorButton.registerAction(`insert-${family}-${type}`, editor => open(editor, type));
});
