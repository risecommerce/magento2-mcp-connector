<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Cron;

use Risecommerce\Mcp\Api\LoggerInterface;
use Risecommerce\Mcp\Model\Config\ModuleConfig;
use Magento\Framework\App\ResourceConnection;
use Throwable;

/**
 * Deletes audit log rows older than `risecommerce_mcp/audit/retention_days`.
 *
 * Raw DELETE (not a collection sweep) to keep memory flat under large logs.
 * Retention of 0 means "never purge".
 */
class PurgeAuditLog
{
    private const TABLE = 'risecommerce_mcp_audit_log';

    /**
     * @param ResourceConnection $resourceConnection
     * @param ModuleConfig $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ModuleConfig $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        try {
            $days = $this->config->getAuditRetentionDays();
        } catch (Throwable $e) {
            $this->logger->warning('MCP audit retention config unreadable, skipping purge.', ['exception' => $e]);
            return;
        }

        if ($days <= 0) {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * 86400));
            $deleted = $connection->delete(
                $this->resourceConnection->getTableName(self::TABLE),
                ['created_at < ?' => $cutoff]
            );
            if ($deleted > 0) {
                $this->logger->info(sprintf(
                    'MCP audit purge removed %d rows older than %s.',
                    $deleted,
                    $cutoff
                ));
            }
        } catch (Throwable $e) {
            $this->logger->error('MCP audit purge failed.', ['exception' => $e]);
        }
    }
}
