<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spipu\ProcessBundle\Exception;

class StepException extends ProcessException
{
    /**
     * Can we rerun the process automatically after this error ?
     * @return bool
     */
    public function canBeRerunAutomatically(): bool
    {
        return false;
    }
}
