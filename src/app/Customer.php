<?php

declare(strict_types=1);

namespace Paytrail\Rest;

/**
 * @package rest-module
 * @author Paytrail <tech@paytrail.com>
 */
class Customer
{
    public $firstName;
    public $lastName;
    public $email;
    public $street;
    public $postalCode;
    public $postalOffice;
    public $country;
    public $telephone;
    public $mobile;
    public $companyName;

    /**
     * Create customer for payment.
     *
     * @param array $customerData
     * @return self
     */
    public static function create(array $customerData): self
    {
        $customer = new self();
        $customer->firstName = $customerData['firstName'] ?? '';
        $customer->lastName = $customerData['lastName'] ?? '';
        $customer->email = $customerData['email'] ?? '';
        $customer->street = $customerData['street'] ?? '';
        $customer->postalCode = $customerData['postalCode'] ?? '';
        $customer->postalOffice = $customerData['postalOffice'] ?? '';
        $customer->country = $customerData['country'] ?? '';
        $customer->telephone = $customerData['telephone'] ?? '';
        $customer->mobile = $customerData['mobile'] ?? '';
        $customer->companyName = $customerData['companyName'] ?? '';

        return $customer;
    }
}
