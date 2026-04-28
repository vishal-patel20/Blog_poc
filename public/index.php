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
// Uses vlucas/phpdotenv to populate $_ENV from the project-root .env file.
// On production servers that inject env vars at the OS/container level,
// the .env file may not exist — we fail gracefully in that case.
$dotenvPath = dirname(__DIR__);
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();

    // Validate required keys are present and non-empty
    $dotenv->required(['API_KEY'])->notEmpty();
}

use App\Controllers\CommentController;
use App\Controllers\PostController;
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

// 1. Rate Limiting Check
// Vulnerability Fix #14: Safe proxy-aware IP resolution.
// Set TRUSTED_PROXIES in .env to the IPs of your load balancers.
// If unset, REMOTE_ADDR is used directly (correct for non-proxied servers).
$trustedProxies = array_filter(
    array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''))
);
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if (!empty($trustedProxies) && in_array($remoteAddr, $trustedProxies, true)) {
    // Trust X-Forwarded-For only when request arrives from a known proxy
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $clientIp  = trim(explode(',', $forwarded)[0]) ?: $remoteAddr;
} else {
    $clientIp = $remoteAddr;
}
\App\Core\RateLimiter::check($clientIp);

// 2. Authentication Check (all endpoints except OPTIONS preflight)
//    Vulnerability Fix #1: Auth now required for ALL methods, including GET,
//    to prevent unauthenticated enumeration of draft/private posts.
if ($request->getMethod() !== 'OPTIONS') {
    // Fail hard if API_KEY is not configured — no insecure fallback
    $apiKey = $_ENV['API_KEY'] ?? null;
    if ($apiKey === null || $apiKey === '') {
        Response::error('Server misconfiguration. Contact administrator.', 500);
    }

    // Case-insensitive Bearer token extraction
    $authHeader = $request->header('Authorization', '');
    $token = trim((string) preg_replace('/^Bearer\s+/i', '', $authHeader));

    // Vulnerability Fix #2: Use constant-time comparison to prevent timing attacks
    if (!hash_equals((string) $apiKey, $token)) {
        Response::error('Unauthorized. Invalid or missing API Key.', 401);
    }

    // Vulnerability Fix #15: Enforce Content-Type: application/json on write requests.
    // Prevents confusion attacks from form-encoded or plain-text payloads.
    if (!in_array($request->getMethod(), ['GET', 'DELETE', 'OPTIONS'], true)) {
        $contentType = $request->header('content-type', '');
        if (!str_contains($contentType, 'application/json')) {
            Response::error('Content-Type must be application/json.', 415);
        }
    }
}

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
    // Vulnerability Fix #3: Always return a generic message; log full details server-side.
    // Never expose exception messages, file paths, or stack traces to clients.
    error_log(
        '[Blog API Error] ' . $e->getMessage() .
        ' in ' . $e->getFile() . ':' . $e->getLine()
    );
    Response::error('Internal Server Error.', 500);
}
