<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Secret;

use Risecommerce\PiiRedactor\Detector\Pattern;

/**
 * Immutable set of secret rules. default() ships the known-vendor pack;
 * consumers extend it with with() — mirrors the ContextWords API style.
 */
final class SecretRules
{
    /**
     * @param SecretRule[] $rules
     */
    private function __construct(private readonly array $rules)
    {
    }

    /**
     * @return self
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * The shipped vendor pack: known token prefixes only (no entropy tier).
     *
     * @return self
     */
    public static function default(): self
    {
        return new self([
            new SecretRule('stripe-secret-live', 'sk_live_', 'A-Za-z0-9', 24, 99, 0.95),
            new SecretRule('stripe-secret-test', 'sk_test_', 'A-Za-z0-9', 24, 99, 0.95),
            new SecretRule('stripe-publishable-live', 'pk_live_', 'A-Za-z0-9', 24, 99, 0.95),
            new SecretRule('stripe-publishable-test', 'pk_test_', 'A-Za-z0-9', 24, 99, 0.95),
            new SecretRule('stripe-restricted-live', 'rk_live_', 'A-Za-z0-9', 24, 99, 0.95),
            new SecretRule('stripe-restricted-test', 'rk_test_', 'A-Za-z0-9', 24, 99, 0.95),
            new SecretRule('aws-access-key-id', 'AKIA', 'A-Z0-9', 16, 16, 0.9),
            new SecretRule('aws-temp-access-key-id', 'ASIA', 'A-Z0-9', 16, 16, 0.9),
            new SecretRule('github-token', 'ghp_', 'A-Za-z0-9', 36, 36, 0.95),
            new SecretRule('github-oauth-token', 'gho_', 'A-Za-z0-9', 36, 36, 0.95),
            new SecretRule('github-user-token', 'ghu_', 'A-Za-z0-9', 36, 36, 0.95),
            new SecretRule('github-server-token', 'ghs_', 'A-Za-z0-9', 36, 36, 0.95),
            new SecretRule('github-refresh-token', 'ghr_', 'A-Za-z0-9', 36, 36, 0.95),
            new SecretRule('github-fine-grained-pat', 'github_pat_', 'A-Za-z0-9_', 22, 255, 0.95),
            SecretRule::fromRegex('slack-token', '/\bxox[abprs]-[A-Za-z0-9-]{10,250}\b/u', 'xox', 0.9),
            new SecretRule('google-api-key', 'AIza', 'A-Za-z0-9_\-', 35, 35, 0.9),
            new SecretRule('openai-project-key', 'sk-proj-', 'A-Za-z0-9_\-', 20, 255, 0.95),
            new SecretRule('anthropic-key', 'sk-ant-', 'A-Za-z0-9_\-', 20, 255, 0.95),
            SecretRule::fromRegex(
                'jwt',
                '/\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\b/u',
                'eyJ',
                0.85,
            ),
        ]);
    }

    /**
     * Returns a copy with the rule appended; the receiver is left unchanged.
     *
     * @param SecretRule $rule
     * @return self
     */
    public function with(SecretRule $rule): self
    {
        return new self([...$this->rules, $rule]);
    }

    /**
     * @return SecretRule[]
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * @return Pattern[]
     */
    public function toPatterns(): array
    {
        return array_map(static fn (SecretRule $rule): Pattern => $rule->toPattern(), $this->rules);
    }
}
