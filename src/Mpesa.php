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
    public function ussdPush(string $phoneNumber, string $amount, string $reference, string $thirdPartyId, string $businessCode, string $passKey): ?array {
        $url = '/mpesa/stkpush/v3/processrequest';
        $timestamp = date('YmdHis');
        $password = base64_encode(hash('sha256', $businessCode . $passKey . $timestamp));
        $data = [
            "MerchantRequestID" => "Partner name -" . uniqid(),
            "BusinessShortCode" => $businessCode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phoneNumber,
            "PartyB" => $businessCode,
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

    public function registerUrl(string $shortCode, string $confirmationUrl = 'https://www.myservice:8080/confirmation', string $validationUrl = 'https://www.myservice:8080/validation'): ?array {
        $url = '/v1/c2b-register-url/register';
        $data = [
            'ShortCode' => $shortCode,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ];

        return $this->request('POST', $url, [], $data);
    }

    public function simulateC2B(string $shortCode, string $phoneNumber, string $amount, string $reference): ?array {
        $url = '/mpesa/b2c/simulatetransaction/v1/request';
        $data = [
            'ShortCode' => $shortCode,
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'Msisdn' => $phoneNumber,
            'BillRefNumber' => $reference
        ];

        return $this->request('POST', $url, [], $data);
    }

    public function queryTransactionStatus(string $transactionId, string $shortCode, string $passKey): ?array {
        $url = '/mpesa/transactionstatus/v1/query';
        $timestamp = date('YmdHis');
        $password = base64_encode(hash('sha256', $shortCode . $passKey . $timestamp));
        $data = [
            'Initiator' => $shortCode,
            'SecurityCredential' => $password,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $transactionId,
            'PartyA' => $shortCode,
            'IdentifierType' => '4',
            'ResultURL' => 'https://www.myservice:8080/result',
            'QueueTimeOutURL' => 'https://www.myservice:8080/timeout',
            'Remarks' => 'Transaction status query',
            'Occasion' => 'Transaction status query'
        ];

        return $this->request('POST', $url, [], $data);
    }

    public function reverseTransaction(
        string $transactionId,
        string $shortCode,
        string $amount,
        string $receiver,
        string $receiverType,
        string $passKey,
        string $originalConversationID
    ): ?array {
        $url = '/mpesa/reversal/v2';
        $timestamp = date('YmdHis');
        $password = base64_encode(hash('sha256', $shortCode . $passKey . $timestamp));
        $data = [
            'OriginatorConversationID' => 'Partner name -' . uniqid(),
            'Initiator' => $shortCode,
            'SecurityCredential' => $password,
            'CommandID' => 'TransactionReversal',
            'TransactionID' => $transactionId,
            'Amount' => $amount,
            'OriginalConversationID' => $originalConversationID,
            'PartyA' => $shortCode,
            'ReceiverIdentifierType' => $receiverType,
            'ReceiverParty' => $receiver,
            'ResultURL' => 'https://www.myservice:8080/result',
            'QueueTimeOutURL' => 'https://www.myservice:8080/timeout',
            'Remarks' => 'B2C Reversal',
            'Occasion' => 'Payout'
        ];

        return $this->request('POST', $url, [], $data);
    }

    public function accountBalance(string $shortCode, string $passKey): ?array {
        $url = '/mpesa/accountbalance/v2/query';
        $timestamp = date('YmdHis');
        $password = base64_encode(hash('sha256', $shortCode . $passKey . $timestamp));
        $data = [
            'OriginatorConversationID' => 'Partner name -' . uniqid(),
            'Initiator' => $shortCode,
            'SecurityCredential' => $password,
            'CommandID' => 'AccountBalance',
            'PartyA' => $shortCode,
            'IdentifierType' => '4',
            'ResultURL' => 'https://www.myservice:8080/result',
            'QueueTimeOutURL' => 'https://www.myservice:8080/timeout',
            'Remarks' => 'Account balance query',
            'Occasion' => 'Account balance query'
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
