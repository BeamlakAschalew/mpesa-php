<?php

namespace Beamlak\MpesaPhp;
use Dotenv\Dotenv;

class MpesaConfig {
    public function __construct() {
        if (file_exists(getcwd() . '/.env')) {
            $dotenv = Dotenv::createImmutable(getcwd());
            $dotenv->safeLoad();
        }
    }

    public function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}