<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Prompt;

use Risecommerce\Mcp\Api\Data\PromptMessage;
use Risecommerce\Mcp\Api\PromptInterface;

class PromptRenderer
{
    /**
     * @param PromptInterface $prompt
     * @param array<string, string> $arguments
     * @return array<int, PromptMessage>
     */
    public function render(PromptInterface $prompt, array $arguments): array
    {
        $declared = $prompt->getArguments();
        $search = [];
        $replace = [];
        foreach ($declared as $argument) {
            $search[] = '{{' . $argument->name . '}}';
            $replace[] = $arguments[$argument->name] ?? '';
        }

        $rendered = [];
        foreach ($prompt->getMessages() as $message) {
            $rendered[] = $search === []
                ? $message
                : new PromptMessage($message->role, str_replace($search, $replace, $message->text));
        }
        return $rendered;
    }
}
