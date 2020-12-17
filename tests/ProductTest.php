<?php

declare(strict_types=1);

namespace Paytrail\Tests;

use Paytrail\Exception\ProductException;
use Paytrail\Rest\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductWithoutTitleThrowsException()
    {
        $this->expectException(ProductException::class);
        Product::create([
            'price' => 10,
        ]);
    }

    public function testProductWithoutPriceThrowsException()
    {
        $this->expectException(ProductException::class);
        Product::create([
            'title' => 'Test Product',
        ]);
    }
}
