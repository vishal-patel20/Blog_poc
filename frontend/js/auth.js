// In-memory user cache — avoids repeated /auth/me calls within one page load
let _currentUser = null;

const auth = {
    /**
     * Check if user is currently logged in by pinging the backend.
     * Uses in-memory cache to avoid repeated network calls on same page.
     */
    isLoggedIn: () => {
        return _currentUser !== null;
    },

    /**
     * Get user from in-memory cache (must call loadUser first)
     */
    getUser: () => _currentUser,

    /**
     * Fetch the current session user from the backend.
     */
    loadUser: async () => {
        try {
            const data = await api.get('/auth/me');
            _currentUser = data.data;
        } catch (e) {
            _currentUser = null;
        }
        return _currentUser;
    },

    /**
     * Login user — session cookie is set by the server automatically.
     */
    login: async (email, password) => {
        const data = await api.post('/auth/login', { email, password });
        _currentUser = data.user;
        _csrfToken = null; // Refresh CSRF token after login
        return data;
    },

    /**
     * Register new user — also creates session.
     */
    register: async (name, email, password) => {
        const data = await api.post('/auth/register', { name, email, password });
        _currentUser = data.user;
        _csrfToken = null;
        return data;
    },

    /**
     * Logout — destroys session on server and clears local cache.
     */
    logout: async (callApi = true) => {
        if (callApi) {
            try {
                await api.post('/auth/logout', {});
            } catch (e) {
                console.error('Logout API failed', e);
            }
        }
        _currentUser = null;
        _csrfToken = null;
        window.location.href = '/login.html';
    },

    /**
     * Helper to update UI based on auth state.
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

