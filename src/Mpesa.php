<?php

namespace Beamlak\MpesaPhp;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Mpesa {
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl;
    private string $accessToken;
    private Client $httpClient;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $config = new MpesaConfig();

        $this->consumerKey = $config->get('MPESA_CONSUMER_KEY');
        $this->consumerSecret = $config->get('MPESA_CONSUMER_SECRET');
        $this->baseUrl = $config->get('MPESA_ENV', 'sandbox') === 'production'
            ? 'https://api.safaricom.et'
            : 'https://sandbox.safaricom.et';

        $this->authenticate();
    }

    /**
     * @throws Exception
     */
    private function authenticate(): void
    {
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $response = $this->request('GET', $url, [
            'Authorization: Basic ' . $credentials
        ]);

        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
        } else {
            throw new Exception('Failed to obtain access token from Mpesa API.');
        }
    }

    /**
     * @throws Exception
     */
    private function request(string $method, string $url, array $data = []): ?array
    {
        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $data
            ]);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new Exception('Mpesa API Request Failed: ' . $e->getMessage());
        }
    }

}