<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRules;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in API_KEY detector. Compiles a SecretRules pack (shipped vendor
 * pack by default) into a PatternDetector.
 */
final class ApiKeyDetector
{
    /**
     * @param string[] $contextWords
     * @param SecretRules|null $rules Defaults to the shipped vendor pack.
     * @return PatternDetector
     */
    public static function create(array $contextWords = [], ?SecretRules $rules = null): PatternDetector
    {
        return new PatternDetector(
            'api-key',
            EntityType::API_KEY->value,
            ($rules ?? SecretRules::default())->toPatterns(),
            null,
            $contextWords,
        );
    }
}
