document.querySelectorAll('[data-toast]').forEach((toast) => {
    const close = () => {
        toast.classList.add('toast-hide');
        window.setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
    window.setTimeout(close, 3000);
});

const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileNav = document.querySelector('[data-mobile-nav]');

menuToggle?.addEventListener('click', () => {
    const isOpen = mobileNav?.classList.toggle('nav-open') ?? false;
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    menuToggle.textContent = isOpen ? '×' : '☰';
});
