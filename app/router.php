<?php
/**
 * Lightweight URL Router
 *
 * Parses the request URI and dispatches to the appropriate controller method.
 */

class Router
{
    private array $routes = [];

    /**
     * Register a GET route
     */
    public function get(string $pattern, callable $handler): void
    {
        $this->routes['GET'][$pattern] = $handler;
    }

    /**
     * Register a POST route
     */
    public function post(string $pattern, callable $handler): void
    {
        $this->routes['POST'][$pattern] = $handler;
    }

    /**
     * Match and dispatch the current request
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash (except for root)
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $handler) {
            $regex = $this->patternToRegex($pattern);
            if (preg_match($regex, $uri, $matches)) {
                // Remove the full match, keep named captures
                array_shift($matches);
                // Filter to only named params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($handler, $params);
                return;
            }
        }

        // No route matched
        http_response_code(404);
        require __DIR__ . '/views/public/404.php';
    }

    /**
     * Convert route pattern to regex
     * Supports {param} syntax: /specimens/{slug} → /specimens/(?P<slug>[^/]+)
     */
    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}
