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

namespace Spipu\ProcessBundle\Service;

/**
 * @SuppressWarnings(PMD.TooManyPublicMethods)
 */
interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Set the progress on the current step
     * @param int $progressOnCurrentStep
     * @return void
     */
    public function setProgress(int $progressOnCurrentStep): void;
}
