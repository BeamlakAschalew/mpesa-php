<?php

use Beamlak\MpesaPhp\Mpesa;
use Beamlak\MpesaPhp\MpesaException;

beforeEach(function () {
    $this->mpesa = new Mpesa('https://www.myservice:8080');
});

test('it can authenticate with the real API', function () {
    try {
        $this->mpesa->authenticatePublic();
        expect($this->mpesa->getAccessToken())->not->toBeNull();
        expect($this->mpesa->getExpiresIn())->not->toBeNull();
    } catch (MpesaException $e) {
        $this->fail('Authentication failed: ' . $e->getMessage());
    }
});

test('it can initiate a USSD push request with the real API', function () {
    try {
        $response = $this->mpesa->ussdPush('251700404709', 100, '1234', '1234', '2060', '5ab0ecb13d56a1818f182cbe463b84370c3768a5f3e355aa1dd706043d722dee');
        expect($response)->toBeArray();
        expect($response['ResponseCode'])->toBeIn(['0', '3007']);
    } catch (MpesaException $e) {
        $this->fail('USSD push request failed: ' . $e->getMessage());
    }
});
