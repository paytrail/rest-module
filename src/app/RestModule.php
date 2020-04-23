<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use Paytrail\Exception\ConnectionException;
use Paytrail\Exceptions\ProductException;
use Paytrail\Exceptions\ValidationException;

/**
 * Rest module
 * 
 * @author Paytrail <tech@paytrail.com>
 * @version 1.0
 */
class RestModule
{
    const SERVICE_URL = 'https://payment.paytrail.com';
    const API_VERSION = 1;

    const TYPE_XML = 'application/xml';
    const TYPE_JSON = 'application/json';

    private $merchant;
    private $customer;
    private $products = [];
    private $price;

    private $payment;
    private $type;

    public function __construct(Merchant $merchant)
    {
        $this->merchant = $merchant;
    }

    public function addCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function addProducts(array $products): void
    {
        if ($this->price) {
            throw new ProductException('Either Price of Product must be added, not both');
        }

        if (count($products) >= 500) {
            throw new ProductException('Paytrail can only handle up to 500 different product rows. Please group products using product amount.');
        }

        $this->products = $products;
    }

    public function addPrice(float $price): void
    {
        if (!empty($this->products)) {
            throw new ProductException('Either Price of Product must be added, not both');
        }

        if ($this->customer) {
            throw new ValidationException('Customer information needs product information');
        }

        $this->price = $price;
    }

    public function createPayment(string $orderNumber, array $paymentData = [], $type = self::TYPE_JSON): void
    {
        if (!$this->price && empty($this->products)) {
            throw new ProductException('Payment must have price or at least one product');
            return;
        }

        $this->type = $type;
        $this->payment = new RestPayment($orderNumber, $paymentData, $this->customer, $this->products, $this->price);
    }

    public function getPaymentLink(): string
    {
        if (!$this->payment) {
            throw new ValidationException('No valid payment found');
        }

        $response = $this->getResponse();
        return $response->url;
    }

    public function getPaymentWidget(): string
    {
        $response = $this->getResponse();

        $html = '<p id="payment"></p>
            <script type="text/javascript" src="//payment.paytrail.com/js/payment-widget-v1.0.min.js"></script>
                <script type="text/javascript">
                    SV.widget.initWithToken(\'payment\',\'' . $response->token . '\', {charset: \'UTF-8\'});
                </script>';

        return $html;
    }

    private function getResponse(): object
    {
        if ($this->type === self::TYPE_JSON) {
            $content = $this->payment->getJsonData();
        } else {
            $content = $this->payment->getXmlData();
        }

        $result = $this->postRequest($content);

        if ($result->httpCode !== 201) {
            if ($result->contentType === self::TYPE_XML) {
                $xml = simplexml_load_string($result->response);

                throw new ConnectionException($xml->errorMessage, $xml->errorCode);
            }

            $response = json_decode($result->response);
            throw new ConnectionException($response->errorMessage);
        }

        $response = $this->getResultResponse($result);

        if (!$response) {
            throw new ConnectionException('Cannot initiate payment, unknown error');
        }

        return $response;
    }

    private function getResultResponse($result): object
    {
        if ($result->contentType === self::TYPE_JSON) {
            return json_decode($result->response);
        }

        return simplexml_load_string($result->response);
    }

    private function postRequest(string $content): object
    {
        if (!function_exists('curl_init')) {
            throw new ConnectionException('Curl extension is not available. Paytrail_Module_Rest requires curl.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SERVICE_URL . '/api-payment/create');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: {$this->type}",
            "Accept: {$this->type}",
            'X-Verkkomaksut-Api-Version: ' . self::API_VERSION
        ));
        curl_setopt($ch, CURLOPT_USERPWD, $this->merchant->id . ':' . $this->merchant->secret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = new \StdClass();
        $result->response = curl_exec($ch);
        $result->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        $curlError = $result->httpCode > 0 ? null : curl_error($ch) . ' (' . curl_errno($ch) . ')';

        curl_close($ch);

        if ($curlError) {
            throw new ConnectionException("Connection failure. Please check that payment.paytrail.com is reachable from your environment ({$curlError})");
        }

        return $result;
    }

    public function returnAuthcodeIsValid(array $returnParameters): bool
    {
        $authCode = $returnParameters['RETURN_AUTHCODE'];
        unset($returnParameters['RETURN_AUTHCODE']);

        $returnParameters[] = $this->merchant->secret;
        $calculatedAuthcode = strtoupper(md5(implode('|', $returnParameters)));

        return $authCode == $calculatedAuthcode;
    }

    public function isPaid(array $returnParameters): bool
    {
        return (isset($returnParameters['METHOD']) && isset($returnParameters['TIMESTAMP']));
    }
}
