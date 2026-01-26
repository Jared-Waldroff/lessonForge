<?php
/**
 * Simple Router for REST API
 * 
 * Handles HTTP requests and routes them to appropriate controllers.
 * Supports GET, POST, PUT, DELETE methods.
 * 
 * @package LessonForge
 */

namespace LessonForge;

class Router
{
    private array $routes = [];
    private array $middleware = [];

    /**
     * Register a GET route
     */
    public function get(string $path, callable|array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|array $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable|array $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable|array $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    /**
     * Add a route to the routes array
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        // Convert path parameters to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    /**
     * Add middleware to run before routes
     */
    public function addMiddleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Dispatch the request to the appropriate handler
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        // Handle CORS preflight
        if ($method === 'OPTIONS') {
            $this->sendCorsHeaders();
            http_response_code(200);
            exit;
        }

        // Run middleware
        foreach ($this->middleware as $middleware) {
            $result = $middleware();
            if ($result === false) {
                return;
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $this->sendCorsHeaders();
                header('Content-Type: application/json');

                try {
                    $handler = $route['handler'];

                    error_log("Dispatching to handler for " . $uri);

                    if (is_array($handler)) {
                        [$class, $method] = $handler;
                        $controller = new $class();
                        $response = $controller->$method($params);
                    } else {
                        $response = $handler($params);
                    }

                    $json = json_encode($response);
                    if ($json === false) {
                        error_log("JSON Encode Error: " . json_last_error_msg());
                        throw new \Exception("Failed to encode response: " . json_last_error_msg());
                    }
                    echo $json;
                } catch (\Exception $e) {
                    error_log("Router Exception: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode([
                        'error' => true,
                        'message' => $e->getMessage()
                    ]);
                }
                return;
            }
        }

        // No route found
        $this->sendCorsHeaders();
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'Route not found'
        ]);
    }

    /**
     * Send CORS headers for cross-origin requests
     */
    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    /**
     * Get JSON body from request
     */
    public static function getJsonBody(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}
