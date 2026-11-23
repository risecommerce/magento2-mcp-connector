<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

use Risecommerce\PiiRedactor\Context\ContextWords;
use Risecommerce\PiiRedactor\Detector\Builtin\ApiKeyDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\CreditCardDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\CryptoAddressDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\DateOfBirthDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\EmailDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\GeoCoordinateDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\IbanDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\IpAddressDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\MacAddressDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\PhoneDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\PrivateKeyDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\SwiftBicDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\UrlDetector;
use Risecommerce\PiiRedactor\Detector\Builtin\VatIdDetector;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRules;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Aggregation point for the built-in detector set. Each entity's regexes and
 * validator live in its own Builtin\* class; this wires context words (the
 * shipped 'en' pack by default, or a consumer-extended pack) into them.
 */
final class BuiltinDetectors
{
    /**
     * @param ContextWords|null $words Defaults to the shipped English pack; pass an extended pack to override.
     * @return PatternDetector[]
     */
    public static function all(?ContextWords $words = null): array
    {
        $words ??= ContextWords::default();

        return [
            self::email($words),
            self::phone($words),
            self::creditCard($words),
            self::iban($words),
            self::ipAddress($words),
            self::macAddress($words),
            self::url($words),
            self::cryptoAddress($words),
            self::dateOfBirth($words),
            self::apiKey($words),
            self::privateKey($words),
            self::vatId($words),
            self::geoCoordinate($words),
            self::swiftBic($words),
        ];
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function email(?ContextWords $words = null): PatternDetector
    {
        return EmailDetector::create(($words ?? ContextWords::default())->for(EntityType::EMAIL));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function phone(?ContextWords $words = null): PatternDetector
    {
        return PhoneDetector::create(($words ?? ContextWords::default())->for(EntityType::PHONE));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function creditCard(?ContextWords $words = null): PatternDetector
    {
        return CreditCardDetector::create(($words ?? ContextWords::default())->for(EntityType::CREDIT_CARD));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function iban(?ContextWords $words = null): PatternDetector
    {
        return IbanDetector::create(($words ?? ContextWords::default())->for(EntityType::IBAN));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function ipAddress(?ContextWords $words = null): PatternDetector
    {
        return IpAddressDetector::create(($words ?? ContextWords::default())->for(EntityType::IP_ADDRESS));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function macAddress(?ContextWords $words = null): PatternDetector
    {
        return MacAddressDetector::create(($words ?? ContextWords::default())->for(EntityType::MAC_ADDRESS));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function url(?ContextWords $words = null): PatternDetector
    {
        return UrlDetector::create(($words ?? ContextWords::default())->for(EntityType::URL));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function cryptoAddress(?ContextWords $words = null): PatternDetector
    {
        return CryptoAddressDetector::create(($words ?? ContextWords::default())->for(EntityType::CRYPTO_ADDRESS));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function dateOfBirth(?ContextWords $words = null): PatternDetector
    {
        return DateOfBirthDetector::create(($words ?? ContextWords::default())->for(EntityType::DATE_OF_BIRTH));
    }

    /**
     * @param ContextWords|null $words
     * @param SecretRules|null $rules Defaults to the shipped vendor pack.
     * @return PatternDetector
     */
    public static function apiKey(?ContextWords $words = null, ?SecretRules $rules = null): PatternDetector
    {
        return ApiKeyDetector::create(($words ?? ContextWords::default())->for(EntityType::API_KEY), $rules);
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function privateKey(?ContextWords $words = null): PatternDetector
    {
        return PrivateKeyDetector::create(($words ?? ContextWords::default())->for(EntityType::PRIVATE_KEY));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function vatId(?ContextWords $words = null): PatternDetector
    {
        return VatIdDetector::create(($words ?? ContextWords::default())->for(EntityType::VAT_ID));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function geoCoordinate(?ContextWords $words = null): PatternDetector
    {
        return GeoCoordinateDetector::create(($words ?? ContextWords::default())->for(EntityType::GEO_COORDINATE));
    }

    /**
     * @param ContextWords|null $words
     * @return PatternDetector
     */
    public static function swiftBic(?ContextWords $words = null): PatternDetector
    {
        return SwiftBicDetector::create(($words ?? ContextWords::default())->for(EntityType::SWIFT_BIC));
    }
}
