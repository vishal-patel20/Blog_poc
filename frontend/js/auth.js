const auth = {
    /**
     * Check if user is currently logged in
     */
    isLoggedIn: () => {
        return !!localStorage.getItem('token');
    },

    /**
     * Get current user data from local storage
     */
    getUser: () => {
        const userStr = localStorage.getItem('user');
        return userStr ? JSON.parse(userStr) : null;
    },

    /**
     * Login user
     */
    login: async (email, password) => {
        const data = await api.post('/auth/login', { email, password });
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return data;
    },

    /**
     * Register new user
     */
    register: async (name, email, password) => {
        const data = await api.post('/auth/register', { name, email, password });
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return data;
    },

    /**
     * Logout user
     */
    logout: async (callApi = true) => {
        if (callApi && auth.isLoggedIn()) {
            try {
                await api.post('/auth/logout', {});
            } catch (e) {
                console.error('Logout API failed', e);
            }
        }
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login.html';
    },

    /**
     * Helper to update UI based on auth state
     */
    updateAuthUI: () => {
        const loggedInElements = document.querySelectorAll('.auth-required');
        const loggedOutElements = document.querySelectorAll('.guest-only');
        const userDisplay = document.getElementById('current-user-name');
        
        const loggedIn = auth.isLoggedIn();
        
        if (loggedIn) {
            loggedInElements.forEach(el => el.classList.remove('hidden'));
            loggedOutElements.forEach(el => el.classList.add('hidden'));
            const user = auth.getUser();
            if (userDisplay && user) {
                userDisplay.textContent = user.name;
            }
        } else {
            loggedInElements.forEach(el => el.classList.add('hidden'));
            loggedOutElements.forEach(el => el.classList.remove('hidden'));
        }
    }
};

// Expose to global scope for button onclick handlers (e.g. logout)
window.auth = auth;
