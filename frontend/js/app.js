let currentPage = 1;
const perPage = 5;

document.addEventListener('DOMContentLoaded', async () => {
    // Initialize UI based on auth state (fetch user from session)
    await auth.loadUser();
    auth.updateAuthUI();
    
    // Load posts
    loadPosts(currentPage);

    // Setup pagination listeners
    document.getElementById('btn-prev').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadPosts(currentPage);
        }
    });

    document.getElementById('btn-next').addEventListener('click', () => {
        const totalPages = parseInt(document.getElementById('total-pages').textContent);
        if (currentPage < totalPages) {
            currentPage++;
            loadPosts(currentPage);
        }
    });
});

async function loadPosts(page) {
    const container = document.getElementById('posts-container');
    const pagination = document.getElementById('pagination-controls');
    
    // Show loading skeleton if clearing
    if (page === 1) {
       container.innerHTML = `
            <div class="card mb-1">
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text skeleton-text-short"></div>
            </div>
            <div class="card mb-1">
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text skeleton-text-short"></div>
            </div>
        `;
    }

    try {
        const response = await api.get(`/posts?page=${page}&per_page=${perPage}`);
        
        container.innerHTML = ''; // Clear container
        
        if (!response.data || response.data.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">No posts found. Check back later!</p>';
            pagination.classList.add('hidden');
            return;
        }

        // Render posts
        response.data.forEach(post => {
            // Only show published posts to public
            if (post.status !== 'published' && (!auth.isLoggedIn() || (auth.getUser().id !== post.author_id && auth.getUser().role !== 'admin'))) {
                return; // Skip rendering
            }

            const excerpt = post.body.length > 150 ? post.body.substring(0, 150) + '...' : post.body;
            const date = new Date(post.created_at).toLocaleDateString();
            
            const card = document.createElement('div');
            card.className = 'card mb-1';
            card.innerHTML = `
                <h2><a href="post.html?id=${post.id}">${escapeHtml(post.title)}</a></h2>
                <div class="flex gap-1 align-center mb-1">
                    <span class="badge badge-${post.status}">${post.status}</span>
                    <small class="text-muted">Posted on ${date}</small>
                </div>
                <p>${escapeHtml(excerpt)}</p>
                <a href="post.html?id=${post.id}" class="btn mt-1">Read More &rarr;</a>
            `;
            container.appendChild(card);
        });

        // Update Pagination Info
        const meta = response.meta;
        document.getElementById('current-page').textContent = meta.current_page;
        document.getElementById('total-pages').textContent = meta.last_page;
        
        document.getElementById('btn-prev').disabled = meta.current_page <= 1;
        document.getElementById('btn-next').disabled = meta.current_page >= meta.last_page;
        
        if (meta.last_page > 1) {
            pagination.classList.remove('hidden');
        } else {
            pagination.classList.add('hidden');
        }

    } catch (error) {
        if (error.message.includes('Unauthorized') || error.message.includes('token')) {
            container.innerHTML = `
                <div class="card text-center" style="padding: 3rem 1.5rem;">
                    <h3 style="margin-bottom: 1rem;">Welcome to BlogAPI</h3>
                    <p class="text-muted mb-2">You must be logged in to read and interact with posts.</p>
                    <a href="login.html" class="btn btn-primary">Login Now</a>
                </div>`;
            pagination.classList.add('hidden');
        } else {
            container.innerHTML = `<div class="alert alert-error">Failed to load posts: ${error.message}</div>`;
        }
    }
}

function escapeHtml(unsafe) {
    return (unsafe || '').toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
