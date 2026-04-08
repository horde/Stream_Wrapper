<?php

declare(strict_types=1);

/**
 * Copyright 2008-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Stream\Wrapper\Test;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Horde\Stream\Wrapper\StringStreamWrapper;
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

    public function testDeprecatedStringStreamInterface(): void
    {
        // Ensure wrapper is registered
        $init = 'x';
        $tmp = StringWrapper::getStream($init);
        fclose($tmp);

        $ob = new class implements StringStreamWrapper {
            public string $str = 'deprecated-path-data';

            public function &getString(): string
            {
                return $this->str;
            }
        };

        $ctx = stream_context_create([
            'horde-string' => [
                'string' => $ob,
            ],
        ]);

        $stream = fopen(StringWrapper::WRAPPER_NAME . '://deprecated', 'rb', false, $ctx);

        $this->assertIsResource($stream);
        $this->assertSame('deprecated-path-data', fread($stream, 1024));

        fclose($stream);
    }

    public function testStreamOpenThrowsExceptionWithoutContext(): void
    {
        // Ensure wrapper is registered
        $init = 'x';
        $tmp = StringWrapper::getStream($init);
        fclose($tmp);

        $ctx = stream_context_create([
            'unrelated-key' => ['foo' => 'bar'],
        ]);

        $this->expectException(Exception::class);

        fopen(StringWrapper::WRAPPER_NAME . '://no-context', 'rb', false, $ctx);
    }

    public function testStatReturnsCompleteStructure(): void
    {
        $string = 'ABCDE';
        $stream = StringWrapper::getStream($string);

        $stat = fstat($stream);

        $this->assertArrayHasKey('dev', $stat);
        $this->assertArrayHasKey('ino', $stat);
        $this->assertArrayHasKey('mode', $stat);
        $this->assertArrayHasKey('nlink', $stat);
        $this->assertArrayHasKey('uid', $stat);
        $this->assertArrayHasKey('gid', $stat);
        $this->assertArrayHasKey('rdev', $stat);
        $this->assertArrayHasKey('size', $stat);
        $this->assertArrayHasKey('atime', $stat);
        $this->assertArrayHasKey('mtime', $stat);
        $this->assertArrayHasKey('ctime', $stat);
        $this->assertArrayHasKey('blksize', $stat);
        $this->assertArrayHasKey('blocks', $stat);
        $this->assertSame(5, $stat['size']);
        $this->assertSame(0, $stat['dev']);

        fclose($stream);
    }

    public function testStatSizeUpdatesAfterWrite(): void
    {
        $string = 'ABC';
        $stream = StringWrapper::getStream($string);

        $this->assertSame(3, fstat($stream)['size']);

        fseek($stream, 0, SEEK_END);
        fwrite($stream, 'DEF');

        $this->assertSame(6, fstat($stream)['size']);

        fclose($stream);
    }

    public function testCloseResetsState(): void
    {
        $string = 'test data';
        $stream = StringWrapper::getStream($string);

        fread($stream, 4);
        $this->assertSame(4, ftell($stream));

        fclose($stream);

        // After close, the original variable is cleared via stream_close
        // (stream_close sets string to '' and pos to 0)
        // Verify that string was cleared by the reference semantics
        $this->assertSame('', $string);
    }

    public function testReadBeyondLength(): void
    {
        $string = 'short';
        $stream = StringWrapper::getStream($string);

        // Read much more than available
        $result = fread($stream, 100000);

        $this->assertSame('short', $result);
        $this->assertTrue(feof($stream));

        fclose($stream);
    }

    public function testWriteOverwriteMiddle(): void
    {
        $string = '0123456789';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 3);
        fwrite($stream, 'XX');

        $this->assertSame('012XX56789', $string);

        fclose($stream);
    }

    public function testSeekCurFromEndPosition(): void
    {
        $string = 'ABCDE';
        $stream = StringWrapper::getStream($string);

        fseek($stream, 0, SEEK_END);
        // Seek back 2 from end position
        $this->assertSame(0, fseek($stream, -2, SEEK_CUR));
        $this->assertSame(3, ftell($stream));
        $this->assertSame('DE', fread($stream, 2));

        fclose($stream);
    }

    public function testSeekEndPositiveOffset(): void
    {
        $string = 'ABCDE';
        $stream = StringWrapper::getStream($string);

        // SEEK_END with positive offset goes beyond string length
        $this->assertSame(-1, fseek($stream, 1, SEEK_END));

        fclose($stream);
    }

    public function testSingleCharString(): void
    {
        $string = 'X';
        $stream = StringWrapper::getStream($string);

        $this->assertSame('X', fread($stream, 1));
        $this->assertSame(1, ftell($stream));
        $this->assertTrue(feof($stream));

        fclose($stream);
    }
}
