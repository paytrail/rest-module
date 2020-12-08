<?php

declare(strict_types=1);

namespace Paytrail\Tests;

use Paytrail\Rest\Customer;
use Paytrail\Rest\Product;
use Paytrail\Rest\RestPayment;
use PHPUnit\Framework\TestCase;

class RestPaymentTest extends TestCase
{
    private $product;
    private $customer;

    public function setUp(): void
    {
        parent::setUp();

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

    public function testGetJsonDataContainsCorrectValuesWithPrice()
    {
        $restPayment = new RestPayment('1234', [], null, [], 10);
        $json = $restPayment->getJsonData();

        $this->assertStringContainsString('"orderNumber":"1234"', $json);
        $this->assertStringContainsString('"currency":"EUR"', $json);
        $this->assertStringContainsString('"locale":"fi_FI"', $json);
        $this->assertStringContainsString('"urlSet":', $json);
        $this->assertStringContainsString('"price":10', $json);
    }

    public function testGetJsonDataContainsCorrectValuesWithCustomerAndProduct()
    {
        $restPayment = new RestPayment('1234', [], $this->customer, [$this->product]);
        $json = $restPayment->getJsonData();

        $this->assertStringContainsString('"orderNumber":"1234"', $json);
        $this->assertStringContainsString('"currency":"EUR"', $json);
        $this->assertStringContainsString('"locale":"fi_FI"', $json);
        $this->assertStringContainsString('"urlSet":', $json);

        $this->assertStringContainsString('"contact":', $json);
        $this->assertStringContainsString('"firstName":"Foo"', $json);
        $this->assertStringContainsString('"lastName":"Bar"', $json);

        $this->assertStringContainsString('"products":', $json);
        $this->assertStringContainsString('"title":"Foo"', $json);
    }

    public function testGetXmlDataContainsCorrectValuesWithPrice()
    {
        $restPayment = new RestPayment('1234', [], null, [], 10);
        $xml = $restPayment->getXmlData();

        $this->assertStringContainsString('<orderNumber>1234', $xml);
        $this->assertStringContainsString('<currency>EUR', $xml);
        $this->assertStringContainsString('<locale>fi_FI', $xml);
        $this->assertStringContainsString('<urlSet>', $xml);
        $this->assertStringContainsString('<price>10', $xml);
    }

    public function testGetXmlDataContainsCorrectValuesWithCustomerAndProduct()
    {
        $restPayment = new RestPayment('1234', [], $this->customer, [$this->product]);
        $xml = $restPayment->getXmlData();

        $this->assertStringContainsString('<orderNumber>1234', $xml);
        $this->assertStringContainsString('<currency>EUR', $xml);
        $this->assertStringContainsString('<locale>fi_FI', $xml);
        $this->assertStringContainsString('<urlSet>', $xml);

        $this->assertStringContainsString('<contact>', $xml);
        $this->assertStringContainsString('<firstName>Foo', $xml);
        $this->assertStringContainsString('<lastName>Bar', $xml);

        $this->assertStringContainsString('<products>', $xml);
        $this->assertStringContainsString('<title>Foo', $xml);
    }
}
