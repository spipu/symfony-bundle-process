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

namespace Spipu\ProcessBundle\Step\Database;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Doctrine\DBAL\Connection;

abstract class AbstractUpdateDatabase implements StepInterface
{
    protected Connection $connection;
    protected array $report = [];
    protected LoggerInterface $logger;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): array
    {
        $this->logger = $logger;

        $this->report = [
            'inserted' => 0,
            'updated'  => 0,
            'deleted'  => 0,
            'disabled' => 0,
        ];

        $this->updateDatabase($parameters);

        return $this->report;
    }

    abstract protected function updateDatabase(ParametersInterface $parameters): void;
}
