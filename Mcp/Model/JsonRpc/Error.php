<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\JsonRpc;

/**
 * JSON-RPC 2.0 error object.
 */
class Error
{
    /**
     * @param ErrorCode $code
     * @param string $message
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        public readonly ErrorCode $code,
        public readonly string $message,
        public readonly ?array $data = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arr = [
            'code' => $this->code->value,
            'message' => $this->message,
        ];
        if ($this->data !== null) {
            $arr['data'] = $this->data;
        }
        return $arr;
    }
}
