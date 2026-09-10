const workspace = document.querySelector('.post-editor-workspace');
if (workspace) {
    const focusButton = workspace.querySelector('[data-post-focus]');
    const advanced = workspace.querySelector('#post-advanced');
    const setFocus = (focused) => {
        workspace.classList.toggle('is-focused', focused);
        document.body.classList.toggle('post-editor-focus', focused);
        focusButton.setAttribute('aria-pressed', String(focused));
    };
    focusButton.hidden = false;
    focusButton.addEventListener('click', () => setFocus(!workspace.classList.contains('is-focused')));
    workspace.querySelector('[data-post-advanced-link]').addEventListener('click', () => {
        advanced.open = true;
    });
    // Reveal invalid controls before Joomla or the browser tries to focus them.
    const reveal = (field) => {
        if (!workspace.contains(field)) return;
        if (field.closest('#post-settings')) setFocus(false);
        for (let parent = field.parentElement; parent && parent !== workspace; parent = parent.parentElement) {
            if (parent.tagName === 'DETAILS') parent.open = true;
        }
    };
    workspace.closest('form').addEventListener('invalid', (event) => reveal(event.target), true);
    const observer = new MutationObserver((mutations) => {
        for (const {target} of mutations) {
            if (target.getAttribute('aria-invalid') === 'true' || target.classList.contains('invalid')) reveal(target);
        }
    });
    observer.observe(workspace, {subtree: true, attributes: true, attributeFilter: ['class', 'aria-invalid']});
}
