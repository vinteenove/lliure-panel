<?php

namespace Lliure\Auth;

use Lliure\Models\User;

class Auth
{
    private static ?self $instance = null;
    private Session $session;
    private ?User $user = null;

    private function __construct()
    {
        $this->session = Session::getInstance();
        $this->loadUserFromSession();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);

        if (!$user) {
            $user = User::findByEmail($username);
        }

        if (!$user || !$user->active) {
            return false;
        }

        if (!$user->verifyPassword($password)) {
            return false;
        }

        $this->login($user);
        return true;
    }

    public function login(User $user): void
    {
        $this->session->regenerate();
        $this->session->set('auth_user_id', $user->id);
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->session->remove('auth_user_id');
        $this->session->destroy();
        $this->user = null;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?User
    {
        return $this->user;
    }

    private function loadUserFromSession(): void
    {
        $userId = $this->session->get('auth_user_id');

        if ($userId) {
            try {
                $this->user = User::load($userId);
            } catch (\Exception $e) {
                $this->session->remove('auth_user_id');
                $this->user = null;
            }
        }
    }
}
