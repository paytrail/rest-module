<?php

declare(strict_types=1);

namespace Paytrail\Rest;

final class S1Payment extends RestPayment
{
    private $price;

    public function __construct($orderNumber, $urlset, $price)
    {
        parent::__construct($orderNumber, $urlset);
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Get payment data as array
     *
     * @return array REST API compatible payment data
     * @throws PaytrailException
     */
    public function getJsonData()
    {
        $data = array(
            "orderNumber" => $this->getOrderNumber(),
            "referenceNumber" => $this->getCustomReferenceNumber(),
            "description" => $this->getDescription(),
            "currency" => $this->getCurrency(),
            "locale" => $this->getLocale(),
            "urlSet" => array(
                "success" => $this->getUrlset()->successUrl,
                "failure" => $this->getUrlset()->failureUrl,
                "pending" => $this->getUrlset()->pendingUrl,
                "notification" => $this->getUrlset()->notificationUrl,
            ),
            "price" => $this->getPrice(),
        );

        return $data;
    }
}