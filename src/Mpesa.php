<?php

namespace Beamlak\MpesaPhp;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Mpesa {
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl;
    private ?string $accessToken = null;
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
            : 'https://apisandbox.safaricom.et';

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10.0,
        ]);

        $this->authenticate();
    }

    /**
     * @throws Exception
     */
    private function authenticate(): void
    {
        $url = '/v1/token/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        // Call request with only the URL endpoint
        $response = $this->request('GET', $url, [
            'Authorization' => 'Basic ' . $credentials
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
    private function request(string $method, string $url, array $headers = [], array $data = []): ?array
    {
        try {
            $options = [
                'headers' => array_merge([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type'  => 'application/json',
                ], $headers),
                'json' => $data
            ];

            $response = $this->httpClient->request($method, $url, $options);

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new Exception('Mpesa API Request Failed: ' . $e->getMessage());
        }
    }
}
