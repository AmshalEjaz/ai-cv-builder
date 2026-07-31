document.querySelectorAll('[data-toast]').forEach((toast) => {
    const close = () => {
        toast.classList.add('toast-hide');
        window.setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
    window.setTimeout(close, 3000);
});
