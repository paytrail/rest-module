<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use GuzzleHttp\Client;
use Paytrail\Exception\ConnectionException;

/**
 * Rest Client
 * 
 * @author Paytrail <tech@paytrail.com>
 * @version 1.0
 */
class RestClient
{
    const SERVICE_URL = 'https://payment.paytrail.com/api-payment/create';
    const API_VERSION = 1;

    private $merchant;
    private $type;

    public function __construct(Merchant $merchant, string $type)
    {
        $this->merchant = $merchant;
        $this->type = $type;
    }

    public function getResponse(RestPayment $payment): object
    {
        if ($this->type === RestModule::TYPE_JSON) {
            $content = $payment->getJsonData();
        } else {
            $content = $payment->getXmlData();
        }

        $response = $this->sendRequest($content);

        if ($response->getStatusCode() !== 201) {
            if ($response->getHeader('Content-Type') === RestModule::TYPE_XML) {
                $xml = simplexml_load_string($response->getBody()->getContents());

                throw new ConnectionException($xml->errorMessage, $xml->errorCode);
            }

            $response = json_decode($response->getBody()->getContents());
            throw new ConnectionException($response->errorMessage);
        }

        $paymentResponse = $this->getResultResponse($response);

        if (!$paymentResponse) {
            throw new ConnectionException('Cannot initiate payment, unknown error');
        }

        return $paymentResponse;
    }

    private function getResultResponse($response)
    {
        if ($response->getHeader('Content-Type')[0] === RestModule::TYPE_JSON) {
            return json_decode($response->getBody()->getContents());
        }

        return simplexml_load_string($response->getBody()->getContents());
    }

    private function sendRequest(string $content)
    {
        $client = new Client();

        return $client->request('POST', self::SERVICE_URL, [
            'body' => $content,
            'auth' => [$this->merchant->id, $this->merchant->secret],
            'headers' => [
                'Content-Type' => $this->type,
                'Accept' => $this->type,
                'X-Verkkomaksut-Api-Version' => self::API_VERSION,
            ]
        ]);
    }
}
