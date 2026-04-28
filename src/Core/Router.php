<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

/**
 * Custom HTTP Router.
 *
 * Supports GET, POST, PUT, PATCH, DELETE.
 * Routes are matched against the request URI and method.
 */
class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $path, callable $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Dispatch the incoming request to the matching route handler.
     *
     * @throws NotFoundException
     */
    public function dispatch(Request $request): mixed
    {
        $method = $request->getMethod();
        $uri    = $request->getUri();

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = $this->patternToRegex($pattern);

            if (preg_match($regex, $uri, $matches)) {
                // Remove numeric keys, keep named captures
                $params = array_filter(
                    $matches,
                    static fn ($key) => !is_int($key),
                    ARRAY_FILTER_USE_KEY
                );

                return $handler($request, $params);
            }
        }

        // Fix #5: Do not reflect user-controlled URI in error messages (info disclosure)
        throw new NotFoundException('Route not found.');
    }

    /**
     * Store a route handler indexed by method and path pattern.
     */
    private function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Convert a route pattern like /api/posts/{id} into a named-capture regex.
     */
    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $regex . '$#';
    }
}
