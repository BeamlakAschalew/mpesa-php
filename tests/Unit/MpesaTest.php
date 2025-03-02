<?php

use Beamlak\MpesaPhp\Mpesa;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

beforeEach(function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['access_token' => 'test_token', 'expires_in' => '3600'])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $this->httpClient = new Client(['handler' => $handlerStack]);

    $this->mpesa = new Mpesa('https://www.myservice:8080');
    $this->mpesa->setHttpClient($this->httpClient);
});

test('it can authenticate', function () {
    $this->mpesa->authenticatePublic();
    expect($this->mpesa->getAccessToken())->toBe('test_token');
    expect($this->mpesa->getExpiresIn())->toBe('3600');
});

test('it can initiate a USSD push request', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['ResponseCode' => '0', 'ResponseDescription' => 'Success'])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $this->httpClient = new Client(['handler' => $handlerStack]);
    $this->mpesa->setHttpClient($this->httpClient);

    $response = $this->mpesa->ussdPush('254700000000', '100', 'TestReference', 'ThirdPartyID', '123456', 'passKey');
    expect($response['ResponseCode'])->toBe('0');
    expect($response['ResponseDescription'])->toBe('Success');
});
