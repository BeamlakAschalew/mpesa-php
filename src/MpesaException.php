<?php

namespace Beamlak\MpesaPhp;

use Exception;

class MpesaException extends Exception {
    private static array $errorMessages = [
        0 => 'Request successful',
        999991 => 'Invalid client id passed: Incorrect basic Authorization username. Input the correct username.',
        999996 => 'Invalid Authentication passed: Incorrect authorization type. Select type as Basic Auth.',
        999997 => 'Invalid Authorization Header: Incorrect basic authorization password. Input the correct password.',
        999998 => 'Required parameter [grant_type] is invalid or empty: Incorrect grant type. Select grant type as client credentials.',
    ];

    public function __construct(int $code, $message = "", ?Exception $previous = null)
    {
        $message = self::$errorMessages[$code] ?? $message;
        parent::__construct($message, $code, $previous);
    }
}