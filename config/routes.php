<?php

/**
 * Lliure app routes — auth + dashboard.
 */

use Lliure\Controllers\DashboardController;
use Lliure\Middleware\AuthMiddleware;

return function(\Router\Router $router) {
    // Shared auth routes
    (require __DIR__ . '/routes-auth.php')($router);

    // Shared user management routes
    (require __DIR__ . '/routes-users.php')($router);

    // Dashboard
    $router->get('/dashboard', function($request, $vars) {
        return (new DashboardController())->index($request, $vars);
    })->middleware(new AuthMiddleware());
};
