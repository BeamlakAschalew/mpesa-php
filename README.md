# Mpesa PHP

A PHP Composer package used to integrate M-Pesa services into your PHP application.

## Installation

To install the package, you need to have Composer installed. Run the following command to install the package:

```bash
composer require beamlak/mpesa-php
```

## Configuration

Create a `.env` file in the root of your project and add the following configuration variables:

```plaintext
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_ENV=sandbox
```

Replace `your_consumer_key` and `your_consumer_secret` with your actual M-Pesa API credentials.

## Usage

### Initialization

To use the Mpesa class, you need to initialize it with the callback URL:

```php
use Beamlak\MpesaPhp\Mpesa;

$mpesa = new Mpesa('https://www.yourcallbackurl.com');
```

### Authentication

To authenticate with the Mpesa API and obtain an access token:

```php
$mpesa->authenticatePublic();
echo $mpesa->getAccessToken();
echo $mpesa->getExpiresIn();
```

### USSD Push Request

To initiate a USSD push request:

```php
$response = $mpesa->ussdPush('254700000000', '100', 'TestReference', 'ThirdPartyID', '123456', 'passKey');
print_r($response);
```

### Register URL

To register confirmation and validation URLs:

```php
$response = $mpesa->registerUrl('123456', 'https://www.yourcallbackurl.com/confirmation', 'https://www.yourcallbackurl.com/validation');
print_r($response);
```

### Simulate C2B Transaction

To simulate a C2B transaction:

```php
$response = $mpesa->simulateC2B('123456', '254700000000', '100', 'TestReference');
print_r($response);
```

### Payout Request

To initiate a payout request:

```php
$response = $mpesa->payout('123456', '254700000000', '100', 'passKey');
print_r($response);
```

### Query Transaction Status

To query the status of a transaction:

```php
$response = $mpesa->queryTransactionStatus('transactionId', '123456', 'passKey');
print_r($response);
```

### Reverse Transaction

To reverse a transaction:

```php
$response = $mpesa->reverseTransaction('transactionId', '123456', '100', 'receiver', 'receiverType', 'passKey', 'originalConversationID');
print_r($response);
```

### Account Balance

To query the account balance:

```php
$response = $mpesa->accountBalance('123456', 'passKey');
print_r($response);
```

## Testing

This package uses Pest for testing. To run the tests, use the following command:

```bash
vendor/bin/pest
```

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Contributing

Feel free to contribute to this package by submitting a pull request or opening an issue.

## Authors

- Beamlak Aschalew (birrletej12@gmail.com)
