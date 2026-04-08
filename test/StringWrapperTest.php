<?php

declare(strict_types=1);

/**
 * Copyright 2008-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Stream\Wrapper\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Horde\Stream\Wrapper\StringWrapper;

#[CoversClass(StringWrapper::class)]
class StringWrapperTest extends TestCase
{
    public function testGetStreamReturnsResource(): void
    {
        $string = 'test';
        $stream = StringWrapper::getStream($string);

        $this->assertIsResource($stream);

        fclose($stream);
    }

    public function testReadFullContent(): void
    {
        $string = 'Hello, World!';
        $stream = StringWrapper::getStream($string);

        $result = fread($stream, 1024);

        $this->assertSame('Hello, World!', $result);

        fclose($stream);
    }

    public function testReadInChunks(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        $this->assertSame('ABC', fread($stream, 3));
        $this->assertSame('DEF', fread($stream, 3));
        $this->assertSame('GHIJ', fread($stream, 4));

        fclose($stream);
    }

    public function testReadEmptyString(): void
    {
        $string = '';
        $stream = StringWrapper::getStream($string);

        $result = fread($stream, 1024);

        $this->assertSame('', $result);

        fclose($stream);
    }

    public function testEofAfterFullRead(): void
    {
        $string = 'test';
        $stream = StringWrapper::getStream($string);

        fread($stream, 1024);

        $this->assertTrue(feof($stream));

        fclose($stream);
    }

    public function testNotEofAtStart(): void
    {
        $string = 'test';
        $stream = StringWrapper::getStream($string);

        $this->assertFalse(feof($stream));

        fclose($stream);
    }

    public function testNotEofAtEnd(): void
    {
        $string = 'test';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 0, SEEK_END);

        $this->assertFalse(feof($stream));

        fclose($stream);
    }

    public function testTellAtStart(): void
    {
        $string = 'test';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(0, ftell($stream));

        fclose($stream);
    }

    public function testTellAfterRead(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        fread($stream, 5);

        $this->assertSame(5, ftell($stream));

        fclose($stream);
    }

    public function testSeekSet(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(0, fseek($stream, 5, SEEK_SET));
        $this->assertSame(5, ftell($stream));
        $this->assertSame('FGHIJ', fread($stream, 5));

        fclose($stream);
    }

    public function testSeekCur(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        fread($stream, 3);
        $this->assertSame(0, fseek($stream, 2, SEEK_CUR));
        $this->assertSame(5, ftell($stream));
        $this->assertSame('FGHIJ', fread($stream, 5));

        fclose($stream);
    }

    public function testSeekEnd(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(0, fseek($stream, -5, SEEK_END));
        $this->assertSame(5, ftell($stream));
        $this->assertSame('FGHIJ', fread($stream, 5));

        fclose($stream);
    }

    public function testSeekToStart(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        fread($stream, 1024);
        $this->assertSame(0, fseek($stream, 0));
        $this->assertSame(0, ftell($stream));
        $this->assertSame('ABCDEFGHIJ', fread($stream, 1024));

        fclose($stream);
    }

    public function testSeekToEnd(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(0, fseek($stream, 0, SEEK_END));
        $this->assertSame(10, ftell($stream));

        fclose($stream);
    }

    public function testSeekBeyondEndFails(): void
    {
        $string = 'ABCDE';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(-1, fseek($stream, 100, SEEK_SET));

        fclose($stream);
    }

    public function testSeekBeforeStartFails(): void
    {
        $string = 'ABCDE';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(-1, fseek($stream, -1, SEEK_SET));

        fclose($stream);
    }

    public function testWriteAtPosition(): void
    {
        $string = 'AAAAAAAAAA';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 3);
        $written = fwrite($stream, 'BBB');

        $this->assertSame(3, $written);
        $this->assertSame('AAABBBAAA', substr($string, 0, 9));

        fclose($stream);
    }

    public function testWriteAtStart(): void
    {
        $string = 'Hello';
        $stream = StringWrapper::getStream($string);

        fwrite($stream, 'XX');
        fseek($stream, 0);

        $this->assertSame('XXllo', fread($stream, 1024));

        fclose($stream);
    }

    public function testWriteExtendingString(): void
    {
        $string = 'ABC';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 0, SEEK_END);
        fwrite($stream, 'DEF');

        $this->assertSame('ABCDEF', $string);

        fclose($stream);
    }

    public function testWriteReturnsLength(): void
    {
        $string = 'test';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(7, fwrite($stream, '1234567'));

        fclose($stream);
    }

    public function testStatReturnsSize(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        $stat = fstat($stream);

        $this->assertSame(10, $stat['size']);

        fclose($stream);
    }

    public function testStatEmptyString(): void
    {
        $string = '';
        $stream = StringWrapper::getStream($string);

        $stat = fstat($stream);

        $this->assertSame(0, $stat['size']);

        fclose($stream);
    }

    public function testReferenceSemantics(): void
    {
        $string = 'original';
        $stream = StringWrapper::getStream($string);

        fwrite($stream, 'REPLACED');

        $this->assertSame('REPLACED', $string);

        fclose($stream);
    }

    public function testMemoryUsageIsBounded(): void
    {
        $bytes = 1024 * 1024;
        $string = str_repeat('*', $bytes);
        $memoryBefore = memory_get_usage();

        $stream = StringWrapper::getStream($string);
        $memoryAfterOpen = memory_get_usage();

        $this->assertLessThan($memoryBefore + $bytes, $memoryAfterOpen);

        while (!feof($stream)) {
            fread($stream, 1024);
        }
        $memoryAfterRead = memory_get_usage();

        $this->assertLessThan($memoryBefore + $bytes, $memoryAfterRead);

        fclose($stream);
    }

    public function testMultipleStreamsFromDifferentStrings(): void
    {
        $string1 = 'FIRST';
        $string2 = 'SECOND';

        $stream1 = StringWrapper::getStream($string1);
        $stream2 = StringWrapper::getStream($string2);

        $this->assertSame('FIRST', fread($stream1, 1024));
        $this->assertSame('SECOND', fread($stream2, 1024));

        fclose($stream1);
        fclose($stream2);
    }

    public function testReadAfterSeekToMiddle(): void
    {
        $string = '0123456789';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 5);
        $this->assertSame('56789', fread($stream, 1024));

        fclose($stream);
    }

    public function testSequentialSeekAndRead(): void
    {
        $string = 'ABCDEFGHIJKLMNOP';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 4);
        $this->assertSame('EFGH', fread($stream, 4));

        fseek($stream, 12);
        $this->assertSame('MNOP', fread($stream, 4));

        fseek($stream, 0);
        $this->assertSame('ABCD', fread($stream, 4));

        fclose($stream);
    }

    public function testBinaryData(): void
    {
        $string = "\x00\x01\x02\xFF\xFE\xFD";
        $stream = StringWrapper::getStream($string);

        $result = fread($stream, 1024);

        $this->assertSame($string, $result);

        fclose($stream);
    }

    public function testSeekCurNegative(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 8);
        $this->assertSame(0, fseek($stream, -3, SEEK_CUR));
        $this->assertSame(5, ftell($stream));

        fclose($stream);
    }

    public function testSeekEndNegativeOffset(): void
    {
        $string = 'ABCDEFGHIJ';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(0, fseek($stream, -3, SEEK_END));
        $this->assertSame(7, ftell($stream));
        $this->assertSame('HIJ', fread($stream, 3));

        fclose($stream);
    }
}
