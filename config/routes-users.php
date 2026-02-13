<?php

/**
 * Shared user management routes — loaded by all apps.
 */

use Lliure\Controllers\UsersController;
use Lliure\Middleware\AuthMiddleware;

return function(\Router\Router $router) {
    $middleware = new AuthMiddleware();

    $router->get('/users', function($request, $vars) {
        return (new UsersController())->index($request, $vars);
    })->middleware($middleware);

    $router->get('/users/create', function($request, $vars) {
        return (new UsersController())->create($request, $vars);
    })->middleware($middleware);

    $router->post('/users/store', function($request, $vars) {
        return (new UsersController())->store($request, $vars);
    })->middleware($middleware);

    $router->get('/users/{id:number}/edit', function($request, $vars) {
        return (new UsersController())->edit($request, $vars);
    })->middleware($middleware);

    $router->post('/users/{id:number}/update', function($request, $vars) {
        return (new UsersController())->update($request, $vars);
    })->middleware($middleware);

    $router->post('/users/{id:number}/delete', function($request, $vars) {
        return (new UsersController())->destroy($request, $vars);
    })->middleware($middleware);
};
