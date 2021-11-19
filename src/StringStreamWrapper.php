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

/**
 * Provides access to the StringStream stream wrapper.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @copyright  2007-2016 Horde LLC
 * @deprecated Use Horde_Stream_Wrapperstring::getStream()
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Stream_Wrapper
 */
interface StringStreamWrapper
{
    /**
     * Return a reference to the wrapped string.
     *
     * @return string
     */
    public function &getString(): string;
}
