<?php

namespace Lliure\Auth;

use Lliure\Models\User;

class Auth
{
    private static ?self $instance = null;

    /** @var null|callable():?User */
    private static $identityResolver = null;
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

    /**
     * [spike] Identidade externa — coexistência v1↔v2.
     *
     * Sob o Vimu quem autentica é o v1 (sessão `ll`), e não existe linha em
     * `lliure_users` para o usuário logado. O resolver traduz a sessão de quem
     * hospeda o v2 num User, evitando um segundo login. Sem resolver registrado,
     * o Auth segue com o fluxo próprio (auth_user_id).
     *
     * @param null|callable():?\Lliure\Models\User $resolver
     */
    public static function setIdentityResolver(?callable $resolver): void
    {
        self::$identityResolver = $resolver;
        self::$instance = null; // a identidade é recarregada no próximo getInstance()
    }

    private function loadUserFromSession(): void
    {
        if (self::$identityResolver !== null) {
            $this->user = (self::$identityResolver)();
            return;
        }

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
