<?php

declare(strict_types=1);

/**
 * Public entry point — all HTTP requests are routed here.
 *
 * To run locally:
 *   php -S localhost:8000 -t public
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// ---------------------------------------------------------------------------
// Load environment variables from .env
// ---------------------------------------------------------------------------
$dotenvPath = dirname(__DIR__);
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
    $dotenv->required(['JWT_SECRET'])->notEmpty();
}

use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\PostController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

// ---------------------------------------------------------------------------
// Boot & Security Pipeline
// ---------------------------------------------------------------------------

$request = new Request();
$router  = new Router();

// 1. Rate Limiting — proxy-aware IP resolution
$trustedProxies = array_filter(
    array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''))
);
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!empty($trustedProxies) && in_array($remoteAddr, $trustedProxies, true)) {
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $clientIp  = trim(explode(',', $forwarded)[0]) ?: $remoteAddr;
} else {
    $clientIp = $remoteAddr;
}
\App\Core\RateLimiter::check($clientIp);

// 2. Determine if route is public (no JWT required)
$method   = $request->getMethod();
$uri      = $request->getUri();

// Handle CORS Preflight immediately
if ($method === 'OPTIONS') {
    Response::noContent();
}

$isPublic = ($method === 'POST' && $uri === '/api/auth/register')
         || ($method === 'POST' && $uri === '/api/auth/login')
         || ($method === 'GET'  && str_starts_with($uri, '/api/posts'));

// 3. JWT Authentication Middleware
$currentUser = null;

if (!$isPublic) {
    $authHeader = $request->header('Authorization', '');
    $token      = Auth::extractBearer((string) $authHeader);

    if ($token === '') {
        Response::error('Unauthorized. Bearer token required.', 401);
    }

    try {
        $currentUser = Auth::decodeToken($token);
    } catch (\Firebase\JWT\ExpiredException) {
        Response::error('Unauthorized. Token has expired. Please log in again.', 401);
    } catch (\Throwable) {
        Response::error('Unauthorized. Invalid token.', 401);
    }

    // Check if token was explicitly revoked (logout)
    if (Auth::isBlacklisted($token)) {
        Response::error('Unauthorized. Token has been revoked. Please log in again.', 401);
    }

    // 4. Content-Type enforcement for write requests
    if (!in_array($method, ['GET', 'DELETE', 'OPTIONS'], true)) {
        $contentType = $request->header('content-type', '');
        if (!str_contains($contentType, 'application/json')) {
            Response::error('Content-Type must be application/json.', 415);
        }
    }

    // 5. RBAC — users can only edit/delete their own posts (handled in PostController)
}

// ---------------------------------------------------------------------------
// Register routes
// ---------------------------------------------------------------------------

$authController    = new AuthController($currentUser);
$postController    = new PostController($currentUser);
$commentController = new CommentController($currentUser);

// Auth routes (register & login are public; logout & me require valid JWT)
$router->post('/api/auth/register', [$authController, 'register']);
$router->post('/api/auth/login',    [$authController, 'login']);
$router->post('/api/auth/logout',   [$authController, 'logout']);
$router->get('/api/auth/me',        [$authController, 'me']);

// Post endpoints
$router->get('/api/posts',         [$postController, 'index']);
$router->get('/api/posts/{id}',    [$postController, 'show']);
$router->post('/api/posts',        [$postController, 'store']);
$router->put('/api/posts/{id}',    [$postController, 'replace']);
$router->patch('/api/posts/{id}',  [$postController, 'update']);
$router->delete('/api/posts/{id}', [$postController, 'destroy']);

// Comment endpoints
$router->get('/api/posts/{id}/comments',  [$commentController, 'index']);
$router->post('/api/posts/{id}/comments', [$commentController, 'store']);

// ---------------------------------------------------------------------------
// Dispatch & handle errors globally
// ---------------------------------------------------------------------------

try {
    $router->dispatch($request);
} catch (NotFoundException $e) {
    Response::error($e->getMessage(), 404);
} catch (ValidationException $e) {
    Response::json(['message' => $e->getMessage(), 'errors' => $e->getErrors()], 422);
} catch (\Throwable $e) {
    error_log('[Blog API Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Internal Server Error.', 500);
}
