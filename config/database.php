<?php

$etcPath = dirname(__DIR__, 2) . '/etc/database.php';

$config = file_exists($etcPath) ? require $etcPath : [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_NAME') ?: '',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
];

// Driver PDO fixo com DSN montado automaticamente
$config['driver'] = 'pdo';
$config['dsn'] = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['database']
);

return $config;
