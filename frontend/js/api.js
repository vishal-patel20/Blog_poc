const API_BASE_URL = 'http://localhost:8000/api';

/**
 * Helper to make API requests with automatic Authorization header
 */
async function apiFetch(endpoint, options = {}) {
    const token = localStorage.getItem('token');
    
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers,
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const config = {
        ...options,
        headers,
    };

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
        
        // Handle 204 No Content
        if (response.status === 204) {
            return null;
        }

        const data = await response.json();

        if (!response.ok) {
            // Handle token expiration/revocation
            if (response.status === 401) {
                auth.logout(false); // Logout without API call to avoid loops
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
