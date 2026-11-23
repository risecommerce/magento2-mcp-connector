<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Validator;

use Risecommerce\PiiRedactor\Validator\IbanValidator;
use PHPUnit\Framework\TestCase;

final class IbanValidatorTest extends TestCase
{
    private IbanValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new IbanValidator();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validIbans(): array
    {
        return [
            'latvia' => ['LV80BANK0000435195001'],
            'germany' => ['DE89370400440532013000'],
            'uk' => ['GB29NWBK60161331926819'],
            'france' => ['FR1420041010050500013M02606'],
            'netherlands' => ['NL91ABNA0417164300'],
        ];
    }

    /**
     * @dataProvider validIbans
     */
    public function testAcceptsValidIban(string $iban): void
    {
        self::assertTrue($this->validator->validate($iban));
    }

    public function testRejectsBadChecksum(): void
    {
        self::assertFalse($this->validator->validate('LV80BANK0000435195002'));
    }

    public function testRejectsWrongLengthForCountry(): void
    {
        // Valid mod-97 cannot save a DE IBAN that is not 22 chars
        self::assertFalse($this->validator->validate('DE8937040044053201300'));
    }

    public function testRejectsUnknownCountry(): void
    {
        self::assertFalse($this->validator->validate('XX89370400440532013000'));
    }
}
