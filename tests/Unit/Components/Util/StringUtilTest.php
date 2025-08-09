<?php

declare(strict_types=1);

namespace Tests\Unit\Components\Util;

use PHPUnit\Framework\TestCase;
use Xentral\Components\Util\StringUtil;
use Xentral\Components\Util\Exception\InvalidArgumentException;

class StringUtilTest extends TestCase
{
    public function testFormatBytesWithZero(): void
    {
        $this->assertSame('0Bytes', StringUtil::formatBytes(0));
    }

    public function testFormatBytesWithOneKilobyte(): void
    {
        $this->assertSame('1,0KB', StringUtil::formatBytes(1024));
    }

    public function testFormatBytesWithVeryLargeNumber(): void
    {
        $bytes = pow(1024, 5);
        $this->assertSame('1.024,0TB', StringUtil::formatBytes($bytes));
    }

    public function testFormatBytesThrowsExceptionOnInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StringUtil::formatBytes('invalid');
    }
}
