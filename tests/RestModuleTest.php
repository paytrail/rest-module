<?php

declare(strict_types=1);

namespace Paytrail\Tests;

use Paytrail\Exception\ProductException;
use Paytrail\Exception\ValidationException;
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

    private $product;
    private $customer;

    private $prophet;

    public function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();



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

    private function getRestModule(RestPayment $payment = null)
    {
        $merchant = Merchant::create('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');

        $restClientMock = $this->prophet->prophesize();
        $restClientMock->willExtend(RestClient::class);
        $restClientMock->getResponse($payment)->willReturn($this->getRestResponse());

        return new RestModule($merchant, $restClientMock->reveal());
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
        $restModule = $this->getRestModule();
        $this->expectException(ValidationException::class);
        $restModule->getPaymentLink();
    }

    public function testExceptionIsThrownWithoutOrderNumberOnPaymentWidget()
    {
        $restModule = $this->getRestModule();
        $this->expectException(ValidationException::class);
        $restModule->getPaymentWidget();
    }

    public function testExceptionIsThrownWithoutProductsOrPrice()
    {
        $restModule = $this->getRestModule();
        $this->expectException(ProductException::class);
        $restModule->createPayment(self::ORDER_NUMBER);
    }

    public function testExceptionIsThrownWhenAddingProductWhenAmountIsSet()
    {
        $restModule = $this->getRestModule();
        $this->expectException(ProductException::class);
        $restModule->addPrice(10);
        $restModule->addProducts([$this->product]);
    }

    public function testExceptionIsThrownWhenAddingAmountAndHasProducts()
    {
        $restModule = $this->getRestModule();
        $this->expectException(ProductException::class);
        $restModule->addProducts([$this->product]);
        $restModule->addPrice(10);
    }

    public function testCustomerInformationWithPriceThrowsException()
    {
        $restModule = $this->getRestModule();
        $this->expectException(ValidationException::class);
        $restModule->addCustomer($this->customer);
        $restModule->addPrice(10);
    }

    public function testPaymentLinkIsCreated()
    {
        $payment = new RestPayment(self::ORDER_NUMBER, [], null, [], 10);

        $restModule = $this->getRestModule($payment);

        $restModule->addPrice(10);
        $restModule->createPayment(self::ORDER_NUMBER, [], RestModule::TYPE_XML);

        $paymentLink = $restModule->getPaymentLink();

        $this->assertSame(self::PAYMENT_LINK, $paymentLink);
    }

    public function testPaymentWidgetIsCreated()
    {
        $payment = new RestPayment(self::ORDER_NUMBER, [], $this->customer, [$this->product]);

        $restModule = $this->getRestModule($payment);

        $restModule->addCustomer($this->customer);
        $restModule->addProducts([$this->product]);
        $restModule->createPayment(self::ORDER_NUMBER);

        $paymentLink = $restModule->getPaymentWidget();

        $this->assertStringContainsString(self::TOKEN, $paymentLink);
        $this->assertStringContainsString(RestModule::WIDGET_URL, $paymentLink);
    }

    public function testIsPaidReturnsCorrectValues()
    {
        $notPaidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058158',
            'RETURN_AUTHCODE' => 'B1370EB96F52DD1EDB2B3400807A6597',
        ];

        $paidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B134C3',
        ];

        $restModule = $this->getRestModule();

        $this->assertFalse($restModule->isPaid($notPaidReturnParameters));
        $this->assertTrue($restModule->isPaid($paidReturnParameters));
    }

    public function testReturnAuhtcodeIsCorrect()
    {
        $notPaidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058158',
            'RETURN_AUTHCODE' => 'B1370EB96F52DD1EDB2B3400807A6597',
        ];

        $paidReturnParameters = [
            'ORDER_NUMBER' => self::ORDER_NUMBER,
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B134C3',
        ];

        $restModule = $this->getRestModule();

        $this->assertTrue($restModule->returnAuthcodeIsValid($notPaidReturnParameters));
        $this->assertTrue($restModule->returnAuthcodeIsValid($paidReturnParameters));
    }

    public function testReturnAuhtcodeIsInvalidWhenParameterIsMissing()
    {
        $returnParameters = [
            'TIMESTAMP' => '1588058042',
            'PAID' => 'da9974de9f',
            'METHOD' => 1,
            'RETURN_AUTHCODE' => '8D9F70E16ACC86876E0A2FF806B134C3',
        ];

        $restModule = $this->getRestModule();

        $this->assertFalse($restModule->returnAuthcodeIsValid($returnParameters));
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

        $restModule = $this->getRestModule();

        $this->assertFalse($restModule->returnAuthcodeIsValid($returnParameters));
    }
}
