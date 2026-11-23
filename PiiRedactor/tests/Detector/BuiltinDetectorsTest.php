<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector;

use Risecommerce\PiiRedactor\Context\ContextWords;
use Risecommerce\PiiRedactor\Detector\BuiltinDetectors;
use Risecommerce\PiiRedactor\Detector\DetectorInterface;
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\EntityType;
use PHPUnit\Framework\TestCase;

final class BuiltinDetectorsTest extends TestCase
{
    /**
     * @param DetectorInterface $detector
     * @param string $text
     * @return string[] The matched substrings.
     */
    private function texts(DetectorInterface $detector, string $text): array
    {
        return array_map(
            static fn ($m): string => substr($text, $m->start, $m->length()),
            $detector->detect($text),
        );
    }

    public function testEmail(): void
    {
        $d = BuiltinDetectors::email();
        self::assertSame(['john.doe+tag@example.co.uk'], $this->texts($d, 'mail john.doe+tag@example.co.uk now'));
        self::assertSame([], $this->texts($d, 'no email here @ all'));
    }

    public function testUrl(): void
    {
        $d = BuiltinDetectors::url();
        self::assertSame(['https://example.com/a?b=1'], $this->texts($d, 'see https://example.com/a?b=1 now'));
        self::assertSame(['www.example.com'], $this->texts($d, 'see www.example.com now'));
    }

    public function testIpAddress(): void
    {
        $d = BuiltinDetectors::ipAddress();
        self::assertSame(['192.168.1.100'], $this->texts($d, 'from 192.168.1.100 today'));
        self::assertSame(['2001:db8::8a2e:370:7334'], $this->texts($d, 'v6 2001:db8::8a2e:370:7334 here'));
        self::assertSame(['192.168.1.100'], $this->texts($d, 'from 192.168.1.100. Next'));
        self::assertSame([], $this->texts($d, 'version 1.2.3.4.beta shipped'));
        // embedded in a longer dotted sequence (version string) must NOT match
        self::assertSame([], $this->texts($d, 'release 1.2.3.4.5 shipped'));
        self::assertSame([], $this->texts($d, 'value 999.999.999.999 invalid'));
    }

    public function testMacAddress(): void
    {
        $d = BuiltinDetectors::macAddress();
        self::assertSame(['00:1A:2B:3C:4D:5E'], $this->texts($d, 'nic 00:1A:2B:3C:4D:5E up'));
        self::assertSame(['00-1a-2b-3c-4d-5e'], $this->texts($d, 'nic 00-1a-2b-3c-4d-5e up'));
    }

    public function testCreditCardRequiresLuhn(): void
    {
        $d = BuiltinDetectors::creditCard();
        self::assertSame(['4111 1111 1111 1111'], $this->texts($d, 'pay 4111 1111 1111 1111 thanks'));
        self::assertSame(['4111-1111-1111-1111'], $this->texts($d, 'pay 4111-1111-1111-1111 thanks'));
        self::assertSame([], $this->texts($d, 'order 4111 1111 1111 1112 thanks')); // Luhn fail
        self::assertSame(1.0, $d->detect('pay 4111111111111111 ok')[0]->score);
    }

    public function testIban(): void
    {
        $d = BuiltinDetectors::iban();
        self::assertSame(['LV80BANK0000435195001'], $this->texts($d, 'acct LV80BANK0000435195001 ok'));
        self::assertSame(['DE89 3704 0044 0532 0130 00'], $this->texts($d, 'acct DE89 3704 0044 0532 0130 00 ok'));
        self::assertSame([], $this->texts($d, 'acct LV80BANK0000435195002 ok')); // mod-97 fail
    }

    public function testPhone(): void
    {
        $d = BuiltinDetectors::phone();
        self::assertSame(['+371 26123456'], $this->texts($d, 'call +371 26123456 now'));
        self::assertSame(['+1 (555) 123-4567'], $this->texts($d, 'call +1 (555) 123-4567 now'));
        // a stray preceding digit must not be swallowed into the match
        self::assertSame(['26123456'], $this->texts($d, 'phone 1 26123456'));
        // bare national number matches at low score (context boosting happens in Analyzer)
        $matches = $d->detect('call 26123456 now');
        self::assertCount(1, $matches);
        self::assertLessThan(0.5, $matches[0]->score);
    }

    public function testCryptoAddress(): void
    {
        $d = BuiltinDetectors::cryptoAddress();
        self::assertSame(
            ['1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'],
            $this->texts($d, 'btc 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa ok'),
        );
        self::assertSame(
            ['0x52908400098527886E0F7030069857D2E4169EE7'],
            $this->texts($d, 'eth 0x52908400098527886E0F7030069857D2E4169EE7 ok'),
        );
    }

    public function testDateOfBirth(): void
    {
        $d = BuiltinDetectors::dateOfBirth();
        $matches = $d->detect('born 12/04/1985 in Riga');
        self::assertCount(1, $matches);
        // base score is low; Analyzer's context boost lifts it past threshold
        self::assertLessThan(0.5, $matches[0]->score);
        self::assertSame([], $this->texts($d, 'born 12/04/2099 impossible')); // validator discards
    }

    public function testAllReturnsFourteenDetectorsWithUniqueNames(): void
    {
        $all = BuiltinDetectors::all();
        $names = array_map(static fn ($d): string => $d->getName(), $all);

        self::assertCount(14, $all);
        self::assertSame($names, array_unique($names));
    }

    public function testWithDefaultsRegistersAllBuiltins(): void
    {
        $registry = DetectorRegistry::withDefaults();

        self::assertCount(14, $registry->all());
        self::assertTrue($registry->has('email'));
    }

    public function testApiKeyDetectsVendorTokenViaAll(): void
    {
        $d = BuiltinDetectors::apiKey();

        $matches = $d->detect('key sk_live_' . str_repeat('a1B2', 6) . ' end');

        self::assertCount(1, $matches);
        self::assertSame('API_KEY', $matches[0]->entityType);
        self::assertContains('token', $d->getContextWords());
    }

    public function testNewDetectorFactoriesExist(): void
    {
        self::assertSame('private-key', BuiltinDetectors::privateKey()->getName());
        self::assertSame('vat-id', BuiltinDetectors::vatId()->getName());
        self::assertSame('geo-coordinate', BuiltinDetectors::geoCoordinate()->getName());
        self::assertSame('swift-bic', BuiltinDetectors::swiftBic()->getName());
    }

    public function testDefaultsShipEnglishWordsOnly(): void
    {
        $phone = BuiltinDetectors::phone();

        self::assertContains('phone', $phone->getContextWords());
        self::assertNotContains('tālrunis', $phone->getContextWords());
    }

    public function testExtendedContextWordsReachDetectors(): void
    {
        $words = ContextWords::default()->withWords(EntityType::PHONE, ['tālrunis']);

        $phone = BuiltinDetectors::phone($words);

        self::assertContains('phone', $phone->getContextWords()); // shipped retained
        self::assertContains('tālrunis', $phone->getContextWords()); // consumer added
    }
}
