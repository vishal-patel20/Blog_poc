<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Models\Post;
use App\Repositories\PostRepository;

/**
 * PostController — handles all /api/posts endpoints.
 *
 * Endpoints:
 *   GET    /api/posts          — paginated list
 *   GET    /api/posts/{id}     — single post
 *   POST   /api/posts          — create
 *   PUT    /api/posts/{id}     — full update
 *   PATCH  /api/posts/{id}     — partial update
 *   DELETE /api/posts/{id}     — soft-delete
 */
class PostController
{
    private PostRepository $repository;

    public function __construct()
    {
        $this->repository = new PostRepository();
    }

    // ------------------------------------------------------------------
    // GET /api/posts
    // ------------------------------------------------------------------

    /**
     * Return a paginated list of all non-deleted posts.
     */
    public function index(Request $request): void
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));

        $posts = $this->repository->findAll($page, $perPage);
        $total = $this->repository->count();

        Response::json([
            'data'  => array_map(
                static fn (Post $p) => $p->toArray(),
                $posts
            ),
            'meta'  => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/posts/{id}
    // ------------------------------------------------------------------

    /**
     * Return a single post by ID.
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): void
    {
        $post = $this->repository->findById((int) $params['id']);

        Response::json(['data' => $post->toArray()]);
    }

    // ------------------------------------------------------------------
    // POST /api/posts
    // ------------------------------------------------------------------

    /**
     * Create a new post after validating required fields.
     */
    public function store(Request $request): void
    {
        $data = $request->all();

        $this->validatePost($data, required: ['title', 'body']);

        $post = new Post(
            title:  trim((string) $data['title']),
            body:   trim((string) $data['body']),
            status: trim((string) ($data['status'] ?? 'draft')),
        );

        $id = $this->repository->save($post);

        Response::created(['data' => $post->toArray()]);
    }

    // ------------------------------------------------------------------
    // PUT /api/posts/{id}
    // ------------------------------------------------------------------

    /**
     * Fully replace an existing post (all fields required).
     *
     * @param array<string, string> $params
     */
    public function replace(Request $request, array $params): void
    {
        $post = $this->repository->findById((int) $params['id']);
        $data = $request->all();

        $this->validatePost($data, required: ['title', 'body', 'status']);

        $post->setTitle(trim((string) $data['title']));
        $post->setBody(trim((string) $data['body']));
        $post->setStatus(trim((string) $data['status']));

        $this->repository->update($post);

        Response::json(['data' => $post->toArray()]);
    }

    // ------------------------------------------------------------------
    // PATCH /api/posts/{id}
    // ------------------------------------------------------------------

    /**
     * Partially update an existing post (at least one field required).
     *
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): void
    {
        $post = $this->repository->findById((int) $params['id']);
        $data = $request->all();

        if (empty($data)) {
            throw new ValidationException(['body' => ['Request body must not be empty.']]);
        }

        if (isset($data['title'])) {
            $post->setTitle(trim((string) $data['title']));
        }

        if (isset($data['body'])) {
            $post->setBody(trim((string) $data['body']));
        }

        if (isset($data['status'])) {
            $this->validateStatus((string) $data['status']);
            $post->setStatus(trim((string) $data['status']));
        }

        $this->repository->update($post);

        Response::json(['data' => $post->toArray()]);
    }

    // ------------------------------------------------------------------
    // DELETE /api/posts/{id}
    // ------------------------------------------------------------------

    /**
     * Soft-delete a post.
     *
     * @param array<string, string> $params
     */
    public function destroy(Request $request, array $params): void
    {
        $this->repository->delete((int) $params['id']);

        Response::json(['message' => 'Post deleted successfully.']);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Validate post input fields and throw ValidationException on failure.
     *
     * Fix #3: Added max-length guards to prevent DoS via oversized payloads.
     *
     * @param array<string, mixed> $data
     * @param string[]             $required
     * @throws ValidationException
     */
    private function validatePost(array $data, array $required): void
    {
        $errors = [];

        // Required field check
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field][] = "The {$field} field is required.";
            }
        }

        // Fix #3: Max length enforcement
        $maxLengths = [
            'title'  => 255,
            'body'   => 65535,
            'status' => 20,
        ];

        foreach ($maxLengths as $field => $max) {
            if (isset($data[$field]) && mb_strlen((string) $data[$field]) > $max) {
                $errors[$field][] = "The {$field} must not exceed {$max} characters.";
            }
        }

        if (isset($data['status'])) {
            try {
                $this->validateStatus((string) $data['status']);
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->getErrors());
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Validate that a status value is one of the accepted values.
     *
     * @throws ValidationException
     */
    private function validateStatus(string $status): void
    {
        $allowed = ['draft', 'published', 'archived'];

        if (!in_array($status, $allowed, true)) {
            throw new ValidationException([
                'status' => ['Status must be one of: ' . implode(', ', $allowed) . '.'],
            ]);
        }
    }
}
