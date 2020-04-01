<?php

declare(strict_types=1);

namespace Paytrail\Rest;

final class E1Payment extends RestPayment
{
    private $contact;
    private $products = array();
    private $includeVat = 1;

    public function __construct($orderNumber, Urlset $urlset, Customer $contact)
    {
        parent::__construct($orderNumber, $urlset);

        $this->_orderNumber = $orderNumber;
        $this->contact = $contact;
        $this->urlset = $urlset;
    }

    /**
     * Use this function to add each order product to payment.
     *
     * Please group same products using $amount. Paytrail
     * supports up to 500 product rows in a single payment.
     *
     * @param string $title
     * @param string $no
     * @param float $amount
     * @param float $price
     * @param float $tax
     * @param flaot $discount
     * @param int $type
     */
    public function addProduct($title, $no, $amount, $price, $tax, $discount, $type = 1)
    {
        if (count($this->products) >= 500) {
            throw new PaytrailException("Paytrail can only handle up to 500 different product rows. Please group products using product amount.");
        }

        $this->products[] = new Product($title, $no, $amount, $price, $tax, $discount, $type);
    }

    /**
     * @return Paytrail_Module_E1contact contact data for this payment
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return array List of Paytrail_Module_E1_Product objects for this payment
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * You can decide whether you wish to use taxless prices (mode=0) or
     * prices which include taxes. Default mode is 1 (taxes are in prices).
     *
     * You should always use the same mode that your web shop uses - otherwise
     * you will get problems with rounding since SV supports prices with only
     * 2 decimals.
     *
     * @param int $mode
     */
    public function setVatMode($mode)
    {
        $this->includeVat = $mode;
    }

    /**
     * @return int Vat mode attached to this payment
     */
    public function getVatMode()
    {
        return $this->includeVat;
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
            "orderDetails" => array(
                "includeVat" => $this->getVatMode(),
                "contact" => array(
                    "telephone" => $this->getContact()->telNo,
                    "mobile" => $this->getContact()->cellNo,
                    "email" => $this->getContact()->email,
                    "firstName" => $this->getContact()->firstName,
                    "lastName" => $this->getContact()->lastName,
                    "companyName" => $this->getContact()->company,
                    "address" => array(
                        "street" => $this->getContact()->addrStreet,
                        "postalCode" => $this->getContact()->addrPostalCode,
                        "postalOffice" => $this->getContact()->addrPostalOffice,
                        "country" => $this->getContact()->addrCountry,
                    ),
                ),
                "products" => array(),
            ),
        );

        foreach($this->getProducts() as $product) {
            $data["orderDetails"]["products"][] = array(
                "title" => $product->title,
                "code" => $product->code,
                "amount" => $product->amount,
                "price" => $product->price,
                "vat" => $product->vat,
                "discount" => $product->discount,
                "type" => $product->type
            );
        }

        return $data;
    }
}