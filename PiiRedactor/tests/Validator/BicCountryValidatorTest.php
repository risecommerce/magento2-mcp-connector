<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Validator;

use Risecommerce\PiiRedactor\Validator\BicCountryValidator;
use PHPUnit\Framework\TestCase;

final class BicCountryValidatorTest extends TestCase
{
    public function testValidCountryCodeKeepsBaseScore(): void
    {
        self::assertNull((new BicCountryValidator())->validate('HABALV22'));    // LV
        self::assertNull((new BicCountryValidator())->validate('DEUTDEFF500')); // DE, 11 chars
        self::assertNull((new BicCountryValidator())->validate('BUKBXK22'));    // XK (Kosovo) included
    }

    public function testInvalidCountryCodeDiscards(): void
    {
        self::assertFalse((new BicCountryValidator())->validate('PASSWORD')); // "WO" is not a country
        self::assertFalse((new BicCountryValidator())->validate('TRANSFER')); // "SF" is not a country
    }
}
