<?php

declare(strict_types=1);

/**
 * Micro-benchmark harness for PiiRedactor.
 *
 * Focus: per-call latency on short log lines (the dominant logger hot path),
 * plus throughput on larger documents and facade construction cost.
 *
 * Run with Xdebug disabled for meaningful numbers:
 *   php -dxdebug.mode=off tools/benchmark.php
 *
 * Optional env:
 *   BENCH_ITER=50000   iterations per measured scenario
 *   BENCH_WARMUP=5000  warmup iterations
 */

use Risecommerce\PiiRedactor\PiiRedactor;

require __DIR__ . '/../vendor/autoload.php';

if (extension_loaded('xdebug') && (xdebug_info('mode') ?? []) !== []) {
    fwrite(STDERR, "WARNING: Xdebug is active; numbers will be skewed. Re-run with -dxdebug.mode=off\n");
}

$iterations = (int)(getenv('BENCH_ITER') ?: 50000);
$warmup = (int)(getenv('BENCH_WARMUP') ?: 5000);

/**
 * Run a closure N times, return [opsPerSec, medianMicros, meanMicros].
 *
 * @param callable():void $fn
 * @param int $iterations
 * @param int $warmup
 * @return array{0: float, 1: float, 2: float}
 */
function bench(callable $fn, int $iterations, int $warmup): array
{
    for ($i = 0; $i < $warmup; $i++) {
        $fn();
    }

    // Sample per-call timings for a median; batch the rest for a stable mean.
    $sampleCount = min($iterations, 2000);
    $samples = [];
    for ($i = 0; $i < $sampleCount; $i++) {
        $t = hrtime(true);
        $fn();
        $samples[] = (hrtime(true) - $t) / 1000.0; // microseconds
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsedNs = hrtime(true) - $start;

    sort($samples);
    $median = $samples[intdiv(count($samples), 2)];
    $meanMicros = ($elapsedNs / $iterations) / 1000.0;
    $opsPerSec = $iterations / ($elapsedNs / 1_000_000_000);

    return [$opsPerSec, $median, $meanMicros];
}

// ---- Fixtures ---------------------------------------------------------------

$cleanShort = 'GET /api/v1/products?page=3&sort=price HTTP/1.1 200 1453 bytes in 12ms';
$emailShort = 'User login failed for account john.doe@example.com from session abc123';
$phoneCtx = 'Customer callback requested, phone +1 (415) 555-0132 during business hours';

$mixedDoc = implode(' ', [
    'Order placed by jane.smith@example.org from 192.168.10.45.',
    'Contact phone +44 20 7946 0958 or call (212) 555-0199.',
    'Payment card 4111 1111 1111 1111 charged to IBAN GB82 WEST 1234 5698 7654 32.',
    'Device MAC 3D:F2:C9:A6:B3:4F connected from https://shop.example.com/checkout?id=42.',
    'Wallet 0x52908400098527886E0F7030069857D2E4169EE7 funded.',
    'Customer born 12/03/1985 per records on file. Reference visit on 2019-07-21.',
]);

$lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod '
    . 'tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam ';
$largeClean = str_repeat($lorem, (int)ceil(100000 / strlen($lorem)));
$largeClean = substr($largeClean, 0, 100000);

$piiSnippets = [
    'mail user{i}@example.com',
    'ip 10.0.{i}.5',
    'card 4111 1111 1111 1111',
    'phone +1 (415) 555-{i}32',
    'wallet 0x52908400098527886E0F7030069857D2E4169EE7',
];
$chunks = [];
for ($i = 0; $i < 50; $i++) {
    $chunks[] = $lorem;
    $snippet = $piiSnippets[$i % count($piiSnippets)];
    $chunks[] = str_replace('{i}', (string)($i % 9 + 1), $snippet) . '. ';
}
$largeWithPii = implode('', $chunks);

// ---- Scenarios --------------------------------------------------------------

$redactor = new PiiRedactor();

$scenarios = [
    'a. clean short log line (no PII)' => [$iterations, static fn () => $redactor->redact($cleanShort)],
    'b. short line + 1 email' => [$iterations, static fn () => $redactor->redact($emailShort)],
    'c. short line + phone w/ context' => [$iterations, static fn () => $redactor->redact($phoneCtx)],
    'd. mixed-PII document (~1 KB)' => [max(2000, intdiv($iterations, 10)), static fn () => $redactor->redact($mixedDoc)],
    'e. large clean text (100 KB)' => [max(200, intdiv($iterations, 200)), static fn () => $redactor->redact($largeClean)],
    'f. large text + 50 PII items' => [max(200, intdiv($iterations, 200)), static fn () => $redactor->redact($largeWithPii)],
    'g1. new PiiRedactor() per call + redact' => [max(2000, intdiv($iterations, 5)), static function () use ($cleanShort) {
        (new PiiRedactor())->redact($cleanShort);
    }],
    'g2. reused instance + redact (same as a)' => [$iterations, static fn () => $redactor->redact($cleanShort)],
    'g3. new PiiRedactor() construction only' => [max(2000, intdiv($iterations, 5)), static function () {
        new PiiRedactor();
    }],
];

printf("%-44s %14s %14s %14s\n", 'scenario', 'ops/sec', 'median µs', 'mean µs');
printf("%s\n", str_repeat('-', 90));
foreach ($scenarios as $label => [$iter, $fn]) {
    [$ops, $median, $mean] = bench($fn, $iter, min($warmup, $iter));
    printf("%-44s %14s %14.3f %14.3f\n", $label, number_format($ops, 0), $median, $mean);
}
