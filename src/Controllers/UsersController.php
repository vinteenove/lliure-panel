<?php

namespace Lliure\Controllers;

use Lliure\Auth\Auth;
use Lliure\Auth\Session;
use Lliure\Bootstrap;
use Lliure\Http\Redirect;
use Lliure\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UsersController
{
    /**
     * Captura o query param ?from= e grava na session.
     * Se já existir na session, mantém.
     */
    private function resolveOrigin(ServerRequestInterface $request): ?string
    {
        $session = Session::getInstance();
        $query = $request->getQueryParams();

        if (!empty($query['from'])) {
            $session->set('users.origin', $query['from']);
        }

        return $session->get('users.origin');
    }

    public function index(ServerRequestInterface $request, array $vars): string
    {
        $origin = $this->resolveOrigin($request);
        $users = User::all();

        $bootstrap = Bootstrap::getInstance();
        return $bootstrap->getTwig()->render('users/index.twig', [
            'users' => $users,
            'origin' => $origin,
        ]);
    }

    public function create(ServerRequestInterface $request, array $vars): string
    {
        $origin = $this->resolveOrigin($request);

        $bootstrap = Bootstrap::getInstance();
        return $bootstrap->getTwig()->render('users/form.twig', [
            'user' => null,
            'origin' => $origin,
        ]);
    }

    public function store(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $session = Session::getInstance();
        $body = $request->getParsedBody();

        $name = trim($body['name'] ?? '');
        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $active = isset($body['active']) ? 1 : 0;

        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            $session->flash('error', 'Preencha todos os campos obrigatórios.');
            return Redirect::response('/users/create');
        }

        if (User::findByUsername($username)) {
            $session->flash('error', 'Este nome de usuário já está em uso.');
            return Redirect::response('/users/create');
        }

        if (User::findByEmail($email)) {
            $session->flash('error', 'Este e-mail já está em uso.');
            return Redirect::response('/users/create');
        }

        try {
            $user = User::build([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password_hash' => User::hashPassword($password),
                'active' => $active,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $user->save();

            $session->flash('success', 'Usuário criado com sucesso.');
            return Redirect::response('/users');
        } catch (\Exception $e) {
            $session->flash('error', 'Erro ao criar usuário: ' . $e->getMessage());
            return Redirect::response('/users/create');
        }
    }

    public function edit(ServerRequestInterface $request, array $vars): string|ResponseInterface
    {
        $origin = $this->resolveOrigin($request);
        $user = User::load($vars['id']);

        if (!$user) {
            $session = Session::getInstance();
            $session->flash('error', 'Usuário não encontrado.');
            return Redirect::response('/users');
        }

        $bootstrap = Bootstrap::getInstance();
        return $bootstrap->getTwig()->render('users/form.twig', [
            'user' => $user,
            'origin' => $origin,
        ]);
    }

    public function update(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $session = Session::getInstance();
        $user = User::load($vars['id']);

        if (!$user) {
            $session->flash('error', 'Usuário não encontrado.');
            return Redirect::response('/users');
        }

        $body = $request->getParsedBody();

        $name = trim($body['name'] ?? '');
        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $active = isset($body['active']) ? 1 : 0;

        if (empty($name) || empty($username) || empty($email)) {
            $session->flash('error', 'Preencha todos os campos obrigatórios.');
            return Redirect::response("/users/{$user->id}/edit");
        }

        $existing = User::findByUsername($username);
        if ($existing && $existing->id !== $user->id) {
            $session->flash('error', 'Este nome de usuário já está em uso.');
            return Redirect::response("/users/{$user->id}/edit");
        }

        $existing = User::findByEmail($email);
        if ($existing && $existing->id !== $user->id) {
            $session->flash('error', 'Este e-mail já está em uso.');
            return Redirect::response("/users/{$user->id}/edit");
        }

        try {
            $user->name = $name;
            $user->username = $username;
            $user->email = $email;
            $user->active = $active;
            $user->updated_at = date('Y-m-d H:i:s');

            if (!empty($password)) {
                $user->password_hash = User::hashPassword($password);
            }

            $user->update();

            $session->flash('success', 'Usuário atualizado com sucesso.');
            return Redirect::response('/users');
        } catch (\Exception $e) {
            $session->flash('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            return Redirect::response("/users/{$user->id}/edit");
        }
    }

    public function destroy(ServerRequestInterface $request, array $vars): ResponseInterface
    {
        $session = Session::getInstance();
        $user = User::load($vars['id']);

        if (!$user) {
            $session->flash('error', 'Usuário não encontrado.');
            return Redirect::response('/users');
        }

        $auth = Auth::getInstance();
        if ($auth->user() && $auth->user()->id == $user->id) {
            $session->flash('error', 'Você não pode excluir seu próprio usuário.');
            return Redirect::response('/users');
        }

        try {
            $user->delete();
            $session->flash('success', 'Usuário excluído com sucesso.');
        } catch (\Exception $e) {
            $session->flash('error', 'Erro ao excluir usuário: ' . $e->getMessage());
        }

        return Redirect::response('/users');
    }
}
