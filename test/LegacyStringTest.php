<?php

declare(strict_types=1);

/**
 * Copyright 2008-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Stream\Wrapper\Test;

use Horde_Stream_Wrapper_String;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Stream_Wrapper_String::class)]
#[RunClassInSeparateProcess]
class LegacyStringTest extends TestCase
{
    public function testGetStreamReturnsResource(): void
    {
        $string = 'test';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertIsResource($stream);

        fclose($stream);
    }

    public function testReadFullContent(): void
    {
        $string = 'ABCDE12345fghij';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertSame('ABCDE12345fghij', fread($stream, 1024));

        fclose($stream);
    }

    public function testEofAfterFullRead(): void
    {
        $string = 'test';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        fread($stream, 1024);

        $this->assertTrue(feof($stream));

        fclose($stream);
    }

    public function testSeekSet(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertSame(0, fseek($stream, 5, SEEK_SET));
        $this->assertSame(5, ftell($stream));
        $this->assertSame('FGHIJ', fread($stream, 5));

        fclose($stream);
    }

    public function testSeekCur(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        fread($stream, 3);
        $this->assertSame(0, fseek($stream, 2, SEEK_CUR));
        $this->assertSame(5, ftell($stream));

        fclose($stream);
    }

    public function testSeekEnd(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertSame(0, fseek($stream, 0, SEEK_END));
        $this->assertSame(10, ftell($stream));

        fclose($stream);
    }

    public function testSeekBeyondEndFails(): void
    {
        $string = 'ABCDE';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertSame(-1, fseek($stream, 100, SEEK_SET));

        fclose($stream);
    }

    public function testWrite(): void
    {
        $string = 'AAAAAAAAAA';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        fseek($stream, 3);
        $written = fwrite($stream, 'BBB');

        $this->assertSame(3, $written);
        fseek($stream, 0);
        $this->assertSame('AAABBBAAA', substr(fread($stream, 1024), 0, 9));

        fclose($stream);
    }

    public function testStatReturnsSize(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $stat = fstat($stream);

        $this->assertSame(10, $stat['size']);

        fclose($stream);
    }

    public function testReferenceSemantics(): void
    {
        $string = 'original';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        fwrite($stream, 'REPLACED');

        $this->assertSame('REPLACED', $string);

        fclose($stream);
    }

    public function testMemoryUsageIsBounded(): void
    {
        $bytes = 1024 * 1024;
        $string = str_repeat('*', $bytes);
        $memoryUsage = memory_get_usage();

        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertLessThan($memoryUsage + $bytes, memory_get_usage());

        while (!feof($stream)) {
            fread($stream, 1024);
        }

        $this->assertLessThan($memoryUsage + $bytes, memory_get_usage());

        fclose($stream);
    }

    public function testNotEofAtEnd(): void
    {
        $string = 'test';
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        fseek($stream, 0, SEEK_END);

        $this->assertFalse(feof($stream));

        fclose($stream);
    }

    public function testBinaryData(): void
    {
        $string = "\x00\x01\x02\xFF\xFE\xFD";
        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertSame($string, fread($stream, 1024));

        fclose($stream);
    }
}
