<?php

declare(strict_types=1);

namespace Paytrail\Rest;

abstract class RestPayment
{
    private $orderNumber;
    private $urlset;
    private $referenceNumber = "";
    private $description = "";
    private $currency = "EUR";
    private $locale = "fi_FI";

    public function __construct(int $orderNumber, Urlset $urlset)
    {
        $this->orderNumber = $orderNumber;
        $this->urlset = $urlset;
    }

    /**
     * @return string order number for this payment
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @return Paytrail_Module_E1urlset payment return url object for this payment
     */
    public function getUrlset()
    {
        return $this->urlset;
    }

    /**
     * You can set a reference number for a payment but it is *not* recommended.
     *
     * Reference number set using this function will only be used for interface payments.
     * Interface payment means a payment done with such a payment method that is used
     * with own contract (using Paytrail only as a technical API). If payment is made
     * with payment method that is used directly with Paytrail contract, this value
     * is not used - instead Paytrail uses auto generated reference number.
     *
     * Using custom reference number may be useful if you need to automatically confirm
     * payments paid directly to your own account with your own contract. With custom
     * reference number you can match payments with it.
     *
     * @param $referenceNumber Customer reference number
     */
    public function setCustomReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    /**
     * @return string Custom reference number attached to this payment
     */
    public function getCustomReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * Change used locale. Locale affects language and number and date presentation formats.
     *
     * Paytrail supports currently three locales: Finnish (fi_FI), English (en_US)
     * and Swedish (sv_SE). Default locale is fi_FI.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        if (!in_array($locale, array("fi_FI", "en_US", "sv_SE"))) {
            throw new PaytrailException("Given locale is unsupported.");
        }

        $this->locale = $locale;
    }

    /**
     * @return string Locale attached to this payment
     */
    public function getLocale()
    {
        return $this->locale;
    }


    /**
     * Set non-default currency. Currently the default currency (EUR) is only supported
     * value.
     *
     * @param $currency Currency in which product prices are given
     */
    public function setCurrency($currency)
    {
        if ($currency != "EUR" && $currency != "SEK") {
            throw new PaytrailException("Currently EUR and SEK are the only supported currency.");
        }

        $this->currency = $currency;
    }

    /**
     * @return string Currency attached to this payment
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * You may optionally set description for the payment. This message
     * will only be visible in merchant's panel with the payment - nowhere else.
     * It allows you to save additional data with payment when necessary.
     *
     * @param string $description Private payment description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string Description attached to this payment
     */
    public function getDescription()
    {
        return $this->description;
    }
}
