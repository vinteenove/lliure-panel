<?php

namespace Lliure\Models;

use LliureCore\Model;
use dibi;

class User extends Model
{
    protected static ?string $table = 'lliure_users';
    protected static ?string $autoIncrementColumn = 'id';
    protected static $primaryKey = 'id';

    protected static array $fields = [
        'id',
        'username',
        'email',
        'password_hash',
        'name',
        'active',
        'created_at',
        'updated_at',
    ];

    public static function findByUsername(string $username): ?static
    {
        return static::findOne(
            'SELECT * FROM %n WHERE username = %s',
            static::$table,
            $username
        );
    }

    public static function findByEmail(string $email): ?static
    {
        return static::findOne(
            'SELECT * FROM %n WHERE email = %s',
            static::$table,
            $email
        );
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
