<?php

declare(strict_types=1);

namespace Paytrail\Tests;

use Paytrail\Exception\TemplateException;
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
