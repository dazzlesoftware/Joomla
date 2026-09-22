// Browser-only test double. Production imports Joomla's editor-api.
export const JoomlaEditor={getActive:()=>Joomla.editors.instances.jform_post_content};
export const JoomlaEditorButton={actions:{'modal-media':()=>{window.browsed=true;}},getActionHandler(name){return this.actions[name];},registerAction(name,handler){this.actions[name]=handler;}};
