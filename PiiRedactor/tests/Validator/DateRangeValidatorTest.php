<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Validator;

use Risecommerce\PiiRedactor\Validator\DateRangeValidator;
use PHPUnit\Framework\TestCase;

final class DateRangeValidatorTest extends TestCase
{
    private DateRangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DateRangeValidator();
    }

    public function testPlausibleDateKeepsBaseScore(): void
    {
        self::assertNull($this->validator->validate('12/04/1985'));
        self::assertNull($this->validator->validate('1985-04-12'));
    }

    public function testFutureYearIsDiscarded(): void
    {
        self::assertFalse($this->validator->validate('12/04/2099'));
    }

    public function testYearBeforeMinimumIsDiscarded(): void
    {
        self::assertFalse($this->validator->validate('12/04/1850'));
    }

    public function testCandidateWithoutYearIsDiscarded(): void
    {
        self::assertFalse($this->validator->validate('12/04'));
    }
}
