<?php

declare(strict_types=1);

namespace Tests;

use Paytrail\Exceptions\ProductException;
use Paytrail\Exceptions\ValidationException;
use Paytrail\Rest\Customer;
use Paytrail\Rest\Merchant;
use Paytrail\Rest\Product;
use Paytrail\Rest\RestClient;
use Paytrail\Rest\RestModule;
use Paytrail\Rest\RestPayment;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

class RestModuleTest extends TestCase
{
    const TOKEN = 'secretToken';
    const PAYMENT_LINK = 'linkToPayment';

    const ORDER_NUMBER = 'Test-Payment-1234';

    private $restModule;
    private $product;
    private $customer;

    private $prophet;

    public function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();

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

    private function getRestResponse()
    {
        $response = new \stdClass();
        $response->url = self::PAYMENT_LINK;
        $response->token = self::TOKEN;
        return $response;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->prophet->checkPredictions();
    }

    public function testExceptionIsThrownWithoutOrderNumberOnPaymentLink()
    {
        $this->expectException(ValidationException::class);
        $this->restModule->getPaymentLink();
    }

    public function testExceptionIsThrownWithoutOrderNumberOnPaymentWidget()
    {
        $this->expectException(ValidationException::class);
        $this->restModule->getPaymentWidget();
    }

    public function testExceptionIsThrownWithoutProductsOrPrice()
    {
        $this->expectException(ProductException::class);
        $this->restModule->createPayment(self::ORDER_NUMBER);
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

    public function testCustomerInformationWithPriceThrowsException()
    {
        $this->expectException(ValidationException::class);
        $this->restModule->addCustomer($this->customer);
        $this->restModule->addPrice(10);
    }

    public function testPaymentLinkIsCreated()
    {
        $payment = new RestPayment(self::ORDER_NUMBER, [], null, [], 10);

        $restResponse = $this->getRestResponse();

        $this->restModule->addPrice(10);
        $this->restModule->createPayment(self::ORDER_NUMBER, [], RestModule::TYPE_XML);

        $restClientMock = $this->prophet->prophesize();
        $restClientMock->willExtend(RestClient::class);
        $restClientMock->getResponse($payment)->willReturn($restResponse);

        $paymentLink = $this->restModule->getPaymentLink($restClientMock->reveal());

        $this->assertSame(self::PAYMENT_LINK, $paymentLink);
    }

    public function testPaymentWidgetIsCreated()
    {
        $payment = new RestPayment(self::ORDER_NUMBER, [], $this->customer, [$this->product]);

        $restResponse = $this->getRestResponse();

        $this->restModule->addCustomer($this->customer);
        $this->restModule->addProducts([$this->product]);
        $this->restModule->createPayment(self::ORDER_NUMBER);

        $restClientMock = $this->prophet->prophesize();
        $restClientMock->willExtend(RestClient::class);
        $restClientMock->getResponse($payment)->willReturn($restResponse);

        $paymentLink = $this->restModule->getPaymentWidget($restClientMock->reveal());

        $this->assertStringContainsString(self::TOKEN, $paymentLink);
        $this->assertStringContainsString(RestModule::WIDGET_URL, $paymentLink);
    }

    public function testIsPaidReturnsCorrectValues()
    {
        $notPaidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058158',
            'RETURN_AUTHCODE ' => 'B1370EB96F52DD1EDB2B3400807A6597'
        ];

        $paidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B134C3',
        ];

        $this->assertFalse($this->restModule->isPaid($notPaidReturnParameters));
        $this->assertTrue($this->restModule->isPaid($paidReturnParameters));
    }

    public function testReturnAuhtcodeIsCorrect()
    {
        $notPaidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058158',
            'RETURN_AUTHCODE' => 'B1370EB96F52DD1EDB2B3400807A6597'
        ];

        $paidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B134C3',
        ];

        $this->assertTrue($this->restModule->returnAuthcodeIsValid($notPaidReturnParameters));
        $this->assertTrue($this->restModule->returnAuthcodeIsValid($paidReturnParameters));
    }

    public function testReturnAuhtcodeIsInvalidWhenParameterIsMissing()
    {
        $returnParameters = [
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B134C3',
        ];

        $this->assertFalse($this->restModule->returnAuthcodeIsValid($returnParameters));
    }

    public function testReturnAuhtcodeIsInvalidWhenCalculatedSumDoesNotMatch()
    {
        $returnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B1AAAA',
        ];

        $this->assertFalse($this->restModule->returnAuthcodeIsValid($returnParameters));
    }
}
