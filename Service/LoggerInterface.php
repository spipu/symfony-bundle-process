<?php
declare(strict_types = 1);

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
