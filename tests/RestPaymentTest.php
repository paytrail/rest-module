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
    const REQUIRED_PAYMENT_DATA = [
        'MERCHANT_ID',
        'URL_SUCCESS',
        'URL_CANCEL',
        'ORDER_NUMBER',
        'PARAMS_IN',
        'PARAMS_OUT',
        'AUTHCODE',
    ];

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
        $this->expectException(ValidationException::class);
        $this->restModule->createPayment('1234');
        $this->restModule->getPaymentLink();
    }

    public function testExceptionIsThrownWhenAddingProductWhenAmountIsSet()
    {
        $this->expectException(ProductException::class);
        $this->restModule->addAmount(10);
        $this->restModule->addProducts([$this->product]);
    }

    public function testExceptionIsThrownWhenAddingAmountAndHasProducts()
    {
        $this->expectException(ProductException::class);
        $this->restModule->addProducts([$this->product]);
        $this->restModule->addAmount(10);
    }

    public function testFormIsCreatedWithProductAndCustomerInformation()
    {
        $this->restModule->addProducts([$this->product]);
        $this->restModule->addCustomer($this->customer);
        $this->restModule->createPayment('order-123');

        $formData = $this->restModule->getPaymentLink();

        $this->assertNotEmpty($formData);

        foreach (self::REQUIRED_PAYMENT_DATA as $requiredData) {
            $this->assertStringContainsString($requiredData, $formData);
        }

        $this->assertStringContainsString('<input name="ITEM_TITLE[0]"', $formData);
        $this->assertStringContainsString('<input name="ITEM_ID[0]"', $formData);
        $this->assertStringContainsString('<input name="ITEM_UNIT_PRICE[0]"', $formData);
        $this->assertStringContainsString('<input name="ITEM_QUANTITY[0]"', $formData);
        $this->assertStringContainsString('<input name="PAYER_PERSON_FIRSTNAME"', $formData);
        $this->assertStringContainsString('<input name="PAYER_PERSON_LASTNAME"', $formData);
    }

    public function testFormIsCreatedWithOnlyAmountAndOrderNumber()
    {
        $this->restModule->addAmount(15);
        $this->restModule->createPayment('order-123');

        $formData = $this->restModule->getPaymentLink();

        $this->assertNotEmpty($formData);

        foreach (self::REQUIRED_PAYMENT_DATA as $requiredData) {
            $this->assertStringContainsString($requiredData, $formData);
        }

        $this->assertStringContainsString('<input name="AMOUNT" type="hidden" value="15">', $formData);
    }
}
