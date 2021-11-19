<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Stream_Wrapper
 */
declare(strict_types=1);

namespace Horde\Stream\Wrapper;

use stdClass;
use Exception;
use streamWrapper;

/**
 * A stream wrapper that will treat a native PHP string as a stream.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Stream_Wrapper
 */
class StringWrapper
{
    /**/
    public const WRAPPER_NAME = 'horde-stream-wrapper-string';

    /**
     * The current context.
     *
     * @var resource
     */
    public $context;

    /**
     * String position.
     *
     * @var int
     */
    protected int $pos;

    /**
     * The string.
     *
     * @var string
     */
    protected string $string;

    /**
     * Unique ID tracker for the streams.
     *
     * @var int
     */
    private static int $id = 0;

    /**
     * Create a stream from a PHP string.
     *
     * @since 2.1.0
     *
     * @param string &$string  A PHP string variable.
     *
     * @return resource|false  A PHP stream pointing to the variable.
     */
    public static function getStream(&$string)
    {
        if (!self::$id) {
            stream_wrapper_register(self::WRAPPER_NAME, __CLASS__);
        }

        /* Needed to keep reference. */
        $ob = new stdClass();
        $ob->string = &$string;

        return fopen(
            self::WRAPPER_NAME . '://' . ++self::$id,
            'wb',
            false,
            stream_context_create([
                self::WRAPPER_NAME => [
                    'string' => $ob,
                ],
            ])
        );
    }

    /**
     * @see streamWrapper::stream_open()
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $opts = stream_context_get_options($this->context);

        if (isset($opts[self::WRAPPER_NAME]['string'])) {
            $this->string =& $opts[self::WRAPPER_NAME]['string']->string;
        } elseif (isset($opts['horde-string']['string'])) {
            // @deprecated
            $this->string =& $opts['horde-string']['string']->getString();
        } else {
            throw new Exception('Use ' . __CLASS__ . '::getStream() to initialize the stream.');
        }

        if (is_null($this->string)) {
            return false;
        }

        $this->pos = 0;

        return true;
    }

    /**
     * @see streamWrapper::stream_close()
     */
    public function stream_close(): void
    {
        $this->string = '';
        $this->pos = 0;
    }

    /**
     * @see streamWrapper::stream_read()
     */
    public function stream_read(int $count): string
    {
        $curr = $this->pos;
        $this->pos += $count;
        return substr($this->string, $curr, $count);
    }

    /**
     * @see streamWrapper::stream_write()
     * Previous versions returned int|false.
     *
     * streamWrapper reference signature says it always returns int
     * Should return the number of bytes that were successfully stored,
     * or 0 if none could be stored.
     *
     * This may break deriving class when upgrading from H5.
     */

    public function stream_write(string $data): int
    {
        $len = strlen($data);

        $this->string = substr_replace($this->string, $data, $this->pos, $len);
        $this->pos += $len;

        return $len;
    }

    /**
     * @see streamWrapper::stream_tell()
     */
    public function stream_tell(): int
    {
        return $this->pos;
    }

    /**
     * @see streamWrapper::stream_eof()
     */
    public function stream_eof(): bool
    {
        return ($this->pos > strlen($this->string));
    }

    /**
     * @see streamWrapper::stream_stat()
     * @return int[]
     */
    public function stream_stat(): array
    {
        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => strlen($this->string),
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
        ];
    }

    /**
     * @see streamWrapper::stream_seek()
     * @return bool
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $pos = 0;
        switch ($whence) {
        case SEEK_SET:
            $pos = $offset;
            break;

        case SEEK_CUR:
            $pos = $this->pos + $offset;
            break;

        case SEEK_END:
            $pos = strlen($this->string) + $offset;
            break;
        }

        if (($pos < 0) || ($pos > strlen($this->string))) {
            return false;
        }

        $this->pos = $pos;

        return true;
    }
}
