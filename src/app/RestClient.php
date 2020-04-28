<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Paytrail\Exception\ConnectionException;

/**
 * @package rest-module
 * @author Paytrail <tech@paytrail.com>
 */
class RestClient
{
    const SERVICE_URL = 'https://payment.paytrail.com/api-payment/create';
    const API_VERSION = 1;

    private $merchant;
    private $type;

    private $client;

    public function __construct(Merchant $merchant, string $type, ?Client $client = null)
    {
        $this->merchant = $merchant;
        $this->type = $type;
        $this->client = $client ?? new Client();
    }

    public function getResponse(RestPayment $payment): object
    {
        $content = $this->getRequestContent($payment);
        return $this->getRequestResponse($content);
    }

    private function getRequestContent(RestPayment $payment): string
    {
        if ($this->type === RestModule::TYPE_JSON) {
            return $payment->getJsonData();
        }

        return $payment->getXmlData();
    }

    private function sendRequest(string $content): Response
    {
        try {
            return $this->client->request('POST', self::SERVICE_URL, [
                'body' => $content,
                'auth' => [$this->merchant->id, $this->merchant->secret],
                'headers' => [
                    'Content-Type' => $this->type,
                    'Accept' => $this->type,
                    'X-Verkkomaksut-Api-Version' => self::API_VERSION,
                ]
            ]);
        } catch (ClientException $e) {
            return $e->getResponse();
        }
    }

    private function getRequestResponse($content)
    {
        $response = $this->sendRequest($content);

        if ($response->getStatusCode() !== 201) {

            if ($response->getHeader('Content-Type')[0] === RestModule::TYPE_XML) {
                $xml = simplexml_load_string($response->getBody()->getContents());

                throw new ConnectionException((string) $xml->errorMessage);
            }

            $response = json_decode($response->getBody()->getContents());
            throw new ConnectionException($response->errorMessage);
        }

        if ($response->getHeader('Content-Type')[0] === RestModule::TYPE_JSON) {
            return json_decode($response->getBody()->getContents());
        }

        return simplexml_load_string($response->getBody()->getContents());
    }
}
