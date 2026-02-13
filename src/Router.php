<?php

namespace Lliure;

class Router
{
    private array $routes = [];

    public function loadRoutes(string $routesFile): void
    {
        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            if (is_array($routes)) {
                $this->routes = array_merge($this->routes, $routes);
            }
        }
    }

    public function addRoute(string $path, array $handler): void
    {
        $this->routes[$path] = $handler;
    }

    public function dispatch(string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        if (isset($this->routes[$uri])) {
            [$controllerClass, $method] = $this->routes[$uri];
            $controller = new $controllerClass();
            $controller->$method();
            return;
        }

        http_response_code(404);
        echo '404 — Rota não encontrada';
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
