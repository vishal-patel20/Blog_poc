<?php

declare(strict_types=1);

/**
 * Public entry point — all HTTP requests are routed here.
 *
 * To run locally:
 *   php -S localhost:8000 -t public
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\CommentController;
use App\Controllers\PostController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

// ---------------------------------------------------------------------------
// Boot
// ---------------------------------------------------------------------------

$request = new Request();
$router  = new Router();

// ---------------------------------------------------------------------------
// Register routes
// ---------------------------------------------------------------------------

$postController    = new PostController();
$commentController = new CommentController();

// Post endpoints
$router->get('/api/posts', [$postController, 'index']);
$router->get('/api/posts/{id}', [$postController, 'show']);
$router->post('/api/posts', [$postController, 'store']);
$router->put('/api/posts/{id}', [$postController, 'replace']);
$router->patch('/api/posts/{id}', [$postController, 'update']);
$router->delete('/api/posts/{id}', [$postController, 'destroy']);

// Comment endpoints
$router->get('/api/posts/{id}/comments', [$commentController, 'index']);
$router->post('/api/posts/{id}/comments', [$commentController, 'store']);

// ---------------------------------------------------------------------------
// Dispatch & handle errors globally
// ---------------------------------------------------------------------------

try {
    $router->dispatch($request);
} catch (NotFoundException $e) {
    Response::error($e->getMessage(), 404);
} catch (ValidationException $e) {
    Response::json([
        'message' => $e->getMessage(),
        'errors'  => $e->getErrors(),
    ], 422);
} catch (\Throwable $e) {
    // Generic server error — hide internals in production
    $debug   = $_ENV['APP_DEBUG'] ?? 'false';
    $message = $debug === 'true' ? $e->getMessage() : 'Internal Server Error.';

    Response::error($message, 500);
}
