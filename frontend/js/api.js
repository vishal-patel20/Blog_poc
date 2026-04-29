const API_BASE_URL = 'http://localhost:8000/api';

// Cache the CSRF token in memory to avoid fetching on every request
let _csrfToken = null;

async function getCsrfToken() {
    if (_csrfToken) return _csrfToken;
    try {
        const res = await fetch(`${API_BASE_URL}/csrf-token`, { credentials: 'include' });
        const data = await res.json();
        _csrfToken = data.token;
    } catch (e) {
        console.error('Failed to fetch CSRF token', e);
    }
    return _csrfToken;
}

/**
 * Helper to make API requests with secure session cookies and CSRF headers.
 */
async function apiFetch(endpoint, options = {}) {
    const method = options.method || 'GET';

    const headers = {
        'Content-Type': 'application/json',
        ...options.headers,
    };

    // Attach CSRF token for all state-changing requests
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
        const csrfToken = await getCsrfToken();
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }
    }

    const config = {
        ...options,
        headers,
        credentials: 'include', // Always send session cookie
    };

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, config);

        // Handle 204 No Content
        if (response.status === 204) {
            return null;
        }

        const data = await response.json();

        if (!response.ok) {
            // Session expired — redirect to login
            if (response.status === 401) {
                _csrfToken = null; // Invalidate CSRF token cache
                if (typeof auth !== 'undefined') {
                    auth.logout(false);
                }
            }
            throw new Error(data.error || data.message || 'An error occurred');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

const api = {
    get: (endpoint) => {
        const separator = endpoint.includes('?') ? '&' : '?';
        const url = `${endpoint}${separator}_t=${Date.now()}`;
        return apiFetch(url, { method: 'GET' });
    },
    post: (endpoint, body) => apiFetch(endpoint, { method: 'POST', body: JSON.stringify(body) }),
    put: (endpoint, body) => apiFetch(endpoint, { method: 'PUT', body: JSON.stringify(body) }),
    patch: (endpoint, body) => apiFetch(endpoint, { method: 'PATCH', body: JSON.stringify(body) }),
    delete: (endpoint) => apiFetch(endpoint, { method: 'DELETE' }),
};
