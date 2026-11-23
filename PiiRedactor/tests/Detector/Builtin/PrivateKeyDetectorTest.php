<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Builtin\PrivateKeyDetector;
use PHPUnit\Framework\TestCase;

final class PrivateKeyDetectorTest extends TestCase
{
    /**
     * @param string $label e.g. 'PRIVATE KEY', 'RSA PRIVATE KEY', 'PGP PRIVATE KEY BLOCK'
     * @return string A synthetic PEM block (fake base64 body).
     */
    private function pem(string $label): string
    {
        $fence = str_repeat('-', 5);

        return $fence . 'BEGIN ' . $label . $fence . "\n"
            . str_repeat("QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo=\n", 3)
            . $fence . 'END ' . $label . $fence;
    }

    /**
     * @return string A synthetic public-key PEM block (must not match).
     */
    private function pemPublic(): string
    {
        $fence = str_repeat('-', 5);

        return $fence . 'BEGIN PUBLIC KEY' . $fence . "\nQUJDREVGRw==\n" . $fence . 'END PUBLIC KEY' . $fence;
    }

    public function testDetectsPemBlocksAtFullConfidence(): void
    {
        $d = PrivateKeyDetector::create();

        foreach (['PRIVATE KEY', 'RSA PRIVATE KEY', 'OPENSSH PRIVATE KEY', 'PGP PRIVATE KEY BLOCK'] as $label) {
            $text = "config:\n" . $this->pem($label) . "\ndone";
            $matches = $d->detect($text);

            self::assertCount(1, $matches, $label);
            self::assertSame(1.0, $matches[0]->score, $label);
            self::assertSame($this->pem($label), substr($text, $matches[0]->start, $matches[0]->length()), $label);
        }
    }

    public function testIgnoresPublicKeyBlocksAndLoneFences(): void
    {
        $d = PrivateKeyDetector::create();
        $fence = str_repeat('-', 5);

        self::assertSame([], $d->detect($this->pemPublic()));
        self::assertSame([], $d->detect($fence . 'BEGIN PRIVATE KEY' . $fence . ' no end fence'));
    }
}
