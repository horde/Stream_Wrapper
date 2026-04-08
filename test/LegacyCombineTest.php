<?php

declare(strict_types=1);

/**
 * Copyright 2009-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Stream\Wrapper\Test;

use Horde_Stream_Wrapper_Combine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Stream_Wrapper_Combine::class)]
#[RunClassInSeparateProcess]
class LegacyCombineTest extends TestCase
{
    public function testGetStreamReturnsResource(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['test']);

        $this->assertIsResource($stream);

        fclose($stream);
    }

    public function testReadCombinedStrings(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABC', 'DEF', 'GHI']);

        $this->assertSame('ABCDEFGHI', fread($stream, 1024));

        fclose($stream);
    }

    public function testReadMixedStringAndStream(): void
    {
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, '12345');

        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', $fp, 'fghij']);

        $this->assertSame('ABCDE12345fghij', fread($stream, 1024));

        fclose($stream);
    }

    public function testEofAfterFullRead(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['test']);

        fread($stream, 1024);

        $this->assertTrue(feof($stream));

        fclose($stream);
    }

    public function testSeekSetToStart(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', '12345']);

        fread($stream, 1024);
        fseek($stream, 0);

        $this->assertSame(0, ftell($stream));

        fclose($stream);
    }

    public function testSeekCurForward(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', '12345']);

        $this->assertSame(0, fseek($stream, 5, SEEK_CUR));
        $this->assertSame(5, ftell($stream));

        fclose($stream);
    }

    public function testSeekEnd(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', '12345']);

        $this->assertSame(0, fseek($stream, 0, SEEK_END));
        $this->assertSame(10, ftell($stream));

        fclose($stream);
    }

    public function testWrite(): void
    {
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, '12345');

        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', $fp, 'fghij']);

        fseek($stream, 5, SEEK_CUR);
        $written = fwrite($stream, '0000000000');

        $this->assertSame(10, $written);

        fclose($stream);
    }

    public function testStatReturnsCombinedSize(): void
    {
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, '12345');

        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', $fp, 'fghij']);

        $stat = fstat($stream);

        $this->assertSame(15, $stat['size']);

        fclose($stream);
    }

    public function testReadAcrossBoundaries(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['AAA', 'BBB', 'CCC']);

        $this->assertSame('AAAB', fread($stream, 4));
        $this->assertSame('BBCC', fread($stream, 4));

        fclose($stream);
    }

    public function testBinaryData(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(["\x00\x01\x02", "\xFF\xFE\xFD"]);

        $this->assertSame("\x00\x01\x02\xFF\xFE\xFD", fread($stream, 1024));

        fclose($stream);
    }

    public function testSeekUpdatesPosition(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['ABCDE', '12345']);

        fseek($stream, 3);
        $this->assertSame(3, ftell($stream));

        fclose($stream);
    }

    public function testSingleByteChunkReads(): void
    {
        $stream = Horde_Stream_Wrapper_Combine::getStream(['AB', 'CD']);

        $result = '';
        for ($i = 0; $i < 4; $i++) {
            $result .= fread($stream, 1);
        }

        $this->assertSame('ABCD', $result);

        fclose($stream);
    }
}
