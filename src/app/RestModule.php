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

    private $restClient;

    public function __construct(Merchant $merchant, RestClient $restClient = null)
    {
        $this->merchant = $merchant;
        $this->restClient = $restClient;
    }

    /**
     * Add customer information to order.
     *
     * @param Customer $customer
     * @return void
     */
    public function addCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Add products to order.
     *
     * @param array $products
     * @return void
     * @throws ProductException
     */
    public function addProducts(array $products): self
    {
        if ($this->price !== null) {
            throw new ProductException('Either Price or Product must be added, not both');
        }

        if (count($products) >= 500) {
            throw new ProductException('Paytrail can only handle up to 500 different product rows. Please group products using product amount.');
        }

        $this->products = $products;

        return $this;
    }

    /**
     * Add order price.
     *
     * @param float $price
     * @return void
     * @throws ProductException
     * @throws ValidationException
     */
    public function addPrice(float $price): self
    {
        if (!empty($this->products)) {
            throw new ProductException('Either Price or Product must be added, not both');
        }

        if ($this->customer !== null) {
            throw new ValidationException('Customer information needs product information');
        }

        $this->price = $price;

        return $this;
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
    public function createPayment(string $orderNumber, array $paymentData = [], string $type = self::TYPE_JSON): self
    {
        if ($this->price === null && empty($this->products)) {
            throw new ProductException('Payment must have price or at least one product');
        }

        $this->type = $type;
        $this->payment = new RestPayment($orderNumber, $paymentData, $this->customer, $this->products, $this->price);

        return $this;
    }

    /**
     * Get link to Paytrail payment page.
     *
     * @param RestClient $restClient
     * @return string
     * @throws ValidationException
     */
    public function getPaymentLink(): string
    {
        if ($this->payment === null) {
            throw new ValidationException('No valid payment found');
        }

        $restClient = $this->restClient ?? new RestClient($this->merchant, $this->type);
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
    public function getPaymentWidget(): string
    {
        if (!$this->payment) {
            throw new ValidationException('No valid payment found');
        }

        $restClient = $this->restClient ?? new RestClient($this->merchant, $this->type);
        $response = $restClient->getResponse($this->payment);

        $templateData = [
            'widgetUrl' => self::WIDGET_URL,
            'token' => $response->token,
        ];

        return Template::render('widget', $templateData);
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
