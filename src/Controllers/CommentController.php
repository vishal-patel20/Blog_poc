<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Models\Comment;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;

/**
 * CommentController — handles comment endpoints nested under posts.
 *
 * Endpoints:
 *   GET  /api/posts/{id}/comments  — list all comments for a post
 *   POST /api/posts/{id}/comments  — add a comment to a post
 */
class CommentController
{
    private CommentRepository $commentRepository;
    private PostRepository $postRepository;

    public function __construct(private readonly ?object $currentUser = null)
    {
        $this->commentRepository = new CommentRepository();
        $this->postRepository    = new PostRepository();
    }

    // ------------------------------------------------------------------
    // GET /api/posts/{id}/comments
    // ------------------------------------------------------------------

    /**
     * Return all comments for the given post.
     *
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params): void
    {
        // Ensure parent post exists (throws 404 if not)
        $this->postRepository->findById((int) $params['id']);

        $comments = $this->commentRepository->findByPostId((int) $params['id']);

        Response::json([
            'data' => array_map(
                static fn (Comment $c) => $c->toArray(),
                $comments
            ),
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/posts/{id}/comments
    // ------------------------------------------------------------------

    /**
     * Add a new comment to the given post.
     *
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params): void
    {
        // Ensure parent post exists (throws 404 if not)
        $this->postRepository->findById((int) $params['id']);

        $data   = $request->all();
        $errors = [];

        if (!isset($data['author']) || trim((string) $data['author']) === '') {
            $errors['author'][] = 'The author field is required.';
        } elseif (mb_strlen((string) $data['author']) > 100) {
            // Fix #3: Prevent oversized author names
            $errors['author'][] = 'The author field must not exceed 100 characters.';
        }

        if (!isset($data['body']) || trim((string) $data['body']) === '') {
            $errors['body'][] = 'The body field is required.';
        } elseif (mb_strlen((string) $data['body']) > 10000) {
            // Fix #3: Prevent oversized comment bodies (DoS guard)
            $errors['body'][] = 'The body field must not exceed 10,000 characters.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $comment = new Comment(
            postId: (int) $params['id'],
            author: trim((string) $data['author']),
            body:   trim((string) $data['body']),
        );

        $this->commentRepository->save($comment);

        Response::created(['data' => $comment->toArray()]);
    }
}
