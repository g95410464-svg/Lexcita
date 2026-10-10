import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.querySelector('span').textContent = visible ? 'visibility_off' : 'visibility';
    });
});

document.querySelectorAll('[data-submit-once]').forEach((form) => {
    const submit = form.querySelector('button[type="submit"]');
    if (submit && !submit.dataset.label) submit.dataset.label = submit.textContent.trim();
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (button && form.checkValidity()) {
            button.disabled = true;
            button.textContent = 'Un momento…';
        }
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-submit-once] button[type="submit"]').forEach((button) => {
        button.disabled = false;
        if (button.dataset.label) button.textContent = button.dataset.label;
    });
});
