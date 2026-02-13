<?php

namespace Lliure\Controllers;

use Lliure\Auth\Auth;
use Lliure\Auth\Session;
use Lliure\Bootstrap;
use Lliure\Http\Redirect;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    public function showLoginForm(ServerRequestInterface $request, array $vars): string|ResponseInterface
    {
        $auth = Auth::getInstance();

        if ($auth->check()) {
            return Redirect::response('/');
        }

        $bootstrap = Bootstrap::getInstance();
        $twig = $bootstrap->getTwig();
        $session = Session::getInstance();

        return $twig->render('auth/login.twig', [
            'error' => $session->getFlash('error'),
        ]);
    }

    public function login(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $auth = Auth::getInstance();
        $session = Session::getInstance();
        $bootstrap = Bootstrap::getInstance();

        $body = $request->getParsedBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($username) || empty($password)) {
            $session->flash('error', 'Preencha todos os campos.');
            return Redirect::response('/login');
        }

        if ($auth->attempt($username, $password)) {
            // url.intended is already a full URL (with basePath), use it directly
            $intended = $session->get('url.intended', $bootstrap->url('/'));
            $session->remove('url.intended');
            return Redirect::away($intended);
        }

        $session->flash('error', 'Usuário ou senha inválidos.');
        return Redirect::response('/login');
    }

    public function logout(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $auth = Auth::getInstance();
        $auth->logout();
        return Redirect::response('/login');
    }
}
