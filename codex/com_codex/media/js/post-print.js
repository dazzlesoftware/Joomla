document.addEventListener('click', (event) => {
  if (event.target.closest('[data-post-print]')) {
    event.preventDefault();
    window.print();
  }
});
