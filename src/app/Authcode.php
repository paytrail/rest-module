<?php

declare(strict_types=1);

namespace Paytrail\Rest;

/**
 * @package rest-module
 * @author Paytrail <tech@paytrail.com>
 */
class Authcode
{
    /**
     * Calculate expected return authcode.
     *
     * @param array $returnParameters
     * @param Merchant $merchant
     * @return string
     */
    public static function calculateReturnAuthCode(array $returnParameters, Merchant $merchant): string
    {
        $returnParameters[] = $merchant->secret;
        unset($returnParameters['RETURN_AUTHCODE']);

        return strToUpper(hash('md5', implode('|', $returnParameters)));
    }
}
