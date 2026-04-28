document.addEventListener('DOMContentLoaded', () => {
    if (auth.isLoggedIn()) {
        window.location.href = '/index.html';
        return;
    }

    const form = document.getElementById('login-form');
    const errorDiv = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            await auth.login(email, password);
            window.location.href = '/index.html';
        } catch (error) {
            errorDiv.textContent = error.message;
            errorDiv.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    });
});
