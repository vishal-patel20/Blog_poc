document.addEventListener('DOMContentLoaded', () => {
    if (auth.isLoggedIn()) {
        window.location.href = '/index.html';
        return;
    }

    const form = document.getElementById('register-form');
    const errorDiv = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating Account...';

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            await auth.register(name, email, password);
            window.location.href = '/index.html';
        } catch (error) {
            // Handle validation errors from API
            if (typeof error.message === 'string') {
                try {
                    const parsed = JSON.parse(error.message);
                    if (parsed.errors) {
                        const escapeHtml = (s) => (s||'').toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
                        const messages = Object.values(parsed.errors).flat().map(escapeHtml);
                        errorDiv.innerHTML = messages.join('<br>');
                    } else {
                        errorDiv.textContent = parsed.message || error.message;
                    }
                } catch {
                    errorDiv.textContent = error.message;
                }
            } else {
                errorDiv.textContent = error.message;
            }
            errorDiv.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Account';
        }
    });
});
