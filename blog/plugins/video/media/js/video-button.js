import { JoomlaEditorButton } from 'editor-api';

const escapeAttribute = value => String(value || '').replace(/["{}\r\n]/g, '').trim();

const field = (label, name, type = 'text', extra = '') => `
  <div class="mb-3">
    <label class="form-label" for="video-${name}">${label}</label>
    <input class="form-control" id="video-${name}" name="${name}" type="${type}" ${extra}>
  </div>`;

const openVideoDialog = (editor) => {
  document.getElementById('joomla-video-dialog')?.remove();

  const dialog = document.createElement('dialog');
  dialog.id = 'joomla-video-dialog';
  dialog.className = 'p-0 border-0 rounded shadow-lg';
  dialog.style.width = 'min(760px, calc(100vw - 2rem))';
  dialog.innerHTML = `
    <form method="dialog" class="bg-body">
      <div class="d-flex align-items-center justify-content-between border-bottom px-4 py-3">
        <h2 class="h4 m-0">Insert Video</h2>
        <button type="button" class="btn-close" data-video-cancel aria-label="Close"></button>
      </div>
      <div class="p-4" style="max-height:70vh;overflow:auto">
        <div class="mb-3">
          <label class="form-label" for="video-source">Source type</label>
          <select class="form-select" id="video-source" name="source">
            <option value="embed">YouTube or Vimeo</option>
            <option value="local">HTML5 video (MP4/WebM/OGV)</option>
          </select>
        </div>
        ${field('Video URL or file path', 'url', 'text', 'required placeholder="https://youtu.be/... or images/video.mp4"')}
        ${field('Accessible title', 'title', 'text', 'placeholder="Video title"')}
        <div class="row">
          <div class="col-md-6">${field('Poster image', 'poster', 'text', 'placeholder="images/poster.jpg"')}</div>
          <div class="col-md-6">${field('Subtitle file', 'subtitle', 'text', 'placeholder="images/captions.vtt"')}</div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <label class="form-label" for="video-ratio">Aspect ratio</label>
            <select class="form-select mb-3" id="video-ratio" name="ratio">
              <option value="16by9">16:9</option><option value="9by16">9:16</option>
              <option value="4by3">4:3</option><option value="1by1">1:1</option>
            </select>
          </div>
          <div class="col-md-4">${field('Start time (seconds)', 'start', 'number', 'min="0"')}</div>
          <div class="col-md-4">${field('End time (seconds)', 'end', 'number', 'min="0"')}</div>
        </div>
        <div class="row">
          <div class="col-md-6">${field('Subtitle language', 'srclang', 'text', 'value="en"')}</div>
          <div class="col-md-6">${field('Subtitle label', 'label', 'text', 'value="English"')}</div>
        </div>
        <div class="d-flex flex-wrap gap-4">
          ${['controls', 'autoplay', 'muted', 'loop', 'nocookie', 'nodownload'].map((name, index) => `
            <label class="form-check"><input class="form-check-input" type="checkbox" name="${name}" ${name === 'controls' || name === 'nocookie' ? 'checked' : ''}> ${name === 'nocookie' ? 'YouTube privacy mode' : name}</label>`).join('')}
        </div>
      </div>
      <div class="border-top px-4 py-3 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-video-cancel>Cancel</button>
        <button type="submit" class="btn btn-primary">Insert Video</button>
      </div>
    </form>`;

  document.body.appendChild(dialog);
  dialog.querySelectorAll('[data-video-cancel]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.querySelector('form').addEventListener('submit', event => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const url = escapeAttribute(data.get('url'));
    if (!url) return;

    const attributes = [`url="${url}"`, `type="${escapeAttribute(data.get('source'))}"`];
    ['title', 'poster', 'subtitle', 'ratio', 'start', 'end', 'srclang', 'label'].forEach(name => {
      const value = escapeAttribute(data.get(name));
      if (value) attributes.push(`${name}="${value}"`);
    });
    ['controls', 'autoplay', 'muted', 'loop', 'nocookie', 'nodownload'].forEach(name => {
      if (data.has(name)) attributes.push(`${name}="1"`);
    });

    editor.replaceSelection(`{video ${attributes.join(' ')}}`);
    dialog.close();
  });
  dialog.addEventListener('close', () => dialog.remove(), {once: true});
  dialog.showModal();
};

JoomlaEditorButton.registerAction('insert-blog-video', openVideoDialog);
