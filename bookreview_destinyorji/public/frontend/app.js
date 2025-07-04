// API Base URL
const API_URL = 'http://localhost:8000';

// DOM Elements
const tabBtns = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const logoutBtn = document.getElementById('logout-btn');
const userInfo = document.getElementById('user-info');
const adminTab = document.getElementById('admin-tab');
const booksGrid = document.getElementById('books-grid');
const bookForm = document.getElementById('book-form');
const addBookForm = document.getElementById('add-book-form');
const bookDetails = document.getElementById('book-details');
const bookInfo = document.getElementById('book-info');
const reviewsList = document.getElementById('reviews-list');
const addReviewForm = document.getElementById('add-review-form');
const reviewForm = document.getElementById('review-form');
const addBookBtn = document.getElementById('add-book-btn');
const manageUsersBtn = document.getElementById('manage-users-btn');
const usersTable = document.getElementById('users-table');
const usersList = document.getElementById('users-list');
const totalBooks = document.getElementById('total-books');
const totalReviews = document.getElementById('total-reviews');
const totalUsers = document.getElementById('total-users');
const notification = document.getElementById('notification');

// State
let currentUser = null;
let currentBook = null;
let books = [];
let reviews = [];
let users = [];

// Tab Navigation
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tabId = btn.dataset.tab;
        
        // Hide all tabs and remove active class
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        
        // Show selected tab and add active class
        btn.classList.add('active');
        document.getElementById(tabId).classList.add('active');
        
        // Load data based on tab
        if (tabId === 'books') {
            loadBooks();
        } else if (tabId === 'admin' && currentUser?.role === 'admin') {
            loadAdminData();
        }
    });
});

// Authentication
loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    
    try {
        const response = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.statuscode === 200) {
            // Store token and user info
            localStorage.setItem('token', data.data.token);
            showNotification('Login successful');
            loginForm.reset();
            checkAuthStatus();
            
            // Switch to books tab
            document.querySelector('[data-tab="books"]').click();
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error logging in', true);
        console.error(error);
    }
});

registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const userData = {
        first_name: document.getElementById('register-first-name').value,
        last_name: document.getElementById('register-last-name').value,
        email: document.getElementById('register-email').value,
        phone: document.getElementById('register-phone').value,
        password: document.getElementById('register-password').value
    };
    
    try {
        const response = await fetch(`${API_URL}/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (data.statuscode === 201) {
            showNotification('Registration successful. Please log in.');
            registerForm.reset();
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error registering user', true);
        console.error(error);
    }
});

logoutBtn.addEventListener('click', () => {
    localStorage.removeItem('token');
    currentUser = null;
    updateAuthUI();
    document.querySelector('[data-tab="auth"]').click();
    showNotification('Logged out successfully');
});

// Books
async function loadBooks() {
    try {
        const response = await fetch(`${API_URL}/books`, {
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        books = data.results || [];
        
        renderBooks();
    } catch (error) {
        showNotification('Error loading books', true);
        console.error(error);
    }
}

function renderBooks() {
    booksGrid.innerHTML = '';
    
    if (books.length === 0) {
        booksGrid.innerHTML = '<p>No books available.</p>';
        return;
    }
    
    books.forEach(book => {
        const bookCard = document.createElement('div');
        bookCard.className = 'book-card';
        bookCard.innerHTML = `
            <img src="${book.cover || 'https://via.placeholder.com/150x200?text=No+Cover'}" alt="${book.title}" class="book-cover">
            <h3>${book.title}</h3>
            <p>By ${book.author}</p>
        `;
        
        bookCard.addEventListener('click', () => {
            currentBook = book;
            showBookDetails();
        });
        
        booksGrid.appendChild(bookCard);
    });
}

async function showBookDetails() {
    // Hide book list and show details
    addBookForm.classList.add('hidden');
    bookDetails.classList.remove('hidden');
    
    // Display book info
    bookInfo.innerHTML = `
        <div class="book-header">
            <img src="${currentBook.cover || 'https://via.placeholder.com/150x200?text=No+Cover'}" alt="${currentBook.title}" class="book-cover">
            <div>
                <h3>${currentBook.title}</h3>
                <p>By ${currentBook.author}</p>
                ${currentUser ? `
                    <div class="book-actions">
                        ${currentUser.id === currentBook.user_id || currentUser.role === 'admin' ? `
                            <button class="btn" onclick="editBook('${currentBook._id}')">Edit</button>
                            <button class="btn" onclick="deleteBook('${currentBook._id}')">Delete</button>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        </div>
        <p class="book-description">${currentBook.description}</p>
    `;
    
    // Load reviews
    await loadReviews(currentBook._id);
    
    // Show add review form if logged in
    if (currentUser) {
        addReviewForm.classList.remove('hidden');
    } else {
        addReviewForm.classList.add('hidden');
    }
}

async function loadReviews(bookId) {
    try {
        const response = await fetch(`${API_URL}/books/${bookId}/reviews`);
        const data = await response.json();
        reviews = data.results || [];
        
        renderReviews();
    } catch (error) {
        showNotification('Error loading reviews', true);
        console.error(error);
    }
}

function renderReviews() {
    reviewsList.innerHTML = '';
    
    if (reviews.length === 0) {
        reviewsList.innerHTML = '<p>No reviews yet.</p>';
        return;
    }
    
    reviews.forEach(review => {
        const reviewEl = document.createElement('div');
        reviewEl.className = 'review';
        reviewEl.innerHTML = `
            <div class="review-header">
                <div class="rating">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</div>
                <div class="review-user">${review.user_name}</div>
            </div>
            <p>${review.text}</p>
            ${currentUser && (currentUser.id === review.user_id || currentUser.role === 'admin') ? `
                <button class="btn" onclick="deleteReview('${review._id}')">Delete</button>
            ` : ''}
        `;
        
        reviewsList.appendChild(reviewEl);
    });
}

bookForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const bookData = {
        title: document.getElementById('book-title').value,
        author: document.getElementById('book-author').value,
        description: document.getElementById('book-description').value,
        cover: document.getElementById('book-cover').value || null
    };
    
    try {
        const response = await fetch(`${API_URL}/books`, {
            method: 'POST',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookData)
        });
        
        const data = await response.json();
        
        if (data.statuscode === 201) {
            showNotification('Book added successfully');
            bookForm.reset();
            addBookForm.classList.add('hidden');
            loadBooks();
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error adding book', true);
        console.error(error);
    }
});

reviewForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const reviewData = {
        rating: parseInt(document.getElementById('review-rating').value),
        text: document.getElementById('review-text').value
    };
    
    try {
        const response = await fetch(`${API_URL}/books/${currentBook._id}/reviews`, {
            method: 'POST',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reviewData)
        });
        
        const data = await response.json();
        
        if (data.statuscode === 201) {
            showNotification('Review added successfully');
            reviewForm.reset();
            loadReviews(currentBook._id);
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error adding review', true);
        console.error(error);
    }
});

async function deleteBook(bookId) {
    if (!confirm('Are you sure you want to delete this book?')) return;
    
    try {
        const response = await fetch(`${API_URL}/books/${bookId}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        
        if (data.statuscode === 200) {
            showNotification('Book deleted successfully');
            bookDetails.classList.add('hidden');
            loadBooks();
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error deleting book', true);
        console.error(error);
    }
}

async function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review?')) return;
    
    try {
        const response = await fetch(`${API_URL}/reviews/${reviewId}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        
        if (data.statuscode === 200) {
            showNotification('Review deleted successfully');
            loadReviews(currentBook._id);
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error deleting review', true);
        console.error(error);
    }
}

// Admin
async function loadAdminData() {
    try {
        // Load stats
        totalBooks.textContent = books.length;
        totalReviews.textContent = reviews.length;
        
        // Load users
        const response = await fetch(`${API_URL}/admin/users`, {
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        
        if (data.statuscode === 200) {
            users = data.data || [];
            totalUsers.textContent = users.length;
        }
    } catch (error) {
        console.error(error);
    }
}

addBookBtn.addEventListener('click', () => {
    bookDetails.classList.add('hidden');
    addBookForm.classList.remove('hidden');
});

manageUsersBtn.addEventListener('click', () => {
    usersTable.classList.toggle('hidden');
    renderUsers();
});

function renderUsers() {
    usersList.innerHTML = '';
    
    if (users.length === 0) {
        usersList.innerHTML = '<tr><td colspan="4">No users found.</td></tr>';
        return;
    }
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.first_name} ${user.last_name}</td>
            <td>${user.email}</td>
            <td>${user.role}</td>
            <td>
                <button class="btn" onclick="changeUserRole('${user._id}', '${user.role === 'admin' ? 'user' : 'admin'}')">Make ${user.role === 'admin' ? 'User' : 'Admin'}</button>
                <button class="btn" onclick="deleteUser('${user._id}')">Delete</button>
            </td>
        `;
        
        usersList.appendChild(row);
    });
}

async function changeUserRole(userId, newRole) {
    try {
        const response = await fetch(`${API_URL}/admin/users/${userId}/role`, {
            method: 'PUT',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ role: newRole })
        });
        
        const data = await response.json();
        
        if (data.statuscode === 200) {
            showNotification(`User role updated to ${newRole}`);
            loadAdminData();
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error updating user role', true);
        console.error(error);
    }
}

async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    
    try {
        const response = await fetch(`${API_URL}/admin/users/${userId}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        
        if (data.statuscode === 200) {
            showNotification('User deleted successfully');
            loadAdminData();
        } else {
            showNotification(data.status, true);
        }
    } catch (error) {
        showNotification('Error deleting user', true);
        console.error(error);
    }
}

// Utility Functions
function getAuthHeaders() {
    const token = localStorage.getItem('token');
    return token ? { 'Authorization': `Bearer ${token}` } : {};
}

function showNotification(message, isError = false) {
    notification.textContent = message;
    notification.className = 'notification show';
    
    if (isError) {
        notification.classList.add('error');
    }
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

async function checkAuthStatus() {
    const token = localStorage.getItem('token');
    
    if (!token) {
        currentUser = null;
        updateAuthUI();
        return;
    }
    
    try {
        // Decode JWT token (this is a simple client-side decode, not verification)
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        
        currentUser = JSON.parse(jsonPayload);
        updateAuthUI();
    } catch (error) {
        localStorage.removeItem('token');
        currentUser = null;
        updateAuthUI();
    }
}

function updateAuthUI() {
    if (currentUser) {
        userInfo.textContent = `Logged in as: ${currentUser.email}`;
        logoutBtn.classList.remove('hidden');
        
        // Show/hide admin tab based on role
        if (currentUser.role === 'admin') {
            adminTab.classList.remove('hidden');
        } else {
            adminTab.classList.add('hidden');
            // If on admin tab, switch to books
            if (document.querySelector('.tab-btn.active').dataset.tab === 'admin') {
                document.querySelector('[data-tab="books"]').click();
            }
        }
    } else {
        userInfo.textContent = 'Not logged in';
        logoutBtn.classList.add('hidden');
        adminTab.classList.add('hidden');
    }
}

// Initialize
checkAuthStatus();