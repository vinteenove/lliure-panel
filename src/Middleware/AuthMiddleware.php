<?php

namespace Lliure\Middleware;

use Lliure\Auth\Auth;
use Lliure\Auth\Session;
use Lliure\Bootstrap;
use Lliure\Http\Redirect;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = Auth::getInstance();

        if ($auth->check()) {
            return $handler->handle($request);
        }

        $session = Session::getInstance();
        $bootstrap = Bootstrap::getInstance();

        // Save full REQUEST_URI (already includes basePath) for redirect after login
        $currentUri = $_SERVER['REQUEST_URI'] ?? $bootstrap->url('/');
        $session->set('url.intended', $currentUri);

        return Redirect::response('/login');
    }
}
