# rest-module
A PHP package for integrating with Paytrail's REST interface.
Using [Paytrail E2 library][e2-module] instead of REST is recommended.

## Installation
Install via composer

```bash
composer require paytrail/rest-module
```

## Documentation

Paytrail official documentation can be found in [here][docs].

## Examples

### Payment without customer and product information

```php
use Paytrail\Rest\Merchant;
use Paytrail\Rest\RestModule;

$merchant = Merchant::create($merchantId, $merchantSecret);
$restModule = new RestModule($merchant);

$paymentLink = $restModule->addPrice(10.50)
    ->createPayment($orderNumber)
    ->getPaymentLink();
```

### Payment widget with customer, product information and custom return urls

Include customer information, discounted product and custom return urls.
Payment, customer and product properties can be found from [documentation][docs].

```php
use Paytrail\Rest\Merchant;
use Paytrail\Rest\Product;
use Paytrail\Rest\Customer;
use Paytrail\Rest\RestModule;

$merchant = Merchant::create($merchantId, $merchantSecret);
$restModule = new RestModule($merchant);

$customer = Customer::create([
    'firstName' => 'Test',
    'lastName' => 'Customer',
    'email' => 'customer.email@nomail.com',
    'street' => 'Test street 1',
    'postalCode' => '100200',
    'postalOffice' => 'Helsinki',
    'country' => 'FI',
    'mobile' => '040123456',
]);
$restModule->addCustomer($customer);

$paymentData = [
    'urlSet' => [
        'success' => 'https://url/to/shop/successUrl',
        'failure' => 'https://url/to/shop/cancelUrl',
        'notification' => 'https://url/to/shop/notifyUrl',
    ],
];

$product = Product::create([
    'title' => 'Test Product',
    'code' => '1234',
    'price' => 50,
    'amount' => 2,
    'discount' => 10,
]);
$shipping = Product::create([
    'title' => 'Shipping',
    'code' => '001',
    'price' => 5,
    'type' => Product::TYPE_POSTAL,
]);
echo $restModule->addProducts([$product, $shipping])
    ->createPayment($orderNumber, $paymentData)
    ->getPaymentWidget();
```

### XML mode

By default all requests and responses are processed as JSON. You can change behavior to XML based when creating payment
```php
$restModule->createPayment($orderNumber, $paymentData, RestModule::TYPE_XML);
```

### Validating completed payment

After returning from payment, whether success or cancelled, validate return authcode. Same validation applies to notify url.

```php
$isValidPayment = $restModule->returnAuthcodeIsValid($_GET);
```

You can also send return parameters as array instead of using `$_GET` superglobal. If return code is not valid, you can get validation errors.

To get status of payment, paid or not.
```php
$isPaid = $restModule->isPaid($_GET);
```

### Validating payment from notification
If customer doesn't return back after payment, status can be verified from capturing payment data from notify url. Return authcode validation is similar than success and cancelled payment, but you also need determine payment status.

```php
$isValidPayment = $restModule->returnAuthcodeIsValid($_GET);
if (!$isValidPayment) {
    // code to handle invalid validation.
}

$isPaid = $restModule->isPaid($_GET);
// Code to handle paid/cancelled status for order.
```

[docs]: https://docs.paytrail.com
[e2-module]: https://github.com/paytrail/e2-module
