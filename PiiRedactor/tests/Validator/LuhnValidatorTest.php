<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Validator;

use Risecommerce\PiiRedactor\Validator\LuhnValidator;
use PHPUnit\Framework\TestCase;

final class LuhnValidatorTest extends TestCase
{
    private LuhnValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LuhnValidator();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validNumbers(): array
    {
        return [
            'visa test' => ['4111111111111111'],
            'mastercard test' => ['5500005555555559'],
            'amex test' => ['378282246310005'],
            'discover test' => ['6011111111111117'],
        ];
    }

    /**
     * @dataProvider validNumbers
     */
    public function testAcceptsValidLuhn(string $number): void
    {
        self::assertTrue($this->validator->validate($number));
    }

    public function testRejectsLuhnFailure(): void
    {
        self::assertFalse($this->validator->validate('4111111111111112'));
    }

    public function testRejectsNonDigits(): void
    {
        self::assertFalse($this->validator->validate('4111-1111-1111-1111'));
    }

    public function testRejectsTooShortAndTooLong(): void
    {
        self::assertFalse($this->validator->validate('411111111111')); // 12 digits
        self::assertFalse($this->validator->validate('41111111111111111111')); // 20 digits
    }
}
