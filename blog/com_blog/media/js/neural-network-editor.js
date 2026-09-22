import { JoomlaEditor, JoomlaEditorButton } from 'editor-api';

const options = Joomla.getOptions('com_blog.ai', {});
const field = name => document.querySelector(`[name="${name}"]`);
const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const blockEditor = () => document.querySelector('[data-post-block-editor]')?.aiEditor;
const editor = () => blockEditor() || Joomla.editors?.instances?.jform_post_content || JoomlaEditor.getActive();
const value = () => editor()?.getValue?.() ?? field('jform[post_content]')?.value ?? '';
const setField = (name, text) => { const input = field(name); if (input) { input.value = text; input.dispatchEvent(new Event('change', {bubbles:true})); } };
async function request(task, data) {
  const form = new FormData();
  Object.entries({...data, post_id:field('jform[id]')?.value || 0, catid:field('jform[catid]')?.value || 0, [options.token]:1}).forEach(([key,val])=>form.append(key,val));
  const response = await fetch(`${options.endpoint}&task=neuralnetwork.${task}`, {method:'POST',body:form,credentials:'same-origin'});
  if (!response.ok) throw new Error('The server could not complete the AI request.');
  const result = await response.json();
  if (!result.success) throw new Error(result.message || 'AI request failed.');
  return result.data;
}
function open(action, activeEditor = editor(), imageTarget = 'editor') {
  const targets = action==='rewrite' ? activeEditor?.rewriteTargets?.() : null;
  let source = activeEditor?.getValue?.() ?? value();
  const selection = activeEditor?.getSelection?.() || '';
  const originalExcerpt = field('jform[excerpt]')?.value || '';
  const dialog = document.createElement('dialog');
  dialog.className='p-4 border rounded shadow bg-body text-body'; dialog.style.cssText='width:min(820px,95vw);max-height:90vh;overflow:auto';
  dialog.innerHTML=`<header class="d-flex justify-content-between"><h2 class="h4">${action==='image'?'Generate image':action==='seo'?'SEO suggestions':action==='excerpt'?'Generate excerpt':'Rewrite content'}</h2><button type="button" class="btn-close" aria-label="Close"></button></header>
    <p class="text-muted">Review the result before applying. Generation sends this content to your configured provider.</p>
    ${action==='rewrite'?`<label class="form-label" for="ai-scope">Content to rewrite</label><select id="ai-scope" class="form-select mb-3">${targets?targets.map((target,i)=>`<option value="${i}">${escape(target.label)}</option>`).join(''):`<option value="post">Whole post</option>${selection?'<option value="selection">Selected text</option>':''}`}</select>`:''}
    ${action==='excerpt'?`<label class="form-label" for="ai-scope">Source</label><select id="ai-scope" class="form-select mb-3"><option value="post">Generate from post</option>${originalExcerpt?'<option value="excerpt">Rewrite existing excerpt</option>':''}</select>`:''}
    <label class="form-label" for="ai-instruction">${action==='image'?'Describe your image':'Instructions (tone, length, or image description for alt text)'}</label><textarea id="ai-instruction" class="form-control mb-3" rows="3" maxlength="4000"></textarea>
    ${action==='image'?'<label class="form-label" for="ai-alt">Image alt text</label><input id="ai-alt" class="form-control mb-3" maxlength="255">':''}
    <button type="button" class="btn btn-primary" data-generate>Generate preview</button>
    <p class="mt-3" role="status" aria-live="polite" data-status></p><div data-result></div>
    <button type="button" class="btn btn-success mt-3" data-apply hidden>${action==='image'?'Save image and use':'Apply to draft'}</button>`;
  document.body.append(dialog); dialog.showModal();
  dialog.querySelector('.btn-close').onclick=()=>dialog.close();
  dialog.addEventListener('close',()=>dialog.remove(),{once:true});
  const status=dialog.querySelector('[data-status]'), generate=dialog.querySelector('[data-generate]'), apply=dialog.querySelector('[data-apply]'), result=dialog.querySelector('[data-result]');
  let generated, scope='post', rewriteEditor=activeEditor;
  generate.onclick=async()=>{
    generate.disabled=true; apply.hidden=true; result.replaceChildren(); status.textContent='Generating…';
    scope=dialog.querySelector('#ai-scope')?.value || 'post';
    try {
      if(targets){rewriteEditor=targets[Number(scope)];if(!rewriteEditor)throw new Error('Add a text block before rewriting.');source=rewriteEditor.getValue();}
      generated=await request('generate',{action,content:action==='image'?'':scope==='selection'?selection:scope==='excerpt'?originalExcerpt:source,instruction:dialog.querySelector('#ai-instruction').value});
      if (!dialog.isConnected) return;
      if (action==='image') { const image=document.createElement('img');image.src=generated.preview;image.alt='Generated preview';image.className='img-fluid';result.append(image); }
      else if (action==='seo') {
        for(const [key,val] of Object.entries(generated.fields)) { const label=document.createElement('label');label.className='form-label d-block mt-2';label.textContent=key.replaceAll('_',' ');const input=document.createElement('textarea');input.className='form-control';input.rows=2;input.dataset.seo=key;input.value=val;label.append(input);result.append(label); }
      } else { const label=document.createElement('label');label.textContent=action==='rewrite'?'Review HTML (editable)':'Review excerpt (editable)';label.className='form-label w-100';const input=document.createElement('textarea');input.className='form-control';input.rows=12;input.dataset.output='';input.value=generated.text;label.append(input);result.append(label); }
      status.textContent='Ready to review. Applying changes updates this draft; save the post when finished.';apply.hidden=false;
    } catch(error){status.textContent=error.message;}finally{generate.disabled=false;}
  };
  apply.onclick=async()=>{
    apply.disabled=true;
    try {
      if(action==='image') {
        const saved=await request('saveImage',{image_id:generated.image_id});
        const alt=dialog.querySelector('#ai-alt').value;
        if(imageTarget==='editor') { if(activeEditor?.addImage)activeEditor.addImage(saved.url,alt);else {if(!activeEditor?.replaceSelection) throw new Error(`Image saved to ${saved.path}. Select it using Media.`);activeEditor.replaceSelection(`<img src="${escape(saved.url)}" alt="${escape(alt)}">`);} }
        else {setField(imageTarget,saved.path);if(imageTarget==='jform[media][featured_image]')setField('jform[media][featured_image_alt]',alt);if(imageTarget==='jform[metadata][og_image]')setField('jform[metadata][image_alt]',alt);}
      } else if(action==='seo') {
        result.querySelectorAll('[data-seo]').forEach(input=>setField(['metakey','metadesc'].includes(input.dataset.seo)?`jform[${input.dataset.seo}]`:`jform[metadata][${input.dataset.seo}]`,input.value));
      } else if(action==='excerpt') {if(field('jform[excerpt]')?.value!==originalExcerpt)throw new Error('The excerpt changed since this preview. Generate a new preview.');setField('jform[excerpt]',result.querySelector('[data-output]').value);const details=field('jform[excerpt]')?.closest('details');if(details)details.open=true;}
      else {
        if((rewriteEditor?.getValue?.() ?? value())!==source) throw new Error('The post changed since this preview. Close this dialog and generate a new preview.');
        const output=result.querySelector('[data-output]').value;
        // Use the original selection text within the unchanged document, not the
        // current editor selection (which may have moved while the dialog was open).
        const updated=scope==='selection'?source.replace(selection,output):output;
        if(scope==='selection' && (!source.includes(selection)||source.indexOf(selection)!==source.lastIndexOf(selection)))throw new Error('Select a unique text passage before rewriting.');
        if(rewriteEditor?.setValue)rewriteEditor.setValue(updated);else setField('jform[post_content]',updated);
      }
      dialog.close();
    }catch(error){status.textContent=error.message;apply.disabled=false;}
  };
}
function init(){
  const content=field('jform[post_content]');if(!content||!options.endpoint)return;
  const toolbar=document.createElement('div');toolbar.className='d-flex flex-wrap gap-2 my-3';toolbar.setAttribute('aria-label','AI writing tools');
  for(const [action,label] of [['rewrite','AI Rewrite'],['excerpt','AI Excerpt'],['seo','AI SEO']]){if(action==='seo'&&!field('jform[metadata][seo_title]'))continue;if(action==='excerpt'&&!field('jform[excerpt]'))continue;const button=document.createElement('button');button.type='button';button.className='btn btn-outline-primary';button.textContent=label;button.onclick=()=>open(action);toolbar.append(button);}
  (document.querySelector('[data-post-block-editor]') || content.closest('.control-group'))?.before(toolbar);
  if(!toolbar.isConnected) content.parentElement.before(toolbar);
  if(options.images){
    if(blockEditor()){const button=document.createElement('button');button.type='button';button.className='btn btn-outline-primary';button.textContent='AI Image block';button.onclick=()=>open('image');toolbar.append(button);}
    for(const name of ['jform[media][featured_image]','jform[metadata][og_image]']){const input=field(name);if(!input)continue;const button=document.createElement('button');button.type='button';button.className='btn btn-outline-primary my-2';button.textContent='Generate image with AI';button.onclick=()=>open('image',editor(),name);(input.closest('.control-group')||input.parentElement).append(button);}
    const original=JoomlaEditorButton.getActionHandler('modal-media');
    if(original) JoomlaEditorButton.registerAction('modal-media',(ed,args)=>{
      const choice=document.createElement('dialog');choice.className='p-4 border rounded shadow bg-body text-body';choice.innerHTML='<h2 class="h4">Media</h2><div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-primary" data-browse>Browse Media</button><button type="button" class="btn btn-outline-primary" data-ai>Generate image with AI</button><button type="button" class="btn btn-secondary" data-close>Cancel</button></div>';document.body.append(choice);choice.showModal();choice.addEventListener('close',()=>choice.remove(),{once:true});choice.querySelector('[data-browse]').onclick=()=>{choice.close();original(ed,args);};choice.querySelector('[data-ai]').onclick=()=>{choice.close();open('image',ed);};choice.querySelector('[data-close]').onclick=()=>choice.close();
    });
  }
}
if(document.readyState==='complete')init();else window.addEventListener('load',init,{once:true});
