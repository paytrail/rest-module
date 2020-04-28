<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use Paytrail\Exceptions\ProductException;

/**
 * @author Paytrail <tech@paytrail.com>
 */
class Product
{
    const TYPE_NORMAL = 1;
    const TYPE_POSTAL = 2;
    const TYPE_HANDLING = 3;

    public $title;
    public $price;
    public $amount;
    public $type;
    public $vat;
    public $discount;
    public $code;

    /**
     * Create new product.
     *
     * @param array $productData
     *
     * @throws ProductException
     *
     * @return self
     */
    public static function create(array $productData): self
    {
        if (!isset($productData['title']) || !isset($productData['price'])) {
            throw new ProductException('title and price are mandatory');
        }

        $product = new self();
        $product->title = $productData['title'];
        $product->price = $productData['price'];
        $product->amount = $productData['amount'] ?? 1;
        $product->type = $productData['type'] ?? self::TYPE_NORMAL;
        $product->vat = $productData['vat'] ?? 24;
        $product->discount = $productData['discount'] ?? 0;
        $product->code = $productData['code'] ?? '';

        return $product;
    }
}
