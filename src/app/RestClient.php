<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Paytrail\Exception\ConnectionException;

/**
 * @author Paytrail <tech@paytrail.com>
 */
class RestClient
{
    const SERVICE_URL = 'https://payment.paytrail.com/api-payment/create';
    const API_VERSION = 1;

    const SUCCESS_STATUS_CODE = 201;

    private $merchant;
    private $type;

    private $client;

    public function __construct(Merchant $merchant, string $type = RestModule::TYPE_JSON, ?Client $client = null)
    {
        $this->merchant = $merchant;
        $this->type = $type;
        $this->client = $client ?? new Client();
    }

    /**
     * Get response from Paytrail rest api.
     *
     * @param RestPayment $payment
     *
     * @return object
     */
    public function getResponse(RestPayment $payment): object
    {
        $content = $this->getRequestContent($payment);
        $response = $this->sendRequest($content);

        return $this->getResponseContent($response);
    }

    /**
     * Get rest request content.
     *
     * @param RestPayment $payment
     *
     * @return string
     */
    private function getRequestContent(RestPayment $payment): string
    {
        if ($this->type === RestModule::TYPE_JSON) {
            return $payment->getJsonData();
        }

        return $payment->getXmlData();
    }

    /**
     * Send request to Paytrail rest api.
     *
     * @param string $content
     *
     * @return Response
     */
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
                ],
            ]);
        } catch (ClientException $e) {
            return $e->getResponse();
        } catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * Get response content from response object.
     *
     * @param string $content
     *
     * @return object
     */
    private function getResponseContent(Response $response): object
    {
        $responseContent = $response->getBody()->getContents();

        if ($response->getStatusCode() !== self::SUCCESS_STATUS_CODE) {
            if ($response->getHeader('Content-Type')[0] === RestModule::TYPE_XML) {
                $xml = simplexml_load_string($responseContent);

                throw new ConnectionException((string) $xml->errorMessage);
            }

            $response = json_decode($responseContent);

            throw new ConnectionException($response->errorMessage);
        }

        if ($response->getHeader('Content-Type')[0] === RestModule::TYPE_JSON) {
            return json_decode($responseContent);
        }

        return simplexml_load_string($responseContent);
    }
}
