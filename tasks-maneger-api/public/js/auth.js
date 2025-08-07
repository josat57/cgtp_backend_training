/**
 * Authentication utilities for handling JWT tokens
 */

// Store the JWT token in localStorage
function storeToken(token) {
    localStorage.setItem('jwtToken', token);
}

// Get the stored JWT token
function getToken() {
    return localStorage.getItem('jwtToken');
}

// Remove the stored JWT token (logout)
function removeToken() {
    localStorage.removeItem('jwtToken');
}

// Check if a token exists
function hasToken() {
    return !!getToken();
}

// Handle API requests with automatic token refresh
async function apiRequest(url, method, data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: data ? JSON.stringify(data) : undefined
    };
    
    // Add token to headers if available
    const token = getToken();
    if (token) {
        options.headers['Authorization'] = `Bearer ${token}`;
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        // Check if token expired
        if (result.status === 'error' && result.message === 'Token expired' && result.code === 401) {
            // Try to refresh the token
            const refreshResult = await refreshToken();
            
            if (refreshResult.success) {
                // Retry the original request with the new token
                return apiRequest(url, method, data);
            } else {
                // If refresh failed, redirect to login
                removeToken();
                window.location.href = '/login.html';
                return { status: 'error', message: 'Session expired. Please login again.' };
            }
        }
        
        return result;
    } catch (error) {
        return { status: 'error', message: `Request failed: ${error.message}` };
    }
}

// Refresh the token
async function refreshToken() {
    const token = getToken();
    if (!token) {
        return { success: false };
    }
    
    try {
        const response = await fetch('/api/refresh-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token: token })
        });
        
        const result = await response.json();
        
        if (result.status === 'success' && result.data && result.data.token) {
            // Store the new token
            storeToken(result.data.token);
            return { success: true };
        } else {
            return { success: false };
        }
    } catch (error) {
        return { success: false };
    }
}