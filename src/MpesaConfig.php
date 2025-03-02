<?php

namespace Beamlak\MpesaPhp;

use Dotenv\Dotenv;

/**
 * Class MpesaConfig
 * @package Beamlak\MpesaPhp
 */
class MpesaConfig {
    /**
     * MpesaConfig constructor.
     */
    public function __construct() {
        if (file_exists(getcwd() . '/.env')) {
            $dotenv = Dotenv::createImmutable(getcwd());
            $dotenv->safeLoad();
        }
    }

    /**
     * Get the value of a configuration key.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
