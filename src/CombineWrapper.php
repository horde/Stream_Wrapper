<?php
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Stream_Wrapper
 */
declare(strict_types=1);

namespace Horde\Stream\Wrapper;

use Exception;
use streamWrapper;

/**
 * A stream wrapper that will combine multiple strings/streams into a single
 * stream. Stream Wrappers implement method signatures defined in the builtin
 * class streamWrapper but they do not need to inherit from it. Not all methods
 * of streamWrapper need to be implemented. We follow the signatures as defined
 * in the php manual
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Stream_Wrapper
 */
class CombineWrapper
{
    /**/
    public const WRAPPER_NAME = 'horde-stream-wrapper-combine';

    /**
     * Context.
     *
     * @var resource
     */
    public $context;

    /**
     * Array that holds the various streams.
     *
     * @var mixed[]
     */
    protected array $data = [];

    /**
     * The combined length of the stream.
     *
     * @var int
     */
    protected int $length = 0;

    /**
     * The current position in the string.
     *
     * @var int
     */
    protected $position = 0;

    /**
     * The current position in the data array.
     *
     * @var int
     */
    protected $datapos = 0;

    /**
     * Have we reached EOF?
     *
     * @var bool
     */
    protected $ateof = false;

    /**
     * Unique ID tracker for the streams.
     *
     * @var int
     */
    private static int $id = 0;

    /**
     * Create a stream from multiple data sources.
     *
     * @since 2.1.0
     *
     * @param mixed[] $data  An array of strings and/or streams to combine into
     *                     a single stream.
     *
     * @return resource|false  A PHP stream.
     */
    public static function getStream(array $data)
    {
        if (!self::$id) {
            stream_wrapper_register(self::WRAPPER_NAME, __CLASS__);
        }

        return fopen(
            self::WRAPPER_NAME . '://' . ++self::$id,
            'wb',
            false,
            stream_context_create([
                self::WRAPPER_NAME => [
                    'data' => $data,
                ],
            ])
        );
    }
    /**
     * @see streamWrapper::stream_open()
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string|null &$opened_path
     *
     * @return bool success
     *
     * @throws Exception
     */
    public function stream_open(string $path, string $mode, $options, ?string &$opened_path = null): bool
    {
        $opts = stream_context_get_options($this->context);

        if (isset($opts[self::WRAPPER_NAME]['data'])) {
            $data = $opts[self::WRAPPER_NAME]['data'];
        } elseif (isset($opts['horde-combine']['data'])) {
            // @deprecated
            $data = $opts['horde-combine']['data']->getData();
        } else {
            throw new Exception('Use ' . __CLASS__ . '::getStream() to initialize the stream.');
        }

        foreach ($data as $val) {
            if (is_string($val)) {
                $fp = fopen('php://temp', 'r+');
                if ($fp === false) {
                    throw new Exception('Failed to open PHP temp stream for rw');
                }
                fwrite($fp, $val);
            } else {
                $fp = $val;
            }

            fseek($fp, 0, SEEK_END);
            $length = ftell($fp);
            rewind($fp);

            $this->data[] = [
                'fp' => $fp,
                'l' => $length,
                'p' => 0,
            ];

            $this->length += $length;
        }

        return true;
    }

    /**
     * @see streamWrapper::stream_read()
     *
     * @param int $count
     *
     * streamWrapper reference signature says it always returns string
     * However the docs say further:
     * If there are less than count byte
     * If no more data is available, return either false or an empty string.
     *
     * We follow the stricter interface and return empty string.
     * This may break deriving class when upgrading from H5.
     *
     * @return string
     */
    public function stream_read(int $count): string
    {
        if ($this->stream_eof()) {
            return '';
        }

        $out = '';
        $tmp = &$this->data[$this->datapos];

        while ($count) {
            if (!is_resource($tmp['fp'])) {
                return '';
            }

            $curr_read = (int) min($count, $tmp['l'] - $tmp['p']);
            if ($curr_read < 0) {
                $curr_read = 0;
            }
            $out .= fread($tmp['fp'], $curr_read);
            $count -= $curr_read;
            $this->position += $curr_read;

            if ($this->position == $this->length) {
                if ($count) {
                    $this->ateof = true;
                    break;
                } else {
                    $tmp['p'] += $curr_read;
                }
            } elseif ($count) {
                if (!isset($this->data[++$this->datapos])) {
                    return '';
                }
                $tmp = &$this->data[$this->datapos];
                rewind($tmp['fp']);
                $tmp['p'] = 0;
            } else {
                $tmp['p'] += $curr_read;
            }
        }

        return $out;
    }

    /**
     * @see streamWrapper::stream_write()
     *
     * @param string $data
     *
     * Previous versions returned int|false.
     *
     * streamWrapper reference signature says it always returns int
     * Should return the number of bytes that were successfully stored,
     * or 0 if none could be stored.
     *
     * This may break deriving class when upgrading from H5.
     *
     * @return int
     */
    public function stream_write(string $data): int
    {
        $tmp = &$this->data[$this->datapos];

        $oldlen = $tmp['l'];
        $res = fwrite($tmp['fp'], $data);
        if ($res === false) {
            return 0;
        }

        $tmp['p'] = ftell($tmp['fp']);
        if ($tmp['p'] > $oldlen) {
            $tmp['l'] = $tmp['p'];
            $this->length += ($tmp['l'] - $oldlen);
        }

        return $res;
    }

    /**
     * @see streamWrapper::stream_tell()
     *
     * @return int
     */
    public function stream_tell(): int
    {
        return $this->position;
    }

    /**
     * @see streamWrapper::stream_eof()
     *
     * @return bool
     */
    public function stream_eof(): bool
    {
        return $this->ateof;
    }

    /**
     * @see streamWrapper::stream_stat()
     *
     * streamWrapper docs offer array|false return type.
     * As we will always return an array, we go for the stricter interface
     *
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
            'size' => $this->length,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
        ];
    }

    /**
     * @see streamWrapper::stream_seek()
     *
     * @param int $offset
     * @param int $whence  SEEK_SET, SEEK_CUR, or SEEK_END
     *
     * @return bool
     */
    public function stream_seek(int $offset, int $whence): bool
    {
        $oldpos = $this->position;
        $this->ateof = false;

        switch ($whence) {
        case SEEK_SET:
            $offset = $offset;
            break;

        case SEEK_CUR:
            $offset = $this->position + $offset;
            break;

        case SEEK_END:
            $offset = $this->length + $offset;
            break;

        default:
            return false;
        }

        $count = $this->position = min($this->length, $offset);

        foreach ($this->data as $key => $val) {
            if ($count < $val['l']) {
                $this->datapos = $key;
                $val['p'] = $count;
                fseek($val['fp'], $count, SEEK_SET);
                break;
            }
            $count -= $val['l'];
        }

        return ($oldpos != $this->position);
    }
}
