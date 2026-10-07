<?php
/**
 * Istanbul University MIS Alumni Portal
 * Core Application Router
 *
 * Dedicated routing layer providing parametric URL matching, HTTP method
 * dispatching, and controller action execution.
 */

class Router {
    /**
     * Registered route list.
     * Each entry contains method, path pattern, regex pattern, and handler.
     */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string $path URL route pattern (e.g. /users or /users/{id}).
     * @param callable|array $handler Controller action or callback.
     * @return self
     */
    public function get(string $path, callable|array $handler): self {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     *
     * @param string $path URL route pattern.
     * @param callable|array $handler Controller action or callback.
     * @return self
     */
    public function post(string $path, callable|array $handler): self {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     *
     * @param string $path URL route pattern.
     * @param callable|array $handler Controller action or callback.
     * @return self
     */
    public function put(string $path, callable|array $handler): self {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a PATCH route.
     *
     * @param string $path URL route pattern.
     * @param callable|array $handler Controller action or callback.
     * @return self
     */
    public function patch(string $path, callable|array $handler): self {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register a DELETE route.
     *
     * @param string $path URL route pattern.
     * @param callable|array $handler Controller action or callback.
     * @return self
     */
    public function delete(string $path, callable|array $handler): self {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Generic route registration method.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH).
     * @param string $path URI path pattern.
     * @param callable|array $handler Handler specification.
     * @return self
     */
    public function addRoute(string $method, string $path, callable|array $handler): self {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        // Convert route pattern with placeholders like {id} into regular expression
        $pattern = preg_replace_callback('#\{([a-zA-Z0-9_]+)\}#', function ($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $normalizedPath);

        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'  => strtoupper(trim($method)),
            'path'    => $normalizedPath,
            'regex'   => $regex,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * Retrieve all registered routes.
     *
     * @return array
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * Find a matching route for the specified HTTP method and URI path.
     *
     * @param string $method HTTP request method.
     * @param string $path Normalized request URI path.
     * @return array|null Route match details with handler and extracted parameters, or null.
     */
    public function match(string $method, string $path): ?array {
        $method = strtoupper(trim($method));
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $normalizedPath, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = urldecode($value);
                    }
                }

                return [
                    'route'   => $route,
                    'handler' => $route['handler'],
                    'params'  => $params
                ];
            }
        }

        return null;
    }

    /**
     * Dispatch the matched route and execute its controller action.
     *
     * @param string $method HTTP method.
     * @param string $path Request path.
     * @param array $payload Request payload (JSON, $_POST, or form data).
     * @return bool True if a route matched and was dispatched, false otherwise.
     */
    public function dispatch(string $method, string $path, array $payload = []): bool {
        $match = $this->match($method, $path);

        if ($match === null) {
            return false;
        }

        $handler = $match['handler'];
        $params = $match['params'];

        // Callable closure handler
        if (is_callable($handler) && !is_array($handler)) {
            $handler($params, $payload);
            return true;
        }

        // Array [ControllerClass, 'actionMethod'] handler
        if (is_array($handler) && count($handler) === 2) {
            [$controller, $action] = $handler;
            $instance = is_object($controller) ? $controller : new $controller();

            if (!method_exists($instance, $action)) {
                throw new BadMethodCallException("Action method '{$action}' does not exist on controller " . get_class($instance));
            }

            $refMethod = new ReflectionMethod($instance, $action);
            $methodParams = $refMethod->getParameters();
            $args = [];

            foreach ($methodParams as $p) {
                $pName = $p->getName();
                if (isset($params[$pName])) {
                    $val = $params[$pName];
                    $type = $p->getType();
                    if ($type && $type->getName() === 'int') {
                        $val = (int)$val;
                    }
                    $args[] = $val;
                } elseif ($pName === 'id' && !empty($params)) {
                    $args[] = (int)reset($params);
                } elseif (in_array($pName, ['postData', 'payload', 'data', 'input'], true)) {
                    $args[] = $payload;
                } elseif ($p->isDefaultValueAvailable()) {
                    $args[] = $p->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }

            $refMethod->invokeArgs($instance, $args);
            return true;
        }

        return false;
    }
}
