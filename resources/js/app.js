import './drafts.js';

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

for (const selector of document.querySelectorAll('[data-account-type]')) {
    const fields = selector
        .closest('form')
        ?.querySelector('[data-business-fields]');
    if (!fields) continue;
    const update = () => {
        const business = selector.value === 'business';
        fields.hidden = !business;
        for (const input of fields.querySelectorAll('input'))
            input.disabled = !business;
    };
    selector.addEventListener('change', update);
    update();
}
