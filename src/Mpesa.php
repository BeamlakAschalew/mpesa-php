<?php

namespace Beamlak\MpesaPhp;

use Beamlak\MpesaPhp\MpesaException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Mpesa
 * @package Beamlak\MpesaPhp
 */
class Mpesa {
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl;
    private ?string $accessToken = null;
    private ?string $expiresIn = null;
    private string $callbackUrl;

    private Client $httpClient;

    /**
     * Mpesa constructor.
     * @param string $callbackUrl
     * @throws Exception
     */
    public function __construct(string $callbackUrl = 'https://www.myservice:8080') {
        $config = new MpesaConfig();

        $this->consumerKey = $config->get('MPESA_CONSUMER_KEY');
        $this->consumerSecret = $config->get('MPESA_CONSUMER_SECRET');
        $this->baseUrl = $config->get('MPESA_ENV', 'sandbox') === 'production'
            ? 'https://api.safaricom.et'
            : 'https://apisandbox.safaricom.et';
        $this->callbackUrl = $callbackUrl;

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10.0,
        ]);

        $this->authenticate();
    }

    /**
     * Display the expiration time of the access token.
     */
    public function display() {
        echo $this->expiresIn;
    }

    /**
     * Set the HTTP client.
     * @param Client $httpClient
     */
    public function setHttpClient(Client $httpClient): void {
        $this->httpClient = $httpClient;
    }

    /**
     * Authenticate with the Mpesa API to obtain an access token.
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
     * Public method to call the private authenticate method for testing purposes.
     */
    public function authenticatePublic(): void {
        $this->authenticate();
    }

    /**
     * Initiate a USSD push request.
     * 
     * @param string $phoneNumber
     * @param string $amount
     * @param string $reference
     * @param string $thirdPartyId
     * @param string $businessCode
     * @param string $passKey
     * @return array|null
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
            "CallBackURL" => $this->callbackUrl . "/result",
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
     * Register confirmation and validation URLs.
     * 
     * @param string $shortCode
     * @param string $confirmationUrl
     * @param string $validationUrl
     * @return array|null
     * @throws MpesaException
     */
    public function registerUrl(string $shortCode, string $confirmationUrl = '', string $validationUrl = ''): ?array {
        $url = '/v1/c2b-register-url/register';
        $data = [
            'ShortCode' => $shortCode,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl ?: $this->callbackUrl . '/confirmation',
            'ValidationURL' => $validationUrl ?: $this->callbackUrl . '/validation'
        ];

        return $this->request('POST', $url, [], $data);
    }

    /**
     * Validate a C2B transaction.
     * 
     * @param string $checkUrl
     * @param string $transactionType
     * @param string $transID
     * @param string $transTime
     * @param string $transAmount
     * @param string $businessShortCode
     * @param string $billRefNumber
     * @param string $msisdn
     * @param string $firstName
     * @param string $middleName
     * @param string $lastName
     * @param string $invoiceNumber
     * @param string $orgAccountBalance
     * @param string $thirdPartyTransID
     * @return array|null
     * @throws MpesaException
     */
    public function validateC2B(
        string $checkUrl,
        string $transactionType,
        string $transID,
        string $transTime,
        string $transAmount,
        string $businessShortCode,
        string $billRefNumber,
        string $msisdn,
        string $firstName,
        string $middleName,
        string $lastName,
        string $invoiceNumber = '',
        string $orgAccountBalance = '',
        string $thirdPartyTransID = ''
    ): ?array {
        $data = [
            "RequestType" => "Validation",
            "TransactionType" => $transactionType,
            "TransID" => $transID,
            "TransTime" => $transTime,
            "TransAmount" => $transAmount,
            "BusinessShortCode" => $businessShortCode,
            "BillRefNumber" => $billRefNumber,
            "InvoiceNumber" => $invoiceNumber,
            "OrgAccountBalance" => $orgAccountBalance,
            "ThirdPartyTransID" => $thirdPartyTransID,
            "MSISDN" => $msisdn,
            "FirstName" => $firstName,
            "MiddleName" => $middleName,
            "LastName" => $lastName
        ];

        return $this->request('POST', $checkUrl, [], $data);
    }

    /**
     * Simulate a C2B transaction.
     * 
     * @param string $shortCode
     * @param string $phoneNumber
     * @param string $amount
     * @param string $reference
     * @return array|null
     * @throws MpesaException
     */
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

    /**
     * Initiate a payout request.
     * 
     * @param string $shortCode
     * @param string $phoneNumber
     * @param string $amount
     * @param string $passKey
     * @return array|null
     * @throws MpesaException
     */
    public function payout(
        string $shortCode,
        string $phoneNumber,
        string $amount,
        string $passKey
    ): ?array {
        $url = '/mpesa/b2c/v2/paymentrequest';
        $timestamp = date('YmdHis');
        $password = base64_encode(hash('sha256', $shortCode . $passKey . $timestamp));
        $data = [
            'OriginatorConversationID' => 'Partner name -' . uniqid(),
            'InitiatorName' => $shortCode,
            'SecurityCredential' => $password,
            'CommandID' => 'BusinessPayment',
            'Amount' => $amount,
            'PartyA' => $shortCode,
            'PartyB' => $phoneNumber,
            'Remarks' => 'Payment',
            'QueueTimeOutURL' => $this->callbackUrl . '/timeout',
            'ResultURL' => $this->callbackUrl . '/result',
            'Occasion' => 'Payment',
        ];

        return $this->request('POST', $url, [], $data);
    }

    /**
     * Query the status of a transaction.
     * 
     * @param string $transactionId
     * @param string $shortCode
     * @param string $passKey
     * @return array|null
     * @throws MpesaException
     */
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
            'ResultURL' => $this->callbackUrl . '/result',
            'QueueTimeOutURL' => $this->callbackUrl . '/timeout',
            'Remarks' => 'Transaction status query',
            'Occasion' => 'Transaction status query'
        ];

        return $this->request('POST', $url, [], $data);
    }

    /**
     * Reverse a transaction.
     * 
     * @param string $transactionId
     * @param string $shortCode
     * @param string $amount
     * @param string $receiver
     * @param string $receiverType
     * @param string $passKey
     * @param string $originalConversationID
     * @return array|null
     * @throws MpesaException
     */
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
            'ResultURL' => $this->callbackUrl . '/result',
            'QueueTimeOutURL' => $this->callbackUrl . '/timeout',
            'Remarks' => 'B2C Reversal',
            'Occasion' => 'Payout'
        ];

        return $this->request('POST', $url, [], $data);
    }

    /**
     * Query the account balance.
     * 
     * @param string $shortCode
     * @param string $passKey
     * @return array|null
     * @throws MpesaException
     */
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
            'ResultURL' => $this->callbackUrl . '/result',
            'QueueTimeOutURL' => $this->callbackUrl . '/timeout',
            'Remarks' => 'Account balance query',
            'Occasion' => 'Account balance query'
        ];

        return $this->request('POST', $url, [], $data);
    }

    /**
     * Make a request to the Mpesa API.
     * 
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array $data
     * @return array|null
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
            echo $e;
            throw new MpesaException(0, 'Mpesa API Request Failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the access token.
     * @return string|null
     */
    public function getAccessToken(): ?string {
        return $this->accessToken;
    }

    /**
     * Get the expiration time of the access token.
     * @return string|null
     */
    public function getExpiresIn(): ?string {
        return $this->expiresIn;
    }
}
