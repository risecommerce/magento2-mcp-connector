<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Context;

use Risecommerce\PiiRedactor\EntityType;

/**
 * Immutable, per-entity-type context-word pack. The library ships a single 'en'
 * pack via {@see self::default()}; consumers extend it with {@see self::withWords()}.
 */
final class ContextWords
{
    /**
     * The shipped English pack, keyed by EntityType::value.
     *
     * @var array<string, string[]>
     */
    private const DEFAULTS = [
        'EMAIL' => ['email', 'e-mail', 'mail', 'contact'],
        'PHONE' => ['phone', 'tel', 'mobile', 'cell', 'call', 'fax', 'whatsapp', 'contact'],
        'CREDIT_CARD' => ['card', 'credit', 'debit', 'visa', 'mastercard', 'amex', 'cc'],
        'IBAN' => ['iban', 'account', 'bank'],
        'IP_ADDRESS' => ['ip', 'address', 'host'],
        'MAC_ADDRESS' => ['mac'],
        'CRYPTO_ADDRESS' => ['wallet', 'btc', 'eth', 'bitcoin', 'ethereum', 'crypto'],
        'DATE_OF_BIRTH' => ['born', 'birth', 'dob', 'birthday'],
        'API_KEY' => ['api key', 'apikey', 'api_key', 'token', 'secret', 'bearer', 'credential'],
        'VAT_ID' => ['vat', 'tax id', 'tax number', 'reg no', 'registration'],
        'GEO_COORDINATE' => ['lat', 'latitude', 'longitude', 'coordinates', 'coords', 'gps', 'geo'],
        'SWIFT_BIC' => ['swift', 'bic', 'bank', 'wire', 'transfer'],
    ];

    /**
     * @param array<string, string[]> $words Per-entity-type words keyed by EntityType::value.
     */
    private function __construct(private readonly array $words)
    {
    }

    /**
     * Empty pack with no context words for any entity type.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * The built-in English context-word pack.
     *
     * @return self
     */
    public static function default(): self
    {
        return new self(self::DEFAULTS);
    }

    /**
     * Returns a copy with the given words merged onto the entity type's existing
     * list (order preserved, duplicates dropped). The receiver is left unchanged.
     *
     * @param string|EntityType $entityType
     * @param string[] $words
     * @return self
     */
    public function withWords(string|EntityType $entityType, array $words): self
    {
        $key = $entityType instanceof EntityType ? $entityType->value : $entityType;

        $merged = $this->words;
        $merged[$key] = array_values(array_unique([...($merged[$key] ?? []), ...$words]));

        return new self($merged);
    }

    /**
     * Resolved context words for one entity type (built-in or custom).
     *
     * @param string|EntityType $entityType
     * @return string[]
     */
    public function for(string|EntityType $entityType): array
    {
        $key = $entityType instanceof EntityType ? $entityType->value : $entityType;

        return $this->words[$key] ?? [];
    }
}
