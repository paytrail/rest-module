<?php

declare(strict_types=1);

namespace Tests;

use Paytrail\Exceptions\TemplateException;
use Paytrail\Rest\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function testTemplateFileNotFoundThrowsException()
    {
        $this->expectException(TemplateException::class);
        Template::render('templateNotFound');
    }
}
