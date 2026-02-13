<?php

/**
 * Shared auth routes — loaded by all apps.
 */

use Lliure\Controllers\AuthController;

return function(\Router\Router $router) {
    $router->get('/login', function($request, $vars) {
        return (new AuthController())->showLoginForm($request, $vars);
    });

    $router->post('/auth/login', function($request, $vars) {
        return (new AuthController())->login($request, $vars);
    });

    $router->get('/logout', function($request, $vars) {
        return (new AuthController())->logout($request, $vars);
    });
};
