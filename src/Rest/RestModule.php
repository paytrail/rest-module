<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use Paytrail\Exception\PaytrailException;

/**
 * Rest module for Deprecated E1 and S1 API versions.
 * 
 * @author Paytrail <tech@paytrail.com>
 * @version 1.0
 * @deprecated E1 and E2 API versions are deprecated, please use E2 API version.
 */
class RestModule
{
    private $merchantId;
    private $merchantSecret;
    private $serviceUrl;

    private $urlset;
    private $customer;
    private $payment;

    /**
     * Initialize module with your own merchant id and merchant secret.
     *
     * While building and testing integration, you can use demo values
     * (merchantId = 13466, merchantSecret = ...)
     *
     * @param int $merchantId
     * @param string $merchantSecret
     */
    public function __construct(int $merchantId, string $merchantSecret, string $serviceUrl = "https://payment.paytrail.com")
    {
        trigger_error('Class ' . __CLASS__ . ' is deprecated', E_USER_DEPRECATED);

        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        $this->serviceUrl = $serviceUrl;
    }

    public function addUrlset($successUrl, $failureUrl, $notificationUrl, $pendingUrl = null)
    {
        $this->urlset = new Urlset($successUrl, $failureUrl, $notificationUrl, $pendingUrl);
    }

    public function addCustomer($firstName, $lastName, $email, $addrStreet, $addrPostalCode, $addrPostalOffice, $addrCountry, $telNo = "", $cellNo = "", $company = "")
    {
        $this->customer = new Customer($firstName, $lastName, $email, $addrStreet, $addrPostalCode, $addrPostalOffice, $addrCountry, $telNo, $cellNo, $company);
    }

    public function addE1Payment($orderNumber)
    {
        $this->payment = new E1Payment($orderNumber, $this->urlset, $this->customer);
    }

    public function addS1Payment($orderNumber, $price)
    {
        $this->payment = new S1Payment($orderNumber, $this->urlset, $price);
    }

    public function addProduct($title, $code, $amount, $price, $vat, $discount, $type)
    {
        $this->payment->addProduct($title, $code, $amount, $price, $vat, $discount, $type);
    }

    /**
     * Get url for payment
     *
     * @param Paytrail_Module_E1_Payment $payment
     * @throws PaytrailException
     * @return Paytrail_Module_E1_Result
     */
    public function processPayment()
    {
        $url = $this->serviceUrl . "/token/json";

        $data = $this->payment->getJsonData();

        // Create data array
        $url = $this->serviceUrl . "/api-payment/create";

        $result = $this->postJsonRequest($url, json_encode($data));

        if ($result->httpCode != 201) {
            if ($result->contentType === "application/xml") {
                $xml = simplexml_load_string($result->response);

                throw new PaytrailException($xml->errorMessage, $xml->errorCode);
            } elseif ($result->contentType == "application/json") {
                $json = json_decode($result->response);

                throw new PaytrailException($json->errorMessage, $json->errorCode);
            }
        }

        $data = json_decode($result->response);

        if (!$data) {
            throw new PaytrailException($result->response, "unknown-error");
        }

        return new Result($data->token, $data->url);
    }

    /**
     * This function can be used to validate parameters returned by return and notify requests.
     * Parameters must be validated in order to avoid hacking of payment confirmation.
     * This function is usually used like:
     *
     * $module = new Paytrail_Module_E1($merchantId, $merchantSecret);
     * if ($module->validateNotifyParams($_GET["ORDER_NUMBER"], $_GET["TIMESTAMP"], $_GET["PAID"], $_GET["METHOD"], $_GET["AUTHCODE"])) {
     *   // Valid notification, confirm payment
     * } else {
     *   // Invalid notification, possibly someone is trying to hack it. Do nothing or create an alert.
     * }
     *
     * @param string $orderNumber
     * @param int $timeStamp
     * @param string $paid
     * @param int $method
     * @param string $authCode
     */
    public function confirmPayment($orderNumber, $timeStamp, $paid, $method, $authCode)
    {
        $base = "{$orderNumber}|{$timeStamp}|{$paid}|{$method}|{$this->merchantSecret}";
        return $authCode == strtoupper(md5($base));
    }

    /**
     * This method submits given parameters to given url as a post request without
     * using curl extension. This should require minimum extensions
     *
     * @param $url
     * @param $content
     * @throws PaytrailException
     */
    private function postJsonRequest($url, $content)
    {
        // Check that curl is available
        if (!function_exists("curl_init")) {
            throw new PaytrailException("Curl extension is not available. Paytrail_Module_Rest requires curl.");
        }

        // Set all the curl options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "X-Verkkomaksut-Api-Version: 1"
        ));
        curl_setopt($ch, CURLOPT_USERPWD, $this->merchantId . ":" . $this->merchantSecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Read result, including http code
        $result = new \StdClass();
        $result->response = curl_exec($ch);
        $result->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        // Got no status code?
        $curlError = $result->httpCode > 0 ? null : curl_error($ch).' ('.curl_errno($ch).')';

        curl_close($ch);

        // Connection failure
        if ($curlError) {
            throw new PaytrailException("Connection failure. Please check that payment.paytrail.com is reachable from your environment ({$curlError})");
        }

        return $result;
    }
}
