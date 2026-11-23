<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Model\OAuth;

use Risecommerce\Mcp\Exception\OAuthException;
use Risecommerce\Mcp\Model\OAuth\Scope;
use Risecommerce\Mcp\Model\OAuth\ScopeValidator;
use PHPUnit\Framework\TestCase;

class ScopeValidatorTest extends TestCase
{
    private ScopeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ScopeValidator();
    }

    public function testParseDefaultsToReadWhenNullOrEmpty(): void
    {
        self::assertSame([Scope::READ], $this->validator->parse(null));
        self::assertSame([Scope::READ], $this->validator->parse(''));
        self::assertSame([Scope::READ], $this->validator->parse('   '));
    }

    public function testParseAcceptsKnownScopes(): void
    {
        self::assertSame([Scope::READ], $this->validator->parse('mcp:read'));
        self::assertSame([Scope::READ, Scope::WRITE], $this->validator->parse('mcp:read mcp:write'));
        self::assertSame([Scope::WRITE, Scope::READ], $this->validator->parse('mcp:write mcp:read'));
    }

    public function testParseDeduplicates(): void
    {
        self::assertSame([Scope::READ], $this->validator->parse('mcp:read mcp:read'));
        self::assertSame(
            [Scope::READ, Scope::WRITE],
            $this->validator->parse('mcp:read mcp:write mcp:read')
        );
    }

    public function testParseRejectsUnknownScope(): void
    {
        try {
            $this->validator->parse('mcp:read mcp:bogus');
            self::fail('Expected OAuthException');
        } catch (OAuthException $e) {
            self::assertSame('invalid_scope', $e->oauthError);
        }
    }

    public function testCanonicalize(): void
    {
        self::assertSame('mcp:read', $this->validator->canonicalize([Scope::READ]));
        self::assertSame(
            'mcp:read mcp:write',
            $this->validator->canonicalize([Scope::READ, Scope::WRITE])
        );
    }
}
