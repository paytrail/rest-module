<?php

declare(strict_types=1);

namespace Paytrail\Rest;

class Customer
{
    public $firstName;
    public $lastName;
    public $email;
    public $addrStreet;
    public $addrPostalCode;
    public $addrPostalOffice;
    public $addrCountry;
    public $telNo;
    public $cellNo;
    public $company;

    /**
     * Contructor for Contact data structure. Contact holds information
     * about the user paying the payment.
     *
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $addrStreet
     * @param string $addrPostalCode
     * @param string $addrPostalOffice
     * @param string $addrCountry
     * @param string $telNo
     * @param string $cellNo
     * @param string $company
     */
    public function __construct($firstName, $lastName, $email, $addrStreet, $addrPostalCode, $addrPostalOffice, $addrCountry, $telNo = "", $cellNo = "", $company = "")
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->addrStreet = $addrStreet;
        $this->addrPostalCode = $addrPostalCode;
        $this->addrPostalOffice = $addrPostalOffice;
        $this->addrCountry = $addrCountry;
        $this->telNo = $telNo;
        $this->cellNo = $cellNo;
        $this->company = $company;
    }
}
