/* Eve & Steve Salon - unobtrusive, accessible admin password toggles */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.salon-admin input[type="password"]').forEach((input) => {
        if (input.closest('.admin-password-control')) return;
        const wrapper = document.createElement('span');
        wrapper.className = 'admin-password-control';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'admin-password-toggle';
        button.textContent = 'Show';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Hide' : 'Show';
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(visible));
        });
        wrapper.appendChild(button);
    });
});
