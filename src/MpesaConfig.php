<?php

namespace Beamlak\MpesaPhp;
use Dotenv\Dotenv;

class MpesaConfig {
    public function __construct()
    {
        $basePath = dirname(__DIR__, 3);
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->safeLoad();
        }
    }

    public function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}