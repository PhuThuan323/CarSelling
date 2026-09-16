<?php

namespace App\Core;

class Router {
    private $routes = [];
    private $currentRoute;

    public function get($path, $controller, $method) {
        $this->routes['GET'][$path] = ['controller' => $controller, 'method' => $method];
    }

    public function post($path, $controller, $method) {
        $this->routes['POST'][$path] = ['controller' => $controller, 'method' => $method];
    }

    public function put($path, $controller, $method) {
        $this->routes['PUT'][$path] = ['controller' => $controller, 'method' => $method];
    }

    public function delete($path, $controller, $method) {
        $this->routes['DELETE'][$path] = ['controller' => $controller, 'method' => $method];
    }

    public function dispatch($path, $method = 'GET') {
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        $match = $this->match($path, $method);
        if ($match === null) {
            http_response_code(404);
            die("Route not found: $method $path");
        }

        $controllerName = $match['route']['controller'];
        $methodName = $match['route']['method'];

        $controller = new $controllerName();
        if (!method_exists($controller, $methodName)) {
            http_response_code(500);
            die("Method $methodName not found in $controllerName");
        }

        return $controller->$methodName(...$match['params']);
    }

    // Static routes win; otherwise compare segment by segment, {id} captures a value.
    private function match(string $path, string $method): ?array {
        $routes = $this->routes[$method] ?? [];

        if (isset($routes[$path])) {
            return ['route' => $routes[$path], 'params' => []];
        }

        $segments = explode('/', trim($path, '/'));

        foreach ($routes as $routePath => $route) {
            if (!str_contains($routePath, '{')) {
                continue;
            }

            $patternSegments = explode('/', trim($routePath, '/'));
            if (count($patternSegments) !== count($segments)) {
                continue;
            }

            $params = [];
            $matched = true;

            foreach ($patternSegments as $index => $patternSegment) {
                if (str_starts_with($patternSegment, '{') && str_ends_with($patternSegment, '}')) {
                    $params[] = $segments[$index];
                    continue;
                }
                if ($patternSegment !== $segments[$index]) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                return ['route' => $route, 'params' => $params];
            }
        }

        return null;
    }

    public function getRoutes() {
        return $this->routes;
    }
}
