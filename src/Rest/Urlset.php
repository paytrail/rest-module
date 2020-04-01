<?php

declare(strict_types=1);

namespace Paytrail\Rest;

class Urlset
{
    public $successUrl;
    public $failureUrl;
    public $notificationUrl;
    public $pendingUrl;

    public function __construct(string $successUrl, string $failureUrl, string $notificationUrl, ?string $pendingUrl = null)
    {
        $this->successUrl = $successUrl;
        $this->failureUrl = $failureUrl;
        $this->notificationUrl = $notificationUrl;
        $this->pendingUrl = $pendingUrl;
    }
}
