// Native <details> works without JavaScript. Enhance it for keyboard and link dismissal.
document.querySelectorAll('.mobile-menu').forEach((menu) => {
    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });
    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menu.open = false;
        });
    });
});
