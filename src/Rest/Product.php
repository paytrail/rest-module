<?php

declare(strict_types=1);

namespace Paytrail\Rest;

class Product
{
    const TYPE_NORMAL = 1;
    const TYPE_POSTAL = 2;
    const TYPE_HANDLING = 3;

    public $title;
    public $code;
    public $amount;
    public $price;
    public $vat;
    public $discount;
    public $type;

    /**
     * @param string $title
     * @param string $code
     * @param float $amount
     * @param float $price
     * @param flaot $vat
     * @param float $discount
     * @param int $type
     */
    public function __construct(string $title, string $code, float $amount, float $price, float $vat, float $discount, int $type)
    {
        $this->title = $title;
        $this->code = $code;
        $this->amount = $amount;
        $this->price = $price;
        $this->vat = $vat;
        $this->discount = $discount;
        $this->type = $type;
    }
}
