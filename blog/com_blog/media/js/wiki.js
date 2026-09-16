document.querySelectorAll('[data-post-wiki]').forEach((wiki) => {
  const contents = wiki.querySelector('[data-wiki-contents]');
  const headings = wiki.querySelector('[data-wiki-content]').querySelectorAll('h2,h3,h4,h5,h6');
  if (!headings.length) return;
  const stack = [{level: 1, list: contents, last: null}];
  headings.forEach((heading, index) => {
    if (!heading.textContent.trim()) return;
    if (!heading.id) {
      const base = 'wiki-' + (heading.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'section');
      let id = base;
      let suffix = 2;
      while (document.getElementById(id)) id = base + '-' + suffix++;
      heading.id = id;
    }
    const level = Number(heading.tagName.substring(1));
    while (stack.length > 1 && level <= stack[stack.length - 1].level) stack.pop();
    let parent = stack[stack.length - 1];
    if (parent.last && level > Number(parent.last.dataset.level)) {
      const nested = document.createElement('ol');
      parent.last.append(nested);
      parent = {level: Number(parent.last.dataset.level), list: nested, last: null};
      stack.push(parent);
    }
    const item = document.createElement('li');
    item.dataset.level = level;
    const link = document.createElement('a');
    link.href = '#' + encodeURIComponent(heading.id);
    link.textContent = heading.textContent.trim();
    item.append(link);
    parent.list.append(item);
    parent.last = item;
  });
  wiki.querySelector('.wiki-contents').hidden = !contents.children.length;
});
