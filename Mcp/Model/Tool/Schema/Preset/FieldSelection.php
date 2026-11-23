<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Tool\Schema\Preset;

use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ObjectBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\SchemaContribution;

/**
 * Produces `fields` / `exclude` — whitelist/blacklist for the field-resolver
 * pipeline's per-entity resolver keys.
 */
class FieldSelection implements SchemaContribution
{
    /** @var string */
    private readonly string $fieldsDescription;

    /** @var string */
    private readonly string $excludeDescription;

    /**
     * @param string $fieldsDescription
     * @param string $excludeDescription
     * @return void
     */
    private function __construct(
        string $fieldsDescription,
        string $excludeDescription
    ) {
        $this->fieldsDescription = $fieldsDescription;
        $this->excludeDescription = $excludeDescription;
    }

    /**
     * @return self
     */
    public static function default(): self
    {
        return new self(
            'Whitelist of resolver keys per row.',
            'Resolver keys to drop from each row.'
        );
    }

    /**
     * @param string $fields
     * @param string $exclude
     * @return self
     */
    public static function describing(string $fields, string $exclude): self
    {
        return new self($fields, $exclude);
    }

    /**
     * @inheritDoc
     */
    public function applyTo(ObjectBuilder $object): void
    {
        $object
            ->array('fields', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description($this->fieldsDescription))
            ->array('exclude', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description($this->excludeDescription));
    }
}
