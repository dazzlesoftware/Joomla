const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
for (const family of ['academy','blog','codex']) {
 const source=fs.readFileSync(`${family}/plugins/blocks/media/js/blocks-button.js`,'utf8').replace(/^import .*;\s*/, '');
 const context={URLSearchParams,location:{search:`?option=com_${family}`},Joomla:{getOptions:()=>[]},JoomlaEditorButton:{registerAction:()=>{}}};
 vm.createContext(context);
 vm.runInContext(source+`;globalThis.api={makeHtml,appearanceHtml,templateOptions};`,context);
 for(const type of ['quote','tabs','accordion','columns','polls']) {
  const html=context.api.makeHtml(type,{text:'Test',content:'Body',url:'1'});
  assert(!html.includes('global'));
  assert(html.includes(type==='polls'?'poll_progress_labels':'_color_mode'));
  assert(!context.api.templateOptions[type].some(x=>x[0]==='global'));
  assert(context.api.appearanceHtml(type).includes('form-select'));
 }
 for(const type of ['alert','button','section','audio','comparison','rule']) assert(context.api.makeHtml(type,{text:'Test',url:'https://example.org'}));
 console.log(`${family}: block insertion defaults, controls and unrelated buttons passed`);
}
