<?php

declare(strict_types=1);

namespace Paytrail\Rest;

/**
 * Class for Merchant data
 *
 * @package e2-module
 * @author Paytrail <tech@paytrail.com>
 */
class Merchant
{
    public $id;
    public $secret;

    public static function create(string $merchantId, string $merchantSecret): self
    {
        $merchant = new self();
        $merchant->id = $merchantId;
        $merchant->secret = $merchantSecret;

        return $merchant;
    }
}
