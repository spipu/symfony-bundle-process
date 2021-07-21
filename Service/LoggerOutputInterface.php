<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Service;

interface LoggerOutputInterface
{
    /**
     * @param array $message
     * @return void
     */
    public function write(array $message): void;
}
