<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use Paytrail\Exceptions\ProductException;
use Paytrail\Exceptions\ValidationException;

/**
 * @author Paytrail <tech@paytrail.com>
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

    /**
     * Add customer information to order.
     *
     * @param Customer $customer
     * @return void
     */
    public function addCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * Add products to order.
     *
     * @param array $products
     * @return void
     * @throws ProductException
     */
    public function addProducts(array $products): void
    {
        if ($this->price !== null) {
            throw new ProductException('Either Price or Product must be added, not both');
        }

        if (count($products) >= 500) {
            throw new ProductException('Paytrail can only handle up to 500 different product rows. Please group products using product amount.');
        }

        $this->products = $products;
    }

    /**
     * Add order price.
     *
     * @param float $price
     * @return void
     * @throws ProductException
     * @throws ValidationException
     */
    public function addPrice(float $price): void
    {
        if (!empty($this->products)) {
            throw new ProductException('Either Price or Product must be added, not both');
        }

        if ($this->customer !== null) {
            throw new ValidationException('Customer information needs product information');
        }

        $this->price = $price;
    }

    /**
     * Create payment.
     *
     * @param string $orderNumber
     * @param array  $paymentData
     * @param string $type
     * @return void
     * @throws ProductException
     */
    public function createPayment(string $orderNumber, array $paymentData = [], string $type = self::TYPE_JSON): void
    {
        if ($this->price === null && empty($this->products)) {
            throw new ProductException('Payment must have price or at least one product');
        }

        $this->type = $type;
        $this->payment = new RestPayment($orderNumber, $paymentData, $this->customer, $this->products, $this->price);
    }

    /**
     * Get link to Paytrail payment page.
     *
     * @param RestClient $restClient
     * @return string
     * @throws ValidationException
     */
    public function getPaymentLink(RestClient $restClient = null): string
    {
        if ($this->payment === null) {
            throw new ValidationException('No valid payment found');
        }

        $restClient = $restClient ?? new RestClient($this->merchant, $this->type);
        $response = $restClient->getResponse($this->payment);

        return (string) $response->url;
    }

    /**
     * Get embed payment widget.
     *
     * @param RestClient $restClient
     * @return string
     * @throws ValidationException
     */
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

    /**
     * Validate return authcode.
     *
     * @param array $returnParameters
     * @return boolean
     */
    public function returnAuthcodeIsValid(array $returnParameters): bool
    {
        return $returnParameters['RETURN_AUTHCODE'] == Authcode::calculateReturnAuthCode($returnParameters, $this->merchant);
    }

    /**
     * Check if order is paid.
     *
     * @param array $returnParameters
     * @return boolean
     */
    public function isPaid(array $returnParameters): bool
    {
        return isset($returnParameters['PAID']);
    }
}
