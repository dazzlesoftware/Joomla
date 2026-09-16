(() => {
  // Multiple family layouts can appear on one page; bind only once.
  if (window.genesisSharePopupBound) return;
  window.genesisSharePopupBound = true;

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-post-share-popup]');
    if (!link || event.defaultPrevented || event.button !== 0
      || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

    const width = Math.min(640, window.screen.availWidth);
    const height = Math.min(640, window.screen.availHeight);
    const left = Math.round(window.screenX + (window.outerWidth - width) / 2);
    const top = Math.round(window.screenY + (window.outerHeight - height) / 2);
    // Open synchronously for popup blockers. Detach the opener before navigation.
    const popup = window.open('about:blank', '_blank',
      `popup=yes,width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    if (!popup) return; // Keep the ordinary link fallback when blocked.
    popup.opener = null;
    popup.location.replace(link.href);
    event.preventDefault();
    popup.focus();
  });
})();
