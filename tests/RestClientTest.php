<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Paytrail\Exception\ConnectionException;
use Paytrail\Rest\Merchant;
use Paytrail\Rest\RestClient;
use Paytrail\Rest\RestModule;
use Paytrail\Rest\RestPayment;
use PHPUnit\Framework\TestCase;

class RestClientTest extends TestCase
{
    private $merchant;
    private $payment;

    public function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
        $this->payment = new RestPayment('1234', [], null, [], 10);
    }

    private function getResponse(MockHandler $mock, string $type = RestModule::TYPE_JSON)
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $restClient = new RestClient($this->merchant, $type, $client);

        return $restClient->getResponse($this->payment);
    }

    public function testJsonDataCanBeParsed()
    {
        $responseBody = '{"foo":"bar"}';
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => RestModule::TYPE_JSON], $responseBody),
        ]);

        $response = $this->getResponse($mock);
        $this->assertSame('bar', $response->foo);
    }

    public function testXmlDataCanBeParsed()
    {
        $responseBody = '<?xml version="1.0" encoding="UTF-8"?><payment><foo>bar</foo></payment>';
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => RestModule::TYPE_XML], $responseBody),
        ]);

        $response = $this->getResponse($mock, RestModule::TYPE_XML);
        $this->assertSame('bar', (string) $response->foo);
    }

    public function testNonSuccessRequestThrowsExceptionAsJson()
    {
        $responseBody = '{"errorMessage":"Error"}';
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => RestModule::TYPE_JSON], $responseBody),
        ]);

        $this->expectException(ConnectionException::class);
        $this->getResponse($mock);
    }

    public function testNonSuccessRequestThrowsExceptionAsXml()
    {
        $responseBody = '<?xml version="1.0" encoding="UTF-8"?><payment><errorMessage>Error</errorMessage></payment>';
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => RestModule::TYPE_XML], $responseBody),
        ]);

        $this->expectException(ConnectionException::class);
        $this->getResponse($mock, RestModule::TYPE_XML);
    }

    public function testNoConnectionThrowsException()
    {
        $mock = new MockHandler([
            new ConnectException('Error', new Request('POST', 'Error')),
        ]);

        $this->expectException(ConnectionException::class);
        $this->getResponse($mock);
    }
}
