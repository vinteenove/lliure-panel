<?php

namespace Lliure;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    private static ?self $instance = null;
    private array $config = [];
    private ?Environment $twig = null;
    private array $apps = [];

    private function __construct()
    {
        $this->loadAutoloader();
        $this->loadConfig();
        $this->initTwig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadAutoloader(): void
    {
        $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new \RuntimeException('vendor/autoload.php não encontrado. Rode composer install na raiz.');
        }
        require_once $autoloadPath;
    }

    private function loadConfig(): void
    {
        $configDir = dirname(__DIR__) . '/config/';

        foreach (['app', 'database'] as $file) {
            $path = $configDir . $file . '.php';
            if (file_exists($path)) {
                $this->config[$file] = require $path;
            }
        }

        if (isset($this->config['app']['timezone'])) {
            date_default_timezone_set($this->config['app']['timezone']);
        }
    }

    private function initTwig(): void
    {
        $viewsDir = dirname(__DIR__) . '/views';
        $loader = new FilesystemLoader($viewsDir);
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => $this->config['app']['debug'] ?? false,
        ]);
    }

    public function registerApp(string $name, string $basePath): void
    {
        $this->apps[$name] = $basePath;

        $viewsPath = $basePath . '/views';
        if (is_dir($viewsPath)) {
            $this->twig->getLoader()->addPath($viewsPath, $name);
        }
    }

    public function getConfig(string $section = null): mixed
    {
        if ($section === null) {
            return $this->config;
        }
        return $this->config[$section] ?? null;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getApps(): array
    {
        return $this->apps;
    }
}
