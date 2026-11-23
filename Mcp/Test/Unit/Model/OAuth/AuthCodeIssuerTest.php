<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Model\OAuth;

use Risecommerce\Mcp\Model\Auth\TokenGenerator;
use Risecommerce\Mcp\Model\Auth\TokenHasher;
use Risecommerce\Mcp\Model\Config\ModuleConfig;
use Risecommerce\Mcp\Model\OAuth\AuthCode;
use Risecommerce\Mcp\Model\OAuth\AuthCodeFactory;
use Risecommerce\Mcp\Model\OAuth\AuthCodeIssuer;
use Risecommerce\Mcp\Model\OAuth\AuthCodeRepository;
use PHPUnit\Framework\TestCase;

class AuthCodeIssuerTest extends TestCase
{
    public function testIssuePersistsHashedCodeAndReturnsPlaintext(): void
    {
        $authCode = $this->createMock(AuthCode::class);
        $authCode->expects(self::once())->method('setCodeHash')->with('hashed');
        $authCode->expects(self::once())->method('setOAuthClientId')->with(7);
        $authCode->expects(self::once())->method('setAdminUserId')->with(42);
        $authCode->expects(self::once())->method('setRedirectUri')->with('https://x/cb');
        $authCode->expects(self::once())->method('setCodeChallenge')->with('challenge-xyz');
        $authCode->expects(self::once())->method('setCodeChallengeMethod')->with('S256');
        $authCode->expects(self::once())->method('setScope')->with('mcp');
        $authCode->expects(self::once())->method('setExpiresAt')->with(self::callback(
            static fn ($v) => is_string($v) && abs(strtotime($v . ' UTC') - (time() + 60)) < 5
        ));

        $factory = $this->createMock(AuthCodeFactory::class);
        $factory->method('create')->willReturn($authCode);

        $repo = $this->createMock(AuthCodeRepository::class);
        $repo->expects(self::once())->method('save')->with($authCode);

        $generator = $this->createMock(TokenGenerator::class);
        $generator->method('generate')->willReturn('plaintext-code');

        $hasher = $this->createMock(TokenHasher::class);
        $hasher->method('hash')->with('plaintext-code')->willReturn('hashed');

        $config = $this->createMock(ModuleConfig::class);
        $config->method('getOAuthAuthCodeLifetime')->willReturn(60);

        $issuer = new AuthCodeIssuer($factory, $repo, $generator, $hasher, $config);
        $code = $issuer->issue(7, 42, 'https://x/cb', 'challenge-xyz', 'S256', 'mcp');

        self::assertSame('plaintext-code', $code);
    }

    public function testIssueAcceptsNullScope(): void
    {
        $authCode = $this->createMock(AuthCode::class);
        $authCode->expects(self::once())->method('setScope')->with(null);

        $factory = $this->createMock(AuthCodeFactory::class);
        $factory->method('create')->willReturn($authCode);
        $repo = $this->createMock(AuthCodeRepository::class);
        $generator = $this->createMock(TokenGenerator::class);
        $generator->method('generate')->willReturn('p');
        $hasher = $this->createMock(TokenHasher::class);
        $hasher->method('hash')->willReturn('h');
        $config = $this->createMock(ModuleConfig::class);
        $config->method('getOAuthAuthCodeLifetime')->willReturn(60);

        $issuer = new AuthCodeIssuer($factory, $repo, $generator, $hasher, $config);
        $issuer->issue(1, 1, 'https://x/cb', 'c', 'S256', null);
    }

    public function testIssueRespectsConfiguredLifetime(): void
    {
        $authCode = $this->createMock(AuthCode::class);
        $authCode->expects(self::once())->method('setExpiresAt')->with(self::callback(
            // 300s lifetime → expires_at within 5s of (now + 300)
            static fn ($v) => is_string($v) && abs(strtotime($v . ' UTC') - (time() + 300)) < 5
        ));

        $factory = $this->createMock(AuthCodeFactory::class);
        $factory->method('create')->willReturn($authCode);
        $repo = $this->createMock(AuthCodeRepository::class);
        $generator = $this->createMock(TokenGenerator::class);
        $generator->method('generate')->willReturn('p');
        $hasher = $this->createMock(TokenHasher::class);
        $hasher->method('hash')->willReturn('h');
        $config = $this->createMock(ModuleConfig::class);
        $config->method('getOAuthAuthCodeLifetime')->willReturn(300);

        $issuer = new AuthCodeIssuer($factory, $repo, $generator, $hasher, $config);
        $issuer->issue(1, 1, 'https://x/cb', 'c', 'S256', 'mcp');
    }
}
