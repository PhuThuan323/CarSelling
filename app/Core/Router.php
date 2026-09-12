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

    public function dispatch($path, $method = 'GET') {
        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            die("Route not found: $method $path");
        }

        $route = $this->routes[$method][$path];
        $controllerName = $route['controller'];
        $methodName = $route['method'];

        $controller = new $controllerName();
        if (!method_exists($controller, $methodName)) {
            http_response_code(500);
            die("Method $methodName not found in $controllerName");
        }

        return $controller->$methodName();
    }

    public function getRoutes() {
        return $this->routes;
    }
}
