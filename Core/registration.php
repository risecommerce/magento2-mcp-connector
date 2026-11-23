<?php
/**
 * This file is part of the Risecommerce_Core package.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Risecommerce_Core
 * to newer versions in the future.
 *
 * @copyright Copyright (c) 2024 Risecommerce, Ltd. (https://risecommerce.com/)
 * @author    Risecommerce <info@risecommerce.com>
 * @license   MIT
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Risecommerce_Core', __DIR__);
