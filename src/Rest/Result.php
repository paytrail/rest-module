<?php

declare(strict_types=1);

namespace Paytrail\Rest;

class Result
{
    private $token;
    private $url;

    public function __construct($token, $url)
    {
        $this->token = $token;
        $this->url = $url;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
