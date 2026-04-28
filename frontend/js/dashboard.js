document.addEventListener('DOMContentLoaded', () => {
    if (!auth.isLoggedIn()) {
        window.location.href = '/login.html';
        return;
    }

    // Role check: All logged-in users can now see post creation.
    const user = auth.getUser();

    loadMyPosts();

    const form = document.getElementById('post-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('post-id').value;
        const title = document.getElementById('title').value;
        const body = document.getElementById('body').value;
        const status = document.getElementById('status').value;
        
        const btnSave = document.getElementById('btn-save');
        const errorDiv = document.getElementById('editor-error');
        
        errorDiv.classList.add('hidden');
        btnSave.disabled = true;
        btnSave.textContent = 'Saving...';

        try {
            if (id) {
                // Update
                await api.put(`/posts/${id}`, { title, body, status });
            } else {
                // Create
                await api.post('/posts', { title, body, status });
            }
            resetEditor();
            loadMyPosts();
        } catch (error) {
            handleError(error, errorDiv);
        } finally {
            btnSave.disabled = false;
            btnSave.textContent = 'Save Post';
        }
    });
});

async function loadMyPosts() {
    const container = document.getElementById('my-posts-container');
    const user = auth.getUser();
    
    try {
        // Fetch posts (we'll fetch a larger page size for the dashboard)
        const response = await api.get('/posts?page=1&per_page=50');
        
        container.innerHTML = '';
        
        // Filter to user's posts (or all if admin)
        const myPosts = response.data.filter(post => post.author_id === user.id || user.role === 'admin');

        if (myPosts.length === 0) {
            container.innerHTML = '<p class="text-muted">You haven\'t created any posts yet.</p>';
            return;
        }

        myPosts.forEach(post => {
            const date = new Date(post.created_at).toLocaleDateString();
            const card = document.createElement('div');
            card.className = 'card mb-1 flex justify-between align-center';
            card.innerHTML = `
                <div>
                    <h4 style="margin-bottom: 0.2rem;"><a href="post.html?id=${post.id}">${escapeHtml(post.title)}</a></h4>
                    <span class="badge badge-${post.status}">${post.status}</span>
                    <small class="text-muted" style="margin-left: 0.5rem;">${date}</small>
                </div>
                <div class="flex gap-1">
                    <button class="btn" onclick="editPost(${post.id})">Edit</button>
                    <button class="btn btn-danger" onclick="deletePost(${post.id})">Delete</button>
                </div>
            `;
            container.appendChild(card);
        });
    } catch (error) {
        container.innerHTML = `<div class="alert alert-error">Failed to load posts: ${error.message}</div>`;
    }
}

async function editPost(id) {
    try {
        const response = await api.get(`/posts/${id}`);
        const post = response.data;
        
        document.getElementById('editor-title').textContent = 'Edit Post';
        document.getElementById('post-id').value = post.id;
        document.getElementById('title').value = post.title;
        document.getElementById('body').value = post.body;
        document.getElementById('status').value = post.status;
        
        document.getElementById('btn-cancel').classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        alert('Failed to load post for editing: ' + error.message);
    }
}

async function deletePost(id) {
    if (confirm('Are you sure you want to delete this post?')) {
        try {
            await api.delete(`/posts/${id}`);
            loadMyPosts();
        } catch (error) {
            alert('Failed to delete post: ' + error.message);
        }
    }
}

function resetEditor() {
    document.getElementById('post-form').reset();
    document.getElementById('post-id').value = '';
    document.getElementById('editor-title').textContent = 'Create New Post';
    document.getElementById('btn-cancel').classList.add('hidden');
}

function handleError(error, errorDiv) {
    if (typeof error.message === 'string') {
        try {
            const parsed = JSON.parse(error.message);
            if (parsed.errors) {
                const messages = Object.values(parsed.errors).flat();
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
}

function escapeHtml(unsafe) {
    return (unsafe || '').toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
