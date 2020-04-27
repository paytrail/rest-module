<?php

declare(strict_types=1);

namespace Tests;

use Paytrail\Exceptions\ProductException;
use Paytrail\Exceptions\ValidationException;
use Paytrail\Rest\Customer;
use Paytrail\Rest\Merchant;
use Paytrail\Rest\Product;
use Paytrail\Rest\RestModule;
use PHPUnit\Framework\TestCase;

class RestPaymentTest extends TestCase
{
    const TOKEN = 'secretToken';
    const PAYMENT_LINK = 'linkToPayment';

    private $restModule;
    private $product;
    private $customer;

    public function setUp(): void
    {
        parent::setUp();

        $merchant = Merchant::create('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');

        $this->restModule = new RestModule($merchant);

        $this->product = Product::create([
            'title' => 'Foo',
            'code' => '001',
            'price' => 2,
        ]);
        $this->customer = Customer::create([
            'firstName' => 'Foo',
            'lastName' => 'Bar',
            'email' => 'customer.email@nomail.com',
            'street' => 'Foo',
            'postalCode' => '1234',
            'postalOffice' => 'Bar',
            'country' => 'FI',
        ]);
    }

    public function testExceptionIsThrownWithoutOrderNumber()
    {
        $this->expectException(ValidationException::class);
        $this->restModule->getPaymentLink();
    }

    public function testExceptionIsThrownWithoutProductsOrPrice()
    {
        $this->expectException(ProductException::class);
        $this->restModule->createPayment('1234');
        $this->restModule->getPaymentLink();
    }

    public function testExceptionIsThrownWhenAddingProductWhenAmountIsSet()
    {
        $this->expectException(ProductException::class);
        $this->restModule->addPrice(10);
        $this->restModule->addProducts([$this->product]);
    }

    public function testExceptionIsThrownWhenAddingAmountAndHasProducts()
    {
        $this->expectException(ProductException::class);
        $this->restModule->addProducts([$this->product]);
        $this->restModule->addPrice(10);
    }
}
