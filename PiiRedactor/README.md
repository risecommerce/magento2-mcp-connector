# PII Redactor

Framework-agnostic PHP library for detecting and redacting PII in text.
No runtime dependencies. PHP 8.1+.

## Install

```bash
composer require risecommerce/pii-redactor
```

## Quick start

```php
use Risecommerce\PiiRedactor\PiiRedactor;

$redactor = new PiiRedactor();

echo $redactor->redact('Mail john@example.com, card 4111 1111 1111 1111')->text();
// Mail [EMAIL], card [CREDIT_CARD]
```

## Detect without redacting

```php
foreach ($redactor->analyze($text)->matches() as $match) {
    printf("%s [%d, %d) score %.2f via %s\n",
        $match->entityType, $match->start, $match->end, $match->score, $match->detectorName);
}
```

## Built-in detectors

EMAIL, PHONE, CREDIT_CARD (Luhn-validated), IBAN (mod-97-validated),
IP_ADDRESS (v4/v6), MAC_ADDRESS, URL, CRYPTO_ADDRESS (BTC/ETH), DATE_OF_BIRTH,
API_KEY (known vendor token prefixes, extensible via `SecretRules`),
PRIVATE_KEY (PEM blocks), VAT_ID (EU-27 formats), GEO_COORDINATE
(range-validated decimal pairs, context-gated), SWIFT_BIC
(country-code-validated, context-gated).

Each detector lives in its own class under `Detector\Builtin\` (regexes,
validator and sanitize config), wired together by `BuiltinDetectors`.

Checksum validation is tri-state: a passing checksum forces confidence to 1.0,
a failing one discards the match, otherwise the pattern's base score applies.

### Context boosting

Weak patterns (a bare 8-digit phone number, an ambiguous date) carry a low base
score on purpose. They only cross the reporting threshold (default 0.5) when a
context word appears within ~40 characters before the match — `+0.35` to the
score, shown in the match's `explanation` (e.g. `context "phone" (+0.35)`).
This is the library's primary false-positive control: without it, weak patterns
either flood with noise at a high base score or stay undetectable.

The library ships a single English (`en`) context-word pack. To recognise terms
in other languages, or words specific to your domain, extend the default pack
with your own words — no fork required. `ContextWords` is immutable; `withWords()`
merges onto an entity type's existing list (de-duplicated) and returns a copy:

```php
use Risecommerce\PiiRedactor\Context\ContextWords;
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\EntityType;

// English context words only (default)
$redactor = new PiiRedactor();

// Extend an existing entity type (e.g. Latvian phone words) and add a custom one
$words = ContextWords::default()
    ->withWords(EntityType::PHONE, ['tālrunis', 'telefons'])   // built-in type
    ->withWords('EMPLOYEE_ID', ['employee', 'staff']);          // custom type (string)

$redactor = new PiiRedactor(DetectorRegistry::withDefaults($words));
```

Pass `ContextWords::none()` for a pack with no context words at all. For full
control over a single detector's words, `Builtin\PhoneDetector::create($words)`
still accepts a plain `string[]`.

**Opting out of boosting.** To score matches purely on their patterns, inject
the no-op enhancer (or build detectors without context words):

```php
use Risecommerce\PiiRedactor\Analyzer;
use Risecommerce\PiiRedactor\NullContextEnhancer;
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;

$analyzer = new Analyzer(DetectorRegistry::withDefaults(), new NullContextEnhancer());
```

With boosting disabled, weak detectors (bare phone/date forms) stay below the
default threshold and are not reported; strong detectors (email, validated card
or IBAN) are unaffected.

## Per-entity strategies

```php
use Risecommerce\PiiRedactor\RedactionConfig;
use Risecommerce\PiiRedactor\Strategy\MaskStrategy;
use Risecommerce\PiiRedactor\Strategy\ReplaceStrategy;

$config = new RedactionConfig(
    ['CREDIT_CARD' => new MaskStrategy('*', 4, true)],   // **** -> ************1111
    new ReplaceStrategy('<REDACTED:{type}>'),            // default for everything else
);

$redactor = new PiiRedactor(config: $config);
```

## Options

```php
use Risecommerce\PiiRedactor\AnalyzerOptions;

$options = new AnalyzerOptions(
    entityTypes: ['EMAIL', 'CREDIT_CARD'],   // restrict types
    minScore: 0.6,                           // raise threshold
    allowList: ['support@risecommerce.com'],      // never redact these literals
);
```

## Custom secret rules

The API_KEY detector compiles a declarative `SecretRules` pack. Add in-house
token formats (prefix + charset + tail length) without forking:

```php
use Risecommerce\PiiRedactor\Detector\BuiltinDetectors;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRule;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRules;

$rules = SecretRules::default()
    ->with(new SecretRule('acme-internal', 'acme_', 'a-f0-9', 32, 32));

$detector = BuiltinDetectors::apiKey(null, $rules);
```

For shapes a prefix can't express, `SecretRule::fromRegex()` accepts a full
pattern plus a guaranteed literal needle.

## Custom detectors

```php
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;

$registry = DetectorRegistry::withDefaults();
$registry->register(new PatternDetector('employee-id', 'EMPLOYEE_ID', [
    new Pattern('emp', '/\bEMP-\d{6}\b/u', 0.9, ['EMP-']),
]));

$redactor = new PiiRedactor($registry);
```

The optional fourth `Pattern` argument is a list of `requiredNeedles`: cheap
literal substrings the engine checks with `str_contains` before running the
regex. If none are present the regex is skipped entirely. Every needle must be
guaranteed to appear in any real match (here, `EMP-` is part of the pattern), or
matches will be lost. The built-in EMAIL/URL/CRYPTO/PHONE detectors use this to
skip their regexes on the (common) lines that contain no `@`, `://`, `0x`, etc.

For ML-grade name/location detection, extend `Detector\RemoteDetector` and
call your NER provider (Google DLP, AWS Comprehend, a Presidio sidecar);
it handles chunking and fail-open/fail-closed behavior.

## Performance & logging

Logging is often on a blocking hot path, so per-call latency matters. **Construct
`PiiRedactor` once and reuse it** — do not build a new instance per log record.
Construction wires up 14 detectors and their context-word packs, which costs about
as much as a full short-line redaction; reusing the instance roughly halves
per-call latency.

Measured on PHP 8.4 (Xdebug off), reusing a single instance:

| Scenario                              | µs/op |
|---------------------------------------|------:|
| Clean short log line (no PII)         |  ~5.3 |
| Short line with one email             |  ~5.9 |
| `new PiiRedactor()` per call + redact | ~11.0 |

So a naive `(new PiiRedactor())->redact($line)` inside a Monolog processor is
~2x slower than holding one instance on the processor. For a Monolog processor,
build the redactor in the constructor and call `redact()` in `__invoke()`.

A throwaway benchmark harness lives at `tools/benchmark.php`
(`php -dxdebug.mode=off tools/benchmark.php`).

## Guarantees & limits

- Byte offsets, UTF-8 safe; all bundled regexes use the `u` modifier.
- A failing detector never breaks redaction (reported in `failures()`,
  strict mode available).
- No regex-based detector can promise 100% recall — treat this as defense
  in depth, not a compliance guarantee.
