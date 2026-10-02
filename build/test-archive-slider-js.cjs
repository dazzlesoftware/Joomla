const fs=require('fs'), vm=require('vm'), assert=require('assert');
for(const family of ['academy','blog','codex'])for(const reduced of [false,true]){
 let cycles=0,pauses=0;const events={}, clicks={}, attrs={};
 const choice={dataset:{bsSlideTo:'1'},classList:{toggle:(k,v)=>{choice.active=v}},setAttribute:(k,v)=>{choice[k]=v}};
 const section={addEventListener:(k,v)=>events[k]=v,querySelectorAll:()=>[choice]};
 const button={closest:()=>section,addEventListener:(k,v)=>clicks[k]=v,setAttribute:(k,v)=>attrs[k]=v,getAttribute:k=>attrs[k]};
 const document={addEventListener:(k,fn)=>fn(),querySelectorAll:s=>s.includes('pause')?[button]:[section]};
 const window={matchMedia:()=>({matches:reduced}),bootstrap:{Carousel:{getOrCreateInstance:()=>({pause:()=>pauses++,cycle:()=>cycles++})}}};
 vm.runInNewContext(fs.readFileSync(`${family}/com_${family}/media/js/archive-featured-slider.js`,'utf8'),{document,window});
 assert.equal(attrs['aria-pressed'],String(reduced));assert.equal(cycles,reduced?0:1);
 clicks.click();assert.equal(attrs['aria-pressed'],String(!reduced));
 events.focusin();assert.equal(attrs['aria-pressed'],'true');let before=cycles;events.mouseleave();assert.equal(cycles,before);
 clicks.click();events.mouseenter();assert(pauses>0);events.mouseleave();assert(cycles>before);
 events['slid.bs.carousel']({to:1});assert(choice.active);assert.equal(choice['aria-current'],'true');
 console.log(`${family}: autoplay, reduced motion, pause/resume, hover, focus and navigation state passed.`);
}
