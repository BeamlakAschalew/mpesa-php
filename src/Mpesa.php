<?php

namespace Beamlak\MpesaPhp;

use Beamlak\MpesaPhp\MpesaException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Mpesa {
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl;
    private ?string $accessToken = null;
    private ?string $expiresIn = null;

    private Client $httpClient;

    /**
     * @throws Exception
     */
    public function __construct() {
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
    public function display() {
        echo $this->expiresIn;
    }

    /**
     * @throws MpesaException
     */
    private function authenticate(): void {
        $url = '/v1/token/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $response = $this->request('GET', $url, [
            'Authorization' => 'Basic ' . $credentials
        ]);

        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->expiresIn = $response['expires_in'];
        } else {
            $errorCode = $response['resultCode'] ?? 0;
            throw new MpesaException($errorCode, 'Failed to obtain access token from Mpesa API.');
        }
    }



    /**
     * @throws MpesaException
     */
    public function ussdPush(string $phoneNumber, string $amount, string $reference, string $thirdPartyId): ?array {
        $url = '/mpesa/stkpush/v3/processrequest';

        $data = [
            "MerchantRequestID" => "Partner name -" . uniqid(),
            "BusinessShortCode" => "1020",
            "Password" => "M2VkZGU2YWY1Y2RhMzIyOWRjMmFkMTRiMjdjOWIwOWUxZDFlZDZiNGQ0OGYyMDRiNjg0ZDZhNWM2NTQyNTk2ZA==",
            "Timestamp" => date('YmdHis'),
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phoneNumber,
            "PartyB" => "1020",
            "PhoneNumber" => $phoneNumber,
            "CallBackURL" => "https://www.myservice:8080/result",
            "AccountReference" => $reference,
            "TransactionDesc" => "Payment Reason",
            "ReferenceData" => [
                [
                    "Key" => "ThirdPartyReference",
                    "Value" => $thirdPartyId
                ]
            ]
        ];

        return $this->request('POST', $url, [], $data);
    }

    /**
     * @throws MpesaException
     */
    private function request(string $method, string $url, array $headers = [], array $data = []): ?array {
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
            throw new MpesaException('Mpesa API Request Failed: ' . $e->getMessage());
        }
    }
}
