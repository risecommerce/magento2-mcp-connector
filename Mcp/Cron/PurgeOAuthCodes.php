<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Cron;

use Risecommerce\Mcp\Api\LoggerInterface;
use Risecommerce\Mcp\Model\OAuth\AuthCodeRepository;
use Risecommerce\Mcp\Model\OAuth\AuthorizeHandoffStorage;
use Risecommerce\Mcp\Model\OAuth\RefreshTokenRepository;
use Throwable;

class PurgeOAuthCodes
{
    /**
     * @param AuthCodeRepository $authCodeRepository
     * @param RefreshTokenRepository $refreshTokenRepository
     * @param AuthorizeHandoffStorage $handoffStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AuthCodeRepository $authCodeRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly AuthorizeHandoffStorage $handoffStorage,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        try {
            $codes = $this->authCodeRepository->deleteExpired();
            $refreshes = $this->refreshTokenRepository->deleteExpired();
            $handoffs = $this->handoffStorage->purgeExpired();
            $this->logger->info('OAuth purge complete.', [
                'auth_codes_deleted' => $codes,
                'refresh_tokens_deleted' => $refreshes,
                'authorize_handoffs_deleted' => $handoffs,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('OAuth purge failed.', ['exception' => $e]);
        }
    }
}
