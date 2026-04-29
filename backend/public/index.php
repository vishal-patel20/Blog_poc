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

// 1. Initialize Strict Sessions
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 2. Apply Security Headers
\App\Core\SecurityHeaders::apply();

$request = new Request();
$router  = new Router();

// 3. Rate Limiting — proxy-aware IP resolution
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

// 4. Determine if route is public
$method   = $request->getMethod();
$uri      = $request->getUri();

if ($method === 'OPTIONS') {
    Response::noContent();
}

// Add CSRF endpoint to public routes
$isPublic = ($method === 'POST' && $uri === '/api/auth/register')
         || ($method === 'POST' && $uri === '/api/auth/login')
         || ($method === 'GET'  && $uri === '/api/csrf-token')
         || ($method === 'GET'  && str_starts_with($uri, '/api/posts'));

// 5. Session & CSRF Middleware
$currentUser = null;

if (!$isPublic) {
    // CSRF Protection for state-changing requests
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $token = $request->header('X-CSRF-Token', $_POST['_token'] ?? '');
        if (!\App\Core\CsrfToken::validate($token)) {
            Response::error('CSRF token mismatch', 403);
        }
    }

    if (!isset($_SESSION['user_id'])) {
        Response::error('Unauthorized. Please log in.', 401);
    }

    // Absolute timeout (8 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
        session_unset();
        session_destroy();
        Response::error('Session expired (Absolute Timeout). Please log in again.', 401);
    }

    // Idle timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        Response::error('Session expired due to inactivity. Please log in again.', 401);
    }

    // Session Data Validation
    if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $clientIp) {
        session_unset();
        session_destroy();
        Response::error('Session invalid (IP changed). Please log in again.', 401);
    }
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_unset();
        session_destroy();
        Response::error('Session invalid (User Agent changed). Please log in again.', 401);
    }

    // Update idle timeout
    $_SESSION['last_activity'] = time();

    // Map session data to a currentUser object (mimicking old JWT payload)
    $currentUser = (object)[
        'sub'   => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'name'  => $_SESSION['name'],
        'role'  => $_SESSION['role'],
    ];

    // 6. Content-Type enforcement for write requests
    if (!in_array($method, ['GET', 'DELETE', 'OPTIONS'], true)) {
        $contentType = $request->header('content-type', '');
        if (!str_contains($contentType, 'application/json')) {
            Response::error('Content-Type must be application/json.', 415);
        }
    }
}

// ---------------------------------------------------------------------------
// Register routes
// ---------------------------------------------------------------------------

$authController    = new AuthController($currentUser);
$postController    = new PostController($currentUser);
$commentController = new CommentController($currentUser);

// CSRF endpoint
$router->get('/api/csrf-token', function() {
    \App\Core\Response::json(['token' => \App\Core\CsrfToken::generate()]);
});

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
