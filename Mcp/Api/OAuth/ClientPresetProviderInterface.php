<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api\OAuth;

use Risecommerce\Mcp\Api\Data\OAuth\ClientPresetInterface;

/**
 * Supplies the OAuth client edit form with its "Preset" dropdown contents.
 * Implementations are DI-array backed so satellite modules can append
 * additional presets without touching core.
 */
interface ClientPresetProviderInterface
{
    /**
     * @return array<int, ClientPresetInterface>
     */
    public function getAll(): array;
}
