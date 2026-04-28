document.addEventListener('DOMContentLoaded', () => {
    auth.updateAuthUI();

    const urlParams = new URLSearchParams(window.location.search);
    const postId = urlParams.get('id');

    if (!postId) {
        document.getElementById('post-content').innerHTML = '<div class="alert alert-error">No post ID provided.</div>';
        return;
    }

    loadPost(postId);
    
    // Setup comment form
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const bodyInput = document.getElementById('comment-body');
            const submitBtn = document.getElementById('submit-comment');
            const errorDiv = document.getElementById('comment-error');
            
            errorDiv.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';

            try {
                await api.post(`/posts/${postId}/comments`, { body: bodyInput.value });
                bodyInput.value = '';
                loadComments(postId); // reload comments
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post Comment';
            }
        });
    }
});

async function loadPost(id) {
    const container = document.getElementById('post-content');
    try {
        const response = await api.get(`/posts/${id}`);
        const post = response.data;
        const date = new Date(post.created_at).toLocaleDateString();

        document.title = `${post.title} - Blog REST API`;

        // Format body with paragraphs
        const formattedBody = escapeHtml(post.body).replace(/\n/g, '<br>');

        container.innerHTML = `
            <div class="flex gap-1 align-center mb-1">
                <span class="badge badge-${post.status}">${post.status}</span>
                <span class="text-muted">Posted on ${date}</span>
            </div>
            <h1>${escapeHtml(post.title)}</h1>
            <div style="margin-top: 2rem; font-size: 1.1rem; line-height: 1.8;">
                ${formattedBody}
            </div>
        `;

        document.getElementById('comments-section').classList.remove('hidden');
        loadComments(id);

    } catch (error) {
        container.innerHTML = `<div class="alert alert-error">Failed to load post: ${error.message}</div>`;
    }
}

async function loadComments(postId) {
    const container = document.getElementById('comments-list');
    container.innerHTML = '<div class="skeleton skeleton-text" style="margin: 1.5rem 0;"></div>';

    try {
        const response = await api.get(`/posts/${postId}/comments`);
        const comments = response.data;

        if (comments.length === 0) {
            container.innerHTML = '<p class="text-muted" style="padding: 1.5rem 0;">No comments yet. Be the first!</p>';
            return;
        }

        container.innerHTML = '';
        comments.forEach(comment => {
            const date = new Date(comment.created_at).toLocaleString();
            const div = document.createElement('div');
            div.className = 'comment';
            div.innerHTML = `
                <div class="comment-author">${escapeHtml(comment.author)}</div>
                <div class="comment-date">${date}</div>
                <div class="comment-body">${escapeHtml(comment.body).replace(/\n/g, '<br>')}</div>
            `;
            container.appendChild(div);
        });

    } catch (error) {
        container.innerHTML = `<div class="alert alert-error" style="margin: 1.5rem 0;">Failed to load comments: ${error.message}</div>`;
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
