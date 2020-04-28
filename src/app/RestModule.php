<?php

declare(strict_types=1);

namespace Paytrail\Rest;

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
    const TYPE_XML = 'application/xml';
    const TYPE_JSON = 'application/json';

    const WIDGET_URL = 'https://payment.paytrail.com/js/payment-widget-v1.0.min.js';

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

    public function getPaymentLink(RestClient $restClient = null): string
    {
        if (!$this->payment) {
            throw new ValidationException('No valid payment found');
        }

        $restClient = $restClient ?? new RestClient($this->merchant, $this->type);

        $response = $restClient->getResponse($this->payment);

        return (string) $response->url;
    }

    public function getPaymentWidget(RestClient $restClient = null): string
    {
        if (!$this->payment) {
            throw new ValidationException('No valid payment found');
        }

        $restClient = $restClient ?? new RestClient($this->merchant, $this->type);

        $response = $restClient->getResponse($this->payment);

        $html = '<p id="paytrailPayment"></p>
            <script type="text/javascript" src="' . self::WIDGET_URL . '"></script>
                <script type="text/javascript">
                    SV.widget.initWithToken(\'paytrailPayment\',\'' . $response->token . '\', {charset: \'UTF-8\'});
                </script>';

        return $html;
    }

    public function returnAuthcodeIsValid(array $returnParameters): bool
    {
        return $returnParameters['RETURN_AUTHCODE'] == Authcode::calculateReturnAuthCode($returnParameters, $this->merchant);
    }

    public function isPaid(array $returnParameters): bool
    {
        return isset($returnParameters['PAID']);
    }
}
