<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

use Risecommerce\PiiRedactor\Context\ContextWords;

final class DetectorRegistry
{
    /** @var array<string, DetectorInterface> */
    private array $detectors = [];

    /**
     * @param DetectorInterface $detector
     * @return void
     */
    public function register(DetectorInterface $detector): void
    {
        $this->detectors[$detector->getName()] = $detector;
    }

    /**
     * @param string $name
     * @return void
     */
    public function remove(string $name): void
    {
        unset($this->detectors[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->detectors[$name]);
    }

    /**
     * @return DetectorInterface[]
     */
    public function all(): array
    {
        return array_values($this->detectors);
    }

    /**
     * @param ContextWords|null $words Context words for the built-ins; defaults to the shipped 'en' pack.
     * @return self
     */
    public static function withDefaults(?ContextWords $words = null): self
    {
        $registry = new self();
        foreach (BuiltinDetectors::all($words) as $detector) {
            $registry->register($detector);
        }

        return $registry;
    }
}
