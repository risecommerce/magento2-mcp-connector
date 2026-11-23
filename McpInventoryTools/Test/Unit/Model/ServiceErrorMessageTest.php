<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Test\Unit\Model;

use Risecommerce\McpInventoryTools\Model\ServiceErrorMessage;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ServiceErrorMessageTest extends TestCase
{
    public function testPlainExceptionKeepsItsMessage(): void
    {
        $this->assertSame('Boom', $this->formatter()->from(new RuntimeException('Boom')));
    }

    public function testLocalizedExceptionKeepsItsMessage(): void
    {
        $this->assertSame('Nope', $this->formatter()->from(new LocalizedException(__('Nope'))));
    }

    /**
     * MSI validators throw ValidationException whose own message is the constant
     * "Validation Failed"; the reasons only live in getErrors().
     */
    public function testValidationExceptionAppendsItsReasons(): void
    {
        $message = $this->formatter()->from($this->validationException([
            'Can not save link related to Default Source or Default Stock',
        ]));

        $this->assertStringContainsString('Validation Failed', $message);
        $this->assertStringContainsString('Default Source or Default Stock', $message);
    }

    public function testMultipleReasonsAreJoined(): void
    {
        $message = $this->formatter()->from($this->validationException(['First reason', 'Second reason']));

        $this->assertStringContainsString('First reason; Second reason', $message);
    }

    public function testDuplicateReasonsCollapse(): void
    {
        $message = $this->formatter()->from($this->validationException(['Same', 'Same']));

        $this->assertSame(1, substr_count($message, 'Same'));
    }

    public function testValidationExceptionWithNoReasonsIsUnchanged(): void
    {
        $this->assertSame('Validation Failed', $this->formatter()->from($this->validationException([])));
    }

    /**
     * @param string[] $reasons
     * @return ValidationException
     */
    private function validationException(array $reasons): ValidationException
    {
        $phrases = [];
        foreach ($reasons as $reason) {
            $phrases[] = __($reason);
        }

        $result = $this->createMock(ValidationResult::class);
        $result->method('getErrors')->willReturn($phrases);

        return new ValidationException(__('Validation Failed'), null, 0, $result);
    }

    /**
     * @return ServiceErrorMessage
     */
    private function formatter(): ServiceErrorMessage
    {
        return new ServiceErrorMessage();
    }
}
